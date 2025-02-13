<?php

namespace Notch\Framework\Balances\ReservedBalance;

trait ReferencedByReservedBalance
{
    public function balanceReferences()
    {
        return $this->morphMany(ReservedBalance::class, 'ref');
    }
}
