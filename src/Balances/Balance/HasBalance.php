<?php

namespace Notch\Framework\Balances\Balance;

use Illuminate\Support\Arr;

trait HasBalance
{
    /**
     * Get the model's balance amount.
     *
     * @return float|int
     */
    public function getBalanceAttribute()
    {
        return $this->balanceHistory()->sum('amount') / 100;
    }

    /**
     * Get the model's balance amount.
     *
     * @return int
     */
    public function getIntBalanceAttribute()
    {
        return (int) $this->balanceHistory()->sum('amount');
    }

    public function increaseBalance(float $amount, array $parameters = [])
    {
        return $this->createBalanceHistory(round($amount), $parameters);
    }

    public function decreaseBalance(float $amount, array $parameters = [])
    {
        return $this->createBalanceHistory(-round($amount), $parameters);
    }

    public function modifyBalance(float $amount, array $parameters = [])
    {
        return $this->createBalanceHistory(round($amount), $parameters);
    }

    public function resetBalance(?float $newAmount = null, $parameters = [])
    {
        $this->balanceHistory()->delete();

        if (is_null($newAmount)) {
            return true;
        }

        return $this->createBalanceHistory(round($newAmount), $parameters);
    }

    public function hasBalance($amount = 1)
    {
        return $this->balance > 0 && $this->balanceHistory()->sum('amount') >= $amount;
    }

    public function hasNoBalance()
    {
        return $this->balance <= 0;
    }

    protected function createBalanceHistory(float $amount, array $parameters = [])
    {
        $reference = Arr::get($parameters, 'reference');

        $createArguments = collect([
            'amount' => round($amount),
            'description' => Arr::get($parameters, 'description'),
        ])->when($reference, function ($collection) use ($reference) {
            return $collection
                ->put('referenceable_type', $reference->getMorphClass())
                ->put('referenceable_id', $reference->getKey());
        })->toArray();

        return $this->balanceHistory()->create($createArguments);
    }

    /**
     * Get all Balance History.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function balanceHistory()
    {
        return $this->morphMany(Balance::class, 'balanceable');
    }
}
