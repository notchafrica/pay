<?php

namespace Notch\Framework\Traits;

use Notch\Framework\Balances\AvailableBalance\HasAvailableBalance;
use Notch\Framework\Balances\Balance\HasBalance;
use Notch\Framework\Balances\PendingBalance\HasPendingBalance;
use Notch\Framework\Balances\ReservedBalance\HasReservedBalance;
use Notch\Framework\Balances\SandboxBalance\HasSandboxBalance;

trait HasBalances
{
    use HasAvailableBalance;
    use HasBalance;
    use HasPendingBalance;
    use HasReservedBalance;
    use HasSandboxBalance;
}
