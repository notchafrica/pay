<?php

declare(strict_types=1);

namespace Notch\Framework\Services;

final class TinyColor
{
    private $r = 0;

    private $g = 0;

    private $b = 0;

    private $a = 1;

    public function __construct($color)
    {
        if (is_array($color)) {
            $this->fromRgb($color);
        } elseif (is_string($color)) {
            $this->fromString($color);
        }
    }

    private function fromRgb($rgb): void
    {
        $this->r = isset($rgb['r']) ? $this->clamp($rgb['r'], 0, 255) : 0;
        $this->g = isset($rgb['g']) ? $this->clamp($rgb['g'], 0, 255) : 0;
        $this->b = isset($rgb['b']) ? $this->clamp($rgb['b'], 0, 255) : 0;
        $this->a = isset($rgb['a']) ? $this->clamp($rgb['a'], 0, 1) : 1;
    }

    private function fromString($color): void
    {
        // Remove spaces and convert to lowercase
        $color = strtolower(trim($color));

        // Handle hex colors
        if (preg_match('/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i', $color, $matches)) {
            $this->r = hexdec($matches[1]);
            $this->g = hexdec($matches[2]);
            $this->b = hexdec($matches[3]);
            $this->a = 1;
        }
        // Handle hex colors with alpha
        elseif (preg_match('/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i', $color, $matches)) {
            $this->r = hexdec($matches[1]);
            $this->g = hexdec($matches[2]);
            $this->b = hexdec($matches[3]);
            $this->a = hexdec($matches[4]) / 255;
        }
        // Handle rgb/rgba colors
        elseif (preg_match('/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)$/', $color, $matches)) {
            $this->r = $this->clamp(intval($matches[1]), 0, 255);
            $this->g = $this->clamp(intval($matches[2]), 0, 255);
            $this->b = $this->clamp(intval($matches[3]), 0, 255);
            $this->a = isset($matches[4]) ? $this->clamp(floatval($matches[4]), 0, 1) : 1;
        }
    }

    private function clamp($value, $min, $max)
    {
        return max($min, min($max, $value));
    }

    public function toRgb()
    {
        return [
            'r' => $this->r,
            'g' => $this->g,
            'b' => $this->b,
            'a' => $this->a,
        ];
    }

    public function toHex($allow3Char = false)
    {
        $hex = sprintf('#%02x%02x%02x', $this->r, $this->g, $this->b);

        if ($allow3Char) {
            if (($hex[1] === $hex[2]) && ($hex[3] === $hex[4]) && ($hex[5] === $hex[6])) {
                $hex = "#{$hex[1]}{$hex[3]}{$hex[5]}";
            }
        }

        return $hex;
    }

    public function toHsl()
    {
        $r = $this->r / 255;
        $g = $this->g / 255;
        $b = $this->b / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $h = $s = $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);

                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;

                    break;
                case $b:
                    $h = ($r - $g) / $d + 4;

                    break;
            }

            $h /= 6;
        }

        return [
            'h' => round($h * 360),
            's' => round($s * 100),
            'l' => round($l * 100),
            'a' => $this->a,
        ];
    }

    public function getBrightness()
    {
        // http://www.w3.org/TR/AERT#color-contrast
        return ($this->r * 299 + $this->g * 587 + $this->b * 114) / 1000;
    }

    public function isLight($threshold = 128)
    {
        return $this->getBrightness() >= $threshold;
    }

    public function isDark($threshold = 128)
    {
        return ! $this->isLight($threshold);
    }

    public function getLuminance()
    {
        // https://www.w3.org/TR/WCAG20/#relativeluminancedef
        $RsRGB = $this->r / 255;
        $GsRGB = $this->g / 255;
        $BsRGB = $this->b / 255;

        $R = $RsRGB <= 0.03928 ? $RsRGB / 12.92 : pow(($RsRGB + 0.055) / 1.055, 2.4);
        $G = $GsRGB <= 0.03928 ? $GsRGB / 12.92 : pow(($GsRGB + 0.055) / 1.055, 2.4);
        $B = $BsRGB <= 0.03928 ? $BsRGB / 12.92 : pow(($BsRGB + 0.055) / 1.055, 2.4);

        return 0.2126 * $R + 0.7152 * $G + 0.0722 * $B;
    }

    public function getAlpha()
    {
        return $this->a;
    }

    public function setAlpha($alpha)
    {
        $this->a = $this->clamp($alpha, 0, 1);

        return $this;
    }

    public function isMonochrome()
    {
        return $this->r === $this->g && $this->g === $this->b;
    }

    public function lighten($amount = 10)
    {
        $hsl = $this->toHsl();
        $hsl['l'] += $amount;
        $hsl['l'] = $this->clamp($hsl['l'], 0, 100);

        return $this->fromHsl($hsl);
    }

    public function darken($amount = 10)
    {
        return $this->lighten(-$amount);
    }

    public function saturate($amount = 10)
    {
        $hsl = $this->toHsl();
        $hsl['s'] += $amount;
        $hsl['s'] = $this->clamp($hsl['s'], 0, 100);

        return $this->fromHsl($hsl);
    }

    public function desaturate($amount = 10)
    {
        return $this->saturate(-$amount);
    }

    public function grayscale()
    {
        return $this->desaturate(100);
    }

    private function fromHsl($hsl)
    {
        $h = $this->clamp($hsl['h'], 0, 360) / 360;
        $s = $this->clamp($hsl['s'], 0, 100) / 100;
        $l = $this->clamp($hsl['l'], 0, 100) / 100;

        if ($s === 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hue2rgb($p, $q, $h + 1 / 3);
            $g = $this->hue2rgb($p, $q, $h);
            $b = $this->hue2rgb($p, $q, $h - 1 / 3);
        }

        $this->r = round($r * 255);
        $this->g = round($g * 255);
        $this->b = round($b * 255);
        $this->a = isset($hsl['a']) ? $this->clamp($hsl['a'], 0, 1) : $this->a;

        return $this;
    }

    private function hue2rgb($p, $q, $t)
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    public function isValid()
    {
        return ! is_null($this->r) && ! is_null($this->g) && ! is_null($this->b);
    }
}
