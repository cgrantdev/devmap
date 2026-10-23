<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Admin-side command runner so Colin / Julia (and Claude via the
 * logged-in browser session) can trigger a whitelisted set of
 * artisan commands from the browser instead of needing Forge SSH.
 *
 * Hard whitelist: anything NOT in COMMANDS is refused. No arbitrary
 * command execution — the whitelist is the entire interface. Each
 * entry declares a human-readable label + an optional allowed-args
 * set so --limit / --brand / --dry-run come through safely.
 *
 * Admin-gated by the same middleware as every other /admin/*
 * route.
 */
class DevConsoleController extends Controller
{
    /**
     * Safe-to-run commands. Keys are the artisan signature (no args),
     * values describe what each one does + which args are allowed.
     * Add new entries as new ops come up.
     */
    private const COMMANDS = [
        'migrate' => [
            'label' => 'Run pending migrations',
            'args' => ['--force' => true],
        ],
        'migrate:status' => [
            'label' => 'Show migration status (read-only)',
            'args' => [],
        ],
        'products:ensure-vendor-desc-column' => [
            'label' => 'Add vendor_description column directly (bypasses migrate chain)',
            'args' => [],
        ],
        'products:ensure-generated-desc-column' => [
            'label' => 'Add generated_description column (AI gap-filler schema)',
            'args' => [],
        ],
        'products:generate-descriptions' => [
            'label' => 'AI-generate unique descriptions (queue-dispatched by default)',
            'args' => [
                '--dry-run' => true,
                '--limit' => 'int',
                '--brand' => 'string',
                '--force' => true,
                '--inline' => true,
            ],
        ],
        'descriptions:progress' => [
            'label' => 'Show how many products have unique descriptions + queue depth',
            'args' => [],
        ],
        'blogs:image-audit' => [
            'label' => 'Report blog + encyclopedia article image coverage',
            'args' => [],
        ],
        'blogs:recent' => [
            'label' => 'List recent blog posts with authorship signals',
            'args' => [],
        ],
        'blogs:backfill-images' => [
            'label' => 'Set seo_og_image + hero image on blogs/encyclopedia missing one',
            'args' => ['--dry-run' => true],
        ],
        'brands:slug-lookup' => [
            'label' => 'Show brand slugs (optional substring filter)',
            'args' => [],
        ],
        'users:create-seo-agency' => [
            'label' => 'Create an SEO agency user (positional: email, optional name)',
            'args' => [],
            'positional' => ['email' => 'string', 'name' => 'string'],
        ],
        'vendor:inspect' => [
            'label' => 'Dump integration fields for one brand',
            'args' => [],
            'positional' => ['brand' => 'string'],
        ],
        'vendor:discover' => [
            'label' => 'Dispatch page-scraper discovery for one brand',
            'args' => [],
            'positional' => ['brand' => 'string'],
        ],
        'vendor:products-export' => [
            'label' => 'CSV dump of a brand\'s products + duplicate flagging',
            'args' => [],
            'positional' => ['brand' => 'string'],
        ],
        'vendor:category-audit' => [
            'label' => 'Flag miscategorized products for one brand',
            'args' => [],
            'positional' => ['brand' => 'string'],
        ],
        'product:recategorize' => [
            'label' => 'Fix a product\'s category (positional: product_id, category slug/name)',
            'args' => [],
            'positional' => ['product_id' => 'int', 'category' => 'string'],
        ],
        'brands:ensure-generated-desc-column' => [
            'label' => 'Add generated_description to vendor_settings (schema)',
            'args' => [],
        ],
        'brands:generate-descriptions' => [
            'label' => 'AI-generate unique "about" copy for vendors',
            'args' => [
                '--dry-run' => true,
                '--limit' => 'int',
                '--brand' => 'string',
                '--force' => true,
            ],
        ],
        'products:fetch-vendor-descriptions' => [
            'label' => 'Backfill vendor product descriptions',
            'args' => [
                '--dry-run' => true,
                '--limit' => 'int',
                '--brand' => 'string',
                '--force' => true,
            ],
        ],
        'purgo:sync' => [
            'label' => 'Scrape PurgoLabs catalog',
            'args' => ['--dry-run' => true, '--limit' => 'int'],
        ],
        'arp:sync' => [
            'label' => 'Scrape Australian Research Peptides via /llms.txt',
            'args' => ['--dry-run' => true, '--limit' => 'int'],
        ],
        'zenith:sync' => [
            'label' => 'Pull Zenith Bioscience catalog (needs API key on vendor row)',
            'args' => ['--dry-run' => true, '--limit' => 'int'],
        ],
        'zenith:set-token' => [
            'label' => 'Set Zenith bearer token (positional arg: token)',
            'args' => [],
            'positional' => ['token' => 'string'],
        ],
        'mypeptides:sync' => [
            'label' => 'Pull My Peptides UK catalog (public API, no key)',
            'args' => ['--dry-run' => true, '--limit' => 'int'],
        ],
        'feeds:sync-vendors' => [
            'label' => 'Re-pull every vendor XML feed',
            'args' => ['--dry-run' => true, '--brand' => 'string'],
        ],
        'coupons:activate-scheduled-boosts' => [
            'label' => 'Promote scheduled coupon boosts',
            'args' => ['--dry-run' => true],
        ],
        'promotions:revert-expired' => [
            'label' => 'Deactivate expired stackable promos',
            'args' => ['--dry-run' => true],
        ],
        'cache:clear' => [
            'label' => 'Clear application cache',
            'args' => [],
        ],
        'view:clear' => [
            'label' => 'Clear compiled views',
            'args' => [],
        ],
        'route:clear' => [
            'label' => 'Clear route cache (useful after new route deploy)',
            'args' => [],
        ],
        'optimize:clear' => [
            'label' => 'Clear all compiled caches (config/route/view/events)',
            'args' => [],
        ],
    ];

