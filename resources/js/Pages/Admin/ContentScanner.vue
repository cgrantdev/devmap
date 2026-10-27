<template>
  <div class="min-h-screen bg-[color:var(--color-canvas)]">
    <PageHeader title="Content issue scanner" subtitle="Internal notes bleeding onto public pages">
      <template #actions>
        <Link href="/admin/seo" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← SEO dashboard</Link>
      </template>
    </PageHeader>

    <div class="max-w-5xl mx-auto px-6 py-6 space-y-6">
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm font-semibold text-[color:var(--color-ink)]">{{ summary.total }} hit{{ summary.total === 1 ? '' : 's' }} across public content</div>
            <div class="text-[11px] text-[color:var(--color-ink-muted)] mt-1">
              Scans products, encyclopedia entries, blogs, and brand descriptions for suspicious markers that shouldn't go to Google.
            </div>
          </div>
          <div class="flex gap-2">
            <span v-for="(count, type) in summary.by_type" :key="type" class="text-[11px] bg-[color:var(--color-hairline-soft)] px-2 py-1 rounded-full">
              {{ type }}: {{ count }}
            </span>
          </div>
        </div>
        <div class="text-[11px] text-[color:var(--color-ink-muted)] mt-4">
          <span class="font-semibold">Markers scanned:</span>
          <span v-for="(m, i) in markers" :key="i" class="font-mono bg-[color:var(--color-hairline-soft)] px-1.5 py-0.5 rounded ml-1">{{ m }}</span>
        </div>
      </div>

      <div v-if="hits.length === 0" class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-10 text-center">
        <div class="text-sm text-[color:var(--color-ink-muted)]">No internal markers found in public content. 🎉</div>
      </div>

      <div v-else class="space-y-3">
        <div v-for="(h, i) in hits" :key="i" class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5">
          <div class="flex justify-between items-start gap-4 mb-2">
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[color:var(--color-biotech-50)] text-[color:var(--color-biotech-700)]">{{ h.type }}</span>
                <span class="text-[10px] font-mono bg-amber-100 text-amber-800 px-2 py-0.5 rounded">{{ h.marker }}</span>
                <span class="text-[10px] text-[color:var(--color-ink-muted)]">{{ h.field }}</span>
              </div>
              <div class="text-sm font-semibold text-[color:var(--color-ink)] mt-2">{{ h.label }}</div>
            </div>
            <Link :href="h.edit_url" class="shrink-0 text-xs font-medium bg-[color:var(--color-ink)] text-white rounded-lg px-3 py-1.5 hover:opacity-90">Edit →</Link>
          </div>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed mt-2 whitespace-pre-wrap">{{ h.excerpt }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'

defineProps({
  hits: Array,
  markers: Array,
  summary: Object,
})
</script>
