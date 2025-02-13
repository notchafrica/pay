<?php

namespace Notch\Framework\Currency\Drivers;

use Illuminate\Support\Arr;
use Notch\Core\Currency\Contracts\DriverInterface;

abstract class AbstractDriver implements DriverInterface
{
    /**
     * Driver config
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new driver instance.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Get configuration value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }
}
