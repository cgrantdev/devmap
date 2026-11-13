<?php

namespace App\Console\Commands;

use App\Helpers\ImageHelper;
use App\Models\VendorSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Runs the upload-time logo processing over logos stored before it
 * existed: trims baked-in margins, caps the longest side at 600px and
 * encodes the backdrop colour in the filename (ImageHelper::processedLogoName).
 *
 * The original file is never deleted. A processed copy is written next to
 * it as {base}-lp[-bgRRGGBB].webp and vendor_settings.logo is pointed at
 * the copy, so --revert can point every vendor back at the original.
 */
class ReprocessVendorLogos extends Command
{
    protected $signature = 'vendors:reprocess-logos {--dry-run} {--brand=} {--force} {--revert}';

    protected $description = 'Trim, resize and re-encode stored vendor logos (originals kept; --revert restores them).';

    /** Decoded-image budget; larger logos are reported for re-upload. */
    private const MAX_DECODE_BYTES = 700 * 1024 * 1024;

    private const ORIGINAL_EXTENSIONS = ['webp', 'png', 'jpg', 'jpeg', 'gif'];

    public function handle(): int
    {
        if (!function_exists('imagewebp')) {
            $this->error('GD with WebP support is required.');
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $revert = (bool) $this->option('revert');

        $query = VendorSetting::query()
            ->whereNotNull('logo')
            ->where('logo', '!=', '')
            ->with('brand:id,name,slug');
        if ($slug = $this->option('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $slug));
        }
        $settings = $query->orderBy('id')->get();

        $this->info('Vendor logos: ' . $settings->count() . ($dry ? ' (dry run, nothing written)' : ''));

        $counts = ['processed' => 0, 'reverted' => 0, 'skipped' => 0, 'missing' => 0, 'failed' => 0];
        foreach ($settings as $vs) {
            $label = $vs->brand->name ?? ('vendor_setting #' . $vs->id);
            try {
                [$status, $detail] = $revert ? $this->revert($vs, $dry) : $this->process($vs, $dry);
            } catch (\Throwable $e) {
                [$status, $detail] = ['failed', $e->getMessage()];
            }
            $counts[$status]++;
            $this->line(str_pad(strtoupper($status), 10) . $label . ' - ' . $detail);
        }

        $this->newLine();
        $summary = [];
        foreach ($counts as $status => $n) {
            $summary[] = "{$status}: {$n}";
        }
        $this->info(implode('  ', $summary));
        if (!$revert && !$dry && $counts['processed'] > 0) {
            $this->line('Undo with: php artisan vendors:reprocess-logos --revert');
        }

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: string} */
    private function process(VendorSetting $vs, bool $dry): array
    {
        $disk = Storage::disk('public');
        $path = (string) $vs->logo;

        if (preg_match('#^https?://#i', $path) === 1) {
            return ['skipped', 'external URL, not stored on our disk'];
        }
        if (!$disk->exists($path)) {
            return ['missing', "file not found ({$path}); vendor needs a new logo"];
        }
        if (str_ends_with(strtolower($path), '.svg')) {
            return ['skipped', 'SVG (vector), left as is'];
        }

        $source = $path;
        if (ImageHelper::isProcessedLogo($path)) {
            if (!$this->option('force')) {
                return ['skipped', 'already processed'];
            }
            $original = $this->findOriginal($path);
            if ($original === null) {
                return ['skipped', 'already processed and no original kept'];
            }
            $source = $original;
        }

        $abs = $disk->path($source);
        $info = @getimagesize($abs);
        if ($info === false) {
            return ['failed', "not a readable image ({$source})"];
        }
        [$w, $h] = $info;
        $need = $w * $h * 5;
        if ($need > self::MAX_DECODE_BYTES) {
            return ['skipped', "{$w}x{$h} is too large to decode on the server; re-upload it (uploads are now shrunk automatically)"];
        }
        if (!$this->ensureMemory($need)) {
            return ['skipped', "{$w}x{$h} needs more memory than PHP allows here"];
        }

        $image = @imagecreatefromstring((string) $disk->get($source));
        if (!$image) {
            return ['failed', "decode failed ({$source})"];
        }

        $image = ImageHelper::trimAndFitLogo($image);
        $backdrop = ImageHelper::logoBackdropHex($image);
        $nw = imagesx($image);
        $nh = imagesy($image);

        $dir = trim(str_replace('\\', '/', pathinfo($source, PATHINFO_DIRNAME)), './');
        $newPath = ($dir !== '' ? $dir . '/' : '') . ImageHelper::processedLogoName(pathinfo($source, PATHINFO_FILENAME), $backdrop);

        $summary = "{$w}x{$h} -> {$nw}x{$nh}" . ($backdrop ? " (backdrop #{$backdrop})" : '');
        if ($dry) {
            return ['processed', $summary];
        }

        if (!imagewebp($image, $disk->path($newPath), 85)) {
            return ['failed', 'WebP encode failed'];
        }
        $vs->forceFill(['logo' => $newPath])->save();

        return ['processed', "{$summary}; now {$newPath} (original kept: {$source})"];
    }

    /** @return array{0: string, 1: string} */
    private function revert(VendorSetting $vs, bool $dry): array
    {
        $path = (string) $vs->logo;
        if (!ImageHelper::isProcessedLogo($path)) {
            return ['skipped', 'not a processed logo'];
        }
        $original = $this->findOriginal($path);
        if ($original === null) {
            return ['skipped', 'no original kept for this logo'];
        }
        if (!$dry) {
            $vs->forceFill(['logo' => $original])->save();
        }
        return ['reverted', "{$path} -> {$original}"];
    }

    /** The pre-processing file a processed logo was derived from, if kept. */
    private function findOriginal(string $processedPath): ?string
    {
        $disk = Storage::disk('public');
        $dir = trim(str_replace('\\', '/', pathinfo($processedPath, PATHINFO_DIRNAME)), './');
        $base = preg_replace('/-lp(?:-bg[0-9a-f]{6})?$/i', '', pathinfo($processedPath, PATHINFO_FILENAME));
        foreach (self::ORIGINAL_EXTENSIONS as $ext) {
            $candidate = ($dir !== '' ? $dir . '/' : '') . $base . '.' . $ext;
            if ($candidate !== $processedPath && $disk->exists($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    /** Raise memory_limit for one large decode when PHP allows it. */
    private function ensureMemory(int $need): bool
    {
        $limit = $this->memoryLimitBytes();
        if ($limit < 0) {
            return true;
        }
        $required = memory_get_usage(true) + $need + (64 * 1024 * 1024);
        if ($required <= $limit) {
            return true;
        }
        $target = (int) ceil($required / (1024 * 1024)) . 'M';
        if (@ini_set('memory_limit', $target) === false) {
            return false;
        }
        $after = $this->memoryLimitBytes();
        return $after < 0 || $after >= $required;
    }

    private function memoryLimitBytes(): int
    {
        $raw = trim((string) ini_get('memory_limit'));
        if ($raw === '' || $raw === '-1') {
            return -1;
        }
        $unit = strtolower(substr($raw, -1));
        $value = (int) $raw;
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
