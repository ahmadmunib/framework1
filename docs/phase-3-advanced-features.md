# Phase 3: Advanced Features

**Duration:** Weeks 5-6  
**Status:** ⚪ Not Started  
**Dependencies:** Phase 1 & 2 Complete

---

## 🎯 Phase Objectives

Build advanced database capabilities, data management tools, and developer utilities that enable complex application development.

---

## 📋 Components to Build

### 1. Query Builder

**File:** `/framework/Database/QueryBuilder.php`

#### Implementation Steps:
1. [ ] Create fluent `QueryBuilder` class
2. [ ] Implement `select()` with column specification
3. [ ] Add `where()`, `orWhere()`, `whereIn()`, `whereNull()`
4. [ ] Implement `join()`, `leftJoin()`, `rightJoin()`
5. [ ] Add `orderBy()`, `groupBy()`, `having()`
6. [ ] Implement `limit()`, `offset()`
7. [ ] Add aggregate methods: `count()`, `sum()`, `avg()`, `min()`, `max()`
8. [ ] Create `insert()`, `update()`, `delete()` methods
9. [ ] Add `get()`, `first()`, `find()` retrieval methods
10. [ ] Implement chunking for large datasets

#### Code Structure:
```php
<?php
namespace Framework\Database;

class QueryBuilder
{
    public function table(string $table): self
    public function select(...$columns): self
    public function where(string $column, $operator, $value = null): self
    public function orWhere(string $column, $operator, $value = null): self
    public function whereIn(string $column, array $values): self
    public function whereNull(string $column): self
    public function whereNotNull(string $column): self
    public function whereBetween(string $column, array $values): self
    public function join(string $table, string $first, string $operator, string $second): self
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    public function orderBy(string $column, string $direction = 'ASC'): self
    public function groupBy(...$columns): self
    public function having(string $column, $operator, $value): self
    public function limit(int $limit): self
    public function offset(int $offset): self
    public function get(): Collection
    public function first(): ?object
    public function find(int $id): ?object
    public function count(): int
    public function sum(string $column): float
    public function insert(array $data): int
    public function update(array $data): int
    public function delete(): int
    public function paginate(int $perPage = 15): Paginator
    public function chunk(int $count, callable $callback): void
    public function toSql(): string
}
```

#### Usage Examples:
```php
// Simple query
$users = DB::table('users')
    ->where('active', 1)
    ->orderBy('created_at', 'DESC')
    ->get();

// Complex query with joins
$orders = DB::table('orders')
    ->select('orders.*', 'users.name as customer_name')
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->where('orders.status', 'completed')
    ->whereBetween('orders.created_at', [$startDate, $endDate])
    ->orderBy('orders.total', 'DESC')
    ->limit(10)
    ->get();

// Aggregates
$totalSales = DB::table('orders')->where('status', 'completed')->sum('total');
```

#### Testing Checklist:
- [ ] Test select queries
- [ ] Test where clauses
- [ ] Test joins
- [ ] Test aggregates
- [ ] Test insert/update/delete
- [ ] Test SQL generation

---

### 2. ORM (Active Record Pattern)

**Directory:** `/framework/Database/Model/`

#### Implementation Steps:
1. [ ] Create base `Model` class
2. [ ] Implement table name convention
3. [ ] Add magic `__get()` and `__set()` for attributes
4. [ ] Implement `find()`, `all()`, `first()` methods
5. [ ] Add `save()`, `create()`, `update()`, `delete()` methods
6. [ ] Implement relationships:
   - [ ] `hasOne()`
   - [ ] `hasMany()`
   - [ ] `belongsTo()`
   - [ ] `belongsToMany()`
7. [ ] Add timestamps management
8. [ ] Implement soft deletes
9. [ ] Add attribute casting
10. [ ] Create model events (creating, created, updating, etc.)

#### Code Structure:
```php
<?php
namespace Framework\Database;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = ['id'];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $timestamps = true;
    
    public static function find($id): ?static
    public static function findOrFail($id): static
    public static function all(): Collection
    public static function where(string $column, $operator, $value = null): QueryBuilder
    public static function create(array $attributes): static
    public function save(): bool
    public function update(array $attributes): bool
    public function delete(): bool
    public function hasOne(string $related, string $foreignKey = null): HasOne
    public function hasMany(string $related, string $foreignKey = null): HasMany
    public function belongsTo(string $related, string $foreignKey = null): BelongsTo
    public function belongsToMany(string $related, string $table = null): BelongsToMany
}
```

#### Example Model:
```php
<?php
namespace App\Models;

use Framework\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = ['name', 'email', 'password'];
    
    protected array $hidden = ['password', 'remember_token'];
    
    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
    
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}
```

