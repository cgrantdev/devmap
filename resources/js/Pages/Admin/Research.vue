<template>
  <AdminLayout>
    <PageHeader title="Research" :subtitle="`${research.length} papers total`">
      <template #actions>
        <Link
          href="/admin/research/create"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          New Paper
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
      placeholder="Search research..."
      v-model:status="statusFilter"
      :tabs="[
        { value: 'all', label: 'All', count: research.length },
        { value: 'published', label: 'Published', count: research.filter(r => r.status === 'published').length },
        { value: 'draft', label: 'Drafts', count: research.filter(r => r.status === 'draft').length },
      ]"
    />

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Paper</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Peptide</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Type</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Evidence</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Status</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Date</th>
            <th class="px-5 py-3 w-20"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="paper in filteredResearch"
            :key="paper.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            @click="router.visit(`/admin/research/${paper.id}/edit`)"
          >
            <td class="px-5 py-3.5">
              <div class="text-[13px] font-medium text-[color:var(--color-ink)] truncate max-w-sm">{{ paper.title }}</div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ paper.peptide || '—' }}
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ paper.study_type || '—' }}
            </td>
            <td class="px-5 py-3.5">
              <span v-if="paper.evidence_level" :class="[
                'inline-flex items-center px-2 py-0.5 text-[11px] font-semibold',
                paper.evidence_level === 'High' ? 'bg-[color:var(--color-verified-bg)] text-[#065F46]' :
                paper.evidence_level === 'Medium' ? 'bg-[color:var(--color-caution-bg)] text-[#92400E]' :
                'bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)]'
              ]">{{ paper.evidence_level }}</span>
              <span v-else class="text-[13px] text-[color:var(--color-ink-subtle)]">—</span>
            </td>
            <td class="px-5 py-3.5">
              <StatusBadge :status="paper.status" :label="paper.status === 'published' ? 'Published' : 'Draft'" />
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)] ui-mono">
              {{ paper.published_at || paper.created_at }}
            </td>
            <td class="px-5 py-3.5">
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredResearch.length === 0" class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
        No papers match your filters.
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
  research: { type: Array, default: () => [] },
})

const searchTerm = ref('')
const statusFilter = ref('all')

const filteredResearch = computed(() => {
  let list = props.research

  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(r =>
      r.title.toLowerCase().includes(q) ||
      (r.peptide || '').toLowerCase().includes(q) ||
      (r.study_type || '').toLowerCase().includes(q)
    )
  }

  if (statusFilter.value === 'published') {
    list = list.filter(r => r.status === 'published')
  } else if (statusFilter.value === 'draft') {
    list = list.filter(r => r.status === 'draft')
  }

  return list
})
</script>
