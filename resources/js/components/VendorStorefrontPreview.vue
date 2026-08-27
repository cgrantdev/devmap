<template>
  <!-- Full-width brand-page-shaped preview. Mirrors the real /brand/{slug}
       layout so applicants see exactly what will be shipped after approval.
       Fake product cards demonstrate the product-catalog area since we
       don't have their catalog yet at signup. -->
  <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-slate-200">
    <!-- Faux browser chrome so it reads unambiguously as a preview -->
    <div class="px-4 py-2 bg-slate-100 border-b border-slate-200 flex items-center gap-2">
      <div class="flex gap-1.5">
        <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
        <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
        <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
      </div>
      <div class="flex-1 mx-3 text-[11px] text-slate-500 ui-mono bg-white px-2.5 py-1 rounded border border-slate-200 truncate">
        peptidemap.com/brand/{{ slugPreview }}
      </div>
    </div>

    <!-- Brand header — matches /brand/{slug} layout -->
    <div class="px-6 py-5 border-b border-slate-200 bg-white">
      <div class="flex items-start gap-4">
        <div class="w-16 h-16 rounded border border-slate-200 flex items-center justify-center overflow-hidden bg-slate-50 flex-shrink-0">
          <img v-if="logoUrl" :src="logoUrl" :alt="data.name || 'Logo'" class="w-full h-full object-contain p-1" />
          <span v-else class="text-[10px] text-slate-400">LOGO</span>
        </div>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-semibold text-slate-900 truncate">{{ data.name || 'Your brand name' }}</h1>
          <!-- Row 1: stars + reviews + location — matches the real page's rating cluster. -->
          <div class="flex items-center gap-1 mt-1 flex-wrap">
            <svg v-for="n in 5" :key="n" class="w-4 h-4 text-slate-300" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
            <span class="text-[12px] text-slate-500 ml-1.5">Rating shown after your first review</span>
            <span v-if="data.location" class="ml-2 text-[12px] text-slate-600 flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 00-8 8c0 5.5 8 12 8 12s8-6.5 8-12a8 8 0 00-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
              Ships from {{ data.location }}
            </span>
          </div>
          <!-- Row 2: tagline sits BELOW the rating/ships-from line so it
               reads as a hook, not a subtitle on the name. -->
          <p
            v-if="data.tagline"
            class="text-[13px] text-slate-700 mt-2 leading-snug"
          >{{ data.tagline }}</p>
          <p
            v-else
            class="text-[13px] text-slate-300 italic mt-2 leading-snug"
          >Your tagline will appear here</p>
        </div>
        <div class="hidden sm:flex flex-col gap-2 flex-shrink-0">
          <button type="button" class="h-9 px-4 rounded-md text-[12px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] cursor-default">Visit website →</button>
          <div class="flex items-center gap-2 border-2 border-dashed border-emerald-300 bg-emerald-50 px-3 h-9 rounded-md">
            <span class="text-[9px] uppercase tracking-wider font-semibold text-emerald-700">Code</span>
            <span class="ui-mono text-[13px] font-bold text-emerald-800">PMAP</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Two-col: main + sidebar (like real brand page) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6 bg-slate-50">
      <!-- Main column: products + reviews -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Product-grid preview — placeholder cards to show shape -->
        <div class="bg-white border border-slate-200 rounded-lg p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-slate-900">Your products</h3>
            <span class="text-[11px] text-slate-400 italic">Imported after approval</span>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div v-for="p in sampleProducts" :key="p.name" class="border border-slate-200 rounded-md overflow-hidden flex flex-col">
              <div class="aspect-square bg-gradient-to-br from-blue-50 to-purple-50 flex items-center justify-center border-b border-slate-200">
                <svg class="w-14 h-16 text-slate-300" viewBox="0 0 80 120" fill="none">
                  <rect x="20" y="20" width="40" height="80" rx="2" fill="white" stroke="currentColor" stroke-width="1.5"/>
                  <rect x="18" y="15" width="44" height="8" rx="1" fill="currentColor"/>
                  <rect x="22" y="60" width="36" height="36" rx="1" fill="#93C5FD" opacity="0.7"/>
                </svg>
              </div>
              <div class="p-2.5 flex-1 flex flex-col">
                <div class="text-[12px] font-semibold text-slate-900 leading-tight line-clamp-2 min-h-[2.2em]">{{ p.name }}</div>
                <div class="text-[11px] text-slate-500 mt-1">{{ data.name || 'Your brand' }}</div>
                <div class="text-[14px] font-bold text-slate-900 mt-2">${{ p.price }}</div>
                <button type="button" class="mt-2 h-7 rounded text-[11px] font-semibold text-white bg-blue-600 cursor-default">View Product</button>
              </div>
            </div>
          </div>
          <p class="mt-4 text-[11px] text-slate-500 italic text-center">These are placeholder products — your actual catalog imports from your site after approval.</p>
        </div>

        <!-- About / description -->
        <div class="bg-white border border-slate-200 rounded-lg p-5">
          <h3 class="text-base font-semibold text-slate-900 mb-3">About {{ data.name || 'your brand' }}</h3>
          <p v-if="data.description" class="text-[13px] text-slate-700 leading-relaxed whitespace-pre-line">{{ data.description }}</p>
          <p v-else class="text-[13px] text-slate-400 italic">Your company description will appear here — this is what buyers read to decide.</p>
        </div>

        <!-- Third-party review-badges panel removed 2026-08-27: no
             competitor promotion. Imported reviews still land in the
             Customer Reviews section on the real storefront. -->
      </div>

      <!-- Sidebar: business details (with hours + open/closed baked in) -->
      <aside class="space-y-4">
        <div class="bg-white border border-slate-200 rounded-lg p-5">
          <div class="flex items-center justify-between gap-2 mb-3">
            <h3 class="text-base font-semibold text-slate-900">Business details</h3>
            <span v-if="openPill" :class="['text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded-full', openPill.open ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700']">
              ● {{ openPill.label }}
            </span>
          </div>
          <!-- Hours — line per day, Mon → Sun -->
          <div v-if="hoursDays.length" class="mb-2 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2 text-[11px] uppercase tracking-wider font-semibold text-slate-500 mb-1.5">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Hours <span v-if="hoursTz" class="text-slate-400 font-normal normal-case">· {{ hoursTz }}</span>
            </div>
            <div class="space-y-0.5">
              <div v-for="d in hoursDays" :key="d.day" class="flex items-center justify-between text-[12px]">
                <span class="text-slate-500 w-10">{{ d.day }}</span>
                <span :class="d.label === 'Closed' ? 'text-slate-400 italic' : 'text-slate-800'">{{ d.label }}</span>
              </div>
            </div>
          </div>
          <div v-if="data.founded_year" class="flex items-center gap-2 text-[12px] text-slate-700 mb-2">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            Founded {{ data.founded_year }}
          </div>
          <div v-if="data.contact_email" class="flex items-center gap-2 text-[12px] text-slate-700 mb-2 truncate">
            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.991 5.727a2 2 0 01-2.009 0L2 7"/></svg>
            <span class="truncate">{{ data.contact_email }}</span>
          </div>
          <div v-if="data.phone" class="flex items-center gap-2 text-[12px] text-slate-700 mb-2">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
            {{ data.phone }}
          </div>
        </div>

        <!-- Shipping / Returns — break-words + overflow-hidden so a long
             unbroken run like "TESTTESTTESTTEST..." wraps instead of
             pushing the card off the sidebar. -->
        <div v-if="data.shipping_info" class="bg-white border border-slate-200 rounded-lg p-5 overflow-hidden">
          <h3 class="text-sm font-semibold text-slate-900 mb-2">🚚 Shipping</h3>
          <p class="text-[12px] text-slate-600 leading-relaxed break-words">{{ data.shipping_info }}</p>
        </div>
        <div v-if="data.return_policy" class="bg-white border border-slate-200 rounded-lg p-5 overflow-hidden">
          <h3 class="text-sm font-semibold text-slate-900 mb-2">↩ Returns</h3>
          <p class="text-[12px] text-slate-600 leading-relaxed break-words">{{ data.return_policy }}</p>
        </div>

        <!-- USP badges — single wrapping row of icon chips. Compact bar
             below Business Details so trust signals catch the eye without
             stealing sidebar height. -->
        <div v-if="uspBadges.length" class="bg-white border border-slate-200 rounded-lg p-5">
          <h3 class="text-sm font-semibold text-slate-900 mb-3">Highlights</h3>
          <div class="flex flex-wrap gap-1.5">
            <div v-for="u in uspBadges" :key="u.key" class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-indigo-50 border border-indigo-100">
              <span class="text-[13px] leading-none">{{ u.icon }}</span>
              <span class="text-[11px] font-medium text-indigo-900 leading-none">{{ u.label }}</span>
            </div>
          </div>
        </div>

        <div v-if="paymentMethodsList.length" class="bg-white border border-slate-200 rounded-lg p-5">
          <h3 class="text-sm font-semibold text-slate-900 mb-2">Payment</h3>
          <div class="flex flex-wrap gap-1.5">
            <span v-for="pm in paymentMethodsList" :key="pm" class="text-[11px] px-2 py-0.5 rounded border border-slate-200 bg-slate-50 text-slate-700">{{ pm }}</span>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onUnmounted } from 'vue'
