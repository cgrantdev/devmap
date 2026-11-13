<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Point homepage hero slides at .webp copies where one sits next to the
 * .png on disk. s1-desktop.png is 612 KB against a 50 KB .webp sibling.
 * Slides with no .webp on disk keep their PNG. The original JSON is kept
 * under hero_slides_pre_webp so down() restores it exactly.
 */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'hero_slides')->first();
        if (!$row || !$row->value) {
            return;
        }

        $slides = json_decode($row->value, true);
        if (!is_array($slides)) {
            return;
        }

        $changed = false;
        foreach ($slides as $i => $slide) {
            if (!is_array($slide)) {
                continue;
            }
            foreach (['image', 'image_mobile'] as $field) {
                $webp = $this->webpSibling($slide[$field] ?? null);
                if ($webp !== null) {
                    $slides[$i][$field] = $webp;
                    $changed = true;
                }
            }
        }

        if (!$changed) {
            return;
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'hero_slides_pre_webp'],
            ['value' => $row->value, 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('settings')->where('key', 'hero_slides')->update([
            'value' => json_encode($slides, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $backup = DB::table('settings')->where('key', 'hero_slides_pre_webp')->value('value');
        if ($backup === null) {
            return;
        }

        DB::table('settings')->where('key', 'hero_slides')->update([
            'value' => $backup,
            'updated_at' => now(),
        ]);
        DB::table('settings')->where('key', 'hero_slides_pre_webp')->delete();
    }

    /**
     * The .webp path for a .png hero image when that file exists on disk.
     * Handles /absolute public paths and bare filenames stored by
     * /admin/banners (storage/app/public/hero_slides). Remote URLs are left
     * alone because there is no way to check them here.
     */
    private function webpSibling($path): ?string
    {
        if (!is_string($path) || !preg_match('/\.png$/i', $path)) {
            return null;
        }
        if (preg_match('#^(https?:)?//#i', $path)) {
            return null;
        }

        $webp = preg_replace('/\.png$/i', '.webp', $path);

        $disk = str_starts_with($webp, '/')
            ? public_path(ltrim($webp, '/'))
            : storage_path('app/public/hero_slides/' . $webp);

        return is_file($disk) ? $webp : null;
    }
};
