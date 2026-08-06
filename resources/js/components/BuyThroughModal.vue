<template>
  <transition
    enter-active-class="transition-opacity duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="visible"
      class="fixed inset-0 z-[100] bg-black/50 flex items-center justify-center p-4"
      @click.self="close"
    >
      <div class="bg-white rounded-[14px] w-full max-w-md p-6 relative shadow-2xl">
        <button
          @click="close"
          aria-label="Close"
          class="absolute top-3 right-3 text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink)] p-1"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>

        <div class="text-[10px] uppercase tracking-[0.14em] font-semibold text-[color:var(--color-ink-subtle)] mb-1">
          Your discount code
        </div>
        <div class="text-[15px] font-semibold text-[color:var(--color-ink)] leading-snug mb-4 pr-6">
          Save {{ discountPct ? discountPct + '%' : '' }} at {{ brandName || 'this vendor' }} with code below
        </div>

        <!-- Big copy-to-clipboard coupon card -->
        <button
          @click="copy"
          :class="[
            'w-full flex items-center justify-between gap-3 px-4 py-3.5 rounded-[10px] border-2 border-dashed transition-all mb-4',
            copied
              ? 'border-emerald-400 bg-emerald-50 text-emerald-800'
              : 'border-amber-400 bg-amber-50 text-amber-900 hover:bg-amber-100',
          ]"
          :title="copied ? 'Copied!' : 'Click to copy'"
        >
          <span class="ui-mono text-2xl font-bold tracking-wide">{{ code }}</span>
          <span class="flex items-center gap-1.5 text-[12px] font-semibold">
            <template v-if="copied">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Copied
            </template>
            <template v-else>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
              Copy
            </template>
          </span>
        </button>

        <p class="text-[12px] text-[color:var(--color-ink-muted)] leading-relaxed mb-5">
          The discount often applies automatically after clicking Continue. If it doesn't at checkout, paste this code manually.
        </p>

        <a
          :href="destination"
          @click="handleContinue"
          target="_blank"
          rel="noopener noreferrer nofollow sponsored"
          class="ui-focus relative w-full inline-flex items-center justify-center gap-2 h-11 px-5 rounded-[10px] text-white font-semibold text-[14px] bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] hover:-translate-y-[0.5px] transition-all overflow-hidden"
        >
          <!-- Progress fill that drains as the countdown ticks. Sits behind
               the label; opacity kept low so text stays readable. -->
          <span
            class="absolute inset-y-0 left-0 bg-white/15 transition-[width] duration-1000 ease-linear pointer-events-none"
            :style="{ width: countdown > 0 ? ((countdown / AUTO_REDIRECT_SECONDS) * 100) + '%' : '0%' }"
            aria-hidden="true"
          ></span>
          <span class="relative flex items-center gap-2">
            Continue to {{ brandName || 'vendor' }}
            <span v-if="countdown > 0" class="ui-mono text-[12px] font-semibold text-white/70">
              {{ countdown }}s
            </span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
          </span>
        </a>
        <button
          v-if="countdown > 0"
          @click="cancelAutoRedirect"
          class="mt-2 w-full text-center text-[11px] text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink)] transition-colors"
        >
          Cancel auto-redirect
        </button>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref } from 'vue'

const AUTO_REDIRECT_SECONDS = 4

const visible = ref(false)
const destination = ref('')
const code = ref('PMAP')
const brandName = ref('')
const discountPct = ref(null)
const copied = ref(false)
const countdown = ref(0)
let _copyTimer = null
let _countdownTimer = null

/**
 * Open the modal. Called by parent pages via ref: modal.value.open({...}).
 * Kept intentionally imperative — this is a UI overlay driven by click
 * events, not app state.
 *
 * Also starts a 4-second auto-redirect countdown. If the user does nothing,
 * we open the vendor page for them (opt-out via 'Cancel auto-redirect' link
 * or clicking Copy — copying implies they want more time to grab the code).
 */
function open({ destination: dest, code: c, brandName: b, discountPct: pct }) {
  destination.value = dest || ''
  code.value = (c || 'PMAP').toUpperCase()
  brandName.value = b || ''
  discountPct.value = pct ? Math.round(pct) : null
  copied.value = false
  visible.value = true
  startCountdown()
}

function startCountdown() {
  clearInterval(_countdownTimer)
  countdown.value = AUTO_REDIRECT_SECONDS
  _countdownTimer = setInterval(() => {
    countdown.value -= 1
    if (countdown.value <= 0) {
      clearInterval(_countdownTimer)
      autoRedirect()
    }
  }, 1000)
}

function cancelAutoRedirect() {
  clearInterval(_countdownTimer)
  countdown.value = 0
}

function autoRedirect() {
  if (!destination.value) return
  window.open(destination.value, '_blank', 'noopener,noreferrer')
  visible.value = false
}

function close() {
  visible.value = false
  clearTimeout(_copyTimer)
  clearInterval(_countdownTimer)
  countdown.value = 0
  copied.value = false
}

async function copy() {
  // Copying implies the user wants a moment to sit with the code — cancel
  // the auto-redirect so they don't get yanked mid-thought.
  cancelAutoRedirect()
  const text = code.value
  try {
    await navigator.clipboard.writeText(text)
    copied.value = true
  } catch {
    const ta = document.createElement('textarea')
    ta.value = text; ta.style.cssText = 'position:fixed;left:-9999px'
    document.body.appendChild(ta); ta.select()
    try { document.execCommand('copy'); copied.value = true } catch {}
    document.body.removeChild(ta)
  }
  clearTimeout(_copyTimer)
  _copyTimer = setTimeout(() => { copied.value = false }, 2000)
}

function handleContinue() {
  // Manual click on Continue — kill both timers and let the anchor do its thing.
  visible.value = false
  clearTimeout(_copyTimer)
  clearInterval(_countdownTimer)
}

defineExpose({ open, close })
</script>
