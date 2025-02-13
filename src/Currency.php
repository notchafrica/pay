<?php

namespace Notch\Framework;

use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Currency
{
    /**
     * Currency configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Laravel application
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * User's currency
     *
     * @var string
     */
    protected $user_currency;

    /**
     * Currency driver instance.
     *
     * @var Contracts\DriverInterface
     */
    protected $driver;

    /**
     * Formatter instance.
     *
     * @var Contracts\FormatterInterface
     */
    protected $formatter;

    /**
     * Cached currencies
     *
     * @var array
     */
    protected $currencies_cache;

    /**
     * Create a new instance.
     *
     * @param  array  $config
     */
    public function __construct(FactoryContract $cache)
    {
        $this->config = config('currency');
        $this->cache = $cache->store($this->config('cache_driver'));
    }

    /**
     * Format given number.
     *
     * @param  float  $amount
     * @param  string  $from
     * @param  string  $to
     * @param  bool  $format
     * @return string|null
     */
    public function convert($amount, $from = null, $to = null, $format = true)
    {
        // Get currencies involved
        $from = strtoupper($from ?: $this->config('default'));
        $to = strtoupper($to ?: $this->getUserCurrency());

        // Get exchange rates
        $from_rate = $this->getCurrencyProp($from, 'exchange_rate');
        $to_rate = $this->getCurrencyProp($to, 'exchange_rate');
        // Skip invalid to currency rates
        if ($to_rate === null) {
            return null;
        }

        try {
            // Convert amount
            if ($from === $to) {
                $value = $amount;
            } else {
                $value = ($amount * (float) $to_rate) / (float) $from_rate;
            }
        } catch (\Exception $e) {
            // Prevent invalid conversion or division by zero errors
            return null;
        }

        // Return value
        return $value;
    }

    /**
     * Format the value into the desired currency.
     *
     * @param  float  $value
     * @param  string  $code
     * @param  bool  $include_symbol
     * @return string
     */
    public function format($value, $code = null, $include_symbol = true)
    {
        // Get default currency if one is not set
        $code = $code ?: $this->config('default');

        // Remove unnecessary characters
        $value = preg_replace('/[\s\',!]/', '', $value);

        // Check for a custom formatter
        if ($formatter = $this->getFormatter()) {
            return $formatter->format($value, $code);
        }

        // Get the measurement format
        $format = $this->getCurrencyProp($code, 'format');

        // Value Regex
        $valRegex = '/([0-9].*|)[0-9]/';

        // Match decimal and thousand separators
        preg_match_all('/[\s\',.!]/', $format, $separators);

        if ($thousand = Arr::get($separators, '0.0', null)) {
            if ($thousand == '!') {
                $thousand = '';
            }
        }

        $decimal = Arr::get($separators, '0.1', null);

        // Match format for decimals count
        preg_match($valRegex, $format, $valFormat);

        $valFormat = Arr::get($valFormat, 0, 0);

        // Count decimals length
        $decimals = $decimal ? strlen(substr(strrchr($valFormat, $decimal), 1)) : 0;

        // Do we have a negative value?
        if ($negative = $value < 0 ? '-' : '') {
            $value = $value * -1;
        }

        // Format the value

        $currency = $this->getCurrency($code);

        $value = number_format((float) $value, (int) ($currency->fraction ?? 2), $decimal, $thousand);
        // Apply the formatted measurement
        if ($include_symbol) {
            $value = preg_replace($valRegex, $value, $format);
        }

        // Return value
        return $negative.$value;
    }

    /**
     * Set user's currency.
     *
     * @param  string  $code
     */
    public function setUserCurrency($code)
    {
        $this->user_currency = strtoupper($code);
    }

    /**
     * Return the user's currency code.
     *
     * @return string
     */
    public function getUserCurrency()
    {
        return $this->user_currency ?: $this->config('default');
    }

    /**
     * Determine if the provided currency is valid.
     *
     * @param  string  $code
     * @return array|null
     */
    public function hasCurrency($code)
    {
        return $this->getCurrencies()->where('code', $code)->exists();
    }

    /**
     * Determine if the provided currency is active.
     *
     * @param  string  $code
     * @return bool
     */
    public function isActive($code)
    {
        return $code && (bool) Arr::get($this->getCurrency($code), 'active', false);
    }

    /**
     * Return the current currency if the
     * one supplied is not valid.
     *
     * @param  string  $code
     * @return array|null
     */
    public function getCurrency($code = null)
    {

        $code = $code ?: $this->getUserCurrency();

        return Cache::remember('currency_'.$code, 3600 * 24, function () use ($code) {
            return $this->getCurrencies()->firstWhere('code', $code);
        });
    }

    /**
     * Return all currencies.
     *
     * @return array
     */
    public function getCurrencies()
    {
        return $this->getDriver();
    }

    public function getDriver()
    {
        if ($this->driver === null) {
            $this->driver = new \Notch\Framework\Currency\Models\Currency;
        }

        return $this->driver;
    }

    /**
     * Get formatter driver.
     *
     * @return \RestUniverse\Currency\Contracts\FormatterInterface
     */
    public function getFormatter()
    {
        if ($this->formatter === null && $this->config('formatter') !== null) {
            // Get formatter configuration
            $config = $this->config('formatters.'.$this->config('formatter'), []);

            // Get formatter class
            $class = Arr::pull($config, 'class');

            // Create formatter instance
            $this->formatter = new $class(array_filter($config));
        }

        return $this->formatter;
    }

    /**
     * Clear cached currencies.
     */
    public function clearCache()
    {
        $this->cache->forget('notch.currency');
        $this->currencies_cache = null;
    }

    /**
     * Get configuration value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function config($key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return Arr::get($this->config, $key, $default);
    }

    /**
     * Get the given property value from provided currency.
     *
     * @param  string  $code
     * @param  string  $key
     * @param  mixed  $default
     * @return array
     */
    protected function getCurrencyProp($code, $key, $default = null)
    {
        return Arr::get($this->getCurrency($code), $key, $default);
    }

    /**
     * Get a given value from the current currency.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return Arr::get($this->getCurrency(), $key);
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->getDriver(), $method], $parameters);
    }
}