    public function index()
    {
        $commands = collect(self::COMMANDS)->map(fn ($meta, $sig) => [
            'signature' => $sig,
            'label' => $meta['label'],
            'args' => array_keys($meta['args']),
        ])->values();

        return Inertia::render('Admin/DevConsole', [
            'commands' => $commands,
        ]);
    }

    /**
     * Optional bearer-token path so Claude can trigger commands without
     * a live admin session cookie. Set DEV_CONSOLE_TOKEN in Forge env
     * to a long random string; then POST with
     *   Authorization: Bearer <token>
     * will be accepted even when the request has no admin session.
     *
     * Session-authed admin requests still work exactly as before.
     */
    private function tokenAccepted(Request $request): bool
    {
        $expected = config('services.dev_console.token');
        if (!$expected) return false;
        $sent = $request->bearerToken() ?: $request->header('X-Dev-Console-Token');
        return $sent && hash_equals($expected, (string) $sent);
    }

    public function run(Request $request): JsonResponse
    {
        // Admin session OR bearer token. The route itself is still
        // behind auth+role+ceo middleware, so the token path has to
        // ride a parallel route — see runWithToken() for that.
        $signature = $request->input('command');
        $meta = self::COMMANDS[$signature] ?? null;
        if (!$meta) {
            return response()->json([
                'ok' => false,
                'error' => "Command '{$signature}' is not on the whitelist.",
            ], 422);
        }

        $args = $this->sanitizeArgs($request->input('args', []), $meta['args']);

        // Positional args — artisan accepts them as unnamed array
        // entries at the start. Whitelist-defined, same sanitization
        // rules as flag args.
        if (!empty($meta['positional'])) {
            foreach ($meta['positional'] as $name => $type) {
                $v = $request->input('positional.' . $name) ?? $request->input('args.' . $name);
                if ($v === null || $v === '') continue;
                if ($type === 'int') $v = (int) $v;
                else $v = (string) $v;
                $args[$name] = $v;
            }
        }

        $buffer = new BufferedOutput();
        $started = microtime(true);
        try {
            $exitCode = Artisan::call($signature, $args, $buffer);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'signature' => $signature,
                'args' => $args,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
        $durationMs = (int) round((microtime(true) - $started) * 1000);

        return response()->json([
            'ok' => $exitCode === 0,
            'signature' => $signature,
            'args' => $args,
            'exit_code' => $exitCode,
            'duration_ms' => $durationMs,
            'output' => $buffer->fetch(),
        ]);
    }

    /**
     * Public bearer-token entry point for Claude. Routed OUTSIDE the
     * admin middleware group (see routes/web.php). Validates the
     * shared secret in Authorization: Bearer <token>, then falls
     * through to the same run() handler.
     */
    public function runWithToken(Request $request): JsonResponse
    {
        if (!$this->tokenAccepted($request)) {
            return response()->json(['ok' => false, 'error' => 'invalid or missing token'], 401);
        }
        return $this->run($request);
    }

    /**
     * Keep only args the whitelist permits for this command, and
     * coerce int values. Anything the user tries to sneak in is
     * silently dropped — no partial exec on bad input.
     */
    private function sanitizeArgs(array $input, array $allowed): array
    {
        $out = [];
        foreach ($input as $key => $value) {
            if (!isset($allowed[$key])) continue;
            $spec = $allowed[$key];
            if ($spec === true) {
                // Boolean flag: include only when truthy.
                if ($value) $out[$key] = true;
            } elseif ($spec === 'int') {
                $v = (int) $value;
                if ($v > 0) $out[$key] = $v;
            } elseif ($spec === 'string') {
                $v = trim((string) $value);
                if ($v !== '') $out[$key] = $v;
            }
        }
        return $out;
    }
}
