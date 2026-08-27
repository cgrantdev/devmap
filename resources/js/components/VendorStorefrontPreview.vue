<template>
  <!-- Preview that mirrors the real /brand/{slug} layout tick-for-tick:
       same 3/4 grid, same header cluster (name → stars/rating/location →
       tagline → description), same sidebar order (Business Details →
       Highlights → Policies). Vendors see exactly the storefront they get. -->
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

    <!-- Brand header — mirrors the real page's structure: logo + name row,
         rating cluster below name, tagline below rating, description in a
         max-w-2xl block, coupon + Visit buttons at right. -->
    <div class="border-b border-slate-200 bg-white">
      <div class="px-5 lg:px-8 py-5 lg:py-6">
        <div class="flex items-center gap-4 mb-3">
          <!-- Logo -->
          <div class="w-12 h-12 lg:w-16 lg:h-16 flex-shrink-0 border border-slate-200 bg-white flex items-center justify-center overflow-hidden rounded">
            <img v-if="logoUrl" :src="logoUrl" :alt="data.name || 'Logo'" class="w-full h-full object-contain p-1" />
            <span v-else class="text-lg lg:text-xl font-bold text-slate-400 select-none">{{ initials }}</span>
          </div>

          <!-- Name + rating cluster + tagline -->
          <div class="flex-1 min-w-0">
            <h1 class="text-xl lg:text-3xl font-semibold tracking-tight text-slate-900 truncate">{{ data.name || 'Your brand name' }}</h1>
            <div class="flex items-center mt-0.5 flex-wrap">
              <span class="inline-flex items-center gap-0">
                <svg v-for="n in 5" :key="n" class="w-3 lg:w-3.5 h-3 lg:h-3.5 -mr-px text-slate-300" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
              </span>
              <span class="text-[12px] lg:text-[13px] font-semibold text-slate-900 ml-1.5">0.0</span>
              <span class="ml-1 text-[12px] lg:text-[13px] text-slate-500">(0)</span>
              <span v-if="data.location" class="hidden sm:flex items-center gap-1 ml-2 text-[12px] lg:text-[13px] text-slate-500">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 00-8 8c0 5.5 8 12 8 12s8-6.5 8-12a8 8 0 00-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ data.location }}
              </span>
              <span v-if="data.contact_email" class="hidden sm:flex items-center gap-1 ml-2 text-[12px] lg:text-[13px] text-slate-500 truncate max-w-[180px]">
                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m22 7-8.991 5.727a2 2 0 01-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                <span class="truncate">{{ data.contact_email }}</span>
              </span>
            </div>
            <div v-if="data.tagline" class="mt-1.5">
              <span class="text-[12px] lg:text-[14px] text-slate-500 italic">{{ data.tagline }}</span>
            </div>
            <div v-else class="mt-1.5">
              <span class="text-[12px] lg:text-[14px] text-slate-300 italic">Your tagline will appear here</span>
            </div>
          </div>

          <!-- Visit button -->
          <button type="button" class="hidden md:inline-flex items-center gap-2 h-10 lg:h-11 px-5 lg:px-6 text-[13px] lg:text-[14px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow rounded flex-shrink-0 cursor-default">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
            Visit website
          </button>

          <!-- Coupon -->
          <div class="hidden md:inline-flex items-center gap-3 h-10 lg:h-11 px-4 lg:px-5 border-2 border-dashed border-emerald-300 bg-emerald-50 flex-shrink-0 rounded">
            <span class="text-[9px] lg:text-[10px] uppercase tracking-[0.1em] font-semibold text-emerald-600">Coupon</span>
            <span class="ui-mono text-[15px] lg:text-[18px] font-bold text-emerald-800 tracking-widest">PMAP</span>
          </div>
        </div>

        <!-- Description below header row (max-w-2xl) — matches real page -->
        <div v-if="data.description" class="max-w-2xl">
          <p class="text-[13px] text-slate-500 leading-relaxed line-clamp-3">{{ data.description }}</p>
        </div>
        <div v-else class="max-w-2xl">
          <p class="text-[13px] text-slate-300 italic leading-relaxed">Your company description will appear here — this is what buyers read to decide.</p>
        </div>
      </div>
    </div>

    <!-- Body: 3/4 grid matching the real /brand/{slug} page -->
    <div class="px-5 lg:px-8 py-6 lg:py-10 bg-slate-50">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
        <!-- Main column (products) -->
        <div class="lg:col-span-3 space-y-6">
          <section>
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-[11px] uppercase tracking-[0.08em] font-semibold text-slate-500">
                Products <span class="ui-mono">({{ sampleProducts.length }})</span>
              </h2>
              <span class="text-[11px] text-slate-400 italic">Imported after approval</span>
            </div>

            <!-- Faux search input to mirror the real page control row -->
            <div class="relative mb-4">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <div class="w-full h-9 pl-9 pr-3 border border-slate-200 rounded bg-white text-[12px] text-slate-400 leading-9">Search products…</div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
              <div v-for="p in sampleProducts" :key="p.name" class="border border-slate-200 rounded-md overflow-hidden flex flex-col bg-white">
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
          </section>
        </div>

        <!-- Sidebar — order matches real page: Business Details → Highlights →
             Policies. -->
        <aside class="lg:col-span-1">
          <div class="space-y-6">
            <!-- Business Details -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
              <div class="flex items-center justify-between gap-2 mb-4">
                <h3 class="text-lg text-slate-900">Business Details</h3>
                <span v-if="openPill" :class="['text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded-full', openPill.open ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700']">
                  ● {{ openPill.label }}
                </span>
              </div>

              <div v-if="hoursDays.length" class="mb-3 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-xs text-slate-500 mb-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  <span>Hours <span v-if="hoursTz" class="text-slate-400">· {{ hoursTz }}</span></span>
                </div>
                <div class="space-y-0.5 pl-6">
                  <div v-for="d in hoursDays" :key="d.day" class="flex items-center justify-between text-sm">
                    <span class="text-slate-500 w-12">{{ d.day }}</span>
                    <span :class="d.label === 'Closed' ? 'text-slate-400 italic' : 'text-slate-900'">{{ d.label }}</span>
                  </div>
                </div>
              </div>

              <div class="space-y-3">
                <div v-if="data.founded_year" class="flex items-start gap-3">
                  <svg class="w-4 h-4 text-slate-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  <div>
                    <div class="text-xs text-slate-500">Established</div>
                    <div class="text-sm text-slate-900">{{ data.founded_year }}</div>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <svg class="w-4 h-4 text-slate-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                  <div>
                    <div class="text-xs text-slate-500">Rating</div>
                    <div class="text-sm text-slate-900">0.0 / 5.0 (0 reviews)</div>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <svg class="w-4 h-4 text-slate-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 002 0l7-4A2 2 0 0021 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73z"/><polyline points="3.29 7 12 12 20.71 7"/><path d="m7.5 4.27 9 5.15"/></svg>
                  <div>
                    <div class="text-xs text-slate-500">Products</div>
                    <div class="text-sm text-slate-900">Imported after approval</div>
                  </div>
                </div>
                <div v-if="data.phone" class="flex items-start gap-3">
                  <svg class="w-4 h-4 text-slate-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                  <div>
                    <div class="text-xs text-slate-500">Phone</div>
                    <div class="text-sm text-slate-900">{{ data.phone }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Policies (Shipping + Returns) — sits directly under
                 Business Details on the real live brand page. -->
            <div v-if="data.shipping_info || data.return_policy" class="bg-white border border-slate-200 rounded-lg p-5 overflow-hidden">
              <h3 class="text-lg text-slate-900 mb-4">Policies</h3>
              <div v-if="data.shipping_info" class="mb-3">
                <div class="text-xs text-slate-500 mb-1">🚚 Shipping</div>
                <p class="text-[13px] text-slate-700 leading-relaxed break-words">{{ data.shipping_info }}</p>
              </div>
              <div v-if="data.return_policy">
                <div class="text-xs text-slate-500 mb-1">↩ Returns</div>
                <p class="text-[13px] text-slate-700 leading-relaxed break-words">{{ data.return_policy }}</p>
              </div>
            </div>

            <!-- Highlights (USPs) — matches the sidebar's third-slot
                 position on the live page. -->
            <div v-if="uspBadges.length" class="bg-white border border-slate-200 rounded-lg p-5">
              <h3 class="text-lg text-slate-900 mb-3">Highlights</h3>
              <div class="flex flex-wrap gap-1.5">
                <div v-for="u in uspBadges" :key="u.key" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-100">
                  <span class="text-[14px] leading-none">{{ u.icon }}</span>
                  <span class="text-[12px] font-medium text-indigo-900 leading-none">{{ u.label }}</span>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onUnmounted } from 'vue'
