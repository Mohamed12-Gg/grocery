<?php

namespace App\Filters;

class MealFilter extends QueryFilter
{
    public function category_id($value): void
    {
        $this->builder->where('category_id', $value);
    }

    public function is_available($value): void
    {
        $this->builder->where('is_available', $value);
    }

    public function subcategory_id($value): void
    {
        $this->builder->where('subcategory_id', $value);
    }

    public function title($value): void
    {
        $this->builder->where('title', 'like', '%'.$value.'%');
    }

    public function description($value): void
    {
        $this->builder->where('description', 'like', '%'.$value.'%');
    }
}