#### Usage Examples:
```php
// Find user
$user = User::find(1);

// Query with relationships
$user = User::with('posts')->find(1);

// Create user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_BCRYPT),
]);

// Update user
$user->name = 'Jane Doe';
$user->save();

// Access relationships
$posts = $user->posts;
```

---

### 3. Migrations System

**Directory:** `/database/migrations/`

#### Implementation Steps:
1. [ ] Create `Migration` base class
2. [ ] Implement `Schema` builder class
3. [ ] Add migration runner CLI
4. [ ] Track executed migrations in database
5. [ ] Implement `up()` and `down()` methods
6. [ ] Add rollback functionality
7. [ ] Create migration generator command

#### Schema Builder Methods:
```php
<?php
namespace Framework\Database;

class Schema
{
    public static function create(string $table, callable $callback): void
    public static function table(string $table, callable $callback): void
    public static function drop(string $table): void
    public static function dropIfExists(string $table): void
    public static function rename(string $from, string $to): void
}

class Blueprint
{
    public function id(): self
    public function bigIncrements(string $column): self
    public function string(string $column, int $length = 255): self
    public function text(string $column): self
    public function integer(string $column): self
    public function bigInteger(string $column): self
    public function float(string $column): self
    public function decimal(string $column, int $precision, int $scale): self
    public function boolean(string $column): self
    public function date(string $column): self
    public function datetime(string $column): self
    public function timestamp(string $column): self
    public function timestamps(): self
    public function softDeletes(): self
    public function nullable(): self
    public function default($value): self
    public function unique(): self
    public function index(): self
    public function foreign(string $column): ForeignKey
    public function dropColumn(string $column): void
}
```

#### Migration Example:
```php
<?php
// database/migrations/2025_01_01_000001_create_users_table.php

use Framework\Database\Migration;
use Framework\Database\Schema;
use Framework\Database\Blueprint;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
```

#### CLI Commands:
```bash
php framework/cli migrate              # Run all pending migrations
php framework/cli migrate:rollback     # Rollback last batch
php framework/cli migrate:reset        # Rollback all migrations
php framework/cli migrate:fresh        # Drop all & re-migrate
php framework/cli make:migration create_posts_table
```

---

### 4. Seeding System

**Directory:** `/database/seeds/`

#### Implementation Steps:
1. [ ] Create `Seeder` base class
2. [ ] Implement seeder runner
3. [ ] Add factory-like data generation
4. [ ] Create CLI command for seeding
5. [ ] Support seeder dependencies

#### Code Structure:
```php
<?php
namespace Framework\Database;

abstract class Seeder
{
    abstract public function run(): void;
    
    protected function call(string $seeder): void
    protected function insert(string $table, array $data): void
}

class Faker
{
    public function name(): string
    public function email(): string
    public function text(int $length = 200): string
    public function number(int $min = 0, int $max = 100): int
    public function date(string $format = 'Y-m-d'): string
    public function boolean(): bool
    public function randomElement(array $array): mixed
}
```

#### Seeder Example:
```php
<?php
// database/seeds/UserSeeder.php

use Framework\Database\Seeder;
use Framework\Database\Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = new Faker();
        
        for ($i = 0; $i < 50; $i++) {
            $this->insert('users', [
                'name' => $faker->name(),
                'email' => $faker->email(),
                'password' => password_hash('password', PASSWORD_BCRYPT),
            ]);
        }
    }
}
```

---

### 5. Authorization (RBAC)

**Files:** `/framework/Auth/Authorization.php`, `/config/permissions.php`

#### Implementation Steps:
1. [ ] Create roles and permissions tables
2. [ ] Implement `Gate` class for simple checks
3. [ ] Create policy classes for model authorization
4. [ ] Add `can()` helper method
5. [ ] Implement middleware for authorization
6. [ ] Add Blade directives: `@can`, `@cannot`

#### Code Structure:
```php
<?php
namespace Framework\Auth;

class Gate
{
    public static function define(string $ability, callable $callback): void
    public static function allows(string $ability, ...$arguments): bool
    public static function denies(string $ability, ...$arguments): bool
    public static function authorize(string $ability, ...$arguments): void
}

abstract class Policy
{
    public function before(?User $user, string $ability): ?bool
    public function view(User $user, Model $model): bool
    public function create(User $user): bool
    public function update(User $user, Model $model): bool
    public function delete(User $user, Model $model): bool
}
```

#### Usage Examples:
```php
// Define gates
Gate::define('edit-post', function (User $user, Post $post) {
    return $user->id === $post->user_id;
});

// Check permissions
if (Gate::allows('edit-post', $post)) {
    // User can edit
}

// In controller
$this->authorize('update', $post);

// In view
@can('edit', $post)
    <a href="/posts/{{ $post->id }}/edit">Edit</a>
@endcan
```

