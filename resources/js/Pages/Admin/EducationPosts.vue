<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Education Posts Management</h1>
        <p class="text-gray-600 mt-2">Manage all education posts</p>
      </div>
      <Link href="/admin/education-posts/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold shadow">
        + New Education Post
      </Link>
    </div>
    
    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      {{ $page.props.flash.success }}
    </div>
    
    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ $page.props.flash.error }}
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="post in posts.data" :key="post.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ post.title }}</div>
              <div class="text-sm text-gray-500">{{ post.slug }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span v-if="post.status === 'published'" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                Published
              </span>
              <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                Draft
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ post.rating }} ({{ post.rating_count }} reviews)</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span v-if="post.published_at" class="text-sm text-gray-900">{{ post.published_at }}</span>
              <span v-else class="text-sm text-gray-400">Draft</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ post.created_at }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <Link :href="`/admin/education-posts/${post.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
              <button @click="deletePost(post)" :disabled="deleteForm.processing" class="text-red-600 hover:text-red-900">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Pagination -->
      <div v-if="posts.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <Link v-if="posts.current_page > 1" :href="posts.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Previous
          </Link>
          <Link v-if="posts.current_page < posts.last_page" :href="posts.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Next
          </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing <span class="font-medium">{{ posts.from }}</span> to <span class="font-medium">{{ posts.to }}</span> of <span class="font-medium">{{ posts.total }}</span> results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <Link v-for="page in pages" :key="page" :href="getPageUrl(page)" :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                page === posts.current_page
                  ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                  : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                page === '...' ? 'cursor-default' : ''
              ]">
                {{ page }}
              </Link>
            </nav>
          </div>
        </div>
      </div>
      
      <div v-if="posts.data.length === 0" class="p-6 text-center text-gray-500">
        No education posts found. <Link href="/admin/education-posts/create" class="text-blue-600 hover:text-blue-800">Create your first education post</Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { computed } from 'vue'

const props = defineProps({
  posts: Object,
})

const deleteForm = useForm({})

const pages = computed(() => {
  const current = props.posts.current_page
  const last = props.posts.last_page
  const pages = []
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 3) {
      for (let i = 1; i <= 4; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 2) {
      pages.push(1)
      pages.push('...')
      for (let i = last - 3; i <= last; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    }
  }
  
  return pages
})

const getPageUrl = (page) => {
  if (page === '...' || page === props.posts.current_page) return '#'
  const url = new URL(window.location.href)
  url.searchParams.set('page', page)
  return url.pathname + url.search
}

const deletePost = (post) => {
  if (confirm(`Are you sure you want to delete "${post.title}"?`)) {
    deleteForm.delete(`/admin/education-posts/${post.id}`, {
      preserveScroll: true,
    })
  }
}
</script>

