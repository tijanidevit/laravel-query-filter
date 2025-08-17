<?php

namespace Tijanidevit\QueryFilter\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Closure;

class FilterableMacros
{
    public static function boot(): void
    {
        static::registerFilterBy();
        static::registerFilterByRelation();
        static::registerSearch();
        static::registerOrSearch();
        static::registerSearchByRelation();
        static::registerFilterByMonth();
        static::registerFilterByYear();
        static::registerFilterFromRequest();
        static::registerFilterByDateRange();
        static::registerSortResultBy();
        static::registerFilterByDate();
    }

    protected static function registerFilterBy(): void
    {
        Builder::macro('filterBy', function ($column, $value = null) {
            // Allow: filterBy('name', 'tj')
            if (is_string($column)) {
                if ($value === null || $value === '') {
                    return $this;
                }

                if (is_array($value) && array_key_exists('null', $value)) {
                    return $value['null']
                        ? $this->whereNull($column)
                        : $this->whereNotNull($column);
                }

                if (is_array($value)) {
                    return $this->whereIn($column, $value);
                }

                return $this->where($column, '=', $value);
            }

            // Allow: filterBy(['name' => 'tj', 'status' => ['active', 'pending']])
            if (is_array($column)) {
                foreach ($column as $col => $val) {
                    if (
                        ($val === null || $val === '' || (is_array($val) && empty($val)))
                        && !is_bool($val)
                        && !(is_array($val) && array_key_exists('null', $val))
                    ) {
                        continue;
                    }

                    if (is_array($val) && array_key_exists('null', $val)) {
                        $this->where(function ($q) use ($col, $val) {
                            $val['null']
                                ? $q->whereNull($col)
                                : $q->whereNotNull($col);
                        });
                    } elseif (is_array($val)) {
                        $this->whereIn($col, $val);
                    } else {
                        $this->where($col, '=', $val);
                    }
                }

                return $this; // <-- RETURN AFTER LOOP, NOT INSIDE
            }

            return $this;
        });
    }

    protected static function registerSearch(): void
    {
        Builder::macro('search', function ($column, $value = null) {
            // Allow: search('name', 'tj')
            if (is_string($column)) {
                if ($value === null || $value === '') {
                    return $this;
                }

                // Default "like" search condition
                return $this->where($column, 'like', "%$value%");
            }

            return $this;
        });
    }

