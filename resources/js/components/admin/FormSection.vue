<template>
  <div class="mb-8">
    <div v-if="title" class="mb-4">
      <h3 class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">{{ title }}</h3>
      <p v-if="description" class="text-sm text-[color:var(--color-ink-muted)] mt-1">{{ description }}</p>
    </div>
    <div class="bg-white border border-[color:var(--color-hairline)] p-5">
      <div :class="columns === 2 ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : 'space-y-4'">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  columns: { type: Number, default: 1 },
})
</script>
