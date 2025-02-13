<?php

namespace Notch\Framework\Balances\AvailableBalance;

use Illuminate\Support\Arr;

trait HasAvailableBalance
{
    public function getAvailableBalanceAttribute()
    {
        return $this->availableBalanceHistory()->sum('amount') / 100;
    }

    public function getIntAvailableBalanceAttribute()
    {
        return (int) $this->availableBalanceHistory()->sum('amount');
    }

    public function increaseAvailableBalance(float $amount, array $parameters = [])
    {
        return $this->createAvailableBalanceHistory(round($amount), $parameters);
    }

    public function decreaseAvailableBalance(float $amount, array $parameters = [])
    {
        return $this->createAvailableBalanceHistory(-round($amount), $parameters);
    }

    public function modifyAvailableBalance(float $amount, array $parameters = [])
    {
        return $this->createAvailableBalanceHistory(round($amount), $parameters);
    }

    public function resetAvailableBalance(?float $newAmount = null, $parameters = [])
    {
        $this->availableBalanceHistory()->delete();

        if (is_null($newAmount)) {
            return true;
        }

        return $this->createAvailableBalanceHistory(round($newAmount), $parameters);
    }

    public function hasAvailableBalance($amount = 1)
    {
        return $this->balance > 0 && $this->availableBalanceHistory()->sum('amount') >= $amount;
    }

    /**
     * Check if there is no more balance.
     *
     * @return bool
     */
    public function hasNoAvailableBalance()
    {
        return $this->balance <= 0;
    }

    protected function createAvailableBalanceHistory(float $amount, array $parameters = [])
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

        return $this->availableBalanceHistory()->create($createArguments);
    }

    public function availableBalanceHistory()
    {
        return $this->morphMany(AvailableBalance::class, 'balanceable');
    }
}
