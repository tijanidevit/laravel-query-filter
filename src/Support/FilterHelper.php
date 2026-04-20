<?php

namespace Tijanidevit\QueryFilter\Support;

class FilterHelper
{
    /**
     * Determine if a value should participate in a filter condition.
     * 
     * This helper ensures that empty inputs (null, empty strings, empty arrays) 
     * are safely ignored by the query builder, preventing accidental broad querying.
     * However, explicit boolean intent (true/false) and the special 
     * `['null' => true]` nullable sentinel are explicitly allowed.
     *
     * @param mixed $value The incoming filter value to inspect.
     * @return bool True if the value is clean and should be queried, false otherwise.
     */
    public static function isFilterable(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_array($value) && array_key_exists('null', $value)) {
            return true;
        }

        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return false;
        }

        return true;
    }
}
