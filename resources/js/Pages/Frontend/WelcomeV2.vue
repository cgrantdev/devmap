<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- ========================================================= -->
    <!-- 1. HERO                                                    -->
    <!-- ========================================================= -->
    <section class="relative">
      <div class="ui-spotlight absolute inset-0 pointer-events-none" />
      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-10 pt-4 lg:pt-6">
        <HeroCarousel :slides="heroSlides" />
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 2. COMPOUND CATEGORIES — "What are you researching?"       -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
      <SectionHeader
        eyebrow="Browse compounds"
        title="What are you researching?"
        description="Explore every major peptide category — vendor pricing, lab data, and research articles for each compound."
        accent="accent"
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

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <a
          v-for="compound in topCompounds"
          :key="compound.slug"
          :href="`/encyclopedia/${compound.slug}`"
          class="ui-focus group flex flex-col gap-3 p-5 rounded-[14px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms]"
        >
          <div class="ui-display text-[16px] font-semibold text-[color:var(--color-ink)] tracking-tight group-hover:text-[color:var(--color-accent-600)] transition-colors leading-tight">
            {{ compound.name }}
          </div>
          <div class="flex items-center gap-3 text-[12px] text-[color:var(--color-ink-muted)]">
            <span class="flex items-center gap-1">
              <svg class="w-3 h-3 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
              <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ compound.vendor_count }}</span> vendors
            </span>
            <span v-if="compound.from_price" class="ui-mono text-[color:var(--color-verified)]">
              from ${{ compound.from_price }}
            </span>
          </div>
        </a>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 3. VENDORS — top vendors as list rows (one representation) -->
    <!-- ========================================================= -->
    <section class="bg-white border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
        <SectionHeader
          eyebrow="Vendor network"
          title="Top-rated vendors"
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

        <!-- List rows, not card grid — matches /vendors page -->
        <div class="space-y-3">
          <a
            v-for="(vendor, idx) in verifiedVendors.slice(0, 6)"
            :key="vendor.id"
            :href="vendor.url"
            class="ui-focus group flex items-center gap-4 md:gap-5 p-4 rounded-[14px] border border-[color:var(--color-hairline)] bg-[color:var(--color-bg)] hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-sm)] transition-all duration-[180ms]"
          >
            <div class="w-11 h-11 rounded-[10px] flex-shrink-0 overflow-hidden flex items-center justify-center" :style="{ background: vendorGradient(vendor.name) }">
              <img v-if="vendor.logo_url" :src="vendor.logo_url" :alt="vendor.name" class="max-h-full max-w-full object-contain p-1" loading="lazy" />
              <span v-else class="ui-display text-white text-sm font-bold">{{ vendor.name.slice(0,2).toUpperCase() }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="ui-display text-[15px] font-semibold text-[color:var(--color-ink)] truncate tracking-tight">{{ vendor.name }}</h3>
              <div class="flex items-center gap-3 text-xs text-[color:var(--color-ink-muted)] mt-0.5">
                <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ vendor.product_count }}</span> compounds
              </div>
            </div>
            <div class="hidden sm:flex items-center gap-1.5 flex-shrink-0">
              <div class="flex gap-0.5">
                <svg v-for="n in 5" :key="n" class="w-3 h-3" :class="n <= Math.round(vendor.rating_average) ? 'text-[color:var(--color-caution)]' : 'text-[color:var(--color-hairline)]'" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
              </div>
              <span class="ui-mono text-sm font-bold text-[color:var(--color-ink)]">{{ (vendor.rating_average || 0).toFixed(1) }}</span>
            </div>
            <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all flex-shrink-0 hidden md:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
          </a>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 4. COMPARE CTA — drives to comparison page                 -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
      <div class="rounded-[20px] border border-[color:var(--color-hairline)] bg-gradient-to-r from-[color:var(--color-accent-50)] to-white p-10 md:p-14 flex flex-col md:flex-row items-start md:items-center gap-8">
        <div class="flex-1">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-3">Price comparison</div>
          <h2 class="ui-display text-2xl md:text-3xl font-semibold tracking-tight text-[color:var(--color-ink)] mb-3">
            See every vendor's price, side by side
          </h2>
          <p class="text-[15px] text-[color:var(--color-ink-muted)] leading-relaxed max-w-xl">
            {{ formatNumber(stats.compounds) }} compounds across {{ formatNumber(stats.total_vendors) }} vendors. Sorted cheapest-first. Updated continuously.
          </p>
        </div>
        <Button as="a" href="/compare" variant="primary" size="lg">
          Compare prices
          <template #icon-right>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M13 5l7 7-7 7"/>
            </svg>
          </template>
        </Button>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 5. LATEST RESEARCH — authority builder                     -->
    <!-- ========================================================= -->
    <section v-if="editorial.length" class="bg-white border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16 lg:py-20">
        <SectionHeader
          eyebrow="Research & education"
          title="Latest insights"
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
              class="mb-4 border border-[color:var(--color-hairline)]"
            />
            <div class="flex items-center gap-2 text-[11px] text-[color:var(--color-ink-subtle)] mb-2 ui-mono">
              <span>{{ post.date }}</span>
              <span>·</span>
              <span>{{ post.read_time }}</span>
            </div>
            <h3 class="ui-display text-lg font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug group-hover:text-[color:var(--color-accent-600)] transition-colors">
              {{ post.title }}
            </h3>
          </a>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 6. BECOME A VENDOR CTA                                     -->
    <!-- ========================================================= -->
    <section class="relative bg-[color:var(--color-ink)] text-white overflow-hidden">
      <div class="absolute inset-0" :style="{ background: 'radial-gradient(ellipse 900px 500px at 20% 40%, rgba(99,102,241,0.25) 0%, transparent 60%)' }" />
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[140%] h-px bg-gradient-to-r from-transparent via-white/20 to-transparent" />

      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-10 py-20 lg:py-24">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-10">
          <div class="flex-1 max-w-xl">
            <div class="text-[11px] uppercase tracking-[0.14em] font-semibold text-[color:var(--color-accent-400)] mb-3">For vendors</div>
            <h2 class="ui-display text-3xl md:text-4xl font-semibold tracking-tight text-white leading-[1.1] mb-4">
              List your research peptides on PeptideMap
            </h2>
            <p class="text-white/60 text-[15px] leading-relaxed">
              Auto-sync from WooCommerce. Click analytics. Free during beta.
            </p>
          </div>
          <div class="flex items-center gap-4 flex-shrink-0">
            <a
              href="/become-a-vendor"
              class="ui-focus inline-flex items-center gap-2 h-12 px-7 rounded-[12px] bg-white text-[color:var(--color-ink)] text-[15px] font-semibold hover:bg-white/95 transition-all shadow-lg"
            >
              Get listed
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
              </svg>
            </a>
            <a href="/vendor/login" class="text-sm text-white/50 hover:text-white font-medium transition-colors">
              Vendor sign in →
            </a>
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
import SectionHeader from '@/components/ui/SectionHeader.vue'
import HeroCarousel from '@/components/ui/HeroCarousel.vue'
import BlogImage from '@/components/ui/BlogImage.vue'

defineProps({
  stats: { type: Object, default: () => ({}) },
  heroSlides: { type: Array, default: () => [] },
  verifiedVendors: { type: Array, default: () => [] },
  topCompounds: { type: Array, default: () => [] },
  editorial: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

function formatNumber(n) {
  if (n === null || n === undefined) return '—'
  return new Intl.NumberFormat().format(n)
}

function vendorGradient(name) {
  const palette = [
    ['#1E293B', '#4F46E5'], ['#0F172A', '#6366F1'], ['#1E3A8A', '#3B82F6'],
    ['#0C4A6E', '#0EA5E9'], ['#134E4A', '#14B8A6'], ['#312E81', '#4F46E5'],
  ]
  let h = 0
  for (let i = 0; i < name.length; i++) h = (h * 31 + name.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
}
</script>
