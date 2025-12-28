<?php

declare(strict_types=1);

namespace Framework\Database;

use PDO;

/**
 * SQL Query Builder
 * 
 * Provides a fluent interface for building SQL queries.
 * 
 * @example
 * DB::table('users')->where('active', 1)->orderBy('name')->get();
 */
class QueryBuilder
{
    protected ?string $connection;
    protected string $table = '';
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $orders = [];
    protected array $groups = [];
    protected ?string $having = null;
    protected array $joins = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected bool $distinct = false;

    public function __construct(?string $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Set the table
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set columns to select
     */
    public function select(string|array ...$columns): self
    {
        $this->columns = [];
        
        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->columns = array_merge($this->columns, $column);
            } else {
                $this->columns[] = $column;
            }
        }
        
        return $this;
    }

    /**
     * Add a column to select
     */
    public function addSelect(string|array ...$columns): self
    {
        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->columns = array_merge($this->columns, $column);
            } else {
                $this->columns[] = $column;
            }
        }
        return $this;
    }

    /**
     * Set distinct
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Add a where clause
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        // Handle where('column', 'value') shorthand
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND',
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add an OR where clause
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR',
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add a where IN clause
     */
    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
            'not' => false,
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Add a where NOT IN clause
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
            'not' => true,
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Add a where NULL clause
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND',
            'not' => false,
        ];

        return $this;
    }

    /**
     * Add a where NOT NULL clause
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND',
            'not' => true,
        ];

        return $this;
    }

    /**
     * Add a where BETWEEN clause
     */
    public function whereBetween(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
            'not' => false,
        ];

        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];

        return $this;
    }

    /**
     * Add a where LIKE clause
     */
    public function whereLike(string $column, string $value): self
    {
        return $this->where($column, 'LIKE', $value);
    }

    /**
     * Add a raw where clause
     */
    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => 'AND',
        ];

        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * Add a JOIN clause
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Add a LEFT JOIN clause
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Add a RIGHT JOIN clause
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Add an ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }

    /**
     * Order by descending
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Add a GROUP BY clause
     */
    public function groupBy(string ...$columns): self
    {
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    /**
     * Add a HAVING clause
     */
    public function having(string $column, string $operator, mixed $value): self
    {
        $this->having = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Set the LIMIT
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Alias for limit
     */
    public function take(int $limit): self
    {
        return $this->limit($limit);
    }

    /**
     * Set the OFFSET
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Alias for offset
     */
    public function skip(int $offset): self
    {
        return $this->offset($offset);
    }

    /**
     * Execute the query and get all results
     */
    public function get(): array
    {
        $sql = $this->toSql();
        return Connection::select($sql, $this->bindings, $this->connection);
    }

    /**
     * Get the first result
     */
    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Find a record by ID
     */
    public function find(int|string $id, string $column = 'id'): ?object
    {
        return $this->where($column, $id)->first();
    }

    /**
     * Get a single column value
     */
    public function value(string $column): mixed
    {
        $this->select($column);
        $result = $this->first();
        return $result?->$column;
    }

    /**
     * Get a column as array
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $this->select($key ? [$column, $key] : $column);
        $results = $this->get();
        
        $plucked = [];
        foreach ($results as $row) {
            if ($key) {
                $plucked[$row->$key] = $row->$column;
            } else {
                $plucked[] = $row->$column;
            }
        }
        
        return $plucked;
    }

    /**
     * Check if any records exist
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Check if no records exist
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * Get the count
     */
    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    /**
     * Get the sum
     */
    public function sum(string $column): float
    {
        return (float) $this->aggregate('SUM', $column);
    }

    /**
     * Get the average
     */
    public function avg(string $column): float
    {
        return (float) $this->aggregate('AVG', $column);
    }

    /**
     * Get the minimum
     */
    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * Get the maximum
     */
    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * Execute an aggregate function
     */
    protected function aggregate(string $function, string $column): mixed
    {
        $this->columns = ["{$function}({$column}) as aggregate"];
        $result = $this->first();
        return $result?->aggregate;
    }

    /**
     * Insert a record
     */
    public function insert(array $values): int
    {
        $columns = array_keys($values);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return Connection::insert($sql, array_values($values), $this->connection);
    }

    /**
     * Insert multiple records
     */
    public function insertBatch(array $records): bool
    {
        if (empty($records)) {
            return false;
        }

        $columns = array_keys($records[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($records), $placeholders));

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->table,
            implode(', ', $columns),
            $allPlaceholders
        );

        $bindings = [];
        foreach ($records as $record) {
            $bindings = array_merge($bindings, array_values($record));
        }

        return Connection::statement($sql, $bindings, $this->connection);
    }

    /**
     * Update records
     */
    public function update(array $values): int
    {
        $sets = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $sets[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s%s',
            $this->table,
            implode(', ', $sets),
            $this->compileWheres()
        );

        $bindings = array_merge($bindings, $this->bindings);

        return Connection::update($sql, $bindings, $this->connection);
    }

    /**
     * Delete records
     */
    public function delete(): int
    {
        $sql = sprintf(
            'DELETE FROM %s%s',
            $this->table,
            $this->compileWheres()
        );

        return Connection::delete($sql, $this->bindings, $this->connection);
    }

    /**
     * Truncate the table
     */
    public function truncate(): bool
    {
        return Connection::statement("TRUNCATE TABLE {$this->table}", [], $this->connection);
    }

    /**
     * Get the SQL query string
     */
    public function toSql(): string
    {
        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= implode(', ', $this->columns);
        $sql .= ' FROM ' . $this->table;

        // Joins
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // Where clauses
        $sql .= $this->compileWheres();

        // Group by
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        // Having
        if ($this->having) {
            $sql .= ' HAVING ' . $this->having;
        }

        // Order by
        if (!empty($this->orders)) {
            $orders = array_map(fn($o) => "{$o['column']} {$o['direction']}", $this->orders);
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }

        // Limit and offset
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    /**
     * Compile where clauses
     */
    protected function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = ' WHERE ';
        $first = true;

        foreach ($this->wheres as $where) {
            if (!$first) {
                $sql .= " {$where['boolean']} ";
            }
            $first = false;

            switch ($where['type']) {
                case 'basic':
                    $sql .= "{$where['column']} {$where['operator']} ?";
                    break;

                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $not = $where['not'] ? 'NOT ' : '';
                    $sql .= "{$where['column']} {$not}IN ({$placeholders})";
                    break;

                case 'null':
                    $not = $where['not'] ? 'NOT ' : '';
                    $sql .= "{$where['column']} IS {$not}NULL";
                    break;

                case 'between':
                    $not = $where['not'] ? 'NOT ' : '';
                    $sql .= "{$where['column']} {$not}BETWEEN ? AND ?";
                    break;

                case 'raw':
                    $sql .= $where['sql'];
                    break;
            }
        }

        return $sql;
    }

    /**
     * Get the bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Paginate results
     */
    public function paginate(int $perPage = 15, int $page = null): array
    {
        $page = $page ?? ($_GET['page'] ?? 1);
        $page = max(1, (int) $page);

        // Get total count
        $total = (clone $this)->count();

        // Get paginated results
        $this->limit($perPage)->offset(($page - 1) * $perPage);
        $items = $this->get();

        $lastPage = (int) ceil($total / $perPage);

        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => ($page - 1) * $perPage + 1,
            'to' => min($page * $perPage, $total),
        ];
    }

    /**
     * Process results in chunks
     */
    public function chunk(int $count, callable $callback): bool
    {
        $page = 1;

        do {
            $results = (clone $this)
                ->limit($count)
                ->offset(($page - 1) * $count)
                ->get();

            if (empty($results)) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            $page++;
        } while (count($results) === $count);

        return true;
    }
}
