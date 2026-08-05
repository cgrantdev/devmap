<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- Breadcrumb -->
    <nav class="max-w-[1024px] mx-auto px-6 lg:px-10 pt-6 text-[12px] text-[color:var(--color-ink-subtle)]" aria-label="Breadcrumb">
      <ol class="flex items-center gap-1.5">
        <li><a href="/" class="hover:text-[color:var(--color-ink)]">Home</a></li>
        <li>/</li>
        <li><a href="/vendors" class="hover:text-[color:var(--color-ink)]">Vendors</a></li>
        <li>/</li>
        <li class="text-[color:var(--color-ink-muted)] font-medium">Integration</li>
      </ol>
    </nav>

    <!-- Header -->
    <section class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1024px] mx-auto px-6 lg:px-10 pt-6 pb-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-biotech-600)] mb-3">For vendor IT teams</div>
        <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-3">
          Vendor Integration Guide
        </h1>
        <p class="text-[color:var(--color-ink-muted)] leading-relaxed text-lg max-w-3xl">
          Three ways to sync your product catalog with Peptidemap. Pick the one that fits your infrastructure — most vendors ship in under a day.
        </p>
      </div>
    </section>

    <!-- Choose your path -->
    <section class="max-w-[1024px] mx-auto px-6 lg:px-10 pt-10 pb-6">
      <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">Choose a path</div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <a href="#json-feed" class="block p-5 rounded-[12px] border-2 border-emerald-200 bg-emerald-50/40 hover:border-emerald-400 transition-colors">
          <div class="text-[10px] uppercase tracking-wider font-bold text-emerald-700 mb-1.5">Recommended</div>
          <div class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-1">1. JSON Feed</div>
          <div class="text-[12px] text-[color:var(--color-ink-muted)]">You host a public /products.json endpoint. We poll daily.</div>
          <div class="text-[11px] text-emerald-700 font-semibold mt-2">Your effort: ~4 hrs</div>
        </a>
        <a href="#push-api" class="block p-5 rounded-[12px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] transition-colors">
          <div class="text-[10px] uppercase tracking-wider font-bold text-[color:var(--color-ink-subtle)] mb-1.5">On request</div>
          <div class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-1">2. Push API</div>
          <div class="text-[12px] text-[color:var(--color-ink-muted)]">You POST products to us whenever your catalog changes. Webhook flow.</div>
          <div class="text-[11px] text-[color:var(--color-ink-subtle)] font-semibold mt-2">Your effort: ~6 hrs</div>
        </a>
        <a href="#custom-scraper" class="block p-5 rounded-[12px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] transition-colors">
          <div class="text-[10px] uppercase tracking-wider font-bold text-[color:var(--color-ink-subtle)] mb-1.5">Bespoke</div>
          <div class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-1">3. Custom Scraper</div>
          <div class="text-[12px] text-[color:var(--color-ink-muted)]">Your API already exists. We adapt to your shape.</div>
          <div class="text-[11px] text-[color:var(--color-ink-subtle)] font-semibold mt-2">Your effort: 0 hrs</div>
        </a>
      </div>
    </section>

    <!-- Already supported platforms -->
    <section class="max-w-[1024px] mx-auto px-6 lg:px-10 pt-6 pb-10">
      <div class="p-5 rounded-[10px] bg-[color:var(--color-bg)] border border-[color:var(--color-hairline)]">
        <div class="text-[11px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mb-2">Already supported out of the box</div>
        <div class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
          <strong class="text-[color:var(--color-ink)]">WooCommerce · WordPress REST · BigCommerce · Medusa · Shopify (via JSON-LD) · generic JSON feed · XML feed</strong>. If you're on any of these, we can plug in with just the store URL and (if required) an API key — no dev work on your side.
        </div>
      </div>
    </section>

    <!-- Path 1: JSON Feed -->
    <section id="json-feed" class="border-t border-[color:var(--color-hairline)] scroll-mt-24">
      <div class="max-w-[1024px] mx-auto px-6 lg:px-10 py-10">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">Recommended</span>
          <span class="text-[11px] text-[color:var(--color-ink-subtle)]">Your effort: ~4 hrs</span>
        </div>
        <h2 class="ui-display text-2xl md:text-3xl font-semibold text-[color:var(--color-ink)] mb-3">Path 1 · JSON Feed</h2>
        <p class="text-[color:var(--color-ink-muted)] leading-relaxed mb-6">
          Host a public JSON endpoint on your own infrastructure. Peptidemap polls it once daily, diffs against last run, and updates our directory with your latest prices, stock, and product info. Zero coupling — you can regenerate or cache the file however you want on your side.
        </p>

        <h3 class="text-[13px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mb-2">Endpoint</h3>
        <CodeBlock language="http" :code="`GET https://your-domain.com/peptidemap/products.json\nContent-Type: application/json\n(Bearer or Basic auth optional — tell us and we&#39;ll add the header)`" />

        <h3 class="text-[13px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mt-6 mb-2">Response schema</h3>
        <CodeBlock language="json" :code="jsonFeedExample" />

        <h3 class="text-[13px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mt-6 mb-3">Field reference</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-[13px] border border-[color:var(--color-hairline)] rounded overflow-hidden">
            <thead class="bg-[color:var(--color-bg)]">
              <tr>
                <th class="text-left px-3 py-2 font-semibold text-[color:var(--color-ink-subtle)] w-40">Field</th>
                <th class="text-left px-3 py-2 font-semibold text-[color:var(--color-ink-subtle)] w-24">Required</th>
                <th class="text-left px-3 py-2 font-semibold text-[color:var(--color-ink-subtle)]">Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in jsonFieldRows" :key="row.field" class="border-t border-[color:var(--color-hairline-soft)]">
                <td class="px-3 py-2 ui-mono text-[12px] text-[color:var(--color-ink)]">{{ row.field }}</td>
                <td class="px-3 py-2">
                  <span :class="row.required ? 'text-red-600' : 'text-[color:var(--color-ink-subtle)]'" class="text-[11px] uppercase font-semibold">{{ row.required ? 'Required' : 'Optional' }}</span>
                </td>
                <td class="px-3 py-2 text-[color:var(--color-ink-muted)]">{{ row.notes }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <h3 class="text-[13px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mt-6 mb-3">Requirements</h3>
        <ul class="space-y-2 text-[14px] text-[color:var(--color-ink-muted)]">
          <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">✓</span>Publicly accessible HTTPS URL (or add auth — see below)</li>
          <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">✓</span>Full catalog per response — we diff on <code class="ui-mono text-[12px] bg-[color:var(--color-bg)] px-1 rounded">external_id</code>, so removed items disappear correctly</li>
          <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">✓</span>Prices in USD, no currency symbols in the number</li>
          <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">✓</span><code class="ui-mono text-[12px] bg-[color:var(--color-bg)] px-1 rounded">image_url</code> must be publicly accessible (no signed URLs that expire)</li>
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-ink-subtle)] mt-1">·</span>NDJSON also accepted (one product per line) if your catalog is huge</li>
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-ink-subtle)] mt-1">·</span>Auth: we support <code class="ui-mono text-[12px] bg-[color:var(--color-bg)] px-1 rounded">Authorization: Bearer &lt;token&gt;</code> or Basic — just tell us</li>
        </ul>
      </div>
    </section>

    <!-- Path 2: Push API -->
    <section id="push-api" class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)] scroll-mt-24">
      <div class="max-w-[1024px] mx-auto px-6 lg:px-10 py-10">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded bg-slate-200 text-slate-700">On request</span>
          <span class="text-[11px] text-[color:var(--color-ink-subtle)]">Your effort: ~6 hrs</span>
        </div>
        <h2 class="ui-display text-2xl md:text-3xl font-semibold text-[color:var(--color-ink)] mb-3">Path 2 · Push API</h2>
        <p class="text-[color:var(--color-ink-muted)] leading-relaxed mb-6">
          Instead of us pulling, you push. Whenever your catalog changes on your side (new product, price update, stock change), your system POSTs to our endpoint. We upsert and reflect in the directory within seconds. Good fit if you want near-realtime pricing on Peptidemap or your platform can't host a public JSON file.
        </p>

        <div class="p-4 rounded-[8px] bg-amber-50 border border-amber-200 text-[13px] text-amber-800 mb-6">
          <strong>Available on request.</strong> We generate a per-vendor API key on your first sync. Ping <a href="mailto:info@peptidemap.com" class="underline font-semibold">info@peptidemap.com</a> and we'll spin one up.
        </div>

        <h3 class="text-[13px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mb-2">Endpoint</h3>
        <CodeBlock language="http" :code="pushApiExample" />

        <h3 class="text-[13px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mt-6 mb-2">Response</h3>
        <CodeBlock language="json" :code="`{\n  &quot;received&quot;: 42,\n  &quot;created&quot;: 3,\n  &quot;updated&quot;: 39,\n  &quot;errors&quot;: []\n}`" />

        <h3 class="text-[13px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mt-6 mb-3">Notes</h3>
        <ul class="space-y-2 text-[14px] text-[color:var(--color-ink-muted)]">
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-ink-subtle)] mt-1">·</span>Same product schema as the JSON feed above — send one or many per POST</li>
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-ink-subtle)] mt-1">·</span>Rate limit: 60 requests/minute per API key</li>
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-ink-subtle)] mt-1">·</span>To delete a product, send it with <code class="ui-mono text-[12px] bg-white px-1 rounded">"status": "discontinued"</code></li>
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-ink-subtle)] mt-1">·</span>Idempotent by <code class="ui-mono text-[12px] bg-white px-1 rounded">external_id</code> — safe to retry</li>
        </ul>
      </div>
    </section>

    <!-- Path 3: Custom scraper -->
    <section id="custom-scraper" class="border-t border-[color:var(--color-hairline)] scroll-mt-24">
      <div class="max-w-[1024px] mx-auto px-6 lg:px-10 py-10">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded bg-slate-200 text-slate-700">Bespoke</span>
          <span class="text-[11px] text-[color:var(--color-ink-subtle)]">Your effort: 0 hrs</span>
        </div>
        <h2 class="ui-display text-2xl md:text-3xl font-semibold text-[color:var(--color-ink)] mb-3">Path 3 · Custom Scraper</h2>
        <p class="text-[color:var(--color-ink-muted)] leading-relaxed mb-6">
          If you already expose product data through your own API — internal or otherwise — we can adapt to your shape rather than asking you to build to ours. We've done this for BigCommerce, Medusa v2, WooCommerce with variants, and Shopify themes emitting Product JSON-LD schema.
        </p>

        <h3 class="text-[13px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mb-3">What we need from you</h3>
        <ul class="space-y-2 text-[14px] text-[color:var(--color-ink-muted)]">
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-accent-600)] mt-1">→</span>Endpoint URL(s) — listing + product detail</li>
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-accent-600)] mt-1">→</span>Auth scheme (API key header, OAuth, or none)</li>
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-accent-600)] mt-1">→</span>A sample response so we can map fields to our schema</li>
          <li class="flex items-start gap-2"><span class="text-[color:var(--color-accent-600)] mt-1">→</span>Rate limit or preferred cadence (default is daily)</li>
        </ul>

        <div class="mt-6 p-4 rounded-[8px] bg-[color:var(--color-bg)] border border-[color:var(--color-hairline)] text-[13px] text-[color:var(--color-ink-muted)]">
          Typical lead time is 1–3 business days from receiving your docs. If your platform is one of the ones already supported (see top of page), no custom work needed — we just wire it up with your credentials.
        </div>
      </div>
    </section>

    <!-- Contact -->
    <section class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-ink)]">
      <div class="max-w-[1024px] mx-auto px-6 lg:px-10 py-10 text-center">
        <h2 class="ui-display text-2xl md:text-3xl font-semibold text-white mb-3">Ready to connect?</h2>
        <p class="text-white/70 leading-relaxed mb-6 max-w-2xl mx-auto">
          Email us at <a href="mailto:info@peptidemap.com" class="text-[color:var(--color-accent-400)] underline font-semibold">info@peptidemap.com</a> with which path you want and any docs you already have. We usually reply same-day.
        </p>
        <a href="mailto:info@peptidemap.com?subject=Vendor%20integration%20-%20&body=Which%20path%3A%20(JSON%20feed%20%2F%20Push%20API%20%2F%20Custom%20scraper)%0A%0ACompany%3A%0AWebsite%3A%0AWe%20already%20have%3A%20"
          class="ui-focus inline-flex items-center gap-2 h-11 px-6 rounded-[10px] bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] text-white font-semibold text-[14px] hover:-translate-y-[0.5px] transition-all">
          Email us
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, defineComponent, h } from 'vue'
import ModernLayout from '../Layouts/ModernLayout.vue'

