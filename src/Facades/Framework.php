<?php

namespace Notch\Framework\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Notch\Framework\Framework
 */
class Framework extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Notch\Framework\Framework::class;
    }
}
