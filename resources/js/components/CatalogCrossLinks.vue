<template>
  <!-- Flat inline pill row. Each pill shows a short action label
       ("Encyclopedia entry", "Compare prices", "Peptide calculator") — not
       the full "{compound} encyclopedia entry" copy the server sends,
       since the compound name is already in the H1 right above. Groups
       are flattened; the group label is used as the short pill text so
       the data shape on the server can stay as-is. -->
  <nav v-if="pills.length" aria-label="Related catalog pages" class="flex flex-wrap gap-2 mt-4">
    <a
      v-for="p in pills"
      :key="p.href"
      :href="p.href"
      class="ui-focus inline-flex items-center gap-1 h-7 px-3 rounded-full border border-[color:var(--color-hairline)] bg-white text-[12px] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-500)] hover:text-[color:var(--color-ink)] transition-colors"
    >
      {{ p.label }}
      <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
    </a>
  </nav>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  groups: { type: Array, default: () => [] },
})

// Short labels keyed by the group `label` the server emits. Falls
// back to the raw group label (and a compound-vs page emits its own
// pair labels — those pass through verbatim).
const SHORT = {
  'Encyclopedia': 'Encyclopedia entry',
  'Compare listings': 'Compare prices',
  'Peptide calculator': 'Peptide calculator',
  'Products listed': 'Product listings',
}

const pills = computed(() => {
  const out = []
  for (const g of props.groups || []) {
    const short = SHORT[g.label]
    for (const l of g.links || []) {
      // If the group has only one link, use the short action label;
      // if several (e.g. multiple compare pages), fall back to the
      // server-emitted link label which already names the pair.
      out.push({
        href: l.href,
        label: short && g.links.length === 1 ? short : (l.label || g.label),
      })
    }
  }
  return out
})
</script>
