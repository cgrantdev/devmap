<template>
  <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
    <!-- Search -->
    <div class="relative flex-1 max-w-sm">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input
        :value="search"
        @input="$emit('update:search', $event.target.value)"
        type="text"
        :placeholder="placeholder"
        class="w-full h-9 pl-9 pr-4 text-sm border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15 transition-colors"
      />
    </div>

    <!-- Status tabs -->
    <div v-if="tabs.length" class="flex items-center gap-1 bg-[color:var(--color-hairline-soft)] p-0.5">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="$emit('update:status', tab.value)"
        :class="[
          'h-7 px-3 text-[12px] font-medium transition-colors',
          status === tab.value
            ? 'bg-white text-[color:var(--color-ink)] shadow-sm'
            : 'text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]',
        ]"
      >
        {{ tab.label }}
        <span v-if="tab.count !== undefined" class="ml-1 ui-mono text-[10px]">{{ tab.count }}</span>
      </button>
    </div>

    <!-- Extra slot for sort/actions -->
    <slot />
  </div>
</template>

<script setup>
defineProps({
  search: { type: String, default: '' },
  placeholder: { type: String, default: 'Search...' },
  status: { type: String, default: 'all' },
  tabs: { type: Array, default: () => [] },
})

defineEmits(['update:search', 'update:status'])
</script>
