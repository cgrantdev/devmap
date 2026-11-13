<?php

namespace App\Support\Og;

use GdImage;

/**
 * Peptidemap social cards (1200x630). One shared frame: deep navy, the
 * logo's sky-to-green glow, the logo's network sphere as a watermark, a
 * white wordmark and a footer. Each card type lays out its own content.
 *
 * Every method returns a drawn OgCanvas; the caller saves it.
 */
final class OgCards
{
    private const BG = '#0B1020';

    private const SKY = '#0EA5E9';

    private const GREEN = '#10B981';

    private const MINT = '#6EE7B7';

    private const SKY_LIGHT = '#7DD3FC';

    private const WHITE = '#FFFFFF';

    private const MUTED = '#CBD5E1';

    private const SUBTLE = '#94A3B8';

    private const ASSET_VERSION = 'v1';

    private const LEFT = 72;

    // ---------------------------------------------------------------
    // Card types
    // ---------------------------------------------------------------

    /**
     * @param  array{name: string, logo: ?string, location: ?string, listings: int, from: ?string, code: ?string, percent: ?float, path: string}  $d
     */
    public static function vendor(array $d): OgCanvas
    {
        $c = self::frame($d['path']);
        self::logoTile($c, $d['logo'], $d['name'], self::LEFT, 150, 290);

        $x = 402;
        $w = 1128 - $x;
        $eyebrow = 'VENDOR'.(!empty($d['location']) ? ' · '.mb_strtoupper($d['location']) : '');
        $c->text($x, 196, $c->ellipsize($eyebrow, 'InterTight-SemiBold', 19, $w), 'InterTight-SemiBold', 19, self::SKY_LIGHT, 1, 2.5);

        [$size, $lines] = $c->fit($d['name'], 'InterTight-Bold', 70, 44, $w, 2);
        $y = 214 + $size * 0.92;
        foreach ($lines as $line) {
            $c->text($x, $y, $line, 'InterTight-Bold', $size, self::WHITE);
            $y += $size * 1.08;
        }

        $meta = number_format($d['listings']).' listings';
        if (!empty($d['from'])) {
            $meta .= ' · from '.$d['from'];
        }
        $c->text($x, $y + 6, $meta, 'InterTight-SemiBold', 26, self::MUTED);

        $ty = max($y + 40, 372);
        if (!empty($d['code'])) {
            self::ticket($c, $x, $ty, min(560, $w), 104, $d['code'], $d['percent'] ?? null, 46);
        } else {
            self::pill($c, $x, $ty + 20, 'Compare their prices on Peptidemap', 22);
        }

        return $c;
    }

    /**
     * @param  array{name: string, vendor: string, image: ?string, price: string, retail: ?string, per_mg: ?string, size: ?string, code: ?string, percent: ?float, path: string}  $d
     */
    public static function product(array $d): OgCanvas
    {
        $c = self::frame($d['path']);
        $c->roundedRect(self::LEFT, 150, 290, 290, 32, self::WHITE);
        if (empty($d['image']) || !$c->imageContain($d['image'], self::LEFT + 22, 172, 246, 246)) {
            self::vialGlyph($c, self::LEFT, 150, 290);
        }

        $x = 402;
        $w = 1128 - $x;
        $c->text($x, 196, $c->ellipsize(mb_strtoupper($d['vendor']), 'InterTight-SemiBold', 19, $w), 'InterTight-SemiBold', 19, self::SKY_LIGHT, 1, 2.5);

        [$size, $lines] = $c->fit($d['name'], 'InterTight-Bold', 58, 38, $w, 2);
        $y = 214 + $size * 0.92;
        foreach ($lines as $line) {
            $c->text($x, $y, $line, 'InterTight-Bold', $size, self::WHITE);
            $y += $size * 1.08;
        }

        $py = $y + 52;
        $pw = $c->text($x, $py, $d['price'], 'JetBrainsMono-Bold', 56, self::WHITE);
        if (!empty($d['retail'])) {
            $rx = $x + $pw + 18;
            $rw = $c->text($rx, $py - 4, $d['retail'], 'InterTight-SemiBold', 28, self::SUBTLE);
            $c->roundedRect($rx - 2, $py - 15, $rw + 4, 2.5, 1, self::SUBTLE);
        }
        $sub = array_values(array_filter([$d['per_mg'] ?? null, $d['size'] ?? null]));
        if ($sub) {
            $c->text($x, $py + 38, implode(' · ', $sub), 'InterTight-SemiBold', 24, self::MUTED);
        }

        if (!empty($d['code'])) {
            self::ticket($c, $x, min($py + 64, 470), min(520, $w), 96, $d['code'], $d['percent'] ?? null, 42);
        }

        return $c;
    }

