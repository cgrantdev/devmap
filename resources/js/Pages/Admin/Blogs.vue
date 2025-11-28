<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Blog Management</h1>
        <p class="text-gray-600 mt-2">Manage all blog posts</p>
      </div>
      <Link href="/admin/blogs/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold shadow">
        + New Blog Post
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
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Featured</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <template v-for="blog in blogs.data" :key="blog.id">
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="blog.image" class="w-16 h-16 rounded overflow-hidden border border-gray-200">
                  <img :src="blog.image" :alt="blog.title" class="w-full h-full object-cover" />
                </div>
                <div v-else class="w-16 h-16 rounded bg-gray-100 border border-gray-200 flex items-center justify-center">
                  <span class="text-xs text-gray-400">No Image</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ blog.title }}</div>
                <div class="text-sm text-gray-500">{{ blog.slug }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="blog.status === 'published'" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                  Published
                </span>
                <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                  Draft
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="blog.is_featured" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                  Featured
                </span>
                <span v-else class="text-sm text-gray-500">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="blog.published_at" class="text-sm text-gray-900">{{ blog.published_at }}</span>
                <span v-else class="text-sm text-gray-400">Draft</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ blog.created_at }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button 
                  @click="toggleQuickEdit(blog.id)" 
                  class="text-indigo-600 hover:text-indigo-900 mr-4"
                  :title="expandedRows.includes(blog.id) ? 'Close Quick Edit' : 'Quick Edit'"
                >
                  <svg v-if="!expandedRows.includes(blog.id)" class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  <svg v-else class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <Link :href="`/admin/blogs/${blog.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                <button @click="deleteBlog(blog)" :disabled="deleteForm.processing" class="text-red-600 hover:text-red-900">
                  Delete
                </button>
              </td>
            </tr>
            <!-- Quick Edit Row -->
            <tr v-if="expandedRows.includes(blog.id)" class="bg-gray-50">
              <td colspan="7" class="px-6 py-4">
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                  <h3 class="text-sm font-semibold text-gray-700 mb-4">Quick Edit</h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                      <input 
                        v-model="quickEditForms[blog.id].title" 
                        type="text" 
                        class="w-full border rounded px-3 py-2 text-sm"
                        @keyup.enter="saveQuickEdit(blog.id)"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                      <select 
                        v-model="quickEditForms[blog.id].status" 
                        class="w-full border rounded px-3 py-2 text-sm"
                      >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                      </select>
                    </div>
                  </div>
                  <div class="mt-4 flex justify-end gap-2">
                    <button 
                      @click="toggleQuickEdit(blog.id)" 
                      class="px-4 py-2 text-sm rounded bg-gray-200 hover:bg-gray-300"
                    >
                      Cancel
                    </button>
                    <button 
                      @click="saveQuickEdit(blog.id)" 
                      :disabled="quickEditForms[blog.id].processing"
                      class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold"
                    >
                      {{ quickEditForms[blog.id].processing ? 'Saving...' : 'Update' }}
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
      
      <!-- Pagination -->
      <div v-if="blogs.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <Link v-if="blogs.current_page > 1" :href="blogs.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Previous
          </Link>
          <Link v-if="blogs.current_page < blogs.last_page" :href="blogs.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Next
          </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing <span class="font-medium">{{ blogs.from }}</span> to <span class="font-medium">{{ blogs.to }}</span> of <span class="font-medium">{{ blogs.total }}</span> results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <Link v-for="page in pages" :key="page" :href="getPageUrl(page)" :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                page === blogs.current_page
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
      
      <div v-if="blogs.data.length === 0" class="p-6 text-center text-gray-500">
        No blog posts found. <Link href="/admin/blogs/create" class="text-blue-600 hover:text-blue-800">Create your first blog post</Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { computed, ref, reactive } from 'vue'

const props = defineProps({
  blogs: Object,
})

const deleteForm = useForm({})
const expandedRows = ref([])
const quickEditForms = reactive({})

const toggleQuickEdit = (blogId) => {
  const index = expandedRows.value.indexOf(blogId)
  if (index > -1) {
    expandedRows.value.splice(index, 1)
    // Clean up form when closing
    delete quickEditForms[blogId]
  } else {
    expandedRows.value.push(blogId)
    // Initialize form with current values when opening
    const blog = props.blogs.data.find(b => b.id === blogId)
    if (blog && !quickEditForms[blogId]) {
      quickEditForms[blogId] = useForm({
        title: blog.title,
        status: blog.status,
      })
    }
  }
}

const saveQuickEdit = (blogId) => {
  const form = quickEditForms[blogId]
  form.patch(`/admin/blogs/${blogId}/quick-update`, {
    preserveScroll: true,
    onSuccess: () => {
      // Reload the blogs data to get updated information
      router.reload({ only: ['blogs'], preserveScroll: true })
      // Close the quick edit
      toggleQuickEdit(blogId)
    },
  })
}

const pages = computed(() => {
  const current = props.blogs.current_page
  const last = props.blogs.last_page
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
  if (page === '...' || page === props.blogs.current_page) return '#'
  const url = new URL(window.location.href)
  url.searchParams.set('page', page)
  return url.pathname + url.search
}

const deleteBlog = (blog) => {
  if (confirm(`Are you sure you want to delete "${blog.title}"?`)) {
    deleteForm.delete(`/admin/blogs/${blog.id}`, {
      preserveScroll: true,
    })
  }
}
</script>