defineProps({
  seo: { type: Object, default: () => ({}) },
})

const jsonFieldRows = [
  { field: 'external_id', required: true, notes: 'Your stable SKU. We dedupe on this — never change it once assigned.' },
  { field: 'name', required: true, notes: 'Product name as it appears in your catalog.' },
  { field: 'url', required: true, notes: 'Direct link to the product page on your site.' },
  { field: 'price', required: true, notes: 'USD, no currency symbol. Numeric.' },
  { field: 'sale_price', required: false, notes: 'If set + lower than price, we show the sale price with a strikethrough on retail.' },
  { field: 'image_url', required: false, notes: 'Publicly accessible URL. HTTPS preferred.' },
  { field: 'description', required: false, notes: 'Plain text or HTML — we strip tags on ingest.' },
  { field: 'in_stock', required: false, notes: 'true / false. Defaults to true if omitted.' },
  { field: 'size_mg', required: false, notes: 'e.g. "10mg", "5mg/5mg", "30mL", "1000 IU". Free-text string.' },
  { field: 'variants[]', required: false, notes: 'For multi-size products — array of {external_id, price, sale_price, size_mg, in_stock}. Each variant becomes its own row on Peptidemap.' },
]

const jsonFeedExample = `{
  "products": [
    {
      "external_id": "BIO-BPC157-10MG",
      "name": "BPC-157 10mg",
      "url": "https://example.com/products/bpc-157-10mg",
      "image_url": "https://example.com/img/bpc-157.jpg",
      "description": "Body Protective Compound 157, 10mg vial.",
      "in_stock": true,

      // For a single-price product:
      "price": 39.99,
      "sale_price": 29.99
    },
    {
      "external_id": "BIO-TB500",
      "name": "TB-500",
      "url": "https://example.com/products/tb-500",
      "image_url": "https://example.com/img/tb-500.jpg",
      "description": "Thymosin Beta-4 fragment.",

      // OR variant-keyed for multi-size products:
      "variants": [
        { "external_id": "BIO-TB500-5MG",  "price": 22.99, "size_mg": "5mg",  "in_stock": true },
        { "external_id": "BIO-TB500-10MG", "price": 39.99, "size_mg": "10mg", "in_stock": true },
        { "external_id": "BIO-TB500-20MG", "price": 69.99, "size_mg": "20mg", "in_stock": false }
      ]
    }
  ]
}`

