<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    public function generateNumber(string $table, string $column, string $prefix, int $pad = 6): string
    {
        $last = DB::table($table)
            ->where($column, 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value($column);

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix.'-'.str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }
}
