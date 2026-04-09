<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- ========================================================= -->
    <!-- HERO CAROUSEL                                              -->
    <!-- ========================================================= -->
    <section class="relative">
      <div class="ui-spotlight absolute inset-0 pointer-events-none" />
      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-10 pt-10 lg:pt-14">
        <HeroCarousel :slides="heroSlides" />
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- STATS ROW — big numbers, centered, label below             -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-14 lg:py-16">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-12">
        <Stat
          :value="formatNumber(stats.verified_vendors)"
          label="Verified vendors"
          size="lg"
          centered
        />
        <Stat
          :value="formatNumber(stats.compounds)"
          label="Compounds tracked"
          size="lg"
          centered
        />
        <Stat
          :value="formatNumber(stats.categories)"
          label="Product categories"
          size="lg"
          centered
        />
        <Stat
          :value="formatNumber(stats.total_vendors)"
          label="Total vendors"
          size="lg"
          centered
        />
      </div>
    </section>

    <!-- Brand strip — no label, just logos -->
    <section
      v-if="brandStripVendors.length"
      class="bg-white border-y border-[color:var(--color-hairline)]"
    >
      <BrandStrip :brands="brandStripVendors" />
    </section>

    <!-- ========================================================= -->
    <!-- AD SLOT #1 (thin, after brand strip)                       -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-12 lg:pt-14">
      <AdSlot
        title="Your brand, front and center on PeptideMap."
        subtitle="Premium mid-page placement. ~3× the CTR of footer ads."
      />
    </section>

    <!-- ========================================================= -->
    <!-- VERIFIED VENDORS                                           -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
      <SectionHeader
        eyebrow="Our vendor network"
        title="Top-rated peptide vendors"
        description="Browse the highest-rated research peptide suppliers on PeptideMap, ranked by reviews and compound selection."
        accent="accent"
      >
        <template #cta>
          <Button as="a" href="/vendors" variant="secondary">
            All vendors
            <template #icon-right>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
              </svg>
            </template>
          </Button>
        </template>
      </SectionHeader>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <VendorCard v-for="vendor in verifiedVendors" :key="vendor.id" :vendor="vendor" />
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- TRENDING COMPOUNDS — FULL-BLEED DARK SECTION               -->
    <!-- ========================================================= -->
    <section class="relative bg-[#0A0B0E] text-white overflow-hidden">
      <!-- Decorative orbs -->
      <div class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full bg-[color:var(--color-accent-500)] opacity-[0.12] blur-[120px] pointer-events-none" />
      <div class="absolute -bottom-40 -right-40 w-[600px] h-[600px] rounded-full bg-[color:var(--color-biotech-500)] opacity-[0.12] blur-[120px] pointer-events-none" />
      <div
        class="absolute inset-0 opacity-[0.025] pointer-events-none"
        :style="{
          backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)',
          backgroundSize: '48px 48px',
        }"
      />

      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-10 py-20 lg:py-24">
        <div class="flex items-end justify-between gap-6 flex-wrap mb-10 md:mb-14">
          <div class="max-w-2xl">
            <div class="text-[11px] uppercase tracking-[0.14em] font-semibold text-[color:var(--color-biotech-400)] mb-3">
              Most researched
            </div>
            <h2 class="ui-display text-[32px] md:text-5xl font-semibold tracking-[-0.02em] text-white leading-[1.05]">
              Trending compounds
            </h2>
            <p class="mt-3 text-white/60 text-base leading-relaxed max-w-xl">
              Highest-rated research peptides across our vendor network, updated daily.
            </p>
          </div>
          <Button as="a" href="/products" variant="secondary" class="!bg-white/10 !border-white/20 !text-white hover:!bg-white/20">
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
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- LIMITED TIME DEALS                                         -->
    <!-- ========================================================= -->
    <section v-if="limitedDeals.length" class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
      <SectionHeader
        eyebrow="Limited time"
        title="Active discount codes"
        description="Verified vendor deals with one-click coupon copy. No sign-ups, no email walls."
        accent="caution"
      >
        <template #cta>
          <Button as="a" href="/deals" variant="secondary">
            All deals
            <template #icon-right>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
              </svg>
            </template>
          </Button>
        </template>
      </SectionHeader>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <DealCard v-for="deal in limitedDeals" :key="deal.id" :deal="deal" />
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- AD SLOT #2                                                 -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-4 pb-8">
      <AdSlot
        title="Launch a new compound on PeptideMap"
        subtitle="New-product spotlight placement. Perfect for seasonal batches."
      />
    </section>

    <!-- ========================================================= -->
    <!-- PEPTIDE ENCYCLOPEDIA                                       -->
    <!-- ========================================================= -->
    <section v-if="encyclopediaCategories.length" class="bg-white border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
        <SectionHeader
          eyebrow="Reference"
          title="Peptide encyclopedia"
          description="Research-grade information on every major peptide category. Mechanism of action, protocols, and vendor availability — all cited."
          accent="muted"
        >
          <template #cta>
            <Button as="a" href="/encyclopedia" variant="secondary">
              Full encyclopedia
              <template #icon-right>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
              </template>
            </Button>
          </template>
        </SectionHeader>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <EncyclopediaCard v-for="entry in encyclopediaCategories" :key="entry.id" :entry="entry" />
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- LATEST INSIGHTS (blogs)                                    -->
    <!-- ========================================================= -->
    <section v-if="editorial.length" class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
      <SectionHeader
        eyebrow="Research & education"
        title="Latest insights"
        description="Peptide research and education from the PeptideMap team and contributors."
        accent="muted"
      >
        <template #cta>
          <Button as="a" href="/news" variant="link">
            All research
            <template #icon-right>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
              </svg>
            </template>
          </Button>
        </template>
      </SectionHeader>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <a
          v-for="post in editorial"
          :key="post.id"
          :href="`/blog/${post.slug}`"
          class="ui-focus group"
        >
          <BlogImage
            :src="post.image"
            :title="post.title"
            :alt="post.title"
            aspect="16/10"
            radius="lg"
            class="mb-5 border border-[color:var(--color-hairline)]"
          />
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
    </section>

    <!-- ========================================================= -->
    <!-- WHY PEPTIDEMAP                                             -->
    <!-- ========================================================= -->
    <section id="how-verification-works" class="bg-white border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
        <SectionHeader
          eyebrow="Why PeptideMap"
          title="Data-first. Not marketing-first."
          accent="accent"
        />
        <TrustBar :items="trustItems" />
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- BECOME A VENDOR — FULL BLEED, BADASS                       -->
    <!-- ========================================================= -->
    <section class="relative bg-[#0A0B0E] text-white overflow-hidden">
      <!-- Background art -->
      <div
        class="absolute inset-0"
        :style="{ background: 'radial-gradient(ellipse 1200px 600px at 20% 30%, rgba(79,70,229,0.35) 0%, transparent 60%), radial-gradient(ellipse 900px 500px at 80% 70%, rgba(59,130,246,0.25) 0%, transparent 60%)' }"
      />
      <!-- Grid pattern -->
      <div
        class="absolute inset-0 opacity-[0.035] pointer-events-none"
        :style="{
          backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)',
          backgroundSize: '56px 56px',
        }"
      />
      <!-- Moving gradient edge highlights -->
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[140%] h-px bg-gradient-to-r from-transparent via-white/25 to-transparent" />
      <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[140%] h-px bg-gradient-to-r from-transparent via-white/15 to-transparent" />

      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-10 py-24 lg:py-32">
        <div class="max-w-4xl">
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/15 bg-white/5 backdrop-blur text-[11px] uppercase tracking-[0.14em] font-semibold text-[color:var(--color-accent-400)] mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-[color:var(--color-accent-400)] animate-pulse" />
            Free during beta
          </div>

          <h2 class="ui-display text-white text-5xl md:text-6xl lg:text-7xl font-semibold tracking-[-0.025em] leading-[0.96] mb-6">
            Scale your peptide<br/>
            <span class="bg-gradient-to-r from-white via-[color:var(--color-accent-400)] to-[color:var(--color-biotech-400)] bg-clip-text text-transparent">
              research business.
            </span>
          </h2>

          <p class="text-white/65 text-lg md:text-xl leading-relaxed max-w-2xl mb-12">
            Join the verified vendor network. Connect your store in minutes. Let researchers find you.
          </p>
        </div>

        <!-- Benefits grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 max-w-5xl">
          <div
            v-for="benefit in vendorBenefits"
            :key="benefit.title"
            class="relative p-6 rounded-[14px] border border-white/10 bg-white/[0.03] backdrop-blur hover:bg-white/[0.05] hover:border-white/20 transition-all duration-[200ms] group"
          >
            <div class="w-10 h-10 rounded-[10px] bg-gradient-to-br from-[color:var(--color-accent-500)] to-[color:var(--color-biotech-500)] flex items-center justify-center mb-4 group-hover:scale-105 transition-transform duration-[200ms]" v-html="benefit.icon" />
            <h3 class="ui-display text-white text-lg font-semibold tracking-tight mb-2">
              {{ benefit.title }}
            </h3>
            <p class="text-white/55 text-sm leading-relaxed">
              {{ benefit.body }}
            </p>
          </div>
        </div>

        <!-- Big CTA bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 sm:gap-8 pt-8 border-t border-white/10">
          <div class="flex items-center gap-4">
            <a
              href="/become-a-vendor"
              class="ui-focus group inline-flex items-center gap-3 h-14 px-8 rounded-[14px] bg-white text-[color:var(--color-ink)] text-base font-semibold hover:bg-white/95 transition-all shadow-[0_0_40px_-10px_rgba(99,102,241,0.5)]"
            >
              Apply to get listed
              <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform duration-[180ms]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
              </svg>
            </a>
            <a
              href="/vendor/login"
              class="ui-focus text-sm text-white/60 hover:text-white font-medium transition-colors"
            >
              Vendor sign in →
            </a>
          </div>
          <div class="flex items-center gap-6 md:ml-auto text-sm text-white/50">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-[color:var(--color-verified)]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12" />
              </svg>
              No credit card
            </div>
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-[color:var(--color-verified)]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12" />
              </svg>
              5-minute setup
            </div>
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
import SectionHeader from '@/components/ui/SectionHeader.vue'
import HeroCarousel from '@/components/ui/HeroCarousel.vue'
import BrandStrip from '@/components/ui/BrandStrip.vue'
import AdSlot from '@/components/ui/AdSlot.vue'
import VendorCard from '@/components/ui/VendorCard.vue'
import ProductCard from '@/components/ui/ProductCard.vue'
import DealCard from '@/components/ui/DealCard.vue'
import EncyclopediaCard from '@/components/ui/EncyclopediaCard.vue'
import BlogImage from '@/components/ui/BlogImage.vue'
import TrustBar from '@/components/ui/TrustBar.vue'

