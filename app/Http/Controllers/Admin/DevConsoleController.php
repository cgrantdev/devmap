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

    public function run(Request $request): JsonResponse
    {
        $signature = $request->input('command');
        $meta = self::COMMANDS[$signature] ?? null;
        if (!$meta) {
            return response()->json([
                'ok' => false,
                'error' => "Command '{$signature}' is not on the whitelist.",
            ], 422);
        }

        $args = $this->sanitizeArgs($request->input('args', []), $meta['args']);

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
