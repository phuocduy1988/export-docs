<?php

namespace Onetech\Pattern\Traits\Filter;

use Illuminate\Database\Eloquent\Builder;
use Onetech\Pattern\Services\Filters\AbstractQueryFilter;

trait HasFilter
{
    public function scopeFilter($query, AbstractQueryFilter $filter): Builder
    {
        return $filter->apply($query);
    }
}
