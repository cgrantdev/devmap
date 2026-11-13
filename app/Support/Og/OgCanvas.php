<?php

namespace App\Support\Og;

use GdImage;
use RuntimeException;

/**
 * 1200x630 social card drawn with GD. Everything is drawn at 2x on an
 * internal canvas and resampled down on save, which gives anti-aliased
 * edges and text without a browser. Public coordinates and sizes are in
 * 1x card pixels.
 */
final class OgCanvas
{
    public const W = 1200;

    public const H = 630;

    private const S = 2;

    /**
     * Sizes are given in pixels; GD takes points at 96 DPI (libgd 2.1+),
     * so pixels x 0.75 = points.
     */
    private const PT = 0.75;

    private GdImage $im;

    public function __construct()
    {
        if (!function_exists('imagettftext')) {
            throw new RuntimeException('GD FreeType support is required for OG cards.');
        }
        $im = imagecreatetruecolor(self::W * self::S, self::H * self::S);
        if (!$im) {
            throw new RuntimeException('Could not allocate the OG canvas.');
        }
        $this->im = $im;
        imagealphablending($this->im, true);
        imagesavealpha($this->im, false);
    }

    public static function font(string $name): string
    {
        return resource_path('fonts/og/'.$name.'.ttf');
    }

    /** '#RRGGBB' plus opacity 0..1 to a GD colour (GD alpha is 0 opaque .. 127 clear). */
    public function color(string $hex, float $opacity = 1.0): int
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $alpha = (int) round(127 - max(0.0, min(1.0, $opacity)) * 127);

