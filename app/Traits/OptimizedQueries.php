<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait OptimizedQueries
{
    /**
     * Cache query results
     */
    public function scopeCached(Builder $query, string $key, int $minutes = 60)
    {
        return Cache::remember($key, $minutes * 60, function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Paginate with caching
     */
    public function scopeCachedPaginate(Builder $query, string $key, int $perPage = 15, int $minutes = 30)
    {
        $page = request('page', 1);
        $cacheKey = "{$key}_page_{$page}_per_{$perPage}";
        
        return Cache::remember($cacheKey, $minutes * 60, function () use ($query, $perPage) {
            return $query->paginate($perPage);
        });
    }

    /**
     * Optimized search with full-text index
     */
    public function scopeFullTextSearch(Builder $query, array $columns, string $term)
    {
        $columns = implode(',', $columns);
        
        return $query->whereRaw(
            "MATCH({$columns}) AGAINST(? IN BOOLEAN MODE)",
            [$term]
        );
    }

    /**
     * Batch insert with better performance
     */
    public static function batchInsert(array $data, int $chunkSize = 1000)
    {
        $chunks = array_chunk($data, $chunkSize);
        
        DB::transaction(function () use ($chunks) {
            foreach ($chunks as $chunk) {
                static::insert($chunk);
            }
        });
    }

    /**
     * Clear related cache
     */
    public function clearRelatedCache(array $keys = [])
    {
        foreach ($keys as $key) {
            Cache::forget($key);
            // Clear paginated cache
            for ($page = 1; $page <= 10; $page++) {
                Cache::forget("{$key}_page_{$page}_per_15");
                Cache::forget("{$key}_page_{$page}_per_25");
                Cache::forget("{$key}_page_{$page}_per_50");
            }
        }
    }
}