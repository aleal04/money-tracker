<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

abstract class BaseQueryRepository
{
    protected string $table;

    protected ?Model $model = null;

    /**
     * Create a query builder instance for the repository's table with optional conditions and ordering.
     *
     * @param  array  $attributes  The columns to select.
     * @param  array  $where  The where conditions.
     * @param  array  $orWhere  The orWhere conditions.
     * @param  string  $orderBy  The order by clause.
     * @param  Model|null  $model  The model to use instead of the repository's default model.
     */
    public function query(
        array $attributes = ['*'],
        array $where = [],
        array $orWhere = [],
        string $orderBy = '1',
        ?Model $model = null,
    ): EloquentBuilder|QueryBuilder {
        $query = $this->buildBaseQuery($model);

        return $query
            ->select($attributes)
            ->where($where)
            ->when(! empty($orWhere), fn ($query) => $query->orWhere($orWhere))
            ->orderByRaw($orderBy);
    }

    /**
     * Build the base query using Eloquent when a model is provided.
     */
    protected function buildBaseQuery(?Model $model = null): EloquentBuilder|QueryBuilder
    {
        $queryModel = $model ?? $this->model;

        if ($queryModel !== null) {
            $table = $model === null ? $this->table : $queryModel->getTable();

            return (clone $queryModel)->newQuery()->from($table);
        }

        return DB::table($this->table);
    }

    /**
     * Create a query builder instance for a specific table with optional conditions and ordering.
     *
     * @param  string  $from  The table name.
     * @param  array  $attributes  The columns to select.
     * @param  array  $where  The where conditions.
     * @param  array  $orWhere  The orWhere conditions.
     * @param  string|null  $orderBy  The order by clause.
     */
    protected function queryFrom(
        string $from,
        array $attributes = ['*'],
        array $where = [],
        array $orWhere = [],
        ?string $orderBy = null
    ): QueryBuilder {
        $query = DB::table($from)
            ->select($attributes)
            ->where($where);

        if (! empty($orWhere)) {
            $query->orWhere($orWhere);
        }

        if ($orderBy) {
            $query->orderByRaw($orderBy);
        }

        return $query;
    }
}
