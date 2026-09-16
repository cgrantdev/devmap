<template>
  <Head>
    <title>{{ seo.title }}</title>
    <meta name="description" :content="seo.description" />
    <link rel="canonical" :href="seo.canonical" />
    <meta property="og:type" content="website" />
    <meta property="og:title" :content="seo.og_title" />
    <meta property="og:description" :content="seo.og_description" />
    <meta property="og:url" :content="seo.url" />
  </Head>
  <ModernLayout>
    <section class="max-w-[820px] mx-auto px-5 lg:px-8 pt-10 pb-16">
      <!-- Breadcrumb -->
      <nav class="text-[12px] text-[color:var(--color-ink-subtle)] mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center gap-1.5">
          <li><a href="/" class="hover:text-[color:var(--color-ink-muted)]">Home</a></li>
          <li aria-hidden="true">/</li>
          <li><a href="/vendors" class="hover:text-[color:var(--color-ink-muted)]">Vendors</a></li>
          <li aria-hidden="true">/</li>
          <li class="text-[color:var(--color-ink-muted)] font-medium">{{ brand.name }} coupon</li>
        </ol>
      </nav>

      <!-- Hero — the H1 is the exact-match query pattern -->
      <div class="flex items-center gap-4 mb-6">
        <div v-if="brand.logo" class="w-14 h-14 rounded-[10px] overflow-hidden border border-[color:var(--color-hairline)] bg-white p-1 flex-shrink-0">
          <img :src="brand.logo" :alt="brand.name" class="w-full h-full object-contain" />
        </div>
        <div class="min-w-0">
          <div class="text-[11px] uppercase tracking-[0.14em] font-semibold text-emerald-700 mb-1">Verified coupon</div>
          <h1 class="ui-display text-3xl md:text-4xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] leading-[1.1]">
            {{ brand.name }} Coupon Code
          </h1>
        </div>
      </div>

      <!-- Big code card -->
      <div class="rounded-[16px] border-2 border-dashed border-emerald-400 bg-gradient-to-b from-emerald-50 to-white p-6 md:p-8 mb-6">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
          <div class="min-w-0">
            <div class="text-[11px] uppercase tracking-[0.14em] font-bold text-emerald-700 mb-2">Code</div>
            <div class="ui-mono font-black text-4xl md:text-5xl tracking-wider text-emerald-900 leading-none select-all">{{ coupon.code }}</div>
            <p v-if="coupon.percent_off" class="mt-3 text-emerald-800 text-[14px]">
              Save <strong class="font-bold text-emerald-900">{{ coupon.percent_off }}%</strong> across
              <strong class="font-semibold">{{ coupon.product_count }} products</strong> at {{ brand.name }}.
            </p>
            <p v-else class="mt-3 text-emerald-800 text-[14px]">
              Applied at checkout across <strong class="font-semibold">{{ coupon.product_count }} products</strong>.
            </p>
          </div>
          <div class="flex flex-col gap-2 w-full md:w-auto md:min-w-[220px]">
            <button
              type="button"
              @click="copyCode"
              class="ui-focus inline-flex items-center justify-center gap-2 h-11 px-5 rounded-[10px] bg-emerald-600 hover:bg-emerald-700 text-white text-[14px] font-semibold transition-colors shadow-sm"
            >
              <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              {{ copied ? 'Copied' : 'Copy code' }}
            </button>
            <a
              v-if="coupon.affiliate_url"
              :href="coupon.affiliate_url"
              target="_blank"
              rel="noopener sponsored"
              class="ui-focus inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-[10px] border border-emerald-600 text-emerald-800 hover:bg-emerald-50 text-[14px] font-semibold transition-colors"
            >
              Shop {{ brand.name }}
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- How to use -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-8">
        <div class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white p-4">
          <div class="ui-mono text-[11px] text-[color:var(--color-ink-subtle)] mb-1">Step 1</div>
          <div class="text-[13px] font-semibold text-[color:var(--color-ink)]">Copy the code</div>
          <p class="text-[12px] text-[color:var(--color-ink-muted)] mt-1 leading-relaxed">Tap the copy button above. Code is <span class="ui-mono font-semibold">{{ coupon.code }}</span>.</p>
        </div>
        <div class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white p-4">
          <div class="ui-mono text-[11px] text-[color:var(--color-ink-subtle)] mb-1">Step 2</div>
          <div class="text-[13px] font-semibold text-[color:var(--color-ink)]">Shop {{ brand.name }}</div>
          <p class="text-[12px] text-[color:var(--color-ink-muted)] mt-1 leading-relaxed">Add products to your cart at the {{ brand.name }} storefront.</p>
        </div>
        <div class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white p-4">
          <div class="ui-mono text-[11px] text-[color:var(--color-ink-subtle)] mb-1">Step 3</div>
          <div class="text-[13px] font-semibold text-[color:var(--color-ink)]">Paste at checkout</div>
          <p class="text-[12px] text-[color:var(--color-ink-muted)] mt-1 leading-relaxed">
            <span v-if="coupon.percent_off">{{ coupon.percent_off }}%</span>
            <span v-else>your</span> discount applies automatically.
          </p>
        </div>
      </div>

      <!-- FAQ / body copy for query-match depth -->
      <div class="space-y-5">
        <div>
          <h2 class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-2">Is the {{ brand.name }} coupon code {{ coupon.code }} legit?</h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
            Yes — {{ coupon.code }} is the verified Peptidemap code for {{ brand.name }}. We're a partner, and every code on Peptidemap is tested by the vendor before it goes live.
            <span v-if="coupon.percent_off">It gives you {{ coupon.percent_off }}% off at checkout.</span>
          </p>
        </div>
        <div>
          <h2 class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-2">Does the discount stack with other promotions?</h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
            {{ brand.name }} may run additional sitewide sales; those usually stack on top of {{ coupon.code }}. Their <a :href="'/brand/' + brand.slug" class="text-[color:var(--color-accent-600)] hover:underline">storefront page</a> shows any live promotions.
          </p>
        </div>
        <div v-if="coupon.lowest_price">
          <h2 class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-2">What's the cheapest product at {{ brand.name }}?</h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
            The lowest-priced active product from {{ brand.name }} on Peptidemap is <strong class="ui-mono text-[color:var(--color-ink)]">${{ coupon.lowest_price.toFixed(2) }}</strong> — code {{ coupon.code }} applies on top.
          </p>
        </div>
        <div>
          <h2 class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-2">Is {{ brand.name }} legitimate?</h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
            {{ brand.name }} is a Peptidemap-listed vendor. Read reviews and browse their full catalog on the
            <a :href="'/brand/' + brand.slug" class="text-[color:var(--color-accent-600)] hover:underline">{{ brand.name }} storefront</a>.
          </p>
        </div>
      </div>

      <p class="mt-10 text-[11px] text-[color:var(--color-ink-subtle)] leading-relaxed">
        All products for research use only (RUO). Peptidemap may earn a commission when you use this code.
      </p>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

const props = defineProps({
  brand: { type: Object, required: true },
  coupon: { type: Object, required: true },
  seo: { type: Object, required: true },
})

const copied = ref(false)
function copyCode() {
  try {
    navigator.clipboard?.writeText(props.coupon.code)
  } catch (_) { /* clipboard blocked — user can still select-all */ }
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}
</script>
