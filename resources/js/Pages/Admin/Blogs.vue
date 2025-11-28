<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-normal text-slate-700">Blog Management</h1>
        <p class="text-slate-500 mt-2">Manage all blog posts</p>
      </div>
      <Link href="/admin/blogs/create" class="inline-flex items-center px-5 py-2.5 bg-blue-500 text-white rounded-xl hover:bg-blue-600 font-medium shadow-sm hover:shadow transition-all duration-200">
        + New Blog Post
      </Link>
    </div>
    
    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
      {{ $page.props.flash.success }}
    </div>
    
    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
      {{ $page.props.flash.error }}
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-100">
      <table class="min-w-full divide-y divide-slate-100">
        <thead class="bg-slate-50/50">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Image</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Title</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Status</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Featured</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Published</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Created</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-100">
          <template v-for="blog in blogs.data" :key="blog.id">
            <tr class="hover:bg-slate-50/50 transition-colors duration-150">
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="blog.image" class="w-16 h-16 rounded-xl overflow-hidden border border-slate-100 shadow-sm">
                  <img :src="blog.image" :alt="blog.title" class="w-full h-full object-cover" />
                </div>
                <div v-else class="w-16 h-16 rounded-xl bg-slate-100 border border-slate-100 flex items-center justify-center">
                  <span class="text-xs text-slate-400">No Image</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-slate-800">{{ blog.title }}</div>
                <div class="text-sm text-slate-500">{{ blog.slug }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="blog.status === 'published'" class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-green-50 text-green-700 border border-green-200">
                  Published
                </span>
                <span v-else class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-600 border border-slate-100">
                  Draft
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="blog.is_featured" class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                  Featured
                </span>
                <span v-else class="text-sm text-slate-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="blog.published_at" class="text-sm text-slate-700">{{ blog.published_at }}</span>
                <span v-else class="text-sm text-slate-400">Draft</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                {{ blog.created_at }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button 
                  @click="toggleQuickEdit(blog.id)" 
                  class="text-blue-500 hover:text-blue-600 mr-4 transition-colors duration-150"
                  :title="expandedRows.includes(blog.id) ? 'Close Quick Edit' : 'Quick Edit'"
                >
                  <svg v-if="!expandedRows.includes(blog.id)" class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  <svg v-else class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <Link :href="`/admin/blogs/${blog.id}/edit`" class="text-blue-500 hover:text-blue-600 mr-4 transition-colors duration-150">Edit</Link>
                <button @click="deleteBlog(blog)" :disabled="deleteForm.processing" class="text-red-500 hover:text-red-600 transition-colors duration-150">
                  Delete
                </button>
              </td>
            </tr>
            <!-- Quick Edit Row -->
            <tr v-if="expandedRows.includes(blog.id)" class="bg-slate-50/50">
              <td colspan="7" class="px-6 py-4">
                <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
                  <h3 class="text-sm font-medium text-slate-700 mb-4">Quick Edit</h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1.5">Title *</label>
                      <input 
                        v-model="quickEditForms[blog.id].title" 
                        type="text" 
                        class="w-full border border-slate-100 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        @keyup.enter="saveQuickEdit(blog.id)"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1.5">Status *</label>
                      <select 
                        v-model="quickEditForms[blog.id].status" 
                        class="w-full border border-slate-100 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                      >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                      </select>
                    </div>
                  </div>
                  <div class="mt-4 flex justify-end gap-2">
                    <button 
                      @click="toggleQuickEdit(blog.id)" 
                      class="px-4 py-2 text-sm rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-all duration-200 font-medium"
                    >
                      Cancel
                    </button>
                    <button 
                      @click="saveQuickEdit(blog.id)" 
                      :disabled="quickEditForms[blog.id].processing"
                      class="px-4 py-2 text-sm rounded-xl bg-blue-500 text-white hover:bg-blue-600 font-medium transition-all duration-200 shadow-sm"
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
      <div v-if="blogs.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-slate-100 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <Link v-if="blogs.current_page > 1" :href="blogs.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-slate-100 text-sm font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 transition-all duration-200">
            Previous
          </Link>
          <Link v-if="blogs.current_page < blogs.last_page" :href="blogs.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-slate-100 text-sm font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 transition-all duration-200">
            Next
          </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-slate-600">
              Showing <span class="font-medium">{{ blogs.from }}</span> to <span class="font-medium">{{ blogs.to }}</span> of <span class="font-medium">{{ blogs.total }}</span> results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px" aria-label="Pagination">
              <Link v-for="page in pages" :key="page" :href="getPageUrl(page)" :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-all duration-200',
                page === blogs.current_page
                  ? 'z-10 bg-blue-50 border-blue-300 text-blue-600'
                  : 'bg-white border-slate-100 text-slate-600 hover:bg-slate-50',
                page === '...' ? 'cursor-default' : ''
              ]">
                {{ page }}
              </Link>
            </nav>
          </div>
        </div>
      </div>
      
      <div v-if="blogs.data.length === 0" class="p-6 text-center text-slate-500">
        No blog posts found. <Link href="/admin/blogs/create" class="text-blue-500 hover:text-blue-600 transition-colors duration-150">Create your first blog post</Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { computed, ref, reactive } from 'vue'

const props = defineProps({
  blogs: Object,
})

const page = usePage()
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
        _token: page.props.csrf_token,
      })
    }
  }
}

const saveQuickEdit = (blogId) => {
  const form = quickEditForms[blogId]
  // Update CSRF token before submission
  form._token = page.props.csrf_token
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
