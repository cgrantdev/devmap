<template>
  <!-- Copy-to-clipboard coupon ticket, styled like the homepage hero's
       code pill. Sits inside cards whose whole surface is a link, so the
       click is stopped here (relative z-10 lifts it above the card's
       stretched-link overlay) and only copies the code. -->
  <button
    type="button"
    :class="[
      'ui-focus group/ticket relative z-10 flex w-full items-center gap-2 text-left',
      'rounded-[10px] border-2 border-dashed transition-colors',
      tone === 'solid'
        ? 'bg-emerald-500 hover:bg-emerald-600 border-emerald-200 text-white shadow-sm'
        : 'bg-emerald-50 hover:bg-emerald-100 border-emerald-300 text-emerald-800',
      size === 'sm' ? 'h-10 pl-2.5 pr-1.5' : 'h-12 pl-3 pr-2',
    ]"
    :title="copied ? 'Copied' : `Copy code ${displayCode}`"
    :aria-label="copied ? `Code ${displayCode} copied` : `Copy coupon code ${displayCode}`"
    @click.stop.prevent="copy"
  >
    <span class="flex min-w-0 flex-1 flex-col items-start leading-none">
      <span
        :class="[
          'text-[9px] font-bold uppercase tracking-[0.14em] whitespace-nowrap',
          tone === 'solid' ? 'text-emerald-50' : 'text-emerald-600',
        ]"
      >{{ copied ? 'Copied!' : label }}</span>
      <span
        :class="[
          'ui-mono mt-1 max-w-full truncate font-black uppercase tracking-[0.06em]',
          size === 'sm' ? 'text-[14px]' : 'text-[15px] sm:text-[17px]',
        ]"
      >{{ displayCode }}</span>
    </span>
    <span
      aria-hidden="true"
      :class="[
        'inline-flex flex-shrink-0 items-center justify-center rounded-md transition-colors',
        size === 'sm' ? 'h-7 w-7' : 'h-8 w-8',
        tone === 'solid' ? 'bg-white/15 group-hover/ticket:bg-white/25' : 'bg-emerald-600/10 group-hover/ticket:bg-emerald-600/20',
      ]"
    >
      <svg v-if="!copied" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" /></svg>
      <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
    </span>
  </button>
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'

const props = defineProps({
  code: { type: String, required: true },
  // Vendor discount for this code, e.g. 10 -> "10% off".
  percent: { type: [Number, String], default: null },
  // 'sm' for dense grids, 'md' otherwise.
  size: { type: String, default: 'md' },
  // 'solid' (hero-style green) or 'soft' (light green on busy surfaces).
  tone: { type: String, default: 'solid' },
})

const emit = defineEmits(['copied'])

const displayCode = computed(() => String(props.code || '').trim().toUpperCase())

const percentValue = computed(() => {
  const n = parseFloat(props.percent)
  if (!n || n <= 0 || n >= 100) return null
  return Number.isInteger(n) ? n : Number(n.toFixed(1))
})

const label = computed(() => (percentValue.value ? `${percentValue.value}% off · code` : 'Coupon code'))

const copied = ref(false)
let timer = null

async function copy() {
  const text = displayCode.value
  if (!text) return
  let ok = false
  try {
    await navigator.clipboard.writeText(text)
    ok = true
  } catch {
    // Older Safari / insecure contexts: fall back to a hidden textarea.
    try {
      const ta = document.createElement('textarea')
      ta.value = text
      ta.setAttribute('readonly', '')
      ta.style.position = 'fixed'
      ta.style.opacity = '0'
      document.body.appendChild(ta)
      ta.select()
      ok = document.execCommand('copy')
      document.body.removeChild(ta)
    } catch {
      ok = false
    }
  }
  if (!ok) return
  copied.value = true
  emit('copied', text)
  clearTimeout(timer)
  timer = setTimeout(() => { copied.value = false }, 1800)
}

onBeforeUnmount(() => clearTimeout(timer))
</script>