---

### 6. Caching System

**Directory:** `/framework/Cache/`

#### Implementation Steps:
1. [ ] Create cache driver interface
2. [ ] Implement file-based cache driver
3. [ ] Add Redis driver (if available)
4. [ ] Implement cache expiration
5. [ ] Add tag-based cache clearing
6. [ ] Create cache helper function

#### Code Structure:
```php
<?php
namespace Framework\Cache;

class Cache
{
    public static function get(string $key, $default = null): mixed
    public static function put(string $key, $value, int $seconds = 3600): bool
    public static function forever(string $key, $value): bool
    public static function forget(string $key): bool
    public static function flush(): bool
    public static function has(string $key): bool
    public static function remember(string $key, int $seconds, callable $callback): mixed
    public static function rememberForever(string $key, callable $callback): mixed
    public static function tags(array $tags): TaggedCache
}
```

#### Usage Examples:
```php
// Store value
Cache::put('user.1', $user, 3600);

// Retrieve value
$user = Cache::get('user.1');

// Remember pattern
$users = Cache::remember('users.all', 3600, function () {
    return User::all();
});

// Tagged cache
Cache::tags(['users'])->put('user.1', $user, 3600);
Cache::tags(['users'])->flush();
```

---

### 7. AJAX Data Loading Components

**Files:** `/public/js/data-table.js`, `/framework/Database/DataTable.php`

#### Implementation Steps:
1. [ ] Create server-side `DataTable` class
2. [ ] Implement column filtering
3. [ ] Add multi-column sorting
4. [ ] Create search functionality
5. [ ] Build JavaScript data table component
6. [ ] Add filter persistence (localStorage)
7. [ ] Implement CSV export

#### Server-Side:
```php
<?php
namespace Framework\Database;

class DataTable
{
    public static function make(QueryBuilder $query): self
    public function filter(array $filters): self
    public function sort(string $column, string $direction): self
    public function search(string $term, array $columns): self
    public function paginate(int $perPage = 15): array
    public function toJson(): string
}
```

#### JavaScript Component:
```javascript
// public/js/data-table.js
class DataTable {
    constructor(options) {
        this.container = options.container;
        this.endpoint = options.endpoint;
        this.columns = options.columns;
    }
    
    load(params = {})
    filter(column, value)
    sort(column, direction)
    search(term)
    export(format)
    render(data)
}
```

---

### 8. Data Export Utilities

**File:** `/framework/Export/Exporter.php`

#### Implementation Steps:
1. [ ] Create CSV exporter with proper encoding
2. [ ] Implement basic Excel export (.xlsx)
3. [ ] Add PDF export wrapper
4. [ ] Support large dataset streaming
5. [ ] Add column mapping configuration

#### Code Structure:
```php
<?php
namespace Framework\Export;

class Exporter
{
    public static function csv(array $data, string $filename, array $headers = []): void
    public static function excel(array $data, string $filename, array $headers = []): void
    public static function pdf(string $html, string $filename, array $options = []): void
    public static function stream(QueryBuilder $query, string $format, callable $transform): void
}
```

---

### 9. Logging System

**Directory:** `/framework/Log/`

#### Implementation Steps:
1. [ ] Create logger with PSR-3-like interface
2. [ ] Implement log levels (debug, info, warning, error, critical)
3. [ ] Add file-based logging with rotation
4. [ ] Support multiple channels
5. [ ] Add context logging

#### Code Structure:
```php
<?php
namespace Framework\Log;

class Log
{
    public static function debug(string $message, array $context = []): void
    public static function info(string $message, array $context = []): void
    public static function warning(string $message, array $context = []): void
    public static function error(string $message, array $context = []): void
    public static function critical(string $message, array $context = []): void
    public static function channel(string $name): Logger
}
```

---

## 📅 Week-by-Week Schedule

### Week 5
| Day | Task |
|-----|------|
| Day 1-2 | Query Builder (all methods) |
| Day 3-4 | ORM (Model class + relationships) |
| Day 5 | Testing Query Builder & ORM |

### Week 6
| Day | Task |
|-----|------|
| Day 1 | Migrations & Seeding |
| Day 2 | Authorization (RBAC) |
| Day 3 | Caching System |
| Day 4 | Data Tables & Export |
| Day 5 | Logging + Integration testing |

---

## ✅ Phase Completion Criteria

- [ ] Query builder generates correct SQL
- [ ] ORM relationships work correctly
- [ ] Migrations run and rollback properly
- [ ] Seeders populate test data
- [ ] Authorization gates and policies work
- [ ] Cache stores and retrieves data
- [ ] Data tables filter, sort, and paginate
- [ ] Export generates valid CSV/Excel files
- [ ] Logging captures all levels
- [ ] All unit tests passing