import { USP_OPTIONS } from '@/data/uspOptions'
import { humanize as humanizeHours, daysList as hoursDaysList, openStatus } from '@/composables/useBusinessHours'

const props = defineProps({
  data: { type: Object, default: () => ({}) },
  logo: { type: [File, String, null], default: null },
})

// Sample product cards — pure fillers so the product-catalog area has
// realistic shape during signup. Names are common research peptides so
// the applicant can see how their eventual product data will render.
const sampleProducts = [
  { name: 'BPC-157 (5mg)', price: '29.99' },
  { name: 'TB-500 (10mg)', price: '49.00' },
  { name: 'Semaglutide (5mg)', price: '89.00' },
  { name: 'Tirzepatide (10mg)', price: '129.00' },
  { name: 'GHK-Cu (50mg)', price: '19.99' },
  { name: 'CJC-1295 (5mg)', price: '39.50' },
]

// URL-safe slug preview from company name for the faux address bar.
const slugPreview = computed(() => {
  const raw = (props.data?.name || 'your-brand').toString().toLowerCase().trim()
  return raw.replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'your-brand'
})

// Logo File → object URL, revoke on change/unmount.
const objectUrl = ref(null)
watch(() => props.logo, (next) => {
  if (objectUrl.value) { URL.revokeObjectURL(objectUrl.value); objectUrl.value = null }
  if (next instanceof File) objectUrl.value = URL.createObjectURL(next)
}, { immediate: true })
onUnmounted(() => { if (objectUrl.value) URL.revokeObjectURL(objectUrl.value) })
const logoUrl = computed(() => {
  if (objectUrl.value) return objectUrl.value
  if (typeof props.logo === 'string' && props.logo) return props.logo
  return null
})