        return imagecolorallocatealpha($this->im, $r, $g, $b, $alpha);
    }

    public function fill(string $hex): void
    {
        imagefilledrectangle($this->im, 0, 0, self::W * self::S, self::H * self::S, $this->color($hex));
    }

    /**
     * Soft radial glow: stacked translucent ellipses, drawn at a quarter
     * scale and stretched up, since the result is blurry by design.
     */
    public function glow(float $cx, float $cy, float $radius, string $hex, float $strength = 0.5, int $steps = 36): void
    {
        $q = 4;
        $w = (int) ceil(self::W * self::S / $q);
        $h = (int) ceil(self::H * self::S / $q);
        $layer = imagecreatetruecolor($w, $h);
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        imagefill($layer, 0, 0, imagecolorallocatealpha($layer, 0, 0, 0, 127));
        imagealphablending($layer, true);

        $hexs = ltrim($hex, '#');
        $r = hexdec(substr($hexs, 0, 2));
        $g = hexdec(substr($hexs, 2, 2));
        $b = hexdec(substr($hexs, 4, 2));
        $per = max(1, (int) round($strength * 127 / $steps * 2.2));
        for ($i = 0; $i < $steps; $i++) {
            $rad = $radius * (1 - $i / $steps) * self::S / $q;
            $col = imagecolorallocatealpha($layer, $r, $g, $b, max(0, 127 - $per));
            imagefilledellipse($layer, (int) ($cx * self::S / $q), (int) ($cy * self::S / $q), (int) ($rad * 2), (int) ($rad * 2), $col);
        }
        imagecopyresampled($this->im, $layer, 0, 0, 0, 0, self::W * self::S, self::H * self::S, $w, $h);
        imagedestroy($layer);
    }

    /** Faint dot grid over the background. */
    public function dotGrid(int $gap, string $hex, float $opacity): void
    {
        $col = $this->color($hex, $opacity);
        $d = 2 * self::S;
        for ($y = $gap / 2; $y < self::H; $y += $gap) {
            for ($x = $gap / 2; $x < self::W; $x += $gap) {
                imagefilledellipse($this->im, (int) ($x * self::S), (int) ($y * self::S), $d, $d, $col);
            }
        }
    }

    public function roundedRect(float $x, float $y, float $w, float $h, float $r, string $hex, float $opacity = 1.0): void
    {
        $col = $this->color($hex, $opacity);
        $x1 = (int) round($x * self::S);
        $y1 = (int) round($y * self::S);
        $x2 = (int) round(($x + $w) * self::S) - 1;
        $y2 = (int) round(($y + $h) * self::S) - 1;
        $rr = (int) round(min($r, $w / 2, $h / 2) * self::S);

        if ($opacity < 1.0) {
            // Overlapping pieces would double the alpha at the seams; draw
            // the shape opaque on a mask layer and blend it in once.
            $this->blendShape(fn (GdImage $layer, int $c) => $this->rrect($layer, $x1, $y1, $x2, $y2, $rr, $c), $hex, $opacity);

            return;
        }
        $this->rrect($this->im, $x1, $y1, $x2, $y2, $rr, $col);
    }

    private function rrect(GdImage $im, int $x1, int $y1, int $x2, int $y2, int $rr, int $col): void
    {
        imagefilledrectangle($im, $x1 + $rr, $y1, $x2 - $rr, $y2, $col);
        imagefilledrectangle($im, $x1, $y1 + $rr, $x1 + $rr - 1, $y2 - $rr, $col);
        imagefilledrectangle($im, $x2 - $rr + 1, $y1 + $rr, $x2, $y2 - $rr, $col);
        $d = $rr * 2;
        imagefilledellipse($im, $x1 + $rr, $y1 + $rr, $d, $d, $col);
        imagefilledellipse($im, $x2 - $rr, $y1 + $rr, $d, $d, $col);
        imagefilledellipse($im, $x1 + $rr, $y2 - $rr, $d, $d, $col);
        imagefilledellipse($im, $x2 - $rr, $y2 - $rr, $d, $d, $col);
    }

    /** Draw an opaque shape on a transparent layer, then blend it at $opacity. */
    private function blendShape(callable $draw, string $hex, float $opacity): void
    {
        $w = self::W * self::S;
        $h = self::H * self::S;
        $layer = imagecreatetruecolor($w, $h);
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        imagefill($layer, 0, 0, imagecolorallocatealpha($layer, 0, 0, 0, 127));
        $hexs = ltrim($hex, '#');
        $alpha = (int) round(127 - max(0.0, min(1.0, $opacity)) * 127);
        $col = imagecolorallocatealpha($layer, hexdec(substr($hexs, 0, 2)), hexdec(substr($hexs, 2, 2)), hexdec(substr($hexs, 4, 2)), $alpha);
        $draw($layer, $col);
        imagecopy($this->im, $layer, 0, 0, 0, 0, $w, $h);
        imagedestroy($layer);
    }

    /** Dashed outline just inside a rounded rectangle (straight edges only). */
    public function dashedBorder(float $x, float $y, float $w, float $h, float $r, string $hex, float $opacity, float $thickness = 2, float $dash = 9, float $gap = 6): void
    {
        $col = $this->color($hex, $opacity);
        $t = max(1, (int) round($thickness * self::S));
        imagesetthickness($this->im, $t);
        $inset = $thickness / 2 + 1;
        $segments = [
            [$x + $r, $y + $inset, $x + $w - $r, $y + $inset],
            [$x + $r, $y + $h - $inset, $x + $w - $r, $y + $h - $inset],
            [$x + $inset, $y + $r, $x + $inset, $y + $h - $r],
            [$x + $w - $inset, $y + $r, $x + $w - $inset, $y + $h - $r],
        ];
        foreach ($segments as [$ax, $ay, $bx, $by]) {
            $len = hypot($bx - $ax, $by - $ay);
            for ($p = 0; $p < $len; $p += $dash + $gap) {
                $e = min($len, $p + $dash);
                imageline(
                    $this->im,
                    (int) round(($ax + ($bx - $ax) * $p / $len) * self::S),
                    (int) round(($ay + ($by - $ay) * $p / $len) * self::S),
                    (int) round(($ax + ($bx - $ax) * $e / $len) * self::S),
                    (int) round(($ay + ($by - $ay) * $e / $len) * self::S),
                    $col
                );
            }
        }
        imagesetthickness($this->im, 1);
    }

    /** Width in 1x pixels of $text set in $font at $size. */
    public function measure(string $text, string $font, float $size, float $tracking = 0): float
    {
        if ($text === '') {
            return 0;
        }
        if ($tracking != 0) {
            $w = 0;
            foreach (mb_str_split($text) as $ch) {
                $w += $this->measure($ch, $font, $size) + $tracking;
            }

            return $w - $tracking;
        }
        $box = imagettfbbox($size * self::S * self::PT, 0, self::font($font), $text);

        return (max($box[2], $box[4]) - min($box[0], $box[6])) / self::S;
    }

    /**
     * Draw $text with its baseline at $y. Returns the drawn width.
     * $tracking adds letter spacing (used for small caps eyebrows).
     */
    public function text(float $x, float $y, string $text, string $font, float $size, string $hex, float $opacity = 1.0, float $tracking = 0): float
    {
        $col = $this->color($hex, $opacity);
        $path = self::font($font);
        if ($tracking == 0) {
            imagettftext($this->im, $size * self::S * self::PT, 0, (int) round($x * self::S), (int) round($y * self::S), $col, $path, $text);

            return $this->measure($text, $font, $size);
        }
        $cx = $x;
        foreach (mb_str_split($text) as $ch) {
            imagettftext($this->im, $size * self::S * self::PT, 0, (int) round($cx * self::S), (int) round($y * self::S), $col, $path, $ch);
            $cx += $this->measure($ch, $font, $size) + $tracking;
        }

        return $cx - $x - $tracking;
    }

    /** Cut $text with an ellipsis so it fits $maxWidth. */
    public function ellipsize(string $text, string $font, float $size, float $maxWidth): string
    {
        if ($this->measure($text, $font, $size) <= $maxWidth) {
            return $text;
        }
        $chars = mb_str_split($text);
        while (count($chars) > 1) {
            array_pop($chars);
            $candidate = rtrim(implode('', $chars)).'…';
            if ($this->measure($candidate, $font, $size) <= $maxWidth) {
                return $candidate;
            }
        }

        return '…';
    }

    /**
     * Largest size in [$min, $max] (step 2) at which $text wraps into at
     * most $maxLines lines of $maxWidth. Returns [size, lines]; the last
     * line is ellipsized when even $min does not fit.
     *
     * @return array{0: float, 1: list<string>}
     */
    public function fit(string $text, string $font, float $max, float $min, float $maxWidth, int $maxLines): array
    {
        for ($size = $max; $size >= $min; $size -= 2) {
            $lines = $this->wrap($text, $font, $size, $maxWidth);
            if (count($lines) <= $maxLines) {
                return [$size, $lines];
            }
        }
        $lines = $this->wrap($text, $font, $min, $maxWidth);
        $kept = array_slice($lines, 0, $maxLines);
        $rest = implode(' ', array_slice($lines, $maxLines - 1));
        $kept[$maxLines - 1] = $this->ellipsize($rest, $font, $min, $maxWidth);

        return [$min, $kept];
    }

    /** @return list<string> */
    public function wrap(string $text, string $font, float $size, float $maxWidth): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $try = $line === '' ? $word : $line.' '.$word;
            if ($line !== '' && $this->measure($try, $font, $size) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Load an image file (png/jpg/webp/gif) and draw it contained and
     * centred in the box. Returns false when the file can't be read.
     */
    public function imageContain(string $path, float $x, float $y, float $w, float $h): bool
    {
        $src = self::load($path);
        if (!$src) {
            return false;
        }
        $this->drawContained($src, $x, $y, $w, $h);
        imagedestroy($src);

        return true;
    }

    public function drawContained(GdImage $src, float $x, float $y, float $w, float $h): void
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        if ($sw < 1 || $sh < 1) {
            return;
        }
        $scale = min($w / $sw, $h / $sh);
        $dw = $sw * $scale;
        $dh = $sh * $scale;
        $dx = $x + ($w - $dw) / 2;
        $dy = $y + ($h - $dh) / 2;
        imagealphablending($src, true);
        imagecopyresampled(
            $this->im,
            $src,
            (int) round($dx * self::S),
            (int) round($dy * self::S),
            0,
            0,
            (int) round($dw * self::S),
            (int) round($dh * self::S),
            $sw,
            $sh
        );
    }

    public static function load(string $path): ?GdImage
    {
        if ($path === '' || !is_file($path) || filesize($path) > 15 * 1024 * 1024) {
            return null;
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }
        $img = @imagecreatefromstring($data);
        if (!$img) {
            return null;
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
        imagesavealpha($img, true);

        return $img;
    }

    public function gd(): GdImage
    {
        return $this->im;
    }

    public function scale(): int
    {
        return self::S;
    }

    /** Resample to 1200x630 and write a PNG. */
    public function save(string $path): bool
    {
        $out = imagecreatetruecolor(self::W, self::H);
        imagecopyresampled($out, $this->im, 0, 0, 0, 0, self::W, self::H, self::W * self::S, self::H * self::S);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $ok = imagepng($out, $path, 6);
        imagedestroy($out);

        return $ok && is_file($path) && filesize($path) > 1000;
    }

    public function __destruct()
    {
        imagedestroy($this->im);
    }
}
