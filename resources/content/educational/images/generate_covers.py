#!/usr/bin/env python3
"""Draw original Peptidemap educational covers.

These are generated artwork in the site's dark OG style. They are not stock
photos and they are not the shared og-default or /images/blogs/1.jpg placeholder.
"""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

W, H = 1200, 800
FONT_DIR = Path("/usr/share/fonts/truetype/macos")
ROOT = Path(__file__).resolve().parents[4]
OUT_DIRS = [
    Path(__file__).resolve().parent,
    ROOT / "public" / "images" / "educational",
]


def font(weight: str, size: int) -> ImageFont.FreeTypeFont:
    name = {
        "regular": "Inter-Regular.ttf",
        "medium": "Inter-Medium.ttf",
        "semibold": "Inter-SemiBold.ttf",
        "bold": "Inter-Bold.ttf",
    }[weight]
    return ImageFont.truetype(str(FONT_DIR / name), size)


def lerp(a, b, t):
    return tuple(int(a[i] + (b[i] - a[i]) * t) for i in range(len(a)))


def text_width(draw, text, face) -> float:
    box = draw.textbbox((0, 0), text, font=face)
    return box[2] - box[0]


def fit_font(draw, text, weight, max_size, max_width):
    size = max_size
    while size > 28:
        face = font(weight, size)
        if text_width(draw, text, face) <= max_width:
            return face
        size -= 2
    return font(weight, 28)


def tracked(draw, text, y, face, fill, tracking=2.4):
    widths = [text_width(draw, ch, face) for ch in text]
    total = sum(widths) + tracking * (len(text) - 1)
    x = (W - total) / 2
    for ch, width in zip(text, widths):
        draw.text((x, y), ch, font=face, fill=fill)
        x += width + tracking


def canvas(accent):
    img = Image.new("RGB", (W, H))
    draw = ImageDraw.Draw(img)
    top = (5, 8, 16)
    bottom = (11, 20, 38)
    for y in range(H):
        draw.line([(0, y), (W, y)], fill=lerp(top, bottom, y / (H - 1)))

    glow = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    glow_draw = ImageDraw.Draw(glow)
    glow_draw.ellipse([W - 560, -220, W + 120, 460], fill=accent + (78,))
    glow_draw.ellipse([-240, H - 380, 460, H + 120], fill=(34, 197, 94, 46))
    glow = glow.filter(ImageFilter.GaussianBlur(72))

    dots = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    dots_draw = ImageDraw.Draw(dots)
    for y in range(16, H, 28):
        for x in range(16, W, 28):
            dots_draw.ellipse([x, y, x + 1.6, y + 1.6], fill=(255, 255, 255, 32))

    base = Image.alpha_composite(img.convert("RGBA"), glow)
    return Image.alpha_composite(base, dots)


def rounded_rect(draw, box, radius, fill, outline=None, width=1):
    draw.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=width)


def wordmark(draw, y):
    peptide = font("regular", 28)
    bold = font("bold", 28)
    left = text_width(draw, "peptide", peptide)
    right = text_width(draw, "map", bold)
    gap = 1
    x = (W - (left + right + gap)) / 2
    draw.text((x, y), "peptide", font=peptide, fill=(226, 232, 240, 230))
    draw.text((x + left + gap, y), "map", font=bold, fill=(255, 255, 255, 255))


def row(draw, box, label, detail, accent):
    rounded_rect(draw, box, 16, (255, 255, 255, 16), (255, 255, 255, 36), 1)
    draw.rounded_rectangle(
        [box[0], box[1], box[0] + 6, box[3]],
        radius=3,
        fill=accent + (255,),
    )
    label_font = font("semibold", 26)
    detail_font = font("regular", 18)
    draw.text((box[0] + 28, box[1] + 16), label, font=label_font, fill=(248, 250, 252, 255))
    draw.text((box[0] + 28, box[1] + 50), detail, font=detail_font, fill=(203, 213, 225, 230))


def cover(filename, eyebrow, title, subtitle, accent, rows):
    img = canvas(accent)
    draw = ImageDraw.Draw(img)
    panel = [300, 78, 900, 690]
    rounded_rect(draw, panel, 28, (8, 12, 24, 168), (255, 255, 255, 28), 1)

    tracked(draw, eyebrow, 108, font("semibold", 18), accent + (255,), tracking=3.2)

    title_font = fit_font(draw, title, "bold", 54, 520)
    title_w = text_width(draw, title, title_font)
    draw.text(((W - title_w) / 2, 148), title, font=title_font, fill=(255, 255, 255, 255))

    sub_font = fit_font(draw, subtitle, "regular", 22, 520)
    sub_w = text_width(draw, subtitle, sub_font)
    draw.text(((W - sub_w) / 2, 214), subtitle, font=sub_font, fill=(203, 213, 225, 235))

    top = 280
    height = 78
    gap = 14
    for index, (label, detail) in enumerate(rows):
        y = top + index * (height + gap)
        row(draw, [336, y, 864, y + height], label, detail, accent)

    wordmark(draw, 640)

    for directory in OUT_DIRS:
        directory.mkdir(parents=True, exist_ok=True)
        img.convert("RGB").save(directory / filename, "PNG", optimize=True)


def main():
    cyan = (56, 189, 248)
    emerald = (52, 211, 153)
    amber = (251, 191, 36)
    rose = (251, 113, 133)

    cover(
        "bpc-157-vs-tb-500-evidence.png",
        "EVIDENCE",
        "BPC-157 vs TB-500",
        "Separate molecules. Separate records.",
        cyan,
        [
            ("BPC-157", "15 amino acids · gastric research line"),
            ("TB-500", "Short fragment of thymosin beta-4"),
            ("Stacking claims", "No published human combination trials"),
        ],
    )
    cover(
        "beginners-guide-to-research-peptides.png",
        "BASICS",
        "Beginner’s Guide",
        "Definitions, labels, and documents.",
        emerald,
        [
            ("01  ·  Peptide", "A chain of amino acids, not a slogan"),
            ("02  ·  RUO label", "A claimed use, not FDA approval"),
            ("03  ·  COA", "Match the lot, then verify the lab"),
        ],
    )
    cover(
        "peptide-legality-fda-ruo-compounding.png",
        "REGULATION",
        "FDA, RUO & Compounding",
        "Three questions. Not one answer.",
        amber,
        [
            ("FDA approval", "A finished drug product authorization"),
            ("503A compounding", "A limited pharmacy pathway"),
            ("RUO listing", "Not the same as either of the above"),
        ],
    )
    cover(
        "fda-peptide-reclassification-2026.png",
        "FDA 2026",
        "Not permission",
        "Off Category 2 is not a license to compound.",
        rose,
        [
            ("Category 2 removal", "Not Category 1 status"),
            ("Withdrawn nomination", "Not the 503A Bulks List"),
            ("PCAC recommendation", "Advisory only, not a final rule"),
        ],
    )


if __name__ == "__main__":
    main()
