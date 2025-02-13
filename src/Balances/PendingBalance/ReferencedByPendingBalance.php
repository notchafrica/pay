<?php

namespace Notch\Framework\Balances\PendingBalance;

trait ReferencedByPendingBalance
{
    public function balanceReferences()
    {
        return $this->morphMany(PendingBalance::class, 'ref');
    }
}
