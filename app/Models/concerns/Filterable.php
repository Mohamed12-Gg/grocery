<?php 

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    //new 
    
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        $filterClass = self::resolveFilterClass();

        // If the specific filter class does not exist, return the original query.
        if (!class_exists($filterClass)) {
            return $query;
        }

        $filter = new $filterClass($request);
        if (method_exists($filter, 'apply')) {
            return $filter->apply($query);
        }

        return $query;
    }

    public static function resolveFilterClass(): string
    {
        return 'App\\Filters\\'.class_basename(self::class).'Filter';
    }
}