<template>
  <div
    :class="[
      'flex items-center justify-between gap-4 py-3',
      { 'border-b border-[color:var(--color-hairline)]': divided },
    ]"
  >
    <div class="flex items-center gap-2 min-w-0">
      <span class="text-sm text-[color:var(--color-ink-muted)] truncate">
        {{ label }}
      </span>
      <span v-if="tooltip" class="text-[color:var(--color-ink-subtle)] cursor-help" :title="tooltip">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" />
          <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </span>
    </div>
    <div
      :class="[
        'text-sm font-medium text-[color:var(--color-ink)] text-right',
        { 'ui-mono': mono },
      ]"
    >
      <slot>{{ value }}</slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: '' },
  mono: { type: Boolean, default: true },
  tooltip: { type: String, default: '' },
  divided: { type: Boolean, default: true },
})
</script>
