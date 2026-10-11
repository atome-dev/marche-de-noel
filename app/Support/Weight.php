<?php

namespace App\Support;

class Weight
{
    /**
     * "200 g", "1 kg", "1,25 kg".
     */
    public static function format(int $grams): string
    {
        if ($grams < 1000) {
            return $grams.' g';
        }

        return rtrim(rtrim(number_format($grams / 1000, 2, ',', ' '), '0'), ',').' kg';
    }
}