    /**
     * @param  array{name: string, eyebrow: string, vendors: int, lowest: ?string, best: ?string, board: list<array{vendor: string, value: string, ratio: float}>, path: string}  $d
     */
    public static function compound(array $d): OgCanvas
    {
        $c = self::frame($d['path']);
        $hasBoard = !empty($d['board']);
        $x = self::LEFT;
        $w = $hasBoard ? 580 : 1056;

        $c->text($x, 186, $c->ellipsize(mb_strtoupper($d['eyebrow']), 'InterTight-SemiBold', 19, $w), 'InterTight-SemiBold', 19, self::SKY_LIGHT, 1, 2.5);
        [$size, $lines] = $c->fit($d['name'], 'InterTight-Bold', $hasBoard ? 96 : 112, 52, $w, 2);
        $y = 204 + $size * 0.92;
        foreach ($lines as $line) {
            $c->text($x, $y, $line, 'InterTight-Bold', $size, self::WHITE);
            $y += $size * 1.05;
        }

        $stats = [[number_format($d['vendors']), 'vendors']];
        if (!empty($d['lowest'])) {
            $stats[] = [$d['lowest'], 'lowest price'];
        }
        if (!empty($d['best'])) {
            $stats[] = [$d['best'], 'best per mg'];
        }
        $sx = $x;
        $sy = max($y + 46, 460);
        foreach ($stats as [$value, $label]) {
            $vw = $c->text($sx, $sy, $value, 'JetBrainsMono-Bold', 40, self::WHITE);
            $lw = $c->text($sx, $sy + 32, $label, 'InterTight-SemiBold', 18, self::SUBTLE);
            $sx += max($vw, $lw) + 44;
        }

        if ($hasBoard) {
            $bx = 700;
            $by = 138;
            $bw = 428;
            $bh = 372;
            $c->roundedRect($bx, $by, $bw, $bh, 26, self::WHITE, 0.06);
            $c->text($bx + 28, $by + 46, 'LOWEST PER MG RIGHT NOW', 'InterTight-SemiBold', 16, self::MINT, 1, 2.2);
            $ry = $by + 92;
            foreach (array_slice($d['board'], 0, 5) as $i => $row) {
                $valueW = $c->measure($row['value'], 'JetBrainsMono-Bold', 22);
                $name = $c->ellipsize($row['vendor'], 'InterTight-SemiBold', 22, $bw - 56 - $valueW - 16);
                $c->text($bx + 28, $ry, $name, 'InterTight-SemiBold', 22, $i === 0 ? self::WHITE : self::MUTED);
                $c->text($bx + $bw - 28 - $valueW, $ry, $row['value'], 'JetBrainsMono-Bold', 22, $i === 0 ? self::MINT : self::MUTED);
                $trackW = $bw - 56;
                $c->roundedRect($bx + 28, $ry + 14, $trackW, 6, 3, self::WHITE, 0.08);
                $c->roundedRect($bx + 28, $ry + 14, max(10, $trackW * max(0.05, min(1, $row['ratio']))), 6, 3, $i === 0 ? self::GREEN : self::SKY, $i === 0 ? 1 : 0.75);
                $ry += 54;
            }
        }

        return $c;
    }

    /**
     * @param  array{name: string, logo: ?string, code: string, percent: ?float, path: string}  $d
     */
    public static function coupon(array $d): OgCanvas
    {
        $c = self::frame($d['path']);
        self::logoTile($c, $d['logo'], $d['name'], self::LEFT, 150, 290);

        $x = 402;
        $w = 1128 - $x;
        $c->text($x, 196, 'COUPON CODE', 'InterTight-SemiBold', 19, self::SKY_LIGHT, 1, 2.5);
        [$size, $lines] = $c->fit($d['name'], 'InterTight-Bold', 64, 40, $w, 1);
        $y = 214 + $size * 0.92;
        $c->text($x, $y, $lines[0] ?? $d['name'], 'InterTight-Bold', $size, self::WHITE);
        $pct = self::percentLabel($d['percent'] ?? null);
        $c->text($x, $y + 46, $pct ? $pct.' off with this code' : 'Peptidemap coupon code', 'InterTight-SemiBold', 26, self::MUTED);

        self::ticket($c, $x, max($y + 80, 330), min(600, $w), 138, $d['code'], $d['percent'] ?? null, 68);

        return $c;
    }

