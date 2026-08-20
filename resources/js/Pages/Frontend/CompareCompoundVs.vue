<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- Breadcrumb -->
    <nav class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-6 text-[12px] text-[color:var(--color-ink-subtle)]" aria-label="Breadcrumb">
      <ol class="flex items-center gap-1.5">
        <li><a href="/" class="hover:text-[color:var(--color-ink)]">Home</a></li>
        <li>/</li>
        <li><a href="/compare" class="hover:text-[color:var(--color-ink)]">Compare</a></li>
        <li>/</li>
        <li class="text-[color:var(--color-ink-muted)] font-medium truncate">{{ a.name }} vs {{ b.name }}</li>
      </ol>
    </nav>

    <!-- Hero — the two compound names as the visual event -->
    <section class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-6 pb-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-biotech-600)] mb-3">Head-to-head</div>
        <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-2">
          <span class="text-indigo-700">{{ a.name }}</span>
          <span class="text-[color:var(--color-ink-subtle)] font-light mx-2">vs</span>
          <span class="text-emerald-700">{{ b.name }}</span>
        </h1>
        <p v-if="a.summary || b.summary" class="text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl">
          Side-by-side vendor comparison, mechanism &amp; use-case differences, and current best prices.
        </p>
      </div>
    </section>

    <!-- Quick-facts snapshot — the 30-second answer, table format -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
      <div class="bg-white rounded-[14px] border border-[color:var(--color-hairline)] overflow-hidden shadow-[var(--shadow-xs)]">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
              <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] w-40">At a glance</th>
              <th class="text-left px-5 py-3 text-[13px] font-semibold text-indigo-700">{{ a.name }}</th>
              <th class="text-left px-5 py-3 text-[13px] font-semibold text-emerald-700">{{ b.name }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in snapshotRows" :key="row.label" class="border-b border-[color:var(--color-hairline-soft)] last:border-b-0">
              <td class="px-5 py-3 text-[12px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)]">{{ row.label }}</td>
              <td class="px-5 py-3 text-[color:var(--color-ink)]" :class="row.aClass || ''">
                <span v-if="row.a === null" class="text-[color:var(--color-ink-subtle)]">—</span>
                <span v-else>{{ row.a }}</span>
              </td>
              <td class="px-5 py-3 text-[color:var(--color-ink)]" :class="row.bClass || ''">
                <span v-if="row.b === null" class="text-[color:var(--color-ink-subtle)]">—</span>
                <span v-else>{{ row.b }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Deep-dive: two columns of structured content — never one long blob -->
    <section v-if="hasDeepContent" class="max-w-[1280px] mx-auto px-6 lg:px-10 pb-14">
      <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">Compare in depth</div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <CompoundColumn :compound="a" accent="indigo" />
        <CompoundColumn :compound="b" accent="emerald" />
      </div>
    </section>

    <!-- Cheapest vendors side-by-side — short tables, not walls -->
    <section class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">Top vendors right now</div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <VendorMiniTable :compound="a" accent="indigo" />
          <VendorMiniTable :compound="b" accent="emerald" />
        </div>
      </div>
    </section>

    <!-- Related pairs -->
    <section v-if="related.length" class="border-t border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">Other head-to-head comparisons</div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <a v-for="r in related" :key="r.url" :href="r.url" class="ui-focus group p-4 rounded-[10px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all">
            <div class="text-[13px] font-semibold text-[color:var(--color-ink)] group-hover:text-[color:var(--color-accent-600)] transition-colors">
              <span class="text-indigo-700">{{ formatSlug(r.raw_a) }}</span>
              <span class="text-[color:var(--color-ink-subtle)] mx-1.5">vs</span>
              <span class="text-emerald-700">{{ formatSlug(r.raw_b) }}</span>
            </div>
            <div class="mt-1 text-[11px] text-[color:var(--color-ink-muted)]">{{ r.tagline }}</div>
          </a>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed, defineComponent, h } from 'vue'
import ModernLayout from '../Layouts/ModernLayout.vue'
import { withSrc } from '@/composables/useOutbound'

const props = defineProps({
  a: { type: Object, required: true },
  b: { type: Object, required: true },
  related: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

function fmtPrice(v) {
  if (v == null) return null
  return '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function betterClass(aVal, bVal, aWinsIfLower = false) {
  if (aVal == null || bVal == null) return { a: '', b: '' }
  const aWins = aWinsIfLower ? aVal < bVal : aVal > bVal
  if (aVal === bVal) return { a: '', b: '' }
  return aWins
    ? { a: 'font-semibold text-emerald-700', b: '' }
    : { a: '', b: 'font-semibold text-emerald-700' }
}

const snapshotRows = computed(() => {
  const cheapestClasses = betterClass(props.a.cheapest_price, props.b.cheapest_price, true)
  const vendorClasses = betterClass(props.a.vendor_count, props.b.vendor_count, false)
  return [
    { label: 'Cheapest', a: fmtPrice(props.a.cheapest_price), b: fmtPrice(props.b.cheapest_price), aClass: cheapestClasses.a, bClass: cheapestClasses.b },
    { label: 'Vendors', a: props.a.vendor_count, b: props.b.vendor_count, aClass: vendorClasses.a, bClass: vendorClasses.b },
    { label: 'Products', a: props.a.product_count, b: props.b.product_count },
    { label: 'Half-life', a: props.a.half_life || null, b: props.b.half_life || null },
    { label: 'Administration', a: truncate(props.a.administration, 80), b: truncate(props.b.administration, 80) },
  ]
})

const hasDeepContent = computed(() =>
  !!(props.a.mechanism || props.b.mechanism || props.a.key_effects?.length || props.b.key_effects?.length || props.a.use_cases?.length || props.b.use_cases?.length || props.a.summary || props.b.summary)
)

function truncate(s, max) {
  if (!s) return null
  const t = String(s)
  return t.length <= max ? t : t.slice(0, max - 1).trimEnd() + '…'
}

function formatSlug(slug) {
  return slug.toUpperCase()
}

/**
 * Renders a compound's deep-dive column — summary, mechanism, key effects,
 * use cases — each in its own card so nothing reads as a wall of prose.
 */
const CompoundColumn = defineComponent({
  props: { compound: Object, accent: String },
  setup(props) {
    const accentBar = {
      indigo: 'bg-indigo-500',
      emerald: 'bg-emerald-500',
    }[props.accent]
    const accentText = {
      indigo: 'text-indigo-700',
      emerald: 'text-emerald-700',
    }[props.accent]

    return () => {
      const c = props.compound
      const sections = []

      // Header
      sections.push(h('div', { class: 'flex items-baseline justify-between mb-4' }, [
        h('h2', { class: `ui-display text-2xl font-semibold ${accentText}` }, c.name),
        h('a', {
          href: c.compare_url,
          class: 'text-[12px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]',
        }, 'Full comparison →'),
      ]))

      const card = (label, body) => h('div', { class: 'bg-white rounded-[10px] border border-[color:var(--color-hairline)] p-4' }, [
        h('div', { class: 'text-[10px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)] mb-1.5' }, label),
        body,
      ])
      const cards = []

      // Summary card — hard cap so it doesn't become a paragraph blob.
      if (c.summary) {
        cards.push(card('Overview', h('p', { class: 'text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed' }, truncate(c.summary, 260))))
      }
      // Mechanism
      if (c.mechanism) {
        cards.push(card('Mechanism of action', h('p', { class: 'text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed' }, truncate(c.mechanism, 320))))
      }
      // Key effects — bullet list from structured field
      if (c.key_effects?.length) {
        cards.push(card('Key effects', h('ul', { class: 'text-[13px] text-[color:var(--color-ink-muted)] space-y-1.5' },
          c.key_effects.slice(0, 5).map(e => h('li', { class: 'flex items-start gap-2' }, [
            h('span', { class: `flex-shrink-0 mt-1.5 w-1 h-1 rounded-full ${accentBar}` }),
            h('span', {}, e.heading ? `${e.heading}${e.text ? ' — ' + truncate(e.text, 140) : ''}` : truncate(e.text || '', 160)),
          ]))
        )))
      }
      // Use cases
      if (c.use_cases?.length) {
        cards.push(card('Common use cases', h('ul', { class: 'text-[13px] text-[color:var(--color-ink-muted)] space-y-1.5' },
          c.use_cases.slice(0, 5).map(e => h('li', { class: 'flex items-start gap-2' }, [
            h('span', { class: `flex-shrink-0 mt-1.5 w-1 h-1 rounded-full ${accentBar}` }),
            h('span', {}, e.heading ? `${e.heading}${e.text ? ' — ' + truncate(e.text, 140) : ''}` : truncate(e.text || '', 160)),
          ]))
        )))
      }

      // Empty state — one small card explaining why the column is thin
      if (!cards.length) {
        cards.push(h('div', { class: 'bg-white rounded-[10px] border border-dashed border-[color:var(--color-hairline)] p-4 text-[13px] text-[color:var(--color-ink-subtle)] italic' },
          `No encyclopedia entry yet for ${c.name}. Vendor snapshot is below.`))
      }

      if (c.encyclopedia_url) {
        cards.push(h('a', {
          href: c.encyclopedia_url,
          class: 'ui-focus inline-flex items-center gap-1 h-8 px-3 rounded-md border border-[color:var(--color-hairline)] bg-white text-[12px] font-medium text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-accent-700)] transition-colors',
        }, `Read the ${c.name} encyclopedia entry →`))
      }

      sections.push(h('div', { class: 'space-y-3' }, cards))

      return h('div', { class: `bg-[color:var(--color-bg)] rounded-[14px] border-l-4 border-t border-r border-b ${props.accent === 'indigo' ? 'border-l-indigo-500' : 'border-l-emerald-500'} border-[color:var(--color-hairline)] p-6` }, sections)
    }
  },
})

/**
 * Top-5 vendor list per compound. Kept intentionally compact — the point
 * of the vs-page is the head-to-head, not the deep vendor drilldown; a
 * link routes to /compare/{slug} for the full N-vendor list.
 */
const VendorMiniTable = defineComponent({
  props: { compound: Object, accent: String },
  setup(props) {
    const accentBorder = props.accent === 'indigo' ? 'border-l-indigo-500' : 'border-l-emerald-500'
    const accentText = props.accent === 'indigo' ? 'text-indigo-700' : 'text-emerald-700'
    return () => {
      const c = props.compound
      const rows = c.top_vendors || []
      return h('div', { class: `bg-white rounded-[14px] border-l-4 border-t border-r border-b ${accentBorder} border-[color:var(--color-hairline)] overflow-hidden` }, [
        h('div', { class: 'px-5 py-4 border-b border-[color:var(--color-hairline)] flex items-baseline justify-between' }, [
          h('div', {}, [
            h('div', { class: `text-[14px] font-semibold ${accentText}` }, c.name),
            h('div', { class: 'text-[11px] text-[color:var(--color-ink-subtle)] mt-0.5' },
              `${c.vendor_count} vendors, cheapest ${fmtPrice(c.cheapest_price) || '—'}`),
          ]),
          c.compare_url ? h('a', {
            href: c.compare_url,
            class: 'text-[12px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]',
          }, `See all ${c.vendor_count} →`) : null,
        ]),
        rows.length ? h('table', { class: 'w-full text-sm' }, [
          h('tbody', {}, rows.map((p, i) => h('tr', {
            key: p.id,
            class: 'border-b border-[color:var(--color-hairline-soft)] last:border-b-0 hover:bg-[color:var(--color-hairline-soft)] transition-colors',
          }, [
            h('td', { class: 'pl-5 pr-3 py-3' }, [
              h('a', { href: `/brand/${p.brand_slug}`, class: 'text-[13px] font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)]' }, p.brand_name),
              i === 0 ? h('div', { class: 'text-[10px] uppercase tracking-wide font-bold text-[color:var(--color-verified)] mt-0.5' }, 'Best price') : null,
            ]),
            h('td', { class: 'px-3 py-3 text-right ui-mono text-[13px] whitespace-nowrap' },
              p.pmap_price
                ? [h('span', { class: 'text-emerald-700 font-semibold' }, fmtPrice(p.pmap_price)),
                   h('div', { class: 'text-[9px] text-emerald-600 uppercase tracking-wide' }, `code ${p.brand_coupon_code || 'PMAP'}`)]
                : fmtPrice(p.effective_price)
            ),
            h('td', { class: 'pl-3 pr-5 py-3 text-right' }, [
              h('a', {
                href: withSrc(p.go_url),
                target: '_blank',
                rel: 'noopener noreferrer nofollow sponsored',
                class: 'ui-focus inline-flex items-center gap-1 h-8 px-3 rounded-md text-[11px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] hover:-translate-y-[1px] transition-all',
              }, 'Buy →'),
            ]),
          ]))),
        ]) : h('div', { class: 'p-6 text-[13px] text-[color:var(--color-ink-subtle)] italic text-center' }, 'No in-stock listings.'),
      ])
    }
  },
})
</script>
