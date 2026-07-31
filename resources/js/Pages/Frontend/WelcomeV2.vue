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
      <div class="relative max-w-[1280px] mx-auto px-5 lg:px-10 pt-1 lg:pt-3">
        <HeroCarousel :slides="heroSlides" />
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 2. VENDORS — visually continues from hero (no gap)         -->
    <!-- ========================================================= -->
    <section class="bg-[color:var(--color-bg)] border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-8 lg:py-12">
        <div class="flex items-center justify-between mb-5">
          <div class="flex items-center gap-3">
            <h2 class="text-[13px] lg:text-[14px] font-semibold text-[color:var(--color-ink)] tracking-tight">Top-rated vendors</h2>
            <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ verifiedVendors.length }}+ verified</span>
          </div>
          <a href="/vendors" class="text-[12px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors flex items-center gap-1">
            All vendors
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-4">
          <a
            v-for="vendor in verifiedVendors.slice(0, 6)"
            :key="vendor.id"
            :href="vendor.url"
            class="ui-focus group flex flex-col border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms] overflow-hidden"
          >
            <div class="aspect-[16/9] bg-white border-b border-[color:var(--color-hairline)] flex items-center justify-center p-4 lg:p-8">
              <img v-if="vendor.logo_url" :src="vendor.logo_url" :alt="vendor.name" class="max-h-full max-w-[75%] object-contain" loading="lazy" />
              <span v-else class="ui-display text-2xl lg:text-4xl font-bold text-[color:var(--color-ink-subtle)]">{{ vendor.name.slice(0,2).toUpperCase() }}</span>
            </div>
            <div class="p-3 lg:p-5 flex-1 flex flex-col gap-2 lg:gap-3">
              <div class="flex items-center justify-between">
                <h3 class="ui-display text-[14px] lg:text-[17px] font-semibold text-[color:var(--color-ink)] tracking-tight truncate">{{ vendor.name }}</h3>
                <span v-if="vendor.coupon_code" class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 border-dashed">
                  <span class="text-emerald-500 text-[8px] uppercase tracking-wider font-semibold">Code</span>
                  <span class="ui-mono">{{ vendor.coupon_code }}</span>
                </span>
              </div>
              <div class="flex items-center gap-1 text-xs">
                <svg v-for="n in 5" :key="n" class="w-3 lg:w-3.5 h-3 lg:h-3.5" :class="n <= Math.round(vendor.rating_average) ? 'text-[color:var(--color-caution)]' : 'text-[color:var(--color-hairline)]'" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                <span class="ui-mono font-semibold text-[color:var(--color-ink)] ml-0.5">{{ (vendor.rating_average || 0).toFixed(1) }}</span>
              </div>
              <div class="mt-auto pt-2 lg:pt-3 border-t border-[color:var(--color-hairline-soft)] flex items-center justify-between text-[11px] lg:text-xs text-[color:var(--color-ink-muted)]">
                <span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ vendor.product_count }}</span> compounds</span>
                <span class="text-[color:var(--color-accent-600)] font-semibold group-hover:translate-x-0.5 transition-transform duration-[180ms] flex items-center gap-0.5">
                  View <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </span>
              </div>
            </div>
          </a>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 3. COMPOUND CATEGORIES — "What are you researching?"       -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 py-12 lg:py-20">
      <SectionHeader
        eyebrow="Browse compounds"
        title="What are you researching?"
        description="Explore every major peptide category — vendor pricing, lab data, and reference material for each compound."
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

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4">
        <a
          v-for="compound in topCompounds"
          :key="compound.slug"
          :href="`/product/${compound.slug}`"
          class="ui-focus group flex flex-col gap-2 lg:gap-3 p-4 lg:p-5 rounded-[14px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms]"
        >
          <div class="ui-display text-[14px] lg:text-[16px] font-semibold text-[color:var(--color-ink)] tracking-tight group-hover:text-[color:var(--color-accent-600)] transition-colors leading-tight">
            {{ compound.name }}
          </div>
          <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-[11px] lg:text-[12px] text-[color:var(--color-ink-muted)]">
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
    <!-- 4. COMPARE + CALCULATOR CTAs — side by side                -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 pb-12 lg:pb-20">
      <div class="grid md:grid-cols-2 gap-4 lg:gap-5">
        <!-- Compare CTA -->
        <div class="rounded-[16px] border border-[color:var(--color-hairline)] bg-gradient-to-br from-[color:var(--color-accent-50)] to-white p-7 lg:p-10 flex flex-col gap-4">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)]">Price comparison</div>
          <h2 class="ui-display text-xl lg:text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">
            See every vendor's price, side by side
          </h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
            {{ formatNumber(stats.compounds) }} compounds across {{ formatNumber(stats.total_vendors) }} vendors. Sorted cheapest-first.
          </p>
          <div class="mt-auto pt-2">
            <Button as="a" href="/compare" variant="primary">
              Compare prices
              <template #icon-right>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </template>
            </Button>
          </div>
        </div>
        <!-- Calculator CTA -->
        <div class="rounded-[16px] border border-[color:var(--color-hairline)] bg-gradient-to-br from-emerald-50 to-white p-7 lg:p-10 flex flex-col gap-4">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-emerald-600">Free tool</div>
          <h2 class="ui-display text-xl lg:text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">
            Peptide reconstitution calculator
          </h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
            Calculate concentration, volume, and doses per vial. Presets for 17+ popular compounds.
          </p>
          <div class="mt-auto pt-2">
            <Button as="a" href="/calculator" variant="secondary">
              Open calculator
              <template #icon-right>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </template>
            </Button>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 5. ENCYCLOPEDIA — compound knowledge base                  -->
    <!-- ========================================================= -->
    <section v-if="encyclopediaCategories && encyclopediaCategories.length" class="bg-[#0A0B0E]">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-12 lg:py-20">
        <div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
          <div>
            <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-400)] mb-2">Peptide encyclopedia</div>
            <h2 class="ui-display text-2xl md:text-3xl font-semibold tracking-tight text-white">Research compound profiles</h2>
            <p class="text-[14px] text-white/50 leading-relaxed mt-2 max-w-xl">Detailed reference material for each compound — structure, research context, vendor availability, and pricing data.</p>
          </div>
          <a href="/encyclopedia" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-[9px] border border-white/15 text-[13px] font-semibold text-white/70 hover:text-white hover:border-white/30 transition-colors flex-shrink-0">
            View all
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <a
            v-for="entry in encyclopediaCategories.slice(0, 6)"
            :key="entry.slug"
            :href="entry.url"
            class="ui-focus group rounded-[14px] border border-white/8 bg-white/[0.04] hover:bg-white/[0.08] hover:border-white/15 p-5 lg:p-6 transition-all duration-200"
          >
            <div class="flex items-start justify-between gap-3 mb-3">
              <h3 class="ui-display text-[16px] font-semibold text-white group-hover:text-[color:var(--color-accent-400)] transition-colors leading-tight">
                {{ entry.name }}
              </h3>
              <svg class="w-4 h-4 text-white/20 group-hover:text-[color:var(--color-accent-400)] group-hover:translate-x-0.5 transition-all flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </div>
            <p v-if="entry.description" class="text-[13px] text-white/40 leading-relaxed line-clamp-2 mb-4">{{ entry.description }}</p>
            <div class="flex items-center gap-3 text-[11px]">
              <span class="ui-mono text-white/60"><span class="text-white font-semibold">{{ entry.vendor_count }}</span> vendors</span>
              <span v-if="entry.from_price" class="ui-mono text-emerald-400">from ${{ entry.from_price }}</span>
            </div>
          </a>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 6. LATEST RESEARCH                                         -->
    <!-- ========================================================= -->
    <section v-if="editorial.length" class="bg-white border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-12 lg:py-20">
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-6">
          <a
            v-if="editorial[0]"
            :href="`/blog/${editorial[0].slug}`"
            class="ui-focus group flex flex-col border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms] overflow-hidden"
          >
            <div class="aspect-[16/9] bg-[#0F172A] overflow-hidden relative">
              <img v-if="editorial[0].image" :src="editorial[0].image" :alt="editorial[0].title" class="w-full h-full object-cover opacity-80 group-hover:opacity-90 group-hover:scale-[1.03] transition-all duration-500" loading="lazy" />
              <div class="absolute inset-0 bg-gradient-to-tr from-[#312E81]/40 via-transparent to-[#4F46E5]/20 pointer-events-none" />
            </div>
            <div class="p-5 lg:p-6 flex-1 flex flex-col gap-2">
              <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ editorial[0].date }} · {{ editorial[0].read_time }}</span>
              <h3 class="ui-display text-lg lg:text-xl font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug group-hover:text-[color:var(--color-accent-600)] transition-colors">
                {{ editorial[0].title }}
              </h3>
              <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-3">{{ editorial[0].excerpt }}</p>
            </div>
          </a>

          <div class="flex flex-col gap-5 lg:gap-6">
            <a
              v-for="post in editorial.slice(1, 4)"
              :key="post.id"
              :href="`/blog/${post.slug}`"
              class="ui-focus group flex flex-row border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[0.5px] transition-all duration-[200ms] overflow-hidden"
            >
              <div class="w-40 lg:w-48 flex-shrink-0 bg-[#0F172A] overflow-hidden relative">
                <img v-if="post.image" :src="post.image" :alt="post.title" class="w-full h-full object-cover opacity-80 group-hover:opacity-90 transition-all duration-500" loading="lazy" />
                <div class="absolute inset-0 bg-gradient-to-tr from-[#312E81]/30 via-transparent to-[#4F46E5]/15 pointer-events-none" />
              </div>
              <div class="p-4 lg:p-5 flex-1 flex flex-col gap-1.5 justify-center">
                <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ post.date }} · {{ post.read_time }}</span>
                <h3 class="ui-display text-[15px] font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug group-hover:text-[color:var(--color-accent-600)] transition-colors">
                  {{ post.title }}
                </h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-2">{{ post.excerpt }}</p>
              </div>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 7. RUO NOTICE — research use disclaimer                    -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 py-8">
      <div class="flex items-start gap-3 p-4 rounded-[12px] bg-[color:var(--color-bg)] border border-[color:var(--color-hairline)]">
        <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <p class="text-[11px] text-[color:var(--color-ink-subtle)] leading-relaxed">
          <strong class="text-[color:var(--color-ink-muted)]">Research Use Only.</strong> All products listed on PeptideMap are intended for laboratory and research purposes only. They are not intended for human consumption, therapeutic use, or any form of self-administration. Always comply with local regulations. PeptideMap does not provide medical advice.
        </p>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 8. VENDOR CTA                                              -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 pb-12 lg:pb-16">
      <div class="border border-[color:var(--color-hairline)] bg-gradient-to-r from-[color:var(--color-accent-50)] to-white p-8 md:p-12 flex flex-col md:flex-row items-start md:items-center gap-6 md:gap-10">
        <div class="flex-1">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-2">For vendors</div>
          <h2 class="ui-display text-xl md:text-2xl font-semibold tracking-tight text-[color:var(--color-ink)] mb-2">
            List your research peptides on PeptideMaps
          </h2>
          <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed">
            Auto-sync from WooCommerce. Click analytics. Affiliate tracking. Free during beta.
          </p>
        </div>
        <div class="flex items-center gap-4 flex-shrink-0">
          <a
            href="/become-a-vendor"
            class="ui-focus inline-flex items-center gap-2 h-11 px-6 text-[14px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all"
          >
            Get listed
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
          <a href="/login" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] font-medium transition-colors">
            Vendor sign in →
          </a>
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

defineProps({
  stats: { type: Object, default: () => ({}) },
  heroSlides: { type: Array, default: () => [] },
  verifiedVendors: { type: Array, default: () => [] },
  topCompounds: { type: Array, default: () => [] },
  encyclopediaCategories: { type: Array, default: () => [] },
  editorial: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

function formatNumber(n) {
  if (n === null || n === undefined) return '—'
  return new Intl.NumberFormat().format(n)
}
</script>
