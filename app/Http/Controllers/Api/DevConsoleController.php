<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevConsoleController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/dev/info — System & environment information.
     */
    public function info(): JsonResponse
    {
        $driver = config('database.default');
        $conn   = config("database.connections.{$driver}");

        $dbInfo = match ($driver) {
            'sqlite' => [
                'driver' => 'sqlite',
                'path'   => $conn['database'],
            ],
            'pgsql' => [
                'driver'   => 'pgsql',
                'host'     => $conn['host'],
                'port'     => $conn['port'],
                'database' => $conn['database'],
                'username' => $conn['username'],
            ],
            'mysql' => [
                'driver'   => 'mysql',
                'host'     => $conn['host'],
                'port'     => $conn['port'],
                'database' => $conn['database'],
                'username' => $conn['username'],
            ],
            default => ['driver' => $driver],
        };

        // Attempt to verify live DB connectivity
        try {
            DB::connection()->getPdo();
            $dbInfo['connected'] = true;
        } catch (\Exception $e) {
            $dbInfo['connected'] = false;
            $dbInfo['error']     = $e->getMessage();
        }

        return $this->sendOk([
            'app_env'          => app()->environment(),
            'app_debug'        => config('app.debug'),
            'app_url'          => config('app.url'),
            'php_version'      => PHP_VERSION,
            'laravel_version'  => app()->version(),
            'database'         => $dbInfo,
            'cache_driver'     => config('cache.default'),
            'queue_driver'     => config('queue.default'),
            'session_driver'   => config('session.driver'),
            'filesystem_disk'  => config('filesystems.default'),
        ]);
    }

    /**
     * GET /api/dev/tables — List all database tables with row counts.
     */
    public function tables(): JsonResponse
    {
        $driver = DB::getDriverName();

        $tableNames = match ($driver) {
            'sqlite' => collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
                ->map(fn ($t) => $t->name)
                ->values()
                ->all(),
            'pgsql' => collect(DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name"))
                ->map(fn ($t) => $t->table_name)
                ->values()
                ->all(),
            'mysql' => collect(DB::select('SHOW TABLES'))
                ->map(fn ($t) => array_values((array) $t)[0])
                ->values()
                ->all(),
            default => [],
        };

        $result = [];
        foreach ($tableNames as $name) {
            try {
                $count = DB::table($name)->count();
            } catch (\Exception) {
                $count = -1;
            }
            $result[] = ['table' => $name, 'count' => $count];
        }

        return $this->sendOk($result);
    }

    /**
     * GET /api/dev/tables/{table} — Paginated rows from a table.
     */
    public function tableRows(string $table): JsonResponse
    {
        if (! $this->isValidTableName($table)) {
            return $this->sendError(400, 'BAD_REQUEST', 'Invalid table name');
        }

        if (! $this->tableExists($table)) {
            return $this->sendError(404, 'NOT_FOUND', "Table '{$table}' not found");
        }

        $page  = max(1, (int) request('page', 1));
        $limit = min(100, max(1, (int) request('limit', 20)));
        $q     = request('q');

        $query = DB::table($table);

        // Search across string-like columns when q is provided
        if ($q) {
            $driver  = DB::getDriverName();
            $columns = $this->getTableColumns($table);

            $query->where(function ($builder) use ($q, $columns, $driver) {
                $first = true;
                foreach ($columns as $column) {
                    $type = strtolower($column->type ?? $column->data_type ?? '');
                    $isText = match ($driver) {
                        'sqlite' => in_array($type, ['text', 'varchar', 'char', '']) || str_contains($type, 'char') || str_contains($type, 'text'),
                        'pgsql'  => in_array($type, ['text', 'character varying', 'varchar', 'char', 'character', 'name']),
                        'mysql'  => in_array($type, ['varchar', 'text', 'char', 'tinytext', 'mediumtext', 'longtext']),
                        default  => false,
                    };
                    if ($isText) {
                        $colName = $column->name ?? $column->column_name;
                        if ($first) {
                            $builder->where($colName, 'like', "%{$q}%");
                            $first = false;
                        } else {
                            $builder->orWhere($colName, 'like', "%{$q}%");
                        }
                    }
                }
            });
        }

        $total = $query->count();
        $rows  = (clone $query)
            ->orderByDesc('id')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return $this->sendOk($rows, [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => (int) ceil($total / max($limit, 1)),
        ]);
    }

    /**
     * POST /api/dev/tables/{table} — Insert a new row.
     */
    public function createRow(string $table, Request $request): JsonResponse
    {
        if (! $this->isValidTableName($table)) {
            return $this->sendError(400, 'BAD_REQUEST', 'Invalid table name');
        }

        if (! $this->tableExists($table)) {
            return $this->sendError(404, 'NOT_FOUND', "Table '{$table}' not found");
        }

        $data = $request->except(['id', 'created_at', 'updated_at']);

        try {
            $id = DB::table($table)->insertGetId($data);

            return $this->sendCreated(['id' => $id]);
        } catch (\Exception $e) {
            return $this->sendError(400, 'BAD_REQUEST', $e->getMessage());
        }
    }

    /**
     * PUT /api/dev/tables/{table}/{id} — Update a row by id.
     */
    public function updateRow(string $table, int $id, Request $request): JsonResponse
    {
        if (! $this->isValidTableName($table)) {
            return $this->sendError(400, 'BAD_REQUEST', 'Invalid table name');
        }

        if (! $this->tableExists($table)) {
            return $this->sendError(404, 'NOT_FOUND', "Table '{$table}' not found");
        }

        $data = $request->except(['id', 'created_at', 'updated_at']);

        // Attach updated_at if table has that column
        $hasTimestamps = collect($this->getTableColumns($table))
            ->pluck($this->columnNameKey())
            ->contains('updated_at');
        if ($hasTimestamps) {
            $data['updated_at'] = now();
        }

        try {
            DB::table($table)->where('id', $id)->update($data);
            $row = DB::table($table)->where('id', $id)->first();

            return $this->sendOk($row);
        } catch (\Exception $e) {
            return $this->sendError(400, 'BAD_REQUEST', $e->getMessage());
        }
    }

    /**
     * DELETE /api/dev/tables/{table}/{id} — Delete a row by id.
     */
    public function deleteRow(string $table, int $id): JsonResponse
    {
        if (! $this->isValidTableName($table)) {
            return $this->sendError(400, 'BAD_REQUEST', 'Invalid table name');
        }

        if (! $this->tableExists($table)) {
            return $this->sendError(404, 'NOT_FOUND', "Table '{$table}' not found");
        }

        try {
            DB::table($table)->where('id', $id)->delete();

            return $this->sendOk(['success' => true]);
        } catch (\Exception $e) {
            return $this->sendError(400, 'BAD_REQUEST', $e->getMessage());
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isValidTableName(string $table): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/', $table);
    }

    private function tableExists(string $table): bool
    {
        try {
            DB::table($table)->limit(0)->get();

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function getTableColumns(string $table): array
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'sqlite' => DB::select("PRAGMA table_info({$table})"),
            'pgsql'  => DB::select("SELECT column_name AS name, data_type AS type FROM information_schema.columns WHERE table_name = ? AND table_schema = 'public' ORDER BY ordinal_position", [$table]),
            'mysql'  => DB::select("SHOW COLUMNS FROM `{$table}`"),
            default  => [],
        };
    }

    private function columnNameKey(): string
    {
        return match (DB::getDriverName()) {
            'pgsql'  => 'name',
            'mysql'  => 'Field',
            default  => 'name',
        };
    }
}
