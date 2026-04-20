<?php

namespace Tijanidevit\QueryFilter\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Closure;
use Tijanidevit\QueryFilter\Support\FilterHelper;
use Tijanidevit\QueryFilter\Support\TimezoneResolver;

trait Filterable
{
    /**
     * Resolve the SQL LIKE operator dynamically for the current database connection.
     * Uses ILIKE for PostgreSQL and LIKE for everything else.
     *
     * @param Builder $query
     * @return string
     */
    protected function getLikeOperator(Builder $query): string
    {
        return $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /**
     * Exact column matching.
     */
    public function scopeFilterBy(Builder $query, $column, $value = null): Builder
    {
        if (is_string($column)) {
            if (!FilterHelper::isFilterable($value)) {
                return $query;
            }

            if (is_array($value) && array_key_exists('null', $value)) {
                return $value['null'] ? $query->whereNull($column) : $query->whereNotNull($column);
            }

            if (is_array($value)) {
                return $query->whereIn($column, $value);
            }

            return $query->where($column, '=', $value);
        }

        if (is_array($column)) {
            foreach ($column as $col => $val) {
                if (!FilterHelper::isFilterable($val)) {
                    continue;
                }

                if (is_array($val) && array_key_exists('null', $val)) {
                    $val['null'] ? $query->whereNull($col) : $query->whereNotNull($col);
                } elseif (is_array($val)) {
                    $query->whereIn($col, $val);
                } else {
                    $query->where($col, '=', $val);
                }
            }
        }

        return $query;
    }

    /**
     * Standard LIKE/ILIKE search on a single column, or mapped key-values.
     */
    public function scopeSearch(Builder $query, $columns, $value = null): Builder
    {
        if (!$columns) {
            return $query;
        }

        $operator = $this->getLikeOperator($query);

        return $query->where(function ($q) use ($columns, $value, $operator) {
            if (is_string($columns)) {
                if ($value !== null && $value !== '') {
                    $q->where($columns, $operator, "%$value%");
                }
                return;
            }

            if (is_array($columns)) {
                foreach ($columns as $column => $val) {
                    if ($val !== null && $val !== '') {
                        $q->where($column, $operator, "%$val%");
                    }
                }
            }
        });
    }

    /**
     * Cross-column OR grouped search or WhereIn wrapper.
     */
    public function scopeSearchIn(Builder $query, $columns, $value = null): Builder
    {
        if (!$columns) {
            return $query;
        }

        $operator = $this->getLikeOperator($query);

        return $query->where(function ($q) use ($columns, $value, $operator) {
            if (is_array($columns) && array_is_list($columns)) {
                foreach ($columns as $column) {
                    if ($value !== null && $value !== '') {
                        $q->orWhere($column, $operator, "%$value%");
                    }
                }
                return;
            }

            foreach ($columns as $column => $vals) {
                if (is_array($vals)) {
                    $q->whereIn($column, $vals);
                }
            }
        });
    }

    /**
     * Top-level grouped OR search block.
     */
    public function scopeOrSearch(Builder $query, $columns, $value = null): Builder
    {
        if (empty($columns)) {
            return $query;
        }

        $operator = $this->getLikeOperator($query);

        return $query->orWhere(function ($q) use ($columns, $value, $operator) {
            if (is_string($columns)) {
                if ($value !== null && $value !== '') {
                    $q->where($columns, $operator, "%$value%");
                }
                return;
            }

            if (is_array($columns) && array_is_list($columns)) {
                foreach ($columns as $column) {
                    if ($value !== null && $value !== '') {
                        $q->orWhere($column, $operator, "%$value%");
                    }
                }
                return;
            }

            foreach ($columns as $column => $val) {
                $searchTerm = $val ?? $value;
                if ($searchTerm !== null && $searchTerm !== '') {
                    $q->orWhere($column, $operator, "%$searchTerm%");
                }
            }
        });
    }

    /**
     * Nested related model LIKE/ILIKE searches via whereHas.
     */
    public function scopeSearchByRelation(Builder $query, string $relation, array $conditions): Builder
    {
        if (!$relation || !$conditions) {
            return $query;
        }

        $operator = $this->getLikeOperator($query);

        return $query->whereHas($relation, function ($q) use ($conditions, $operator) {
            foreach ($conditions as $column => $value) {
                if (!FilterHelper::isFilterable($value)) {
                    continue;
                }

                if (is_array($value) && array_key_exists('null', $value)) {
                    $value['null'] ? $q->whereNull($column) : $q->whereNotNull($column);
                } elseif (is_array($value)) {
                    $q->whereIn($column, $value);
                } else {
                    $q->where($column, $operator, "%$value%");
                }
            }
        });
    }

    /**
     * Map exact or complex filters securely via whereHas relationships.
     */
    public function scopeFilterByRelation(Builder $query, array $relations, string $boolean = 'and'): Builder
    {
        foreach ($relations as $relation => $conditions) {
            if (empty($conditions)) {
                continue;
            }

            if (array_is_list($conditions) && count($conditions) === 3) {
                [$column, $operator, $value] = $conditions;
                $query->whereRelation($relation, $column, $operator, $value);
                continue;
            }

            $query->whereHas($relation, function ($q) use ($conditions, $boolean) {
                foreach ($conditions as $column => $condition) {
                    if (!FilterHelper::isFilterable($condition)) {
                        continue;
                    }

                    if ($condition instanceof Closure) {
                        $q->where(function ($sub) use ($condition) {
                            $condition($sub);
                        }, null, null, $boolean);
                    } elseif (is_array($condition) && count($condition) === 2 && is_string($condition[0])) {
                        [$operator, $value] = $condition;
                        $q->where($column, $operator, $value, $boolean);
                    } elseif (is_array($condition) && array_key_exists('null', $condition)) {
                        $condition['null'] ? $q->whereNull($column, $boolean) : $q->whereNotNull($column, $boolean);
                    } else {
                        $q->where($column, '=', $condition, $boolean);
                    }
                }
            }, $boolean);
        }

        return $query;
    }

    /**
     * Filter dynamically across localized month bounds gracefully.
     */
    public function scopeFilterByMonth(Builder $query, $month, string $column = 'created_at', ?string $timezone = null): Builder
    {
        if (empty($month)) {
            return $query;
        }

        $timezone = TimezoneResolver::resolve($timezone);

        if (is_array($month)) {
            return $query->where(function ($q) use ($month, $column, $timezone) {
                foreach ($month as $m) {
                    $start = Carbon::create(null, $m, 1, 0, 0, 0, $timezone)->startOfMonth();
                    $end   = Carbon::create(null, $m, 1, 0, 0, 0, $timezone)->endOfMonth();
                    $q->orWhereBetween($column, [
                        TimezoneResolver::toUtc($start),
                        TimezoneResolver::toUtc($end),
                    ]);
                }
            });
        }

        $start = Carbon::create(null, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
        $end   = Carbon::create(null, $month, 1, 0, 0, 0, $timezone)->endOfMonth();

        return $query->whereBetween($column, [
            TimezoneResolver::toUtc($start),
            TimezoneResolver::toUtc($end),
        ]);
    }

    /**
     * Filter dynamically across localized yearly bounds gracefully.
     */
    public function scopeFilterByYear(Builder $query, $year, string $column = 'created_at', ?string $timezone = null): Builder
    {
        if (empty($year)) {
            return $query;
        }

        $timezone = TimezoneResolver::resolve($timezone);

        if (is_array($year)) {
            return $query->where(function ($q) use ($year, $column, $timezone) {
                foreach ($year as $y) {
                    $start = Carbon::create($y, 1, 1, 0, 0, 0, $timezone)->startOfYear();
                    $end   = Carbon::create($y, 12, 31, 23, 59, 59, $timezone)->endOfYear();
                    $q->orWhereBetween($column, [
                        TimezoneResolver::toUtc($start),
                        TimezoneResolver::toUtc($end),
                    ]);
                }
            });
        }

        $start = Carbon::create($year, 1, 1, 0, 0, 0, $timezone)->startOfYear();
        $end   = Carbon::create($year, 12, 31, 23, 59, 59, $timezone)->endOfYear();

        return $query->whereBetween($column, [
            TimezoneResolver::toUtc($start),
            TimezoneResolver::toUtc($end),
        ]);
    }

    /**
     * Limit matches exclusively falling inside localized daily boundaries natively translated to UTC.
     */
    public function scopeFilterByDate(Builder $query, $date, string $column = 'created_at', ?string $timezone = null): Builder
    {
        if (!$date) {
            return $query;
        }

        $timezone   = TimezoneResolver::resolve($timezone);
        $startOfDay = Carbon::parse($date, $timezone)->startOfDay();
        $endOfDay   = Carbon::parse($date, $timezone)->endOfDay();

        return $query->whereBetween($column, [
            TimezoneResolver::toUtc($startOfDay),
            TimezoneResolver::toUtc($endOfDay),
        ]);
    }

    /**
     * Restrict matching constraints inside variable missing ranges natively extending.
     */
    public function scopeFilterByDateRange(Builder $query, $dateFrom = null, $dateTo = null, string $column = 'created_at', ?string $timezone = null): Builder
    {
        $timezone = TimezoneResolver::resolve($timezone);

        if ($dateFrom) {
            $query->where($column, '>=', TimezoneResolver::toUtc(Carbon::parse($dateFrom, $timezone)->startOfDay()));
        }

        if ($dateTo) {
            $query->where($column, '<=', TimezoneResolver::toUtc(Carbon::parse($dateTo, $timezone)->endOfDay()));
        }

        return $query;
    }

    /**
     * Automagically inject filter matching arrays explicitly targeting incoming keys directly matching column queries securely.
     */
    public function scopeFilterFromRequest(Builder $query, $request, $filters = []): Builder
    {
        foreach ($filters as $dbColumn => $requestKey) {
            if ($request->has($requestKey)) {
                $query->filterBy($dbColumn, $request->input($requestKey));
            }
        }
        return $query;
    }

    /**
     * Parse arrays, delimited strings organically binding mapped structural WhereIn matrices.
     */
    public function scopeFilterWhereIn(Builder $query, ...$args): Builder
    {
        if (isset($args[0]) && is_array($args[0]) && !array_is_list($args[0])) {
            foreach ($args[0] as $column => $values) {
                if (!$column || !$values) {
                    continue;
                }

                if (is_string($values)) {
                    $values = array_map('trim', explode(',', $values));
                }

                if (count($values) > 0) {
                    $query->whereIn($column, $values);
                }
            }
            return $query;
        }

        $column = $args[0] ?? null;
        if (!$column) {
            return $query;
        }

        $values = $args[1] ?? [];
        if (is_string($values)) {
            $values = array_map('trim', explode(',', $values));
        }

        if (!is_array($values)) {
            $values = array_slice($args, 1);
        }

        if (count($values) > 0) {
            $query->whereIn($column, $values);
        }

        return $query;
    }

    public function scopeSortResultBy(Builder $query, $column, $order = 'asc'): Builder
    {
        if ($column) {
            $query->orderBy($column, $order);
        }
        return $query;
    }

    public function scopeLatestBy(Builder $query, $column): Builder
    {
        if ($column) {
            $query->latest($column);
        }
        return $query;
    }

    public function scopeOldestBy(Builder $query, $column): Builder
    {
        if ($column) {
            $query->oldest($column);
        }
        return $query;
    }
}