defineProps({
  stats: { type: Object, default: () => ({}) },
  heroSlides: { type: Array, default: () => [] },
  brandStripVendors: { type: Array, default: () => [] },
  verifiedVendors: { type: Array, default: () => [] },
  trendingProducts: { type: Array, default: () => [] },
  limitedDeals: { type: Array, default: () => [] },
  encyclopediaCategories: { type: Array, default: () => [] },
  editorial: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

const trustItems = [
  {
    icon: 'shield',
    title: 'Trusted.',
    body: 'Independent reviews, rating history, and vendor response-time data — all in one place, all transparent.',
  },
  {
    icon: 'eye',
    title: 'Transparent.',
    body: 'Price per mg, batch data, shipping windows — no marketing noise. Just the numbers you need.',
  },
  {
    icon: 'grid',
    title: 'Comparable.',
    body: 'Side-by-side compound comparison across every vendor in one click. Find the best price and purity instantly.',
  },
]

const vendorBenefits = [
  {
    title: 'Auto-sync products',
    body: 'Connect your WooCommerce store once. Your catalog stays in sync — prices, stock, new products.',
    icon: '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 01-9 9m0 0a9 9 0 01-9-9m9 9v-4m0-5V3m0 0a9 9 0 019 9m-9-9a9 9 0 00-9 9"/></svg>',
  },
  {
    title: 'Reach serious researchers',
    body: 'Buyers browsing PeptideMap are comparing vendors, not tire-kicking. Qualified traffic, higher conversion.',
    icon: '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>',
  },
  {
    title: 'Real-time analytics',
    body: 'Track outbound clicks, top compounds, and conversion funnels. Know exactly what PeptideMap sends you.',
    icon: '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 9l-5 5-3-3-5 5"/></svg>',
  },
]

function formatNumber(n) {
  if (n === null || n === undefined) return '—'
  return new Intl.NumberFormat().format(n)
}
</script>
