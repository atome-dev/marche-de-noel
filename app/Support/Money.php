<?php

namespace App\Support;

/**
 * Amounts in cents shown in euros, French style: "27 €", "35,50 €".
 */
class Money
{
    public static function format(int $cents): string
    {
        $decimals = $cents % 100 === 0 ? 0 : 2;

        return number_format($cents / 100, $decimals, ',', "\u{202F}")."\u{00A0}€";
    }
}
