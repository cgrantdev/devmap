<template>
  <nav v-if="groups.length" aria-label="Related catalog pages" class="mt-4 space-y-3">
    <div v-for="group in groups" :key="group.label">
      <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-1.5">
        {{ group.label }}
      </div>
      <ul class="flex flex-wrap gap-2 list-none p-0 m-0">
        <li v-for="link in group.links" :key="link.href + link.label">
          <a
            :href="link.href"
            class="ui-focus inline-flex items-center h-8 px-3 rounded-md border border-[color:var(--color-hairline)] bg-white text-[13px] font-medium text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-accent-700)] transition-colors"
          >{{ link.label }}</a>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script setup>
defineProps({
  groups: { type: Array, default: () => [] },
})
</script>