    /**
     * @param  array{eyebrow: string, title: string, subtitle: ?string, chips: list<string>, path: string}  $d
     */
    public static function page(array $d): OgCanvas
    {
        $c = self::frame($d['path'], true);
        $x = self::LEFT;
        $w = 760;
        $c->text($x, 186, mb_strtoupper($d['eyebrow']), 'InterTight-SemiBold', 19, self::SKY_LIGHT, 1, 2.5);
        [$size, $lines] = $c->fit($d['title'], 'InterTight-Bold', 80, 46, $w, 3);
        $y = 204 + $size * 0.92;
        foreach ($lines as $line) {
            $c->text($x, $y, $line, 'InterTight-Bold', $size, self::WHITE);
            $y += $size * 1.06;
        }
        if (!empty($d['subtitle'])) {
            $sub = $c->wrap($d['subtitle'], 'InterTight-SemiBold', 26, $w);
            $sy = $y + 14;
            foreach (array_slice($sub, 0, 2) as $line) {
                $c->text($x, $sy, $line, 'InterTight-SemiBold', 26, self::MUTED);
                $sy += 36;
            }
            $y = $sy - 10;
        }
        $cx = $x;
        $cy = max($y + 30, 452);
        foreach ($d['chips'] as $chip) {
            $cx += self::pill($c, $cx, $cy, $chip, 21) + 12;
        }

        return $c;
    }

    // ---------------------------------------------------------------
    // Shared pieces
    // ---------------------------------------------------------------

    /** Background, glow, dot grid, watermark, wordmark and footer. */
    private static function frame(string $path, bool $bigMotif = false): OgCanvas
    {
        $c = new OgCanvas();
        $c->fill(self::BG);
        $c->glow(1080, 40, 560, self::SKY, 0.55);
        $c->glow(80, 640, 520, self::GREEN, 0.4);
        $c->dotGrid(28, self::WHITE, 0.05);

        if ($motif = self::asset('motif')) {
            $size = $bigMotif ? 620 : 420;
            $c->drawContained($motif, 1200 - $size * 0.62, $bigMotif ? -40 : -120, $size, $size);
        }
        if ($logo = self::asset('logo')) {
            $c->drawContained($logo, self::LEFT, 54, 190, 48);
        } else {
            $c->text(self::LEFT, 90, 'peptidemap', 'InterTight-Bold', 34, self::WHITE);
        }

        $c->roundedRect(0, 566, 1200, 1, 0, self::WHITE, 0.08);
        $url = 'peptidemap.com'.($path !== '' && $path !== '/' ? $path : '');
        $c->text(self::LEFT, 606, $c->ellipsize($url, 'JetBrainsMono-Bold', 19, 640), 'JetBrainsMono-Bold', 19, self::SUBTLE);
        $tag = 'Prices checked daily';
        $tw = $c->measure($tag, 'InterTight-SemiBold', 19);
        $c->roundedRect(1128 - $tw - 22, 590, 10, 10, 5, self::GREEN);
        $c->text(1128 - $tw, 606, $tag, 'InterTight-SemiBold', 19, self::MUTED);

        return $c;
    }

    /** White rounded tile with the vendor logo, or the vendor's initials. */
    private static function logoTile(OgCanvas $c, ?string $logo, string $name, float $x, float $y, float $size): void
    {
        $c->roundedRect($x, $y, $size, $size, 32, self::WHITE);
        if ($logo && $c->imageContain($logo, $x + $size * 0.12, $y + $size * 0.12, $size * 0.76, $size * 0.76)) {
            return;
        }
        $initials = self::initials($name);
        $fs = 110;
        $tw = $c->measure($initials, 'InterTight-Bold', $fs);
        $c->text($x + ($size - $tw) / 2, $y + $size / 2 + $fs * 0.36, $initials, 'InterTight-Bold', $fs, '#0F172A');
    }

