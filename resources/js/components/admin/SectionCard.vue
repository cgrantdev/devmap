<template>
  <section
    :id="sectionId"
    class="bg-white border border-[color:var(--color-hairline)] scroll-mt-28"
  >
    <button
      type="button"
      @click="$emit('toggle')"
      class="w-full flex items-center gap-3 px-5 py-4 hover:bg-[color:var(--color-hairline-soft)]/60 transition-colors"
    >
      <span
        class="flex-shrink-0 w-2.5 h-2.5 rounded-full"
        :class="complete ? 'bg-[color:var(--color-verified)]' : 'border border-[color:var(--color-hairline)] bg-white'"
        :title="complete ? 'Filled' : 'Empty'"
      />
      <h2 class="flex-1 text-left text-[15px] font-semibold text-[color:var(--color-ink)]">{{ title }}</h2>
      <span v-if="hint" class="text-[11px] text-[color:var(--color-ink-subtle)] hidden sm:inline">{{ hint }}</span>
      <svg
        class="w-4 h-4 text-[color:var(--color-ink-subtle)] transition-transform"
        :class="{ 'rotate-180': open }"
        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
      >
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </button>
    <div v-show="open" class="px-5 pb-5 pt-1 border-t border-[color:var(--color-hairline-soft)]">
      <div class="space-y-4 pt-4">
        <slot />
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  sectionId: { type: String, required: true },
  title: { type: String, required: true },
  hint: { type: String, default: '' },
  open: { type: Boolean, default: true },
  complete: { type: Boolean, default: false },
})
defineEmits(['toggle'])
</script>
