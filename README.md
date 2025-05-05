# Query Filter for Laravel

A simple, expressive, and powerful Laravel package that provides dynamic Eloquent model filters with clean syntax. It helps you build reusable, maintainable, and readable query logic in Laravel applications.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tijanidevit/query-filter.svg)](https://packagist.org/packages/tijanidevit/query-filter)
[![Downloads](https://img.shields.io/packagist/dt/tijanidevit/query-filter.svg)](https://packagist.org/packages/tijanidevit/query-filter)
[![License](https://img.shields.io/packagist/l/tijanidevit/query-filter.svg)](LICENSE)

---

## 📦 Why Use This Package?

In many Laravel applications, especially admin dashboards, reporting systems, and search filters, developers write the same filtering logic over and over:

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

With Query Filter, you can simplify this:

```php
    User::query()
        ->filterBy([
            'name' => request('name'),
            'status' => request('status'),
        ])
        ->get();

```

---

## 📦 Installation

Run the composer command below:

```bash
composer require tijanidevit/query-filter
```

The package supports auto-discovery. If you're using Laravel < 5.5, add the provider manually in your `config/app.php`:

```php
'providers' => [
    Tijanidevit\QueryFilter\Providers\FilterProvider::class,
]
```

---

## 🔍 Available Filters

### `filterBy()`

Filter by a column or multiple columns.

```php
User::query()->filterBy('status', 'active')->get();


User::query()->filterBy('status', 'active')->filterBy('age', '>' 18)->get();

User::query()->filterBy([
    'name'   => 'Jane',
    'status' => ['active', 'pending'],
])->get();
```

- Handles `null`, arrays, or empty values smartly.
- Validates that the column exists in the database.

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

Supports closures and nullable logic.

---

### `filterByMonth()` / `filterByYear()`

```php
Order::query()
    ->filterByMonth([1, 2]) // January and February
    ->filterByYear(2024)
    ->get();
```

---

### `filterByDate()`

```php
User::query()->filterByDate('2024-01-01')->get();
```

---

### `filterByDateRange()`

```php
Order::query()->filterByDateRange('2024-01-01', '2024-03-01')->get();
```

---

### `filterFromRequest()`

Automatically apply filters based on request input:

```php
User::query()->filterFromRequest(request(), [
    'name', 'email', 'status'
])->get();
```

---

### `sortResultBy()` / `latestBy()` / `oldestBy()`

```php
User::query()->sortResultBy('created_at', 'desc')->get();

Post::query()->latestBy('published_at')->get();
```

---

## 📘 Full Example

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

## ✅ Requirements

- Laravel 9, 10, 11
- PHP 8.0+

---

## 📄 License

MIT © [Mustapha Tijani](mailto:thenewxpat@gmail.com)

---

## 🤝 Contributing

Pull requests and issues are welcome. Help improve the package and make it more awesome!
