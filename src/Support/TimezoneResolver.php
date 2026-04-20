<?php

namespace Tijanidevit\QueryFilter\Support;

use Carbon\Carbon;

class TimezoneResolver
{
    /**
     * Resolve the timezone to use for date-based filtering.
     *
     * Priority:
     *  1. $tz argument passed at call-site (runtime override)
     *  2. Package config: config/query-filter.php → 'timezone'
     *  3. Laravel default: config('app.timezone')
     *  4. Hard fallback: 'UTC'
     */
    public static function resolve(?string $tz = null): string
    {
        return $tz
            ?? config('query-filter.timezone')  // package config (query-filter.php)
            ?? config('app.timezone')           // Laravel default
            ?? 'UTC';
    }

    /**
     * Clone a Carbon instance and convert it to UTC.
     * Cloning prevents mutating the original date object.
     */
    public static function toUtc(Carbon $date): Carbon
    {
        return $date->clone()->setTimezone('UTC');
    }
}
