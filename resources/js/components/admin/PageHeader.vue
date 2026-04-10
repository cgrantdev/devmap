<template>
  <div class="flex items-start justify-between gap-4 mb-6">
    <div>
      <!-- Breadcrumb -->
      <div v-if="breadcrumb.length" class="flex items-center gap-1.5 text-[12px] text-[color:var(--color-ink-subtle)] mb-2">
        <template v-for="(crumb, i) in breadcrumb" :key="i">
          <a v-if="crumb.href" :href="crumb.href" class="hover:text-[color:var(--color-ink)] transition-colors">{{ crumb.label }}</a>
          <span v-else>{{ crumb.label }}</span>
          <span v-if="i < breadcrumb.length - 1" class="text-[color:var(--color-hairline)]">/</span>
        </template>
      </div>
      <h1 class="ui-display text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">{{ title }}</h1>
      <p v-if="subtitle" class="text-sm text-[color:var(--color-ink-muted)] mt-1">{{ subtitle }}</p>
    </div>
    <div class="flex items-center gap-3 flex-shrink-0">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  breadcrumb: { type: Array, default: () => [] },
})
</script>
