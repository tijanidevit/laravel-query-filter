# Query Filter for Laravel

A simple, expressive, and powerful Laravel package that provides dynamic Eloquent model filters with clean syntax. It helps you build reusable, maintainable, and readable query logic in Laravel applications.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tijanidevit/query-filter.svg)](https://packagist.org/packages/tijanidevit/query-filter)
[![Downloads](https://img.shields.io/packagist/dt/tijanidevit/query-filter.svg)](https://packagist.org/packages/tijanidevit/query-filter)
[![License](https://img.shields.io/packagist/l/tijanidevit/query-filter.svg)](LICENSE)

---

## Why Use This Package?

In many Laravel applications, especially admin dashboards, reporting systems, and search filters, developers often repeat the same filtering logic:

```php
$query = User::query();

if (request()->filled('name')) {
    $query->where('name', request('name'));
}

if (request()->filled('status')) {
    $query->where('status', request('status'));
}

return $query->get();
```

**With Query Filter, you can simplify this:**

```php
User::query()
    ->filterBy([
        'name' => request('name'),
        'status' => request('status'),
    ])
    ->get();
```

---

## Installation

Run the composer command:

```bash
composer require tijanidevit/query-filter
```

The package supports auto-discovery. If you are using Laravel < 5.5, add the provider manually in `config/app.php`:

```php
'providers' => [
    Tijanidevit\QueryFilter\Providers\FilterProvider::class,
]
```

---

## Available Filters

### `filterBy()`

Filter by a column or multiple columns.

```php
// Single column
User::query()->filterBy('status', 'active')->get();

// Multiple columns
User::query()->filterBy([
    'name' => 'Jane',
    'status' => ['active', 'pending'],
])->get();

// Handle null values
User::query()->filterBy([
    'email_verified_at' => ['null' => true],
])->get();
```

- Handles `null`, arrays, or empty values smartly.
- Fully chainable with other macros.

---

### `filterByRelation()`

Filter using related models:

```php
Post::query()->filterByRelation([
    'user' => [
        'status' => 'active',
    ]
])->get();
```

Supports:

- Closures for advanced queries
- Nullable checks (`['null' => true]`)
- Standard operators: `['operator', 'value']`

---

### `filterWhereIn()`

Flexible `WHERE IN` filtering for single or multiple columns.

```php
// Single column with array
User::query()->filterWhereIn('status', ['active', 'pending'])->get();

// Single column with multiple values as arguments
User::query()->filterWhereIn('status', 'active', 'pending')->get();

// Single column with comma-separated string
User::query()->filterWhereIn('status', 'active,pending')->get();

// Multiple columns with arrays
User::query()->filterWhereIn([
    'status' => ['active', 'pending'],
    'role' => ['admin', 'editor']
])->get();

// Multiple columns with comma-separated strings
User::query()->filterWhereIn([
    'status' => 'active,pending',
    'role' => 'admin,editor'
])->get();
```

- Automatically converts comma-separated strings into arrays.
- Skips empty or null values.
- Fully chainable.

---

### `filterByMonth()` / `filterByYear()`

Filter by month(s) or year(s):

```php
Order::query()
    ->filterByMonth([1, 2]) // January and February
    ->filterByYear([2023, 2024])
    ->get();
```

- Accepts single value or array of values.
- Uses app timezone or configurable timezone and converts to UTC for queries.

---

### `filterByDate()`

Filter records by a single date:

```php
User::query()->filterByDate('2024-01-01')->get();
```

- Converts to start and end of day automatically.
- Accepts optional timezone.

---

### `filterByDateRange()`

Filter records between two dates:

```php
Order::query()->filterByDateRange('2024-01-01', '2024-03-01')->get();
```

- Accepts optional timezone.
- Works with either `dateFrom`, `dateTo`, or both.

---

### `filterFromRequest()`

Automatically apply filters from request input:

```php
User::query()->filterFromRequest(request(), [
    'name' => 'name',
    'email' => 'email',
    'status' => 'status',
])->get();
```

- Accepts an array where keys are database columns and values are request keys.
- Skips empty or missing request values.

---

### `sortResultBy()`, `latestBy()`, `oldestBy()`

Sort results easily:

```php
User::query()->sortResultBy('created_at', 'desc')->get();

Post::query()->latestBy('published_at')->get();

Post::query()->oldestBy('published_at')->get();
```

---

### `search()` / `orSearch()` / `searchByRelation()`

Search for patterns in columns:

```php
// Search single column
User::query()->search('name', 'John')->get();

// Search multiple columns (OR)
User::query()->orSearch(['name', 'email'], 'John')->get();

// Search in a related model
Post::query()->searchByRelation('user', [
    'name' => 'John',
])->get();
```

- Default search uses SQL `LIKE %value%`.
- Supports relations, closures, and null checks.

---

## Full Example

```php
$users = User::query()
    ->filterBy([
        'name' => request('name'),
        'status' => request('status'),
        'email_verified_at' => ['null' => request('missing_email_verification')],
    ])
    ->filterByRelation([
        'roles' => [
            'slug' => request('role_slug'),
        ],
    ])
    ->filterWhereIn([
        'department' => request('departments'), // array or comma-separated
        'status' => 'active,pending'
    ])
    ->filterByDateRange(request('from'), request('to'))
    ->sortResultBy(request('sort_by'), request('sort_dir', 'asc'))
    ->get();
```

---

## 💡 Tips & Best Practices

Query Filter macros are designed to be **fully chainable**, letting you compose complex queries cleanly. Here are some recommended patterns:

### 1. Combine `filterBy`, `filterWhereIn`, and `filterByRelation`

```php
$users = User::query()
    ->filterBy([
        'status' => request('status'),
        'email_verified_at' => ['null' => request('missing_email_verification')],
    ])
    ->filterWhereIn([
        'department' => request('departments'), // accepts array or comma-separated string
        'role' => 'admin,editor'
    ])
    ->filterByRelation([
        'manager' => [
            'status' => 'active',
        ],
    ])
    ->get();
```

- Use `filterBy` for simple columns with exact or nullable values.
- Use `filterWhereIn` when you want `IN` filtering, supporting arrays, comma-separated strings, or multiple arguments.
- Use `filterByRelation` to filter related models without manually writing `whereHas` queries.

---

### 2. Use `filterByDate` / `filterByDateRange` for date filtering

```php
$orders = Order::query()
    ->filterByDate(request('date'))                     // exact day
    ->filterByDateRange(request('from'), request('to')) // range
    ->filterBy(['status' => request('status')])
    ->get();
```

- Supports optional timezone.
- Automatically converts dates to start and end of day in UTC for consistency.

---

### 3. Search with `search`, `orSearch`, and `searchByRelation`

```php
$posts = Post::query()
    ->search('title', request('title'))
    ->orSearch(['content', 'summary'], request('keyword'))
    ->searchByRelation('author', ['name' => request('author_name')])
    ->get();
```

- `search` is for a single column.
- `orSearch` allows searching across multiple columns.
- `searchByRelation` searches columns in related models.

---

### 4. Sorting and ordering

```php
$users = User::query()
    ->filterBy(['status' => 'active'])
    ->sortResultBy(request('sort_by', 'created_at'), request('sort_dir', 'asc'))
    ->latestBy('last_login')
    ->get();
```

- Use `sortResultBy` for dynamic column sorting.
- Use `latestBy` or `oldestBy` for default chronological ordering.

---

### 5. Combining everything

For a full-featured admin or reporting query:

```php
$users = User::query()
    ->filterBy([
        'status' => request('status'),
        'email_verified_at' => ['null' => request('missing_email_verification')],
    ])
    ->filterWhereIn([
        'department' => request('departments'),
        'role' => 'admin,editor'
    ])
    ->filterByRelation([
        'manager' => [
            'status' => 'active',
        ],
    ])
    ->filterByDateRange(request('from'), request('to'))
    ->orSearch(['name', 'email'], request('search'))
    ->sortResultBy(request('sort_by', 'created_at'), request('sort_dir', 'asc'))
    ->latestBy('last_login')
    ->get();
```

- Keep your queries readable and maintainable by chaining macros instead of nesting raw `where` and `orWhere` calls.
- The macros handle nulls, empty values, and type conversions, so your code stays clean.

---

### Recommendations

1. **Always validate request inputs** before passing to filters if needed.
2. **Use arrays or comma-separated strings** with `filterWhereIn` for maximum flexibility.
3. **Combine macros in logical order**: `filterBy` → `filterWhereIn` → `filterByRelation` → `filterByDate/DateRange` → `search` → `sort`.
4. **Leverage `filterFromRequest`** for repetitive request-based filtering to simplify controllers.

This approach keeps your controllers slim and your queries consistent across the app.

```

---

## Requirements

- Laravel 9, 10, 11
- PHP 8.0+

---

## License

MIT © [Mustapha Tijani](mailto:thenewxpat@gmail.com)

---

## Contributing

Pull requests and issues are welcome. Help improve the package and make it more awesome!
```
