<?php
namespace Tijanidevit\QueryFilter\Support;

use Carbon\Carbon;

class TimezoneResolver
{
    public static function resolve(?string $tz = null): string
    {
        return $tz
            ?? config('query-filter.timezone')   // package-specific config
            ?? config('app.query_timezone')      // optional fallback
            ?? config('app.timezone')            // Laravel default
            ?? 'UTC';
    }

    public static function toUtc(Carbon $date): Carbon
    {
        return $date->clone()->timezone('UTC');
    }
}
