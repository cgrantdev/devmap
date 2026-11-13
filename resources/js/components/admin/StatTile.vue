<template>
  <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5">
    <div class="text-2xl font-semibold text-[color:var(--color-ink)]">{{ n }}</div>
    <div class="text-[11px] text-[color:var(--color-ink-muted)] uppercase tracking-wider mt-1">{{ label }}</div>
  </div>
</template>

<script setup>
defineProps({
  n: { type: [String, Number], default: 0 },
  label: { type: String, default: '' },
})
</script>
