<template>
  <div>
    <div v-if="chips.length" class="flex flex-wrap items-center gap-2 mb-4">
      <button
        type="button"
        @click="selectSize(null)"
        :class="chipClass(selectedSize === null)"
      >All sizes</button>
      <button
        v-for="size in chips"
        :key="size"
        type="button"
        @click="selectSize(size)"
        :class="chipClass(selectedSize === size)"
      >{{ size }} mg</button>
    </div>

    <div v-if="filtered.length" class="bg-white rounded-[14px] border border-[color:var(--color-hairline)] overflow-hidden shadow-[var(--shadow-xs)]">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
              <th scope="col" class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
              <th scope="col" class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Listing (as named by vendor)</th>
              <th scope="col" class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vial (mg)</th>
              <th scope="col" class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Listed price (USD)</th>
              <th scope="col" class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">$/mg</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in visible"
              :key="row.id + '-' + row.url"
              class="border-b border-[color:var(--color-hairline-soft)]"
            >
              <td class="px-5 py-4">
                <a
                  :href="`/brand/${row.brand_slug}`"
                  class="font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors"
                >{{ row.vendor }}</a>
              </td>
              <td class="px-5 py-4">
                <a
                  :href="row.url"
                  class="text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors"
                >{{ row.listing }}</a>
              </td>
              <td class="px-5 py-4 text-right ui-mono text-[color:var(--color-ink-muted)]">{{ row.size_mg }}</td>
              <td class="px-5 py-4 text-right ui-mono text-[color:var(--color-ink)]">${{ money(row.listed_usd) }}</td>
              <td class="px-5 py-4 text-right ui-mono text-[color:var(--color-ink)]">${{ money(row.usd_per_mg) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="filtered.length > 15 && !expanded" class="px-5 py-3 border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
        <button
          type="button"
          class="ui-focus text-[13px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]"
          @click="expanded = true"
        >Show all {{ filtered.length }}</button>
      </div>
    </div>

    <p v-if="footer" class="mt-3 text-[12px] leading-relaxed text-[color:var(--color-ink-subtle)] max-w-4xl">
      {{ footer }}
    </p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  footer: { type: String, default: '' },
})

const selectedSize = ref(null)
const expanded = ref(false)

const chips = computed(() => {
  const counts = {}
  for (const row of props.rows) {
    const size = Number(row.size_mg)
    counts[size] = (counts[size] || 0) + 1
  }
  return Object.entries(counts)
    .filter(([, count]) => count >= 3)
    .map(([size]) => Number(size))
    .sort((a, b) => a - b)
})

const filtered = computed(() => {
  if (selectedSize.value === null) return props.rows
  return props.rows.filter((row) => Number(row.size_mg) === selectedSize.value)
})

const visible = computed(() => (expanded.value ? filtered.value : filtered.value.slice(0, 15)))

function selectSize(size) {
  selectedSize.value = size
  expanded.value = false
}

function chipClass(active) {
  return [
    'ui-focus h-8 px-3 rounded-full text-[12px] font-semibold border transition-colors',
    active
      ? 'bg-[color:var(--color-ink)] text-white border-[color:var(--color-ink)]'
      : 'bg-white text-[color:var(--color-ink)] border-[color:var(--color-hairline)] hover:border-[color:var(--color-accent-400)]',
  ]
}

function money(value) {
  const n = Number(value)
  if (!Number.isFinite(n)) return '—'
  return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
