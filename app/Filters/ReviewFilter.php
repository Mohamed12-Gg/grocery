<?php

namespace App\Filters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReviewFilter {
     public function apply(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('meal_id'), fn ($query) =>
                $query->where('meal_id', $request->meal_id)
            )
            ->when($request->filled('user_id'), fn ($query) =>
                $query->where('user_id', $request->user_id)
            )
            ->when($request->filled('rating'), fn ($query) =>
                $query->where('rating', $request->rating)
            )
            ->when($request->boolean('approved_only', true), fn ($query) =>
                $query->approved()
            )
            ->when($request->filled('min_rating'), fn ($query) =>
                $query->where('rating', '>=', $request->min_rating)
            );
    }
}