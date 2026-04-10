<template>
  <AdminLayout>
    <PageHeader title="Education Posts" :subtitle="`${posts.length} posts total`">
      <template #actions>
        <Link
          href="/admin/education-posts/create"
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
      placeholder="Search education posts..."
      v-model:status="statusFilter"
      :tabs="[
        { value: 'all', label: 'All', count: posts.length },
        { value: 'published', label: 'Published', count: posts.filter(p => p.status === 'published').length },
        { value: 'draft', label: 'Drafts', count: posts.filter(p => p.status === 'draft').length },
      ]"
    />

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Post</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Category</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Rating</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Status</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Date</th>
            <th class="px-5 py-3 w-20"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="post in filteredPosts"
            :key="post.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            @click="router.visit(`/admin/education-posts/${post.id}/edit`)"
          >
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] overflow-hidden">
                  <img v-if="post.image" :src="post.image" :alt="post.title" class="w-full h-full object-cover" loading="lazy" />
                  <div v-else class="w-full h-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
                </div>
                <div class="min-w-0">
                  <div class="text-[13px] font-medium text-[color:var(--color-ink)] truncate max-w-sm">{{ post.title }}</div>
                  <div class="text-[11px] text-[color:var(--color-ink-subtle)] truncate">{{ post.slug }}</div>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ post.category_name || '—' }}
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                <span class="ui-mono text-[13px] text-[color:var(--color-ink)]">{{ post.rating }}</span>
                <span class="text-[11px] text-[color:var(--color-ink-subtle)]">({{ post.rating_count }})</span>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <StatusBadge :status="post.status" :label="post.status === 'published' ? 'Published' : 'Draft'" />
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)] ui-mono">
              {{ post.published_at || post.created_at }}
            </td>
            <td class="px-5 py-3.5">
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredPosts.length === 0" class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
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
  posts: { type: Array, default: () => [] },
})

const searchTerm = ref('')
const statusFilter = ref('all')

const filteredPosts = computed(() => {
  let list = props.posts

  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(p =>
      p.title.toLowerCase().includes(q) ||
      (p.slug || '').toLowerCase().includes(q) ||
      (p.category_name || '').toLowerCase().includes(q)
    )
  }

  if (statusFilter.value === 'published') {
    list = list.filter(p => p.status === 'published')
  } else if (statusFilter.value === 'draft') {
    list = list.filter(p => p.status === 'draft')
  }

  return list
})
</script>
