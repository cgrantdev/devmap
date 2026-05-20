<template>
  <AdminLayout>
    <PageHeader title="Categories" :subtitle="`${allCategories.length} categories total`">
      <template #actions>
        <Link
          href="/admin/categories/create"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          Add Category
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
      placeholder="Search categories..."
      v-model:status="statusFilter"
      :tabs="[
        { value: 'all', label: 'All', count: allCategories.length },
        { value: 'active', label: 'Active', count: allCategories.filter(c => c.is_active).length },
        { value: 'inactive', label: 'Inactive', count: allCategories.filter(c => !c.is_active).length },
      ]"
    />

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Category</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Slug</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Products</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Status</th>
            <th class="px-5 py-3 w-28 text-right text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="category in filteredCategories"
            :key="category.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            @click="router.visit(`/admin/categories/${category.id}/edit`)"
          >
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] overflow-hidden flex items-center justify-center">
                  <img v-if="category.image_url" :src="category.image_url" :alt="category.name" class="w-full h-full object-cover" loading="lazy" />
                  <span v-else class="text-[10px] font-bold text-[color:var(--color-ink-muted)]">{{ category.name.substring(0, 2).toUpperCase() }}</span>
                </div>
                <div class="text-[13px] font-medium text-[color:var(--color-ink)]">{{ category.name }}</div>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-subtle)] ui-mono">
              {{ category.slug }}
            </td>
            <td class="px-5 py-3.5 ui-mono text-[13px] text-[color:var(--color-ink)]">
              {{ category.products_count || 0 }}
            </td>
            <td class="px-5 py-3.5">
              <StatusBadge
                :status="category.is_active ? 'active' : 'inactive'"
                :label="category.is_active ? 'Active' : 'Inactive'"
              />
            </td>
            <td class="px-5 py-3.5 text-right" @click.stop>
              <button
                type="button"
                @click="confirmDelete(category)"
                class="inline-flex items-center gap-1.5 h-8 px-3 text-[12px] font-semibold text-white bg-[#DC2626] hover:bg-[#B91C1C] rounded transition-colors"
                title="Delete this category"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/><path d="M10 11v6M14 11v6"/>
                </svg>
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredCategories.length === 0" class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
        No categories match your filters.
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
  categories: { type: [Object, Array], default: () => [] },
})

// Handle both paginated object and flat array
const allCategories = computed(() => {
  if (Array.isArray(props.categories)) return props.categories
  return props.categories?.data || []
})

const searchTerm = ref('')
const statusFilter = ref('all')

const filteredCategories = computed(() => {
  let list = allCategories.value

  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(c =>
      c.name.toLowerCase().includes(q) ||
      (c.slug || '').toLowerCase().includes(q)
    )
  }

  if (statusFilter.value === 'active') {
    list = list.filter(c => c.is_active)
  } else if (statusFilter.value === 'inactive') {
    list = list.filter(c => !c.is_active)
  }

  return list
})

function confirmDelete(category) {
  const count = category.products_count || 0
  const productLine = count > 0
    ? `${count} product${count === 1 ? '' : 's'} currently in this category will be set to Uncategorized (the products themselves stay).`
    : 'No products are currently assigned to this category.'
  const msg = `Delete "${category.name}"?\n\n${productLine}\n\nThis cannot be undone.`
  if (!confirm(msg)) return
  router.delete(`/admin/categories/${category.id}`, {
    preserveScroll: true,
    preserveState: false,
  })
}
</script>
