<template>
  <AdminLayout>
    <PageHeader title="Encyclopedia" :subtitle="`${entries.length} entries total`">
      <template #actions>
        <Link
          href="/admin/encyclopedia-entries/create"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          New Entry
        </Link>
      </template>
    </PageHeader>

    <!-- Flash -->
    <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 bg-[color:var(--color-verified-bg)] border border-[#A7F3D0] text-[#065F46] text-sm">
      {{ $page.props.flash.success }}
    </div>

    <!-- Filters -->
    <FilterBar
      v-model:search="searchTerm"
      placeholder="Search encyclopedia..."
      v-model:status="statusFilter"
      :tabs="[
        { value: 'all', label: 'All', count: entries.length },
        { value: 'published', label: 'Published', count: entries.filter(e => e.status === 'published').length },
        { value: 'draft', label: 'Drafts', count: entries.filter(e => e.status === 'draft').length },
      ]"
    />

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Entry</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Category</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Status</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Date</th>
            <th class="px-5 py-3 w-20"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="entry in filteredEntries"
            :key="entry.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            @click="router.visit(`/admin/encyclopedia-entries/${entry.id}/edit`)"
          >
            <td class="px-5 py-3.5">
              <div class="min-w-0">
                <div class="text-[13px] font-medium text-[color:var(--color-ink)] truncate max-w-md">{{ entry.title }}</div>
                <div v-if="entry.peptide_full_name" class="text-[11px] text-[color:var(--color-ink-subtle)] truncate">{{ entry.peptide_full_name }}</div>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ entry.category || '—' }}
            </td>
            <td class="px-5 py-3.5">
              <StatusBadge :status="entry.status" :label="entry.status === 'published' ? 'Published' : 'Draft'" />
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)] ui-mono">
              {{ entry.published_at || entry.created_at }}
            </td>
            <td class="px-5 py-3.5">
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredEntries.length === 0" class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
        No entries match your filters.
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import PageHeader from '@/components/admin/PageHeader.vue'
import FilterBar from '@/components/admin/FilterBar.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'

const props = defineProps({
  entries: { type: Array, default: () => [] },
})

const searchTerm = ref('')
const statusFilter = ref('all')

const filteredEntries = computed(() => {
  let list = props.entries

  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(e =>
      e.title.toLowerCase().includes(q) ||
      (e.peptide_full_name || '').toLowerCase().includes(q) ||
      (e.category || '').toLowerCase().includes(q)
    )
  }

  if (statusFilter.value === 'published') {
    list = list.filter(e => e.status === 'published')
  } else if (statusFilter.value === 'draft') {
    list = list.filter(e => e.status === 'draft')
  }

  return list
})
</script>
