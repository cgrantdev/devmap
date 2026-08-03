<template>
  <button
    type="button"
    @click.stop.prevent="toggle"
    :aria-pressed="active"
    :aria-label="active ? 'Remove from wishlist' : 'Add to wishlist'"
    :title="active ? 'Remove from wishlist' : 'Save + get price alerts'"
    :class="[
      'wishlist-heart inline-flex items-center justify-center rounded-full transition-all duration-200 select-none',
      sizeClasses,
      active
        ? 'bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200'
        : 'bg-white/90 text-slate-500 hover:text-rose-500 hover:bg-white border border-black/[0.06] shadow-[0_1px_2px_rgba(10,11,14,0.06)]',
      loading ? 'opacity-70 cursor-wait' : 'cursor-pointer',
    ]"
    :disabled="loading"
  >
    <svg
      :class="[iconClasses, 'transition-transform', bump ? 'scale-125' : 'scale-100']"
      viewBox="0 0 24 24"
      :fill="active ? 'currentColor' : 'none'"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
    </svg>
  </button>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
  type: { type: String, required: true, validator: (v) => ['product', 'category'].includes(v) },
  id: { type: Number, required: true },
  // Optional; when omitted we derive from the shared page.props.wishlist.
  initialActive: { type: Boolean, default: null },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
})

const emit = defineEmits(['toggle'])

const page = usePage()

// Derive initial state from shared Inertia props unless the parent overrode it.
const active = ref(
  props.initialActive !== null
    ? !!props.initialActive
    : isActiveFromShared()
)
const loading = ref(false)
const bump = ref(false) // little pop animation on toggle

function isActiveFromShared() {
  const w = page.props.wishlist
  if (!w) return false
  const list = props.type === 'product' ? w.product_ids : w.category_ids
  return Array.isArray(list) && list.includes(props.id)
}

// Keep in sync when the shared prop changes (e.g. after a page navigation
// where the server has re-shared the wishlist).
watch(
  () => page.props.wishlist,
  () => {
    if (props.initialActive === null) active.value = isActiveFromShared()
  },
  { deep: true }
)

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'w-7 h-7'
    case 'lg': return 'w-11 h-11'
    default: return 'w-9 h-9'
  }
})

const iconClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'w-3.5 h-3.5'
    case 'lg': return 'w-5 h-5'
    default: return 'w-4 h-4'
  }
})

async function toggle() {
  emit('toggle')

  // Guest → send to login with a redirect back to current path
  const user = page.props.auth?.user
  if (!user) {
    const redirect = typeof window !== 'undefined'
      ? window.location.pathname + window.location.search
      : '/'
    router.visit('/login?redirect=' + encodeURIComponent(redirect))
    return
  }

  // Optimistic toggle
  const prev = active.value
  active.value = !prev
  bump.value = true
  setTimeout(() => { bump.value = false }, 200)
  loading.value = true

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      || page.props.csrf_token
    const res = await fetch('/wishlist/toggle', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf || '',
      },
      body: JSON.stringify({ type: props.type, id: props.id }),
    })
    if (!res.ok) throw new Error('toggle failed')
    const json = await res.json()
    active.value = !!json.in_wishlist

    // Update shared prop in place so other hearts on the page reflect the change.
    const w = page.props.wishlist
    if (w) {
      const key = props.type === 'product' ? 'product_ids' : 'category_ids'
      const arr = Array.isArray(w[key]) ? [...w[key]] : []
      const idx = arr.indexOf(props.id)
      if (json.in_wishlist && idx === -1) arr.push(props.id)
      if (!json.in_wishlist && idx !== -1) arr.splice(idx, 1)
      w[key] = arr
      w.count = json.wishlist_count
    }
  } catch (e) {
    // Roll back optimistic update on failure
    active.value = prev
    console.warn('WishlistHeart toggle failed', e)
  } finally {
    loading.value = false
  }
}
</script>
