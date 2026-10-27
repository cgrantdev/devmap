<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Look up the current request path in the redirects table before falling
 * through to the normal router. 301/302 is honored per row. Table is
 * cached (120s) so hot paths never hit the DB; cache busts whenever a
 * row changes via the admin UI (see RedirectsController).
 */
class ApplyRedirects
{
    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = '/' . ltrim($request->path(), '/');
        $map = $this->map();
        if (!isset($map[$path])) {
            return $next($request);
        }
        $hit = $map[$path];

        // Fire-and-forget bookkeeping. Never block the redirect.
        try {
            DB::table('redirects')
                ->where('id', $hit['id'])
                ->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]);
        } catch (\Throwable $e) { /* ignore */ }

        return redirect($hit['to'], $hit['status']);
    }

    private function map(): array
    {
        return Cache::remember('redirects:map', 120, function () {
            if (!\Schema::hasTable('redirects')) return [];
            return Redirect::all()->mapWithKeys(fn ($r) => [
                $r->from_path => [
                    'id' => $r->id,
                    'to' => $r->to_path,
                    'status' => $r->status_code ?: 301,
                ],
            ])->toArray();
        });
    }
}
