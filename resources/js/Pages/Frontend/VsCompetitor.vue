<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
      <link rel="canonical" :href="seo.canonical" />
    </Head>

    <section class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1000px] mx-auto px-6 lg:px-10 py-10">
        <div class="text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-accent-600)] mb-2">
          Side-by-Side Comparison
        </div>
        <h1 class="ui-display text-3xl md:text-4xl font-semibold text-[color:var(--color-ink)] mb-3">
          Peptidemap vs {{ competitor.name }}
        </h1>
        <p class="text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl text-[15px]">
          Both sites help you compare peptide prices across vendors. Here's an honest, audited-Aug-2026 look at how they stack up on catalog depth, freshness, and features actual buyers care about.
        </p>
      </div>
    </section>

    <section class="py-10">
      <div class="max-w-[1000px] mx-auto px-6 lg:px-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="border-2 border-[color:var(--color-accent-500)] rounded-[12px] p-6 bg-[color:var(--color-accent-50)]/30">
            <div class="text-[10px] uppercase tracking-wider font-bold text-[color:var(--color-accent-700)] mb-2">This site</div>
            <div class="text-2xl font-semibold text-[color:var(--color-ink)] mb-4">{{ ours.name }}</div>

            <div class="space-y-3 mb-6">
              <div class="flex justify-between items-baseline"><span class="text-[13px] text-[color:var(--color-ink-muted)]">Vendors</span><span class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">{{ ours.vendor_count }}</span></div>
              <div class="flex justify-between items-baseline"><span class="text-[13px] text-[color:var(--color-ink-muted)]">Tracked products</span><span class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">{{ ours.product_count.toLocaleString() }}</span></div>
              <div class="flex justify-between items-baseline"><span class="text-[13px] text-[color:var(--color-ink-muted)]">Indexed pages</span><span class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">{{ ours.sitemap_urls.toLocaleString() }}</span></div>
              <div class="flex justify-between items-baseline"><span class="text-[13px] text-[color:var(--color-ink-muted)]">Freshness</span><span class="text-[12px] font-semibold text-emerald-700 text-right">{{ ours.freshness }}</span></div>
            </div>

            <div class="text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)] mb-2">What we do well</div>
            <ul class="space-y-1.5 text-[13px] text-[color:var(--color-ink-muted)]">
              <li v-for="s in ours.strengths" :key="s" class="flex items-start gap-2">
                <span class="text-emerald-600 mt-0.5">✓</span><span>{{ s }}</span>
              </li>
            </ul>
          </div>

          <div class="border border-[color:var(--color-hairline)] rounded-[12px] p-6 bg-white">
            <div class="text-[10px] uppercase tracking-wider font-bold text-[color:var(--color-ink-subtle)] mb-2">Competitor</div>
            <div class="text-2xl font-semibold text-[color:var(--color-ink)] mb-1">{{ competitor.name }}</div>
            <div class="text-[12px] text-[color:var(--color-ink-subtle)] mb-4">{{ competitor.domain }}</div>

            <div class="space-y-3 mb-6">
              <div class="flex justify-between items-baseline">
                <span class="text-[13px] text-[color:var(--color-ink-muted)]">Vendors</span>
                <span class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">
                  {{ competitor.vendor_count ?? '—' }}
                  <span v-if="competitor.vendor_count_note" class="text-[10px] font-normal text-[color:var(--color-ink-subtle)] ml-1">({{ competitor.vendor_count_note }})</span>
                </span>
              </div>
              <div class="flex justify-between items-baseline"><span class="text-[13px] text-[color:var(--color-ink-muted)]">Tracked products</span><span class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">{{ competitor.product_count?.toLocaleString() ?? '—' }}</span></div>
              <div class="flex justify-between items-baseline"><span class="text-[13px] text-[color:var(--color-ink-muted)]">Indexed pages</span><span class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">{{ competitor.sitemap_urls.toLocaleString() }}</span></div>
              <div class="flex justify-between items-baseline"><span class="text-[13px] text-[color:var(--color-ink-muted)]">Freshness</span><span class="text-[12px] text-[color:var(--color-ink-muted)] text-right">{{ competitor.freshness }}</span></div>
            </div>

            <div v-if="competitor.strengths?.length" class="text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)] mb-2">Their strengths</div>
            <ul v-if="competitor.strengths?.length" class="space-y-1.5 text-[13px] text-[color:var(--color-ink-muted)] mb-5">
              <li v-for="s in competitor.strengths" :key="s" class="flex items-start gap-2">
                <span class="text-slate-500 mt-0.5">✓</span><span>{{ s }}</span>
              </li>
            </ul>

            <div class="text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)] mb-2">Gaps we identified</div>
            <ul class="space-y-1.5 text-[13px] text-[color:var(--color-ink-muted)]">
              <li v-for="g in competitor.gaps" :key="g" class="flex items-start gap-2">
                <span class="text-amber-600 mt-0.5">·</span><span>{{ g }}</span>
              </li>
            </ul>
          </div>
        </div>

        <div class="mt-8 p-5 rounded-[10px] bg-[color:var(--color-bg)] border border-[color:var(--color-hairline)] text-[12px] text-[color:var(--color-ink-subtle)]">
          Comparison audited August 2026. Competitor numbers pulled from their public sitemaps and homepage claims — if you spot something inaccurate, email <a href="mailto:info@peptidemap.com" class="underline text-[color:var(--color-accent-600)]">info@peptidemap.com</a> and we'll update it. Our numbers are pulled live from our own catalog on every page load.
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
          <a href="/compare" class="inline-flex items-center gap-2 h-11 px-5 rounded-[10px] text-[14px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] hover:-translate-y-[1px] transition-all">
            Try our compare tables →
          </a>
          <a href="/vendors" class="inline-flex items-center gap-2 h-11 px-5 rounded-[10px] text-[14px] font-semibold text-[color:var(--color-ink)] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] transition-colors">
            Browse all vendors
          </a>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

defineProps({
  competitor: { type: Object, required: true },
  ours: { type: Object, required: true },
  seo: { type: Object, default: () => ({}) },
})
</script>