import { USP_OPTIONS } from '@/data/uspOptions'
import { daysList as hoursDaysList, openStatus } from '@/composables/useBusinessHours'

const props = defineProps({
  data: { type: Object, default: () => ({}) },
  logo: { type: [File, String, null], default: null },
})

// Sample product cards — pure fillers so the product-catalog area has
// realistic shape during signup.
const sampleProducts = [
  { name: 'BPC-157 (5mg)', price: '29.99' },
  { name: 'TB-500 (10mg)', price: '49.00' },
  { name: 'Semaglutide (5mg)', price: '89.00' },
  { name: 'Tirzepatide (10mg)', price: '129.00' },
  { name: 'GHK-Cu (50mg)', price: '19.99' },
  { name: 'CJC-1295 (5mg)', price: '39.50' },
]

const slugPreview = computed(() => {
  const raw = (props.data?.name || 'your-brand').toString().toLowerCase().trim()
  return raw.replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'your-brand'
})

const initials = computed(() => {
  const name = (props.data?.name || '').trim()
  if (!name) return 'LOGO'
  return name.split(/\s+/).slice(0, 2).map(w => w[0]?.toUpperCase()).join('') || 'LOGO'
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

const USP_MAP = Object.fromEntries(USP_OPTIONS.map(o => [o.key, o]))
const uspBadges = computed(() => {
  const keys = props.data?.usps
  if (!Array.isArray(keys)) return []
  return keys.map(k => USP_MAP[k]).filter(Boolean)
})

const hoursDays = computed(() => hoursDaysList(props.data?.business_hours_json))
const openPill = computed(() => openStatus(props.data?.business_hours_json))
const hoursTz = computed(() => hoursDays.value[0]?.tz || '')
</script>
