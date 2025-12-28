# Database

## Configuration

Configure your database connection in `config/database.php`:

```php
return [
    'default' => 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'my_app',
            'username' => 'root',
            'password' => 'secret',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];
```

## Connection

### Basic Usage

```php
use Framework\Database\Connection as DB;

// Get PDO instance
$pdo = DB::connection();

// Get specific connection
$pdo = DB::connection('mysql');
```

### Raw Queries

```php
// Select (returns array of objects)
$users = DB::select('SELECT * FROM users WHERE active = ?', [1]);

// Select one record
$user = DB::selectOne('SELECT * FROM users WHERE id = ?', [1]);

// Insert (returns last insert ID)
$id = DB::insert(
    'INSERT INTO users (name, email) VALUES (?, ?)',
    ['John', 'john@example.com']
);

// Update (returns affected rows)
$affected = DB::update(
    'UPDATE users SET name = ? WHERE id = ?',
    ['Jane', 1]
);

// Delete (returns affected rows)
$deleted = DB::delete('DELETE FROM users WHERE id = ?', [1]);

// General statement
DB::statement('DROP TABLE IF EXISTS temp_table');
```

### Transactions

```php
// Manual transaction
DB::beginTransaction();
try {
    DB::insert('INSERT INTO users (name) VALUES (?)', ['John']);
    DB::insert('INSERT INTO profiles (user_id) VALUES (?)', [1]);
    DB::commit();
} catch (\Exception $e) {
    DB::rollback();
    throw $e;
}

// Automatic transaction
DB::transaction(function ($pdo) {
    DB::insert('INSERT INTO users (name) VALUES (?)', ['John']);
    DB::insert('INSERT INTO profiles (user_id) VALUES (?)', [1]);
    // Automatically commits, or rollbacks on exception
});
```

---

## Query Builder

The Query Builder provides a fluent interface for building SQL queries.

### Retrieving Results

```php
use Framework\Database\Connection as DB;

// Get all records
$users = DB::table('users')->get();

// Get first record
$user = DB::table('users')->first();

// Find by ID
$user = DB::table('users')->find(1);

// Get single column value
$name = DB::table('users')->where('id', 1)->value('name');

// Get column as array
$names = DB::table('users')->pluck('name');

// Pluck with keys
$users = DB::table('users')->pluck('name', 'id');
// Result: [1 => 'John', 2 => 'Jane']
```

### Select Columns

```php
// Specific columns
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->get();

// With alias
$users = DB::table('users')
    ->select('name', 'email as user_email')
    ->get();

// Add columns
$query = DB::table('users')->select('name');
$query->addSelect('email');

// Distinct
$users = DB::table('users')
    ->distinct()
    ->select('department')
    ->get();
```

### Where Clauses

```php
// Basic where
$users = DB::table('users')
    ->where('status', 'active')
    ->get();

// With operator
$users = DB::table('users')
    ->where('age', '>=', 18)
    ->get();

// Multiple conditions (AND)
$users = DB::table('users')
    ->where('status', 'active')
    ->where('role', 'admin')
    ->get();

// OR condition
$users = DB::table('users')
    ->where('role', 'admin')
    ->orWhere('role', 'moderator')
    ->get();

// Where IN
$users = DB::table('users')
    ->whereIn('id', [1, 2, 3])
    ->get();

// Where NOT IN
$users = DB::table('users')
    ->whereNotIn('status', ['banned', 'suspended'])
    ->get();

// Where NULL
$users = DB::table('users')
    ->whereNull('deleted_at')
    ->get();

// Where NOT NULL
$users = DB::table('users')
    ->whereNotNull('email_verified_at')
    ->get();

// Where BETWEEN
$users = DB::table('users')
    ->whereBetween('age', [18, 65])
    ->get();

// Where LIKE
$users = DB::table('users')
    ->whereLike('name', '%john%')
    ->get();

// Raw where
$users = DB::table('users')
    ->whereRaw('YEAR(created_at) = ?', [2024])
    ->get();
```

