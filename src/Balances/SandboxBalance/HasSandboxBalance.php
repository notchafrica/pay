<?php

namespace Notch\Framework\Balances\SandboxBalance;

use Illuminate\Support\Arr;

trait HasSandboxBalance
{
    /**
     * Get the model's balance amount.
     *
     * @return float|int
     */
    public function getSandboxBalanceAttribute()
    {
        return $this->sandboxBalanceHistory()->sum('amount') / 100;
    }

    /**
     * Get the model's balance amount.
     *
     * @return int
     */
    public function getIntSandboxBalanceAttribute()
    {
        return (float) $this->sandboxBalanceHistory()->sum('amount');
    }

    public function increaseSandboxBalance(float $amount, array $parameters = [])
    {
        return $this->createSandboxBalanceHistory(round($amount), $parameters);
    }

    public function decreaseSandboxBalance(float $amount, array $parameters = [])
    {
        return $this->createSandboxBalanceHistory(-round($amount), $parameters);
    }

    public function modifySandboxBalance(float $amount, array $parameters = [])
    {
        return $this->createSandboxBalanceHistory(round($amount), $parameters);
    }

    public function resetSandboxBalance(?float $newAmount = null, $parameters = [])
    {
        $this->sandboxBalanceHistory()->delete();

        if (is_null($newAmount)) {
            return true;
        }

        return $this->createSandboxBalanceHistory(round($newAmount), $parameters);
    }

    /**
     * Check if there is a positive balance.
     *
     * @return bool
     */
    public function hasSandboxBalance($amount = 1.0)
    {
        return $this->balance > 0 && $this->sandboxBalanceHistory()->sum('amount') >= $amount;
    }

    /**
     * Check if there is no more balance.
     *
     * @return bool
     */
    public function hasNoSandboxBalance()
    {
        return $this->balance <= 0;
    }

    protected function createSandboxBalanceHistory(float $amount, array $parameters = [])
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

        return $this->sandboxBalanceHistory()->create($createArguments);
    }

    /**
     * Get all Balance History.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function sandboxBalanceHistory()
    {
        return $this->morphMany(SandboxBalance::class, 'balanceable');
    }
}
