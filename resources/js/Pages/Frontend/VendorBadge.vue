<template>
  <ModernLayout>
    <Head>
      <title>{{ brand ? `Get your ${brand.name} badge — Peptidemap` : 'Vendor Badges — Peptidemap' }}</title>
      <meta name="description" content="Copy-paste a Peptidemap badge onto your vendor site. Shows your live rating, backlinks to your Peptidemap listing, and gives your visitors social proof." />
      <meta name="robots" content="index,follow" />
    </Head>

    <!-- Landing (no brand chosen yet) -->
    <section v-if="!brand" class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[900px] mx-auto px-6 lg:px-10 py-12">
        <h1 class="ui-display text-3xl md:text-4xl font-semibold text-[color:var(--color-ink)] mb-3">Vendor Badges</h1>
        <p class="text-[color:var(--color-ink-muted)] leading-relaxed mb-6 max-w-2xl">
          Drop a live Peptidemap badge on your site. Shows your current rating, links back to your listing, and gives your visitors third-party social proof — the same signal reviews.io and Trustpilot widgets provide, backed by our verified-vendor and coupon database.
        </p>

        <div class="mb-6">
          <label class="block text-[13px] font-semibold text-[color:var(--color-ink-muted)] mb-2">Find your vendor</label>
          <input
            v-model="search"
            type="text"
            placeholder="Type your brand name…"
            class="w-full h-11 px-4 rounded-[10px] border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none text-[15px]"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[560px] overflow-y-auto pr-2">
          <a
            v-for="v in filteredVendors"
            :key="v.slug"
            :href="`/for-vendors/badge/${v.slug}`"
            class="ui-focus flex items-center justify-between px-4 py-3 rounded-[10px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:bg-[color:var(--color-accent-50)] transition-colors"
          >
            <div>
              <div class="text-[14px] font-semibold text-[color:var(--color-ink)]">{{ v.name }}</div>
              <div v-if="v.rating_count > 0" class="text-[11px] text-[color:var(--color-ink-subtle)] mt-0.5">
                ★ {{ v.rating_average.toFixed(1) }} · {{ v.rating_count }} reviews
              </div>
            </div>
            <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
          </a>
        </div>
        <p v-if="filteredVendors.length === 0" class="text-[13px] text-[color:var(--color-ink-subtle)] mt-4">
          No vendor matches “{{ search }}”. If you're a vendor and not yet listed, <a href="/become-a-vendor" class="text-[color:var(--color-accent-600)] underline">get listed here</a>.
        </p>
      </div>
    </section>

    <!-- Per-vendor badge page -->
    <template v-else>
      <section class="border-b border-[color:var(--color-hairline)]">
        <div class="max-w-[900px] mx-auto px-6 lg:px-10 py-10">
          <div class="text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)] mb-2">Vendor Badges</div>
          <h1 class="ui-display text-3xl md:text-4xl font-semibold text-[color:var(--color-ink)] mb-3">
            {{ brand.name }}'s Peptidemap badge
          </h1>
          <p class="text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl">
            Pick a style, copy the snippet, paste it anywhere on your site — checkout page, footer, "About us", product cards. Each embed is a backlink to your Peptidemap listing and includes UTM tracking so both sides can see the traffic.
          </p>
          <div class="mt-4 text-[13px] text-[color:var(--color-ink-muted)]">
            Current stats:
            <strong class="text-[color:var(--color-ink)]">★ {{ stats.rating }} / 5</strong>
            · {{ stats.rating_count }} reviews
            · {{ stats.product_count }} listed products
          </div>
        </div>
      </section>

      <section>
        <div class="max-w-[900px] mx-auto px-6 lg:px-10 py-10 space-y-10">
          <div
            v-for="v in variants"
            :key="v.key"
            class="border border-[color:var(--color-hairline)] rounded-[12px] p-6 bg-white"
          >
            <div class="flex items-center justify-between mb-4 gap-4 flex-wrap">
              <h2 class="text-[18px] font-semibold text-[color:var(--color-ink)]">{{ v.label }}</h2>
              <div class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ v.width }} × {{ v.height }}</div>
            </div>

            <!-- Live preview against a neutral canvas so vendors see what
                 it'll look like when embedded on their site. -->
            <div class="mb-5 p-6 rounded-[10px] bg-[color:var(--color-bg)] border border-[color:var(--color-hairline)] flex items-center justify-center">
              <img
                :src="`/badge/${brand.slug}.svg?variant=${v.key}`"
                :alt="`${brand.name} badge, ${v.label} variant`"
                :width="v.width"
                :height="v.height"
                loading="lazy"
              />
            </div>

            <label class="block text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)] mb-2">
              Embed HTML
            </label>
            <div class="relative">
              <pre class="bg-[#0F172A] text-slate-100 rounded-[10px] p-4 pr-24 overflow-x-auto text-[12px] leading-relaxed font-mono whitespace-pre-wrap">{{ snippets[v.key] }}</pre>
              <button
                @click="copy(v.key, snippets[v.key])"
                type="button"
                class="absolute top-3 right-3 h-8 px-3 rounded-[6px] text-[12px] font-semibold bg-white/10 hover:bg-white/20 text-white/90 hover:text-white transition-colors"
              >
                {{ copied === v.key ? '✓ Copied' : 'Copy' }}
              </button>
            </div>
          </div>

          <div class="border border-[color:var(--color-hairline)] rounded-[12px] p-6 bg-[color:var(--color-bg)]">
            <h3 class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-3">Notes</h3>
            <ul class="space-y-2 text-[13px] text-[color:var(--color-ink-muted)]">
              <li class="flex items-start gap-2"><span class="text-[color:var(--color-accent-600)] mt-1">·</span>Rating auto-refreshes hourly — no need to re-embed when you get new reviews</li>
              <li class="flex items-start gap-2"><span class="text-[color:var(--color-accent-600)] mt-1">·</span>Link opens in a new tab, is <code class="ui-mono text-[12px] bg-white px-1 rounded">rel="noopener"</code>, and includes UTM tags so you can see the traffic in your own analytics</li>
              <li class="flex items-start gap-2"><span class="text-[color:var(--color-accent-600)] mt-1">·</span>SVG scales sharp at any DPI — safe to use on retina / mobile</li>
              <li class="flex items-start gap-2"><span class="text-[color:var(--color-accent-600)] mt-1">·</span>No JavaScript required</li>
              <li class="flex items-start gap-2"><span class="text-[color:var(--color-accent-600)] mt-1">·</span>Questions? Email <a href="mailto:info@peptidemap.com" class="text-[color:var(--color-accent-600)] underline">info@peptidemap.com</a></li>
            </ul>
          </div>
        </div>
      </section>
    </template>
  </ModernLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

const props = defineProps({
  brand: { type: Object, default: null },
  stats: { type: Object, default: () => ({}) },
  variants: { type: Array, default: () => [] },
  snippets: { type: Object, default: () => ({}) },
  vendors: { type: Array, default: () => [] },
})

const search = ref('')
const copied = ref(null)

const filteredVendors = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return props.vendors
  return props.vendors.filter(v => v.name.toLowerCase().includes(q))
})

async function copy(key, text) {
  try {
    await navigator.clipboard.writeText(text)
    copied.value = key
    setTimeout(() => { if (copied.value === key) copied.value = null }, 1800)
  } catch {}
}
</script>
