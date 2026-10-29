<?php

namespace App\Support;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Guarded write of one stored content column, with a log line every time.
 *
 * Several 2026_10_22 content migrations skipped columns without saying so
 * when their guard did not match production. This helper makes every
 * outcome visible. Each call prints exactly one line:
 *
 *   "<migration> <slug>.<field>: updated"
 *   "<migration> <slug>.<field>: already current"
 *   "<migration> <slug>.<field>: guard mismatch — nothing written"
 *
 * "updated" and "already current" go to Log::info, the console command
 * (info) and STDOUT. "guard mismatch" goes to Log::warning (with a
 * 200-character excerpt of the stored value in the context), the console
 * command (error) and STDERR. Stream writes are skipped when
 * STDOUT/STDERR are not defined (non-CLI, e.g. /admin dev console
 * migrate); Log:: lines are always written.
 *
 * Comparison uses comparable(): normalize() (CRLF/CR become LF, U+00A0
 * becomes a space, each line is right-trimmed and the whole string
 * trimmed, recursively through arrays), then object keys are sorted at
 * every level. List order still counts; object key order does not. This
 * matters because production's MySQL JSON columns hand keys back in their
 * own order (shorter keys first: links, title, authors, citation, ...),
 * not the payload's, so a strict key-ordered compare turns a matching
 * value into a false mismatch, and a re-run never sees "already current".
 * The value written is the target exactly as given, never the
 * normalized form.
 *
 * How a new content migration should use it:
 *
 *   1. Resolve the row with EncyclopediaPostMatch::find($slug, $command,
 *      $label) and stop (it already logged a WARNING) unless status is
 *      'ok'. Never fall back to a slug lookup; legacy unlinked rows must
 *      stay untouched.
 *   2. Inside one DB::transaction per slug, call
 *      $writer->apply($post, $slug, $field, $target, $oldValues) per
 *      column. $oldValues lists every value the column may legitimately
 *      hold: the live production value read from the page that day, plus
 *      the older and intermediate values from earlier payloads. Do not add
 *      a value you have not seen; an admin edit should end up as a
 *      mismatch.
 *   3. If any call returned UPDATED, saveQuietly() the model before the
 *      transaction closes.
 *   4. Keep the payload in a JSON file and pin its sha256 in the seeder.
 *
 * apply() only sets the attribute; it never saves. Pass $field for the
 * name used in log lines and $column when the stored column differs
 * (for example a category description fallback).
 */
final class GuardedFieldWriter
{
    public const UPDATED = 'updated';

    public const ALREADY = 'already current';

    public const MISMATCH = 'guard mismatch — nothing written';

    /** @var list<string> */
    public array $lines = [];

    /** @var array<string, string> "<slug>.<field>" => status */
    public array $results = [];

    public function __construct(
        private readonly string $migration,
        private readonly ?Command $command = null,
    ) {}

    /**
     * @param  list<mixed>  $oldValues
     */
    public function apply(
        Model $model,
        string $slug,
        string $field,
        mixed $target,
        array $oldValues,
        ?string $column = null,
    ): string {
        $column ??= $field;
        $current = $model->getAttribute($column);
        $normalizedCurrent = self::comparable($current);

        if ($normalizedCurrent === self::comparable($target)) {
            return $this->record($slug, $field, self::ALREADY);
        }

        foreach ($oldValues as $old) {
            if ($normalizedCurrent === self::comparable($old)) {
                $model->setAttribute($column, $target);

                return $this->record($slug, $field, self::UPDATED);
            }
        }

        return $this->record($slug, $field, self::MISMATCH, $current);
    }

    /**
     * Log a skip that happened before any column could be compared, such as
     * a failed post lookup. Always a WARNING.
     */
    public function skip(string $slug, string $field, string $reason): string
    {
        $line = $this->migration.' '.$slug.'.'.$field.': '.$reason.' — nothing written';
        $this->results[$slug.'.'.$field] = $reason;
        $this->lines[] = $line;
        Log::warning($line);
        $this->command?->error($line);
        self::emit('stderr', $line);

        return $reason;
    }

    /**
     * normalize(), plus object (string-keyed) arrays sorted by key at every
     * level, so key order never decides a guard. Lists keep their order.
     */
    public static function comparable(mixed $value): mixed
    {
        $value = self::normalize($value);
        if (! is_array($value)) {
            return $value;
        }
        foreach ($value as $key => $item) {
            $value[$key] = self::comparable($item);
        }
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }

    public static function normalize(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = str_replace(["\r\n", "\r"], "\n", $value);
            $value = str_replace("\u{00A0}", ' ', $value);
            $lines = array_map(rtrim(...), explode("\n", $value));

            return trim(implode("\n", $lines));
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = self::normalize($item);
            }

            return $normalized;
        }

        return $value;
    }

    private function record(string $slug, string $field, string $status, mixed $current = null): string
    {
        $line = $this->migration.' '.$slug.'.'.$field.': '.$status;
        $this->results[$slug.'.'.$field] = $status;
        $this->lines[] = $line;

        if ($status === self::MISMATCH) {
            Log::warning($line, ['excerpt' => self::excerpt($current)]);
            $this->command?->error($line);
            self::emit('stderr', $line);

            return $status;
        }

        Log::info($line);
        $this->command?->info($line);
        self::emit('stdout', $line);

        return $status;
    }

    private static function excerpt(mixed $current): string
    {
        $normalized = self::normalize($current);
        if (is_string($normalized)) {
            $text = $normalized;
        } else {
            $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $text = is_string($encoded) ? $encoded : '';
        }

        return mb_substr(str_replace("\n", ' ', $text), 0, 200);
    }

    private static function emit(string $stream, string $line): void
    {
        $handle = match ($stream) {
            'stderr' => defined('STDERR') ? STDERR : null,
            default => defined('STDOUT') ? STDOUT : null,
        };
        if (is_resource($handle)) {
            @fwrite($handle, $line.PHP_EOL);
        }
    }
}
