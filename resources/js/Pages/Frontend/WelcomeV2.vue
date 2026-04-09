<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- ========================================================= -->
    <!-- 1. HERO                                                    -->
    <!-- ========================================================= -->
    <section class="relative overflow-hidden">
      <div class="ui-spotlight absolute inset-0 pointer-events-none" />
      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-10 pt-20 pb-28 lg:pt-28 lg:pb-36">
        <div class="max-w-3xl">
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-[color:var(--color-hairline)] bg-white/60 backdrop-blur text-xs font-medium text-[color:var(--color-ink-muted)] mb-6">
            <span class="relative flex h-1.5 w-1.5">
              <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[color:var(--color-verified)] opacity-60" />
              <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-[color:var(--color-verified)]" />
            </span>
            Verification engine live · Updated continuously
          </div>

          <h1 class="ui-display text-5xl md:text-6xl lg:text-7xl font-semibold leading-[0.95] tracking-[-0.02em] text-[color:var(--color-ink)]">
            The definitive platform for
            <span class="ui-gradient-text">research peptide vendors.</span>
          </h1>

          <p class="mt-6 text-lg md:text-xl text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl">
            Compare verified suppliers, inspect lab testing, and discover new compounds — all in one place.
          </p>

          <div class="mt-10 flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <Button as="a" href="/vendors" variant="primary" size="lg">
              Explore verified vendors
              <template #icon-right>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
              </template>
            </Button>
            <Button as="a" href="#how-verification-works" variant="ghost" size="lg">
              How verification works
            </Button>
          </div>

          <!-- Trust row -->
          <div class="mt-16 pt-8 border-t border-[color:var(--color-hairline)] grid grid-cols-2 md:grid-cols-4 gap-8">
            <Stat label="Verified vendors" :value="formatNumber(stats.verified_vendors)" size="md" />
            <Stat label="Compounds tracked" :value="formatNumber(stats.compounds)" size="md" />
            <Stat label="Product categories" :value="formatNumber(stats.categories)" size="md" />
            <Stat label="Total vendors" :value="formatNumber(stats.total_vendors)" size="md" />
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 2. LIVE PRICE TICKER                                       -->
    <!-- ========================================================= -->
    <section v-if="tickerItems.length" class="border-y border-[color:var(--color-hairline)] bg-white overflow-hidden">
      <div class="flex gap-12 py-4 ui-marquee whitespace-nowrap">
        <template v-for="loop in 2" :key="loop">
          <div v-for="item in tickerItems" :key="`${loop}-${item.name}`" class="flex items-center gap-3 text-sm">
            <span class="text-[color:var(--color-ink)] font-medium">{{ item.name }}</span>
            <span class="text-[color:var(--color-ink-subtle)]">·</span>
            <span class="text-[color:var(--color-ink-muted)]">{{ item.brand }}</span>
            <span class="text-[color:var(--color-ink-subtle)]">·</span>
            <span class="ui-mono text-[color:var(--color-accent-600)]">from ${{ formatPrice(item.price) }}</span>
          </div>
        </template>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 3. VERIFIED VENDORS                                        -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-24 lg:py-32">
      <div class="flex items-end justify-between mb-12 gap-4 flex-wrap">
        <div class="max-w-xl">
          <div class="text-xs uppercase tracking-[0.1em] font-semibold text-[color:var(--color-accent-600)] mb-3">
            Verified · Tested · Transparent
          </div>
          <h2 class="ui-display text-3xl md:text-4xl font-semibold tracking-tight text-[color:var(--color-ink)] mb-3">
            Vendors we've verified
          </h2>
          <p class="text-[color:var(--color-ink-muted)] leading-relaxed">
            Every vendor passes our verification checklist: independent lab testing, COA transparency, and response-time audits.
          </p>
        </div>
        <Button as="a" href="/vendors" variant="secondary">
          All vendors
          <template #icon-right>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M13 5l7 7-7 7"/>
            </svg>
          </template>
        </Button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <VendorCard v-for="vendor in verifiedVendors" :key="vendor.id" :vendor="vendor" />
      </div>

      <div v-if="!verifiedVendors.length" class="text-center py-20 text-[color:var(--color-ink-subtle)]">
        No verified vendors yet.
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 4. TRENDING COMPOUNDS                                      -->
    <!-- ========================================================= -->
    <section class="bg-white border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-24 lg:py-32">
        <div class="flex items-end justify-between mb-12 gap-4 flex-wrap">
          <div class="max-w-xl">
            <div class="text-xs uppercase tracking-[0.1em] font-semibold text-[color:var(--color-biotech-600)] mb-3">
              Most researched
            </div>
            <h2 class="ui-display text-3xl md:text-4xl font-semibold tracking-tight text-[color:var(--color-ink)] mb-3">
              Trending compounds
            </h2>
            <p class="text-[color:var(--color-ink-muted)] leading-relaxed">
              Highest-rated research peptides across our verified vendor network, updated daily.
            </p>
          </div>
          <Button as="a" href="/products" variant="secondary">
            Browse all
            <template #icon-right>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
              </svg>
            </template>
          </Button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          <ProductCard v-for="product in trendingProducts" :key="product.id" :product="product" />
        </div>

        <div v-if="!trendingProducts.length" class="text-center py-20 text-[color:var(--color-ink-subtle)]">
          No products loaded.
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 5. TRUST BAR (why us)                                      -->
    <!-- ========================================================= -->
    <section id="how-verification-works" class="max-w-[1280px] mx-auto px-6 lg:px-10 py-24 lg:py-32">
      <div class="max-w-2xl mb-14">
        <div class="text-xs uppercase tracking-[0.1em] font-semibold text-[color:var(--color-accent-600)] mb-3">
          Why PeptideMap
        </div>
        <h2 class="ui-display text-3xl md:text-4xl font-semibold tracking-tight text-[color:var(--color-ink)]">
          Data-first. Not marketing-first.
        </h2>
      </div>

      <TrustBar :items="trustItems" />
    </section>

    <!-- ========================================================= -->
    <!-- 6. EDITORIAL / RESEARCH                                    -->
    <!-- ========================================================= -->
    <section v-if="editorial.length" class="bg-white border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-24 lg:py-32">
        <div class="flex items-end justify-between mb-12 gap-4 flex-wrap">
          <div class="max-w-xl">
            <div class="text-xs uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-muted)] mb-3">
              Research & education
            </div>
            <h2 class="ui-display text-3xl md:text-4xl font-semibold tracking-tight text-[color:var(--color-ink)]">
              Latest insights
            </h2>
          </div>
          <Button as="a" href="/news" variant="link">
            All research
            <template #icon-right>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
              </svg>
            </template>
          </Button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <a
            v-for="post in editorial"
            :key="post.id"
            :href="`/blog/${post.slug}`"
            class="ui-focus group"
          >
            <div class="aspect-[16/10] rounded-[14px] overflow-hidden bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] mb-5">
              <img
                v-if="post.image"
                :src="post.image"
                :alt="post.title"
                class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-[400ms] ease-out"
                loading="lazy"
              />
            </div>
            <div class="flex items-center gap-2 text-xs text-[color:var(--color-ink-subtle)] mb-2 ui-mono">
              <span>{{ post.date }}</span>
              <span>·</span>
              <span>{{ post.read_time }}</span>
            </div>
            <h3 class="ui-display text-xl font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug mb-2 group-hover:text-[color:var(--color-accent-600)] transition-colors">
              {{ post.title }}
            </h3>
            <p v-if="post.excerpt" class="text-sm text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-2">
              {{ post.excerpt }}
            </p>
          </a>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 7. VENDOR CTA                                              -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-24 lg:py-32">
      <div class="relative rounded-[20px] overflow-hidden border border-[color:var(--color-hairline)] bg-gradient-to-br from-[color:var(--color-ink)] to-[color:var(--color-accent-700)] p-12 md:p-16">
        <div class="absolute inset-0 ui-spotlight pointer-events-none opacity-50" />
        <div class="relative max-w-2xl">
          <div class="text-xs uppercase tracking-[0.1em] font-semibold text-[color:var(--color-accent-400)] mb-4">
            For vendors
          </div>
          <h2 class="ui-display text-3xl md:text-5xl font-semibold tracking-tight text-white leading-[1.05] mb-6">
            Run a research peptide business? Get listed.
          </h2>
          <p class="text-white/70 text-lg leading-relaxed mb-10">
            Connect your WooCommerce store in minutes. Automatic product sync, verified badge review, detailed analytics. Free during beta.
          </p>
          <div class="flex flex-col sm:flex-row gap-3">
            <Button as="a" href="/become-a-vendor" variant="primary" size="lg">
              Apply to be listed
              <template #icon-right>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
              </template>
            </Button>
            <Button as="a" href="/vendor/login" variant="secondary" size="lg" class="!bg-white/10 !border-white/20 !text-white hover:!bg-white/15">
              Vendor login
            </Button>
          </div>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import Button from '@/components/ui/Button.vue'
