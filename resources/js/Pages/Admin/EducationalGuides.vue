<template>
  <AdminLayout>
    <PageHeader title="Educational Guides" :subtitle="`${guides.length} guides total`">
      <template #actions>
        <Link
          href="/admin/educational-guides/create"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          New Guide
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
      placeholder="Search guides..."
      v-model:status="statusFilter"
      :tabs="[
        { value: 'all', label: 'All', count: guides.length },
        { value: 'published', label: 'Published', count: guides.filter(g => g.status === 'published').length },
        { value: 'draft', label: 'Drafts', count: guides.filter(g => g.status === 'draft').length },
        { value: 'featured', label: 'Featured', count: guides.filter(g => g.is_featured).length },
      ]"
    />

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Guide</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Tag</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Status</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Date</th>
            <th class="px-5 py-3 w-20"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="guide in filteredGuides"
            :key="guide.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            @click="router.visit(`/admin/educational-guides/${guide.id}/edit`)"
          >
            <td class="px-5 py-3.5">
              <div class="min-w-0">
                <div class="text-[13px] font-medium text-[color:var(--color-ink)] truncate max-w-md">{{ guide.title }}</div>
                <div class="text-[11px] text-[color:var(--color-ink-subtle)] truncate">{{ guide.slug }}</div>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ guide.tag || '—' }}
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1.5">
                <StatusBadge :status="guide.status" :label="guide.status === 'published' ? 'Published' : 'Draft'" />
                <span v-if="guide.is_featured" class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-600)]">
                  <svg class="w-2.5 h-2.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                  Featured
                </span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)] ui-mono">
              {{ guide.published_at || guide.created_at }}
            </td>
            <td class="px-5 py-3.5">
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredGuides.length === 0" class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
        No guides match your filters.
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
  guides: { type: Array, default: () => [] },
})

const searchTerm = ref('')
const statusFilter = ref('all')

const filteredGuides = computed(() => {
  let list = props.guides

  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(g =>
      g.title.toLowerCase().includes(q) ||
      (g.slug || '').toLowerCase().includes(q) ||
      (g.tag || '').toLowerCase().includes(q)
    )
  }

  if (statusFilter.value === 'published') {
    list = list.filter(g => g.status === 'published')
  } else if (statusFilter.value === 'draft') {
    list = list.filter(g => g.status === 'draft')
  } else if (statusFilter.value === 'featured') {
    list = list.filter(g => g.is_featured)
  }

  return list
})
</script>
