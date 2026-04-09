# PeptideMap UI Primitives (Modern 2026)

Clean, Tailwind v4-powered component library for the redesigned PeptideMap
frontend. Everything under this folder is self-contained and uses design
tokens defined in `resources/css/app.css` (the `@theme` block).

## Design philosophy

- **Calm, clinical, confident.** Stripe / Vercel / biotech-serious, not hype.
- **Data-forward.** Prices, dosages, test dates = `ui-mono` always.
- **Whitespace as material.** Generous padding. 8px rhythm. No noise.
- **One signature motion.** Lift-on-hover for cards. Everything else stays still.
- **Accessible.** `ui-focus` for keyboard users. Color contrast AAA where possible.

## Components

### `Button.vue`
Primary CTAs, secondary, ghost, link. 3 sizes.

```vue
<Button variant="primary" size="lg">Explore vendors</Button>
<Button as="a" href="/vendors" variant="secondary">Browse</Button>
<Button variant="ghost" size="sm">Cancel</Button>
```

### `Card.vue`
Base surface primitive. Pass `hoverable` for the lift-on-hover interaction.
Pass `featured` for an accent ring.

```vue
<Card hoverable padding="lg">...</Card>
<Card featured>...</Card>
```

### `Badge.vue`
Inline trust / status pills. Variants: `verified`, `tested`, `featured`, `new`,
`caution`, `neutral`. Auto-icon for verified/tested.

```vue
<Badge variant="verified">Verified</Badge>
<Badge variant="tested">Lab tested</Badge>
<Badge variant="featured">Editor's pick</Badge>
```

### `Stat.vue`
Large numeric display with optional label + delta + hint. Use for homepage
hero trust row and analytics dashboards.

```vue
<Stat label="Vendors verified" value="47" size="xl" />
<Stat label="Clicks (30d)" value="1,284" :delta="12.5" hint="vs prior 30d" />
```

### `VendorCard.vue`
Homepage + listing vendor card. Shows logo, rating, verified shield,
compound count, last tested date, thumbnail strip.

```vue
<VendorCard :vendor="{
  name: 'Acme Peptides',
  rating_average: 4.9,
  rating_count: 128,
  verified: true,
  product_count: 42,
  last_tested_label: '3d ago',
  product_thumbs: ['url1','url2','url3'],
  url: '/shop/acme-peptides',
}" />
```

### `ProductCard.vue`
Listing card for compounds. Image, name, brand, price + per-mg, badges.

```vue
<ProductCard :product="{
  name: 'BPC-157 5mg',
  brand_name: 'Acme Peptides',
  brand_verified: true,
  image_url: '...',
  price: 49.99,
  price_per_mg: 9.99,
  verified: true,
  lab_tested: true,
  url: '/product/bpc-157/42',
}" />
```

### `TrustBar.vue`
3-column "why us" section. Pass an items array with icon/title/body.

```vue
<TrustBar :items="[
  { icon: 'shield', title: 'Verified', body: 'We audit every vendor.' },
  { icon: 'eye', title: 'Transparent', body: 'Real data, no marketing.' },
  { icon: 'grid', title: 'Comparable', body: 'Side-by-side in one click.' },
]" />
```

### `DataRow.vue`
Label + value row for product specs, vendor data panels, etc.

```vue
<DataRow label="Purity" value="99.2%" />
<DataRow label="Price per mg" value="$2.49" tooltip="At best vendor" />
```

### `VerifiedShield.vue`
Animated verification badge (gradient shield + checkmark, breathes on mount).
Use anywhere trust needs to be signalled visually.

```vue
<VerifiedShield size="md" />
<VerifiedShield size="lg" show-label :animate="true" />
```

### `SearchPalette.vue`
⌘K / Ctrl+K command palette. Currently routes to `/search?q=` on submit;
live typeahead to be added in a future iteration.

```vue
<SearchPalette />
<!-- Listens globally for cmd/ctrl+k -->
```

## Layouts

### `Layouts/ModernLayout.vue`
Sticky glass-on-scroll nav + 4-column footer. Use for any rebuilt page:

```vue
<template>
  <ModernLayout>
    <!-- your page content -->
  </ModernLayout>
</template>

<script setup>
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
</script>
```

## Design tokens reference

All tokens live in `resources/css/app.css` (`@theme` block). Notable ones:

- Colors: `--color-bg`, `--color-surface`, `--color-ink`, `--color-ink-muted`,
  `--color-accent-500`, `--color-biotech-500`, `--color-verified`
- Fonts: `--font-display` (Inter Tight), `--font-mono` (JetBrains Mono)
- Shadows: `--shadow-xs`, `--shadow-sm`, `--shadow-md`, `--shadow-lg`
- Motion: `--ease-out-smooth`

Helper classes:

- `.ui-display` — heading font + tight tracking
- `.ui-mono` — tabular numbers
- `.ui-gradient-bg` / `.ui-gradient-text` — primary gradient
- `.ui-spotlight` — radial hero background
- `.ui-lift` — hover lift pattern
- `.ui-glass` — sticky nav glass effect
- `.ui-focus` — accessible focus ring
- `.ui-hairline` — 1px border color helper
- `.ui-breathe` — one-time subtle scale animation

## Migration notes (old → new)

The legacy components (`resources/js/components/*.vue` at the top level)
still exist for the pages that haven't been rebuilt yet. **Don't modify
them.** As we rebuild pages one at a time, they switch to using components
from this `ui/` folder.

- `ProductCard.vue` → `ui/ProductCard.vue`
- `VendorCard.vue` → `ui/VendorCard.vue`
- `TopRatedVendorCard.vue` → `ui/VendorCard.vue` (with featured prop)
- `MainButton.vue` → `ui/Button.vue`
- `RatingDisplay.vue` → use inline stars + `ui-mono` number

## What's next

- Phase 2: Homepage redesign (`Pages/Welcome.vue`) using these primitives
- Phase 3: Product detail + vendor detail pages
- Phase 4: Remaining pages (compare, encyclopedia, etc.)
- Polish: dark mode toggle, real typeahead in SearchPalette, count-up numbers