    /** Green coupon ticket with notches, dashed edge, label and code. */
    private static function ticket(OgCanvas $c, float $x, float $y, float $w, float $h, string $code, ?float $percent, float $codeSize): void
    {
        $c->roundedRect($x, $y, $w, $h, 18, self::GREEN);
        $c->dashedBorder($x + 6, $y + 6, $w - 12, $h - 12, 14, '#D1FAE5', 0.9, 2.2, 10, 7);
        // Coupon notches in the background colour on both short edges.
        $c->roundedRect($x - 14, $y + $h / 2 - 14, 28, 28, 14, self::BG);
        $c->roundedRect($x + $w - 14, $y + $h / 2 - 14, 28, 28, 14, self::BG);

        $pct = self::percentLabel($percent);
        $label = $pct ? $pct.' OFF · CODE' : 'COUPON CODE';
        $labelSize = max(16, min(22, $h * 0.17));
        $c->text($x + 34, $y + $h * 0.36, $label, 'InterTight-SemiBold', $labelSize, '#ECFDF5', 1, 2.4);
        $code = mb_strtoupper(trim($code));
        $size = $codeSize;
        while ($size > 24 && $c->measure($code, 'JetBrainsMono-Bold', $size) > $w - 68) {
            $size -= 2;
        }
        $c->text($x + 34, $y + $h * 0.36 + $size * 1.05, $code, 'JetBrainsMono-Bold', $size, self::WHITE);
    }

    /** Translucent pill with text; returns its width. */
    private static function pill(OgCanvas $c, float $x, float $y, string $text, float $size): float
    {
        $tw = $c->measure($text, 'InterTight-SemiBold', $size);
        $w = $tw + 40;
        $h = $size * 2.1;
        $c->roundedRect($x, $y, $w, $h, $h / 2, self::WHITE, 0.09);
        $c->text($x + 20, $y + $h / 2 + $size * 0.36, $text, 'InterTight-SemiBold', $size, self::WHITE, 0.92);

        return $w;
    }

    /** Simple vial outline for listings without a photo. */
    private static function vialGlyph(OgCanvas $c, float $x, float $y, float $size): void
    {
        $cx = $x + $size / 2;
        $c->roundedRect($cx - 34, $y + 62, 68, 26, 6, '#CBD5E1');
        $c->roundedRect($cx - 46, $y + 92, 92, 140, 18, '#E2E8F0');
        $c->roundedRect($cx - 46, $y + 170, 92, 62, 18, '#BAE6FD');
    }

    private static function percentLabel(?float $percent): ?string
    {
        if ($percent === null || $percent <= 0 || $percent >= 100) {
            return null;
        }
        $n = round($percent, 1);

        return (fmod($n, 1.0) == 0.0 ? (string) (int) $n : (string) $n).'%';
    }

    private static function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name)) ?: [];
        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 2));
    }

    // ---------------------------------------------------------------
    // Derived brand assets (built once from public/images/logo.png)
    // ---------------------------------------------------------------

    /** @var array<string, GdImage|null> */
    private static array $assets = [];

    /**
     * 'logo': the wordmark with its dark lettering turned white for the
     * navy card. 'motif': the network sphere alone, faded, as a watermark.
     */
    private static function asset(string $name): ?GdImage
    {
        if (array_key_exists($name, self::$assets)) {
            return self::$assets[$name];
        }
        $cache = storage_path('app/og-assets/'.$name.'-'.self::ASSET_VERSION.'.png');
        if (is_file($cache) && ($img = OgCanvas::load($cache))) {
            return self::$assets[$name] = $img;
        }
        $src = OgCanvas::load(public_path('images/logo.png'));
        if (!$src) {
            return self::$assets[$name] = null;
        }
        $img = $name === 'logo' ? self::whiteLettering($src) : self::fadedSphere($src);
        if (!is_dir(dirname($cache))) {
            @mkdir(dirname($cache), 0755, true);
        }
        imagealphablending($img, false);
        imagesavealpha($img, true);
        @imagepng($img, $cache, 6);

        return self::$assets[$name] = $img;
    }

    /** Dark (low-saturation) pixels become white; the coloured sphere stays. */
    private static function whiteLettering(GdImage $src): GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($src, $x, $y);
                $a = ($rgba >> 24) & 0x7F;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                if ($a < 127 && max($r, $g, $b) < 90) {
                    $r = $g = $b = 255;
                }
                imagesetpixel($out, $x, $y, ($a << 24) | ($r << 16) | ($g << 8) | $b);
            }
        }

        return $out;
    }

    /** The sphere (left part of the logo) at about 18% opacity. */
    private static function fadedSphere(GdImage $src): GdImage
    {
        $h = imagesy($src);
        $w = min(imagesx($src), (int) round($h * 1.05));
        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($src, $x, $y);
                $a = ($rgba >> 24) & 0x7F;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $opacity = (127 - $a) / 127 * 0.18;
                $na = (int) round(127 - $opacity * 127);
                imagesetpixel($out, $x, $y, ($na << 24) | ($r << 16) | ($g << 8) | $b);
            }
        }

        return $out;
    }
}
