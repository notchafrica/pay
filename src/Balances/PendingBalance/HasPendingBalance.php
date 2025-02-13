<?php

namespace Notch\Framework\Balances\PendingBalance;

use Illuminate\Support\Arr;

trait HasPendingBalance
{
    /**
     * Get the model's balance amount.
     *
     * @return float|int
     */
    public function getPendingBalanceAttribute()
    {
        return $this->pendingBalanceHistory()->sum('amount') / 100;
    }

    /**
     * Get the model's balance amount.
     *
     * @return int
     */
    public function getIntPendingBalanceAttribute()
    {
        return (float) $this->pendingBalanceHistory()->sum('amount');
    }

    public function increasePendingBalance(float $amount, array $parameters = [])
    {
        return $this->createPendingBalanceHistory(round($amount), $parameters);
    }

    public function decreasePendingBalance(float $amount, array $parameters = [])
    {
        return $this->createPendingBalanceHistory(-round($amount), $parameters);
    }

    public function modifyPendingBalance(float $amount, array $parameters = [])
    {
        return $this->createPendingBalanceHistory(round($amount), $parameters);
    }

    public function resetPendingBalance(?float $newAmount = null, $parameters = [])
    {
        $this->pendingBalanceHistory()->delete();

        if (is_null($newAmount)) {
            return true;
        }

        return $this->createPendingBalanceHistory(round($newAmount), $parameters);
    }

    /**
     * Check if there is a positive balance.
     *
     * @return bool
     */
    public function hasPendingBalance($amount = 1.0)
    {
        return $this->balance > 0 && $this->pendingBalanceHistory()->sum('amount') >= $amount;
    }

    /**
     * Check if there is no more balance.
     *
     * @return bool
     */
    public function hasNoPendingBalance()
    {
        return $this->balance <= 0;
    }

    protected function createPendingBalanceHistory(float $amount, array $parameters = [])
    {
        $reference = Arr::get($parameters, 'reference');

        $createArguments = collect([
            'amount' => round($amount),
            'description' => Arr::get($parameters, 'description'),
        ])->when($reference, function ($collection) use ($reference) {
            return $collection
                ->put('ref_type', $reference->getMorphClass())
                ->put('ref_id', $reference->getKey());
        })->toArray();

        return $this->pendingBalanceHistory()->create($createArguments);
    }

    /**
     * Get all Balance History.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function pendingBalanceHistory()
    {
        return $this->morphMany(PendingBalance::class, 'balanceable');
    }
}
