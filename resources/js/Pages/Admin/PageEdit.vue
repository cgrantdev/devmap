<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">{{ page ? 'Edit Page' : 'Create New Page' }}</h1>
      <p class="text-slate-500 mt-2">{{ page ? 'Update page details' : 'Add a new page' }}</p>
    </div>
    
    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
      {{ $page.props.flash.success }}
    </div>
    
    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
      {{ $page.props.flash.error }}
    </div>
    
    <div class="flex gap-6">
      <!-- Main Content Area -->
      <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <form @submit.prevent="submit">
          <!-- Error Message -->
          <div v-if="Object.keys(form.errors).length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
            <p class="font-medium">Please fix the following errors:</p>
            <ul class="list-disc list-inside mt-2">
              <li v-for="(error, field) in form.errors" :key="field" class="text-sm">
                {{ Array.isArray(error) ? error[0] : error }}
              </li>
            </ul>
          </div>
          
          <div class="space-y-6">
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Title *</label>
              <input 
                v-model="form.title" 
                type="text" 
                required
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" 
              />
              <p class="text-sm text-slate-500 mt-1">Slug will be automatically generated from title if not provided</p>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Slug</label>
              <input 
                v-model="form.slug" 
                type="text"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" 
                placeholder="auto-generated-from-title"
              />
              <p class="text-sm text-slate-500 mt-1">Leave empty to auto-generate from title</p>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Content</label>
              <QuillEditor
                v-model:content="form.content"
                contentType="html"
                theme="snow"
                :toolbar="toolbarOptions"
                class="bg-white rounded-xl"
              />
            </div>
          </div>
        </form>
      </div>
      
      <!-- Right Sidebar -->
      <div class="w-80 flex-shrink-0 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <h3 class="text-lg font-semibold text-slate-800 mb-4">Publish</h3>
          <div class="space-y-4">
            <button
              @click="submit"
              :disabled="form.processing"
              class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
            >
              {{ form.processing ? 'Saving...' : (page ? 'Update Page' : 'Create Page') }}
            </button>
            <Link
              href="/admin/pages"
              class="block w-full text-center bg-slate-100 text-slate-700 py-2.5 px-4 rounded-xl hover:bg-slate-200 transition-colors font-medium"
            >
              Cancel
            </Link>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <h3 class="text-lg font-semibold text-slate-800 mb-4">SEO Settings</h3>
          <div class="space-y-4">
            <div>
              <label class="block mb-1.5 text-sm font-semibold text-slate-800">Meta Title</label>
              <input 
                v-model="form.meta_title" 
                type="text"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-sm" 
                placeholder="SEO title"
              />
            </div>
            <div>
              <label class="block mb-1.5 text-sm font-semibold text-slate-800">Meta Description</label>
              <textarea 
                v-model="form.meta_description" 
                rows="3"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-sm" 
                placeholder="SEO description"
              ></textarea>
            </div>
          </div>
        </div>

        <div v-if="page" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <h3 class="text-lg font-semibold text-slate-800 mb-4">Page Info</h3>
          <div class="space-y-2 text-sm text-slate-600">
            <div>
              <span class="font-medium">Created:</span>
              <span class="ml-2">{{ new Date(page.created_at).toLocaleDateString() }}</span>
            </div>
            <div>
              <span class="font-medium">Updated:</span>
              <span class="ml-2">{{ new Date(page.updated_at).toLocaleDateString() }}</span>
            </div>
            <div>
              <span class="font-medium">Slug:</span>
              <code class="ml-2 text-xs bg-slate-100 px-2 py-1 rounded">{{ page.slug }}</code>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps({
  page: Object
})

const page = usePage()

const toolbarOptions = [
  [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
  ['bold', 'italic', 'underline', 'strike'],
  [{ 'list': 'ordered'}, { 'list': 'bullet' }],
  [{ 'script': 'sub'}, { 'script': 'super' }],
  [{ 'indent': '-1'}, { 'indent': '+1' }],
  [{ 'direction': 'rtl' }],
  [{ 'color': [] }, { 'background': [] }],
  [{ 'align': [] }],
  ['link', 'image', 'video'],
  ['clean']
]

const form = useForm({
  title: props.page?.title || '',
  slug: props.page?.slug || '',
  content: props.page?.content || '',
  meta_title: props.page?.meta_title || '',
  meta_description: props.page?.meta_description || '',
  _token: page.props.csrf_token,
})

const submit = () => {
  form._token = page.props.csrf_token
  
  if (props.page) {
    form.put(`/admin/pages/${props.page.id}`, {
      preserveScroll: true,
    })
  } else {
    form.post('/admin/pages', {
      preserveScroll: true,
    })
  }
}
</script>

