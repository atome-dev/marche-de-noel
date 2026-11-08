<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Information banner shown on every page (for instance before a maintenance),
 * set in .env with an optional period in the market timezone.
 */
class InfoBanner
{
    public const DATE_FORMAT = 'Y-m-d H:i';

    /**
     * The message to show now, or null outside the period. A malformed date
     * hides the banner: a mistyped end must not leave it shown forever.
     */
    public function message(): ?string
    {
        $message = trim((string) config('marche.info_banner.message'));

        if ($message === '') {
            return null;
        }

        $start = $this->date('start');
        $end = $this->date('end');

        if ($start === false || $end === false) {
            return null;
        }

        $now = CarbonImmutable::now();

        if (($start !== null && $now->lt($start)) || ($end !== null && $now->gte($end))) {
            return null;
        }

        return $message;
    }

    /**
     * @return CarbonImmutable|false|null null when not set, false when malformed
     */
    private function date(string $bound): CarbonImmutable|false|null
    {
        $value = trim((string) config("marche.info_banner.{$bound}"));

        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat(self::DATE_FORMAT, $value, config('marche.timezone'));
        } catch (Throwable) {
            return false;
        }

        return $date !== null && $date->format(self::DATE_FORMAT) === $value ? $date : false;
    }
}
