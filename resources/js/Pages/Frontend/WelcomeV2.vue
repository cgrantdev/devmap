<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- ========================================================= -->
    <!-- HERO CAROUSEL (full-width, paid-placement slots)            -->
    <!-- ========================================================= -->
    <section class="relative">
      <div class="ui-spotlight absolute inset-0 pointer-events-none" />
      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-10 pt-10 lg:pt-14">
        <HeroCarousel :slides="heroSlides" />
      </div>
    </section>

    <!-- STATS ROW (tight, right below hero) -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 mt-10 lg:mt-12 pb-10 lg:pb-12 border-b border-[color:var(--color-hairline)]">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-10">
        <Stat label="Verified vendors" :value="formatNumber(stats.verified_vendors)" size="md" />
        <Stat label="Compounds tracked" :value="formatNumber(stats.compounds)" size="md" />
        <Stat label="Product categories" :value="formatNumber(stats.categories)" size="md" />
        <Stat label="Total vendors" :value="formatNumber(stats.total_vendors)" size="md" />
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- BRAND STRIP (scrolling logos, "trusted by")                -->
    <!-- ========================================================= -->
    <section v-if="brandStripVendors.length" class="bg-white border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-10 pb-6">
        <div class="text-center mb-3">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)]">
            Trusted by {{ brandStripVendors.length }}+ verified vendors
          </div>
        </div>
      </div>
      <BrandStrip :brands="brandStripVendors" />
    </section>

    <!-- ========================================================= -->
    <!-- VERIFIED VENDORS                                           -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
      <SectionHeader
        eyebrow="Verified · Tested · Transparent"
        title="Vendors we've verified"
        description="Every vendor passes our verification checklist: independent lab testing, COA review, and response-time audits."
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
    <!-- TRENDING COMPOUNDS                                         -->
    <!-- ========================================================= -->
    <section class="bg-white border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
        <SectionHeader
          eyebrow="Most researched"
          title="Trending compounds"
          description="Highest-rated research peptides across our verified vendor network, updated daily."
          accent="biotech"
        >
          <template #cta>
            <Button as="a" href="/products" variant="secondary">
              Browse all
              <template #icon-right>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
              </template>
            </Button>
          </template>
        </SectionHeader>

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
    <!-- PEPTIDE ENCYCLOPEDIA (with sponsor slots)                  -->
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
    <!-- TRUST BAR (why us)                                         -->
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
    <!-- VENDOR CTA (tight, connected to footer)                    -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
      <div class="relative rounded-[20px] overflow-hidden border border-[color:var(--color-hairline)] bg-gradient-to-br from-[color:var(--color-ink)] to-[color:var(--color-accent-700)] p-10 md:p-12 lg:p-14">
        <div class="absolute inset-0 ui-spotlight pointer-events-none opacity-50" />
        <div class="absolute -top-20 -right-10 w-72 h-72 rounded-full bg-[color:var(--color-accent-500)] opacity-20 blur-3xl pointer-events-none" />

        <div class="relative flex flex-col md:flex-row items-start md:items-center md:justify-between gap-8">
          <div class="max-w-xl">
            <div class="text-xs uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-400)] mb-3">
              For vendors
            </div>
            <h2 class="ui-display text-[26px] md:text-4xl font-semibold tracking-tight text-white leading-[1.1] mb-3">
              Run a research peptide business? Get listed.
            </h2>
            <p class="text-white/70 text-sm md:text-base leading-relaxed">
              Connect your WooCommerce store in minutes. Automatic product sync, verified badge review, detailed analytics. Free during beta.
            </p>
          </div>
          <div class="flex flex-col sm:flex-row gap-3 flex-shrink-0">
            <Button as="a" href="/become-a-vendor" variant="primary" size="md">
              Apply to be listed
              <template #icon-right>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
              </template>
            </Button>
            <Button as="a" href="/vendor/login" variant="secondary" size="md" class="!bg-white/10 !border-white/20 !text-white hover:!bg-white/15">
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
import SectionHeader from '@/components/ui/SectionHeader.vue'
import HeroCarousel from '@/components/ui/HeroCarousel.vue'
import BrandStrip from '@/components/ui/BrandStrip.vue'
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
</script>
