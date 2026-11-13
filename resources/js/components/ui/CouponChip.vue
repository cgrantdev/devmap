<template>
  <!-- Compact copy-to-clipboard code for tables and tight rows; the full
       CouponTicket is for cards. Stops the click so a surrounding row or
       card link doesn't fire. -->
  <button
    type="button"
    class="ui-focus relative z-10 inline-flex items-center gap-1 h-6 px-1.5 rounded-[6px] border border-dashed border-emerald-300 bg-white/80 hover:bg-emerald-50 text-emerald-700 transition-colors"
    :title="copied ? 'Copied' : `Copy code ${displayCode}`"
    :aria-label="copied ? `Code ${displayCode} copied` : `Copy coupon code ${displayCode}`"
    @click.stop.prevent="onClick"
  >
    <span class="text-[9px] font-bold uppercase tracking-[0.08em] text-emerald-600">{{ copied ? 'Copied' : 'Code' }}</span>
    <span class="ui-mono text-[11px] font-bold uppercase">{{ displayCode }}</span>
    <svg v-if="!copied" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2" /><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" /></svg>
    <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useCopyText } from '@/composables/useCopyText'

const props = defineProps({
  code: { type: String, required: true },
})

const displayCode = computed(() => String(props.code || '').trim().toUpperCase())
const { copied, copy } = useCopyText()

function onClick() {
  copy(displayCode.value)
}
</script>