### Ordering, Grouping & Limits

```php
// Order by
$users = DB::table('users')
    ->orderBy('name')
    ->get();

$users = DB::table('users')
    ->orderBy('created_at', 'DESC')
    ->get();

$users = DB::table('users')
    ->orderByDesc('created_at')
    ->get();

// Limit and offset
$users = DB::table('users')
    ->limit(10)
    ->offset(20)
    ->get();

// Shortcuts
$users = DB::table('users')
    ->take(10)
    ->skip(20)
    ->get();

// Group by
$stats = DB::table('orders')
    ->select('status')
    ->addSelect(DB::raw('COUNT(*) as count'))
    ->groupBy('status')
    ->get();

// Having
$stats = DB::table('orders')
    ->select('user_id')
    ->groupBy('user_id')
    ->having('COUNT(*)', '>', 5)
    ->get();
```

### Joins

```php
// Inner join
$results = DB::table('users')
    ->join('posts', 'users.id', '=', 'posts.user_id')
    ->select('users.name', 'posts.title')
    ->get();

// Left join
$results = DB::table('users')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->get();

// Right join
$results = DB::table('users')
    ->rightJoin('posts', 'users.id', '=', 'posts.user_id')
    ->get();
```

### Aggregates

```php
// Count
$count = DB::table('users')->count();
$count = DB::table('users')->where('active', 1)->count();

// Sum
$total = DB::table('orders')->sum('amount');

// Average
$avg = DB::table('products')->avg('price');

// Min / Max
$min = DB::table('products')->min('price');
$max = DB::table('products')->max('price');

// Check existence
if (DB::table('users')->where('email', $email)->exists()) {
    // Record exists
}

if (DB::table('users')->where('email', $email)->doesntExist()) {
    // Record doesn't exist
}
```

### Insert

```php
// Insert single record (returns ID)
$id = DB::table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// Insert multiple records
DB::table('users')->insertBatch([
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
]);
```

### Update

```php
// Update records (returns affected count)
$affected = DB::table('users')
    ->where('id', 1)
    ->update(['name' => 'New Name']);

// Update multiple fields
$affected = DB::table('users')
    ->where('status', 'pending')
    ->update([
        'status' => 'active',
        'activated_at' => date('Y-m-d H:i:s'),
    ]);
```

### Delete

```php
// Delete records (returns affected count)
$deleted = DB::table('users')
    ->where('id', 1)
    ->delete();

// Delete with multiple conditions
$deleted = DB::table('users')
    ->where('status', 'inactive')
    ->where('created_at', '<', '2020-01-01')
    ->delete();

// Truncate table
DB::table('logs')->truncate();
```

### Pagination

```php
// Paginate results
$result = DB::table('users')
    ->where('active', 1)
    ->paginate(15);

// Result structure:
// [
//     'data' => [...],       // Current page items
//     'current_page' => 1,
//     'per_page' => 15,
//     'total' => 100,
//     'last_page' => 7,
//     'from' => 1,
//     'to' => 15,
// ]

// Access items
foreach ($result['data'] as $user) {
    echo $user->name;
}
```

### Chunking

Process large datasets in chunks:

```php
DB::table('users')->chunk(100, function ($users, $page) {
    foreach ($users as $user) {
        // Process each user
    }
    
    // Return false to stop chunking
    // return false;
});
```

### Debugging

```php
// Get the SQL query
$sql = DB::table('users')
    ->where('active', 1)
    ->toSql();
// SELECT * FROM users WHERE active = ?

// Enable query logging
DB::enableQueryLog();

// Run queries...
$users = DB::table('users')->get();

// Get logged queries
$log = DB::getQueryLog();

// Disable logging
DB::disableQueryLog();
```

## Next Steps

- [Error Handling](./07-error-handling.md)
