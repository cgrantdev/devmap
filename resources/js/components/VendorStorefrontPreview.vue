<template>
  <!-- Compact preview of what the vendor's Peptidemap storefront will
       look like based on their entered fields. Used in two places:
         1. /become-a-vendor onboarding — live-updates as the applicant types
         2. Logged-in vendor dashboard — later, hooks the same shape up to
            an inline-edit mode with save-on-blur
       Kept visually faithful to /brand/{slug} without re-inventing every
       detail — enough to show the applicant the shape of the finished page. -->
  <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
      <div class="text-[11px] uppercase tracking-wider font-semibold text-slate-500">Live storefront preview</div>
      <div class="text-[10px] text-slate-400">Updates as you type</div>
    </div>

    <!-- Header: logo + name + rating placeholder -->
    <div class="p-5 border-b border-slate-100">
      <div class="flex items-center gap-3">
        <div class="w-14 h-14 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200 flex-shrink-0">
          <img v-if="logoUrl" :src="logoUrl" :alt="data.name || 'Logo'" class="w-full h-full object-contain p-1" />
          <span v-else class="text-[10px] text-slate-400">Logo</span>
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-[16px] font-semibold text-slate-900 truncate">{{ data.name || 'Your brand name' }}</div>
          <div class="flex items-center gap-1 mt-0.5">
            <svg v-for="n in 5" :key="n" class="w-3 h-3 text-slate-300" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
            <span class="text-[11px] text-slate-500 ml-1">(reviews shown after approval)</span>
          </div>
          <div v-if="data.location" class="text-[11px] text-slate-500 mt-0.5">Ships from {{ data.location }}</div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="p-5 border-b border-slate-100">
      <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-2">About</div>
      <p v-if="data.description" class="text-[13px] text-slate-700 leading-relaxed whitespace-pre-line line-clamp-6">{{ data.description }}</p>
      <p v-else class="text-[13px] text-slate-400 italic">Your company description will appear here.</p>
    </div>

    <!-- Reviews & Trust badges preview -->
    <div v-if="reviewBadges.length" class="p-5 border-b border-slate-100">
      <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-2">Third-party reviews</div>
      <div class="flex flex-wrap gap-2">
        <div v-for="b in reviewBadges" :key="b.key" class="flex items-center gap-1.5 px-2 py-1 rounded border border-slate-200 bg-slate-50">
          <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[9px] font-bold" :class="b.classes">{{ b.chip }}</span>
          <span class="text-[11px] font-semibold text-slate-700">{{ b.label }}</span>
        </div>
      </div>
    </div>

    <!-- Shipping / Returns / Business hours -->
    <div v-if="data.shipping_info || data.return_policy || data.business_hours" class="p-5 border-b border-slate-100 grid grid-cols-1 gap-3">
      <div v-if="data.shipping_info">
        <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Shipping</div>
        <p class="text-[12px] text-slate-700 leading-relaxed line-clamp-3">{{ data.shipping_info }}</p>
      </div>
      <div v-if="data.return_policy">
        <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Returns</div>
        <p class="text-[12px] text-slate-700 leading-relaxed line-clamp-3">{{ data.return_policy }}</p>
      </div>
      <div v-if="data.business_hours">
        <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Hours</div>
        <p class="text-[12px] text-slate-700">{{ data.business_hours }}</p>
      </div>
    </div>

    <!-- Payment methods -->
    <div v-if="paymentMethodsList.length" class="p-5 border-b border-slate-100">
      <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-2">Payment</div>
      <div class="flex flex-wrap gap-1.5">
        <span v-for="pm in paymentMethodsList" :key="pm" class="text-[11px] px-2 py-0.5 rounded border border-slate-200 bg-white text-slate-700">{{ pm }}</span>
      </div>
    </div>

    <!-- Contact -->
    <div v-if="data.contact_email || data.phone" class="p-5">
      <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-2">Contact</div>
      <div v-if="data.contact_email" class="text-[12px] text-slate-700 truncate">✉ {{ data.contact_email }}</div>
      <div v-if="data.phone" class="text-[12px] text-slate-700 mt-1">☎ {{ data.phone }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onUnmounted } from 'vue'

const props = defineProps({
  // Everything the preview renders. Passed as a plain object so a parent
  // form can just spread its formData through:
  //   :data="{ name: formData.companyName, description: formData.companyDescription, ... }"
  data: { type: Object, default: () => ({}) },
  // A File object (from an <input type=file>) OR a URL string. If a File,
  // we build a temporary object URL so the applicant sees the actual logo.
  logo: { type: [File, String, null], default: null },
})

// Rebuild the object URL when the File changes; revoke the old one so
// we don't leak memory across re-uploads.
const objectUrl = ref(null)
watch(() => props.logo, (next, prev) => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value)
    objectUrl.value = null
  }
  if (next instanceof File) {
    objectUrl.value = URL.createObjectURL(next)
  }
}, { immediate: true })
onUnmounted(() => {
  if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
})
const logoUrl = computed(() => {
  if (objectUrl.value) return objectUrl.value
  if (typeof props.logo === 'string' && props.logo) return props.logo
  return null
})

const paymentMethodsList = computed(() => {
  const raw = props.data?.payment_methods
  if (Array.isArray(raw)) return raw.filter(Boolean)
  return []
})

// Small trust-badge previews (same layout logic as brand page, minimal).
const BADGE_META = {
  reviews_io:   { label: 'Reviews.io',    chip: 'Rio', classes: 'bg-blue-50 text-blue-700 border border-blue-200' },
  trustpilot:   { label: 'Trustpilot',    chip: 'TP',  classes: 'bg-emerald-50 text-emerald-700 border border-emerald-200' },
  google:       { label: 'Google',        chip: 'G',   classes: 'bg-red-50 text-red-700 border border-red-200' },
  pepreviewpro: { label: 'PepReviewPro',  chip: 'PRP', classes: 'bg-amber-50 text-amber-700 border border-amber-200' },
}
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
