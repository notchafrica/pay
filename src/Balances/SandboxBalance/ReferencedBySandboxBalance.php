<?php

namespace Notch\Framework\Balances\SandboxBalance;

trait ReferencedBySandboxBalance
{
    public function balanceReferences()
    {
        return $this->morphMany(SandboxBalance::class, 'ref');
    }
}
