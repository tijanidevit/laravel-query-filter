# Query Filter for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tijanidevit/query-filter.svg?style=flat-square)](https://packagist.org/packages/tijanidevit/query-filter)
[![Total Downloads](https://img.shields.io/packagist/dt/tijanidevit/query-filter.svg?style=flat-square)](https://packagist.org/packages/tijanidevit/query-filter)
[![License](https://img.shields.io/packagist/l/tijanidevit/query-filter.svg?style=flat-square)](LICENSE)

**Query Filter** is a simple, expressive, and powerful Laravel package that provides dynamic Eloquent models filters with clean syntax. It helps build reusable, maintainable, and readable query logic in Laravel apps.

---

## 🔧 Installation

Install the package via Composer:

```bash
composer require tijanidevit/query-filter
```

---

## ⚙️ Setup

In your `AppServiceProvider` or any custom service provider, boot the macros:

```php
use Tijanidevit\QueryFilter\Support\FilterableMacros;

public function boot(): void
{
    FilterableMacros::boot();
}
```

> ✅ Once booted, all macros become available on the Eloquent query builder across your application.

---

## ✨ Features

-   `filterBy()` — Filter by single or multiple columns
-   `filterByRelation()` — Apply conditions to related models
-   `filterByMonth()` / `filterByYear()` — Filter by parts of dates
-   `filterByDate()` / `filterByDateRange()` — Exact or ranged date filters
-   `filterFromRequest()` — Automatically filter from request input
-   `sortResultBy()` — Dynamically sort results
-   `latestBy()` / `oldestBy()` — Use custom column for latest/oldest order
-   **Column validation** — Avoid filtering by non-existent columns with smart caching

---

## 📘 Usage Guide

### 1. Basic Column Filtering

```php
User::query()->filterBy('status', 'active')->get();
```

Or filter multiple at once:

```php
User::query()->filterBy([
    'name'   => 'Jane',
    'status' => ['active', 'pending'],
])->get();
```

#### Nullable Column Filtering

```php
User::query()->filterBy('email_verified_at', ['null' => true])->get(); // WHERE email_verified_at IS NULL
```

---

### 2. Filter by Relations

```php
Post::query()->filterByRelation([
    'user' => [
        'status' => 'active',
    ]
])->get();
```

Supports:

-   Scalar values
-   Array filters
-   Nullable fields
-   Custom `Closure` logic

---

### 3. Filter by Dates

#### Exact Date

```php
User::query()->filterByDate('2024-01-01')->get();
```

#### Month / Year

```php
Order::query()
    ->filterByMonth(4) // April
    ->filterByYear([2023, 2024])
    ->get();
```

#### Date Range

```php
Order::query()->filterByDateRange('2024-01-01', '2024-12-31')->get();
```

---

### 4. Auto-Filtering from Request

```php
User::query()->filterFromRequest(request(), [
    'name', 'email', 'status'
])->get();
```

This will automatically call `filterBy()` for each field if it's present in the request.

---

### 5. Sorting

```php
User::query()->sortResultBy('created_at', 'desc')->get();

Post::query()->latestBy('published_at')->get();
```

---

## 🧠 Column Validation

This package validates whether the columns exist in the database schema before applying filters. This prevents accidental query errors.

### Performance Optimization

-   Per-request memory cache
-   Laravel's cache store (default lifetime: 12 hours)

### Cache Invalidation

Clear Laravel’s cache after schema changes:

```bash
php artisan cache:clear
```

---

## 🧪 Example: Full Filtered Query

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
    ->filterByDateRange(request('from'), request('to'))
    ->sortResultBy(request('sort_by'), request('sort_dir', 'asc'))
    ->get();
```

---

## 🚨 Exceptions

If a non-existent column is passed to a filter, the query will throw an `InvalidArgumentException`:

```
Column 'foo' does not exist on table 'users'.
```

---

## 🧰 Artisan Command (optional enhancement)

You can build a command to warm up or clear column caches:

```bash
php artisan query-filter:cache-clear
php artisan query-filter:cache-warm
```

---

## 📦 Laravel Compatibility

| Laravel Version | Supported |
| --------------- | --------- |
| 9.x             | ✅        |
| 10.x            | ✅        |
| 11.x            | ✅        |

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

## 🙌 Credits

-   Developed by [@tijanidevit](https://github.com/tijanidevit)
-   Inspired by query pipelines and filter patterns in Laravel

---

## 💬 Feedback

Feel free to open an issue or pull request for suggestions, improvements, or bugs. Contributions welcome!