import Stat from '@/components/ui/Stat.vue'
import VendorCard from '@/components/ui/VendorCard.vue'
import ProductCard from '@/components/ui/ProductCard.vue'
import TrustBar from '@/components/ui/TrustBar.vue'

defineProps({
  stats: { type: Object, default: () => ({}) },
  verifiedVendors: { type: Array, default: () => [] },
  trendingProducts: { type: Array, default: () => [] },
  tickerItems: { type: Array, default: () => [] },
  editorial: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

const trustItems = [
  {
    icon: 'shield',
    title: 'Verified.',
    body: 'Every vendor passes our verification checklist: independent lab testing, COA review, and response-time audits before they appear on the platform.',
  },
  {
    icon: 'eye',
    title: 'Transparent.',
    body: 'Price per mg, batch data, lab tests, shipping windows — no marketing noise. Just the numbers you need to make a research decision.',
  },
  {
    icon: 'grid',
    title: 'Comparable.',
    body: 'Side-by-side compound comparison across every vendor in one click. Find the best price, the best purity, and the fastest shipping — instantly.',
  },
]

function formatNumber(n) {
  if (n === null || n === undefined) return '—'
  return new Intl.NumberFormat().format(n)
}

function formatPrice(p) {
  if (p === null || p === undefined || p === '') return '—'
  const num = typeof p === 'number' ? p : parseFloat(p)
  return isNaN(num) ? String(p) : num.toFixed(2)
}
</script>