    protected static function registerOrSearch(): void
    {
        Builder::macro('orSearch', function (array $columns, ?string $value = null) {
            if (empty($value)) {
                return $this;
            }

            return $this->where(function ($query) use ($columns, $value) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$value}%");
                }
            });
        });
    }

    protected static function registerSearchByRelation(): void
    {
        Builder::macro('searchByRelation', function (string $relation, array $conditions) {
            if ($relation && $conditions) {
                return $this->whereHas($relation, function ($query) use ($conditions) {
                    foreach ($conditions as $column => $value) {
                        // Skip empty values (but allow "0" and false explicitly)
                        if (
                            ($value === null || $value === '' || (is_array($value) && empty($value)))
                            && !is_bool($value)
                            && !(is_array($value) && array_key_exists('null', $value))
                        ) {
                            continue;
                        }

                        // Handle null or not-null conditions
                        if (is_array($value) && array_key_exists('null', $value)) {
                            $isNull = $value['null'];
                            $isNull ? $query->whereNull($column) : $query->whereNotNull($column);
                        } elseif (is_array($value)) {
                            // Handle array conditions (whereIn)
                            $query->whereIn($column, $value);
                        } else {
                            // Default "like" search condition
                            $query->where($column, 'like', "%$value%");
                        }
                    }
                });
            }

            return $this;
        });
    }


    protected static function registerFilterByRelation(): void
    {
        Builder::macro('filterByRelation', function (
            array $relations,
            string $boolean = 'and'
        ) {
            foreach ($relations as $relation => $conditions) {
                if (empty($conditions)) {
                    continue; // skip if no conditions passed
                }

                $this->whereHas($relation, function ($query) use ($conditions, $boolean) {
                    foreach ($conditions as $column => $condition) {
                        // Skip empty values (but allow "0" and false explicitly)
                        if (
                            ($condition === null || $condition === '' || (is_array($condition) && empty($condition)))
                            && !is_bool($condition)
                            && !(is_array($condition) && array_key_exists('null', $condition))
                        ) {
                            continue;
                        }

                        if ($condition instanceof Closure) {
                            $query->where(function ($q) use ($condition) {
                                $condition($q);
                            }, null, null, $boolean);
                        } elseif (is_array($condition) && count($condition) === 2 && is_string($condition[0])) {
                            [$operator, $value] = $condition;
                            $query->where($column, $operator, $value, $boolean);
                        } elseif (is_array($condition) && array_key_exists('null', $condition)) {
                            $isNull = $condition['null'];
                            $isNull ? $query->whereNull($column, $boolean) : $query->whereNotNull($column, $boolean);
                        } else {
                            $query->where($column, '=', $condition, $boolean);
                        }
                    }
                }, $boolean);
            }

            return $this;
        });
    }

    protected static function registerFilterByMonth(): void
    {
        Builder::macro('filterByMonth', function ($month, $column = 'created_at') {
            if (is_array($month) && !empty($month)) {
                return $this->whereIn(DB::raw("MONTH($column)"), $month);
            }

            if (!empty($month)) {
                return $this->whereMonth($column, $month);
            }

            return $this;
        });
    }

    protected static function registerFilterByYear(): void
    {
        Builder::macro('filterByYear', function ($year, $column = 'created_at') {
            if (is_array($year) && !empty($year)) {
                return $this->whereIn(DB::raw("YEAR($column)"), $year);
            }

            if (!empty($year)) {
                return $this->whereYear($column, $year);
            }

            return $this;
        });
    }

    protected static function registerFilterFromRequest(): void
    {
        Builder::macro('filterFromRequest', function ($request, $filters = []) {
            foreach ($filters as $column => $value) {
                if ($request->has($column)) {
                    $this->filterBy($column, $request->input($column));
                }
            }

            return $this;
        });
    }

    protected static function registerFilterByDateRange(): void
    {
        Builder::macro('filterByDateRange', function (
            $dateFrom = null,
            $dateTo = null,
            string $column = 'created_at',
            ?string $timezone = null
        ) {
            $timezone = $timezone
                ?? config('app.query_timezone', config('app.timezone', 'UTC'))
                ?? 'UTC';

            if ($dateFrom) {
                $this->where(
                    $column,
                    '>=',
                    Carbon::parse($dateFrom, $timezone)->startOfDay()->timezone('UTC')
                );
            }

            if ($dateTo) {
                $this->where(
                    $column,
                    '<=',
                    Carbon::parse($dateTo, $timezone)->endOfDay()->timezone('UTC')
                );
            }

            return $this;
        });
    }


    protected static function registerSortResultBy(): void
    {
        Builder::macro('sortResultBy', function ($column, $order = 'asc') {
            if ($column) {
                return $this->orderBy($column, $order);
            }

            return $this;
        });
    }

    protected static function registerLatestBy(): void
    {
        Builder::macro('latestBy', function ($column) {
            if ($column) {
                return $this->latest($column);
            }

            return $this;
        });
    }

    protected static function registerOldestBy(): void
    {
        Builder::macro('oldestBy', function ($column) {
            if ($column) {
                return $this->oldest($column);
            }

            return $this;
        });
    }

    protected static function registerFilterByDate(): void
    {
        Builder::macro('filterByDate', function ($date, $column = 'created_at') {
            if ($date) {
                return $this->whereDate($column, '=', $date);
            }

            return $this;
        });
    }
}
