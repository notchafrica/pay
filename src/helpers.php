<?php

use Notch\Framework\Currency;

if (! function_exists('currency')) {
    /**
     * Convert given number.
     *
     * @param  float  $amount
     * @param  string  $from
     * @param  string  $to
     * @param  bool  $format
     * @return \Notch\\Framework\Currency\Currency|string
     */
    function currency($amount = null, $from = null, $to = null, $format = true)
    {
        if (is_null($amount)) {
            return app(Currency::class);
        }

        return app(Currency::class)->convert($amount, $from, $to, $format);
    }
}
