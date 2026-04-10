<template>
  <AdminLayout>
    <PageHeader title="Pages" :subtitle="`${pages.length} pages total`">
      <template #actions>
        <Link
          href="/admin/pages/create"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          New Page
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
      placeholder="Search pages..."
    />

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Page</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Slug</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Updated</th>
            <th class="px-5 py-3 w-20"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="pg in filteredPages"
            :key="pg.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            @click="router.visit(`/admin/pages/${pg.id}/edit`)"
          >
            <td class="px-5 py-3.5">
              <div class="text-[13px] font-medium text-[color:var(--color-ink)]">{{ pg.title }}</div>
            </td>
            <td class="px-5 py-3.5">
              <code class="text-[12px] ui-mono text-[color:var(--color-ink-muted)] bg-[color:var(--color-hairline-soft)] px-1.5 py-0.5">{{ pg.slug }}</code>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)] ui-mono">
              {{ pg.updated_at || pg.created_at }}
            </td>
            <td class="px-5 py-3.5">
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredPages.length === 0" class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
        No pages found.
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

const props = defineProps({
  pages: { type: Array, default: () => [] },
})

const searchTerm = ref('')

const filteredPages = computed(() => {
  let list = props.pages
  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(p =>
      p.title.toLowerCase().includes(q) ||
      (p.slug || '').toLowerCase().includes(q)
    )
  }
  return list
})
</script>