const pushApiExample = `POST https://peptidemap.com/api/vendor/products
Authorization: Bearer <your-api-key>
Content-Type: application/json

{
  "products": [
    { "external_id": "BIO-BPC157-10MG", "name": "BPC-157 10mg",
      "url": "https://example.com/products/bpc-157-10mg",
      "price": 39.99, "in_stock": true }
  ]
}`

/**
 * Read-only code block with a copy-to-clipboard button. Small enough not
 * to justify a shared component yet — inline here.
 */
const CodeBlock = defineComponent({
  props: { code: { type: String, required: true }, language: { type: String, default: '' } },
  setup(props) {
    const copied = ref(false)
    const copy = async () => {
      try {
        await navigator.clipboard.writeText(props.code)
        copied.value = true
        setTimeout(() => { copied.value = false }, 1800)
      } catch {
        // Silent fail — code is visible on-page anyway.
      }
    }
    return () => h('div', { class: 'relative group' }, [
      h('button', {
        onClick: copy,
        class: 'absolute top-2 right-2 h-7 px-2 rounded text-[11px] font-semibold bg-white/10 hover:bg-white/20 text-white/80 hover:text-white transition-colors z-10',
      }, copied.value ? '✓ Copied' : 'Copy'),
      h('pre', { class: 'bg-[#0F172A] text-slate-100 rounded-[10px] p-4 pr-16 overflow-x-auto text-[12px] leading-relaxed font-mono whitespace-pre' },
        h('code', {}, props.code)
      ),
    ])
  },
})
</script>
