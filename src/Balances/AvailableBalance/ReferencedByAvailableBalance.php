<?php

namespace Notch\Framework\Balances\AvailableBalance;

trait ReferencedByAvailableBalance
{
    public function balanceReferences()
    {
        return $this->morphMany(AvailableBalance::class, 'ref');
    }
}
