<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Filter Timezone
    |--------------------------------------------------------------------------
    |
    | This timezone will be used by all filter macros (date, range, month, year)
    | when converting input dates into UTC for database queries.
    |
    | Options:
    | - null: fallback to app.query_timezone
    | - string: e.g. 'Africa/Lagos', 'Asia/Dubai', 'UTC'
    |
    */
    'timezone' => env('QUERY_FILTER_TIMEZONE', 'UTC'),
];
