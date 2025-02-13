<?php

namespace Notch\Framework\Balances\ReservedBalance;

use Illuminate\Support\Arr;

trait HasReservedBalance
{
    /**
     * Get the model's balance amount.
     *
     * @return float|int
     */
    public function getReservedBalanceAttribute()
    {
        return $this->reservedBalanceHistory()->sum('amount') / 100;
    }

    /**
     * Get the model's balance amount.
     *
     * @return int
     */
    public function getIntReservedBalanceAttribute()
    {
        return (float) $this->reservedBalanceHistory()->sum('amount');
    }

    public function increaseReservedBalance(float $amount, array $parameters = [])
    {
        return $this->createReservedBalanceHistory(round($amount), $parameters);
    }

    public function decreaseReservedBalance(float $amount, array $parameters = [])
    {
        return $this->createReservedBalanceHistory(-round($amount), $parameters);
    }

    public function modifyReservedBalance(float $amount, array $parameters = [])
    {
        return $this->createReservedBalanceHistory(round($amount), $parameters);
    }

    public function resetReservedBalance(?float $newAmount = null, $parameters = [])
    {
        $this->reservedBalanceHistory()->delete();

        if (is_null($newAmount)) {
            return true;
        }

        return $this->createReservedBalanceHistory(round($newAmount), $parameters);
    }

    /**
     * Check if there is a positive balance.
     *
     * @return bool
     */
    public function hasReservedBalance($amount = 1.0)
    {
        return $this->balance > 0 && $this->reservedBalanceHistory()->sum('amount') >= $amount;
    }

    /**
     * Check if there is no more balance.
     *
     * @return bool
     */
    public function hasNoReservedBalance()
    {
        return $this->balance <= 0;
    }

    protected function createReservedBalanceHistory(float $amount, array $parameters = [])
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

        return $this->reservedBalanceHistory()->create($createArguments);
    }

    /**
     * Get all Balance History.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function reservedBalanceHistory()
    {
        return $this->morphMany(ReservedBalance::class, 'balanceable');
    }
}
