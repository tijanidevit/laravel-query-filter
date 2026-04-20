# Query Filter for Laravel

A simple, expressive, and powerful Laravel package that provides dynamic Eloquent model filters with clean syntax. It helps you build reusable, maintainable, and readable query logic in Laravel applications.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tijanidevit/query-filter.svg)](https://packagist.org/packages/tijanidevit/query-filter)
[![Downloads](https://img.shields.io/packagist/dt/tijanidevit/query-filter.svg)](https://packagist.org/packages/tijanidevit/query-filter)
[![License](https://img.shields.io/packagist/l/tijanidevit/query-filter.svg)](LICENSE)

---

## 🎯 Why Use This Package?

In many Laravel applications—especially admin dashboards, reporting systems, and search pipelines—developers often repeat the same verbose filtering logic in controllers:

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

**With Query Filter, you can simplify this down to a single, beautifully readable chain:**

```php
User::query()
    ->filterBy([
        'name' => request('name'),
        'status' => request('status'),
    ])
    ->get();
```
*It safely ignores empty parameters automatically, so you never write an `if (request()->filled(...))` block again.*

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require tijanidevit/query-filter
```

*(Optional)* The package supports Auto-Discovery. If you are using an older version of Laravel (pre-5.5), manually register the provider in `config/app.php`:

```php
'providers' => [
    Tijanidevit\QueryFilter\Providers\FilterProvider::class,
]
```

### Applying the Trait
Query Filter utilizes Laravel Scopes. To enable filtering on a model, add the `Filterable` trait:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tijanidevit\QueryFilter\Traits\Filterable;

class User extends Model
{
    use Filterable;
}
```

---

## ⚡ Database Drivers & ILIKE Support

The package dynamically detects your database connection. If you are using **PostgreSQL**, all `search()`, `searchIn()`, `orSearch()`, and `searchByRelation()` methods will automatically utilize the case-insensitive `ILIKE` operator. For all other SQL databases (MySQL, SQLite), it will default natively to `LIKE`.

---

## 📚 Available Filters

### `filterBy()`
Filter by a single column or multiple columns using exact matches.

```php
// Single column matching
User::query()->filterBy('status', 'active')->get();

// Multiple columns exact matching
User::query()->filterBy([
    'name' => 'Jane',
    'status' => ['active', 'pending'], // Safely wraps to WhereIn natively!
])->get();

// Secure Null checks automatically
User::query()->filterBy([
    'email_verified_at' => ['null' => true],
])->get();
```

### `filterByRelation()`
Filter directly using nested related models cleanly without verbose `whereHas` queries.

```php
Post::query()->filterByRelation([
    'author' => [
        'status' => 'active',
        'is_banned' => false
    ]
])->get();
```

### `filterWhereIn()`
Flexible `WHERE IN` filtering parsing arrays and strings automatically.

```php
// Standard Array
User::query()->filterWhereIn('status', ['active', 'pending'])->get();

// Comma-delimited strings (Ideal for external API requests)
User::query()->filterWhereIn('status', 'active,pending')->get();

// Variadic arguments
User::query()->filterWhereIn('status', 'active', 'pending')->get();
```

### `search()`, `searchIn()`, and `orSearch()`
Powerful dynamic `LIKE` search abstractions.

```php
// AND logic across multiple fields
User::query()->search(['name' => 'John', 'city' => 'Lagos'])->get();

// searchIn(): Grouped OR searching across multiple fields using a single keyword constraint
User::query()->searchIn(['first_name', 'last_name', 'email'], 'john')->get();

// orSearch(): Chained top-level grouped OR blocks gracefully appended to pre-existing searches
Article::query()
    ->search('category', 'technology')
    ->orSearch(['title', 'summary'], 'laravel')
    ->get();
```

### `filterFromRequest()`
Automatically bind constraints directly from incoming HTTP Request objects by mapping input keys to database columns.

```php
User::query()->filterFromRequest(request(), [
    'email' => 'login_email',   // Translates to: where email = request('login_email')
    'department_id' => 'dept',
])->get();
```

### `filterByDate()`, `filterByMonth()`, `filterByYear()`, `filterByDateRange()`
Timezone-aware date parsing spanning specific bounding frames neatly.

```php
// Filter single explicit matching date period ranges
User::query()->filterByDate('2024-01-01')->get();

// Search date boundaries safely ignoring omitted limits
Order::query()->filterByDateRange(request('date_from'), request('date_to'))->get();

// Isolate month timelines seamlessly
Post::query()->filterByMonth([1, 2])->get();
```

### Sorting Helpers
Chainable dynamic chronological sorting bounds.

```php
User::query()
    ->sortResultBy(request('sort_column'), request('sort_direction'))
    ->latestBy('last_login')
    ->get();
```

---

## 💡 Advanced Best Practices

Query Filter macros are designed to act composably, linking infinitely together parsing structural queries perfectly.

### The "God Query" Approach
For complex Admin panels parsing 15 distinct variables simultaneously, combine macros sequentially:

```php
$users = User::query()
    ->filterBy([
        'status' => request('status'),
        'email_verified_at' => ['null' => request('missing_email_verification')],
    ])
    ->filterWhereIn([
        'department' => request('departments'), // Array or comma-delimited natively evaluated
    ])
    ->filterByRelation([
        'manager' => [
            'status' => 'active',
        ],
    ])
    ->filterByDateRange(request('from'), request('to'))
    ->searchIn(['name', 'email', 'biography'], request('search_query'))
    ->sortResultBy(request('sort_by', 'created_at'), request('sort_dir', 'desc'))
    ->latestBy('last_login')
    ->get();
```

### Structural Security Recommendations

1. **Always validate request inputs** before blindly dumping `request()` into maps. 
2. Use arrays or comma-delimited structures intelligently parsed with `filterWhereIn()` bridging dashboard parameters structurally.
3. Funnel heavy endpoint evaluations entirely into `filterFromRequest()` maps for ultra-slim API Controllers.

---

## 🛠 Requirements

- Laravel 9, 10, or 11+
- PHP 8.0+

## 📄 License
This package is open-sourced software licensed under the MIT license.
