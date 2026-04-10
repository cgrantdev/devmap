<template>
  <div>
    <!-- Header with breadcrumb + save -->
    <div class="flex items-start justify-between gap-4 mb-6">
      <div>
        <div class="flex items-center gap-1.5 text-[12px] text-[color:var(--color-ink-subtle)] mb-2">
          <a :href="backHref" class="hover:text-[color:var(--color-ink)] transition-colors flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            {{ backLabel }}
          </a>
        </div>
        <h1 class="ui-display text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">{{ title }}</h1>
      </div>
      <div class="flex items-center gap-3 flex-shrink-0">
        <span v-if="saved" class="text-[12px] text-[color:var(--color-verified)] font-medium flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Saved
        </span>
        <slot name="actions" />
        <button
          type="button"
          @click="$emit('save')"
          :disabled="saving"
          class="h-9 px-5 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all disabled:opacity-50"
        >
          {{ saving ? 'Saving...' : 'Save' }}
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div v-if="tabs.length" class="border-b border-[color:var(--color-hairline)] mb-6">
      <div class="flex gap-0">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="$emit('update:activeTab', tab.id)"
          :class="[
            'px-4 py-2.5 text-[13px] font-medium border-b-2 transition-colors -mb-px',
            activeTab === tab.id
              ? 'border-[color:var(--color-accent-600)] text-[color:var(--color-accent-600)]'
              : 'border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:border-[color:var(--color-hairline)]',
          ]"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Content -->
    <slot />
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  backLabel: { type: String, default: 'Back' },
  backHref: { type: String, default: '#' },
  tabs: { type: Array, default: () => [] },
  activeTab: { type: String, default: '' },
  saving: { type: Boolean, default: false },
  saved: { type: Boolean, default: false },
})

defineEmits(['save', 'update:activeTab'])
</script>