const paymentMethodsList = computed(() => {
  const raw = props.data?.payment_methods
  return Array.isArray(raw) ? raw.filter(Boolean) : []
})

const BADGE_META = {
  reviews_io:   { label: 'Reviews.io',    chip: 'Rio', classes: 'bg-blue-50 text-blue-700 border border-blue-200' },
  trustpilot:   { label: 'Trustpilot',    chip: 'TP',  classes: 'bg-emerald-50 text-emerald-700 border border-emerald-200' },
  google:       { label: 'Google',        chip: 'G',   classes: 'bg-red-50 text-red-700 border border-red-200' },
  pepreviewpro: { label: 'PepReviewPro',  chip: 'PRP', classes: 'bg-amber-50 text-amber-700 border border-amber-200' },
}
// Selected USPs → resolved to icon + label using the shared preset list.
// Silently drops any key that's not in the preset (e.g. stale rows before
// a preset renamed a key).
const USP_MAP = Object.fromEntries(USP_OPTIONS.map(o => [o.key, o]))
const uspBadges = computed(() => {
  const keys = props.data?.usps
  if (!Array.isArray(keys)) return []
  return keys.map(k => USP_MAP[k]).filter(Boolean)
})

// Per-day list for the sidebar display, humanized string as a fallback,
// live open/closed pill.
const hoursDays = computed(() => hoursDaysList(props.data?.business_hours_json))
const hoursHuman = computed(() => humanizeHours(props.data?.business_hours_json))
const openPill = computed(() => openStatus(props.data?.business_hours_json))
const hoursTz = computed(() => hoursDays.value[0]?.tz || '')

const reviewBadges = computed(() => {
  const map = {
    reviews_io: props.data?.reviews_io_url,
    trustpilot: props.data?.trustpilot_url,
    google: props.data?.google_reviews_url,
    pepreviewpro: props.data?.pepreviewpro_url,
  }
  return Object.keys(map)
    .filter(k => map[k] && String(map[k]).trim())
    .map(k => ({ key: k, ...BADGE_META[k] }))
})
</script>
