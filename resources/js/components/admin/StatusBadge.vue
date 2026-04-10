<template>
  <span :class="['inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold', colorClass]">
    <span class="w-1.5 h-1.5 rounded-full" :class="dotClass" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
  label: { type: String, default: '' },
})

const displayLabel = computed(() => props.label || props.status)

const colorClass = computed(() => ({
  active: 'bg-[color:var(--color-verified-bg)] text-[#065F46]',
  published: 'bg-[color:var(--color-verified-bg)] text-[#065F46]',
  approved: 'bg-[color:var(--color-verified-bg)] text-[#065F46]',
  pending: 'bg-[color:var(--color-caution-bg)] text-[#92400E]',
  draft: 'bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)]',
  inactive: 'bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)]',
  rejected: 'bg-[color:var(--color-danger-bg)] text-[#991B1B]',
}[props.status] || 'bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)]'))

const dotClass = computed(() => ({
  active: 'bg-[color:var(--color-verified)]',
  published: 'bg-[color:var(--color-verified)]',
  approved: 'bg-[color:var(--color-verified)]',
  pending: 'bg-[color:var(--color-caution)]',
  draft: 'bg-[color:var(--color-ink-subtle)]',
  inactive: 'bg-[color:var(--color-ink-subtle)]',
  rejected: 'bg-[color:var(--color-danger)]',
}[props.status] || 'bg-[color:var(--color-ink-subtle)]'))
</script>
