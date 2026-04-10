<template>
  <AdminLayout>
    <PageHeader title="Blog Posts" :subtitle="`${blogs.length} posts total`">
      <template #actions>
        <Link
          href="/admin/blogs/create"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          New Post
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
      placeholder="Search posts..."
      v-model:status="statusFilter"
      :tabs="[
        { value: 'all', label: 'All', count: blogs.length },
        { value: 'published', label: 'Published', count: blogs.filter(b => b.status === 'published').length },
        { value: 'draft', label: 'Drafts', count: blogs.filter(b => b.status === 'draft').length },
        { value: 'featured', label: 'Featured', count: blogs.filter(b => b.is_featured).length },
      ]"
    />

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Post</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Type</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Author</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Status</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Date</th>
            <th class="px-5 py-3 w-20"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="blog in filteredBlogs"
            :key="blog.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            @click="router.visit(`/admin/blogs/${blog.id}/edit`)"
          >
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-12 h-9 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] overflow-hidden">
                  <img v-if="blog.image" :src="blog.image" :alt="blog.title" class="w-full h-full object-cover" loading="lazy" />
                  <div v-else class="w-full h-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
                </div>
                <div class="min-w-0">
                  <div class="text-[13px] font-medium text-[color:var(--color-ink)] truncate max-w-sm">{{ blog.title }}</div>
                  <div class="text-[11px] text-[color:var(--color-ink-subtle)] truncate">{{ blog.slug }}</div>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ blog.blog_type || '—' }}
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ blog.author_name || '—' }}
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1.5">
                <StatusBadge :status="blog.status" :label="blog.status === 'published' ? 'Published' : 'Draft'" />
                <span v-if="blog.is_featured" class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-600)]">
                  <svg class="w-2.5 h-2.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                  Featured
                </span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)] ui-mono">
              {{ blog.published_at || blog.created_at }}
            </td>
            <td class="px-5 py-3.5">
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredBlogs.length === 0" class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
        No posts match your filters.
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import PageHeader from '@/components/admin/PageHeader.vue'
import FilterBar from '@/components/admin/FilterBar.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'

const props = defineProps({
  blogs: { type: Array, default: () => [] },
})

const searchTerm = ref('')
const statusFilter = ref('all')

const filteredBlogs = computed(() => {
  let list = props.blogs

  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(b =>
      b.title.toLowerCase().includes(q) ||
      (b.slug || '').toLowerCase().includes(q) ||
      (b.author_name || '').toLowerCase().includes(q)
    )
  }

  if (statusFilter.value === 'published') {
    list = list.filter(b => b.status === 'published')
  } else if (statusFilter.value === 'draft') {
    list = list.filter(b => b.status === 'draft')
  } else if (statusFilter.value === 'featured') {
    list = list.filter(b => b.is_featured)
  }

  return list
})
</script>
