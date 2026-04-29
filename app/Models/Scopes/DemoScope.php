<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filters records by the request-scoped "demo_mode" flag.
 *
 * The flag is bound by the BindDemoMode middleware:
 *   demo.peptidemap.com  → demo_mode = true   → only is_demo=true rows
 *   any other host       → demo_mode = false  → only is_demo=false rows
 *   admin routes         → flag not bound     → no filter (show everything)
 *
 * Use ->withoutGlobalScope(DemoScope::class) on any query that should
 * intentionally bypass the filter.
 */
class DemoScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!app()->bound('demo_mode')) {
            return;
        }

        $demoMode = (bool) app('demo_mode');
        $builder->where($model->getTable() . '.is_demo', $demoMode);
    }
}
