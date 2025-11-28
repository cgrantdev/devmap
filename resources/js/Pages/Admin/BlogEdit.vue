<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">{{ blog ? 'Edit Blog Post' : 'Create New Blog Post' }}</h1>
      <p class="text-slate-500 mt-2">{{ blog ? 'Update blog post details' : 'Add a new blog post' }}</p>
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
              <input v-model="form.title" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required />
              <p class="text-sm text-slate-500 mt-1">Slug will be automatically generated from title</p>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Description</label>
              <textarea v-model="form.description" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="3"></textarea>
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
        <!-- Save Button -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex gap-3">
            <Link href="/admin/blogs" class="flex-1 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-center transition-all duration-200 font-medium">Cancel</Link>
            <button 
              @click="submit" 
              :disabled="form.processing" 
              class="flex-1 px-4 py-2.5 rounded-xl bg-blue-500 text-white hover:bg-blue-600 font-medium transition-all duration-200 shadow-sm"
            >
              {{ form.processing ? 'Saving...' : (blog ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
        
        <!-- Sidebar Content -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-6">
          <div>
            <label class="block mb-2 font-semibold text-slate-800">Status *</label>
            <select v-model="form.status" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
            <p class="text-sm text-slate-500 mt-1" v-if="form.status === 'published'">
              Published date will be set automatically when you save
            </p>
          </div>
          
          <div>
            <label class="flex items-center cursor-pointer">
              <input v-model="form.is_featured" type="checkbox" class="mr-2 w-4 h-4 text-blue-500 border-slate-300 rounded focus:ring-blue-500 focus:ring-2" />
              <span class="text-slate-700">Featured Post</span>
            </label>
          </div>
          
          <div>
            <label class="block mb-2 font-semibold text-slate-800">Featured Image</label>
            <div v-if="imagePreview" class="mb-4">
              <img :src="imagePreview" alt="Preview" class="w-full h-48 object-cover rounded-xl border border-slate-100 shadow-sm" />
            </div>
            <div v-else-if="blog?.image" class="mb-4">
              <img :src="blog.image" alt="Current" class="w-full h-48 object-cover rounded-xl border border-slate-100 shadow-sm" />
            </div>
            <div v-else class="mb-4 w-full h-48 bg-slate-100 rounded-xl border border-slate-100 flex items-center justify-center text-slate-400">
              <span>No Image</span>
            </div>
            <input
              @change="handleImageChange"
              type="file"
              accept="image/*"
              class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
            />
            <p class="text-sm text-slate-500 mt-1">Upload featured image</p>
          </div>
          
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">Read Time</label>
            <input v-model="form.read_time" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="e.g., 19 Min Read" />
          </div>
          
          <div v-if="blog?.published_at" class="pt-4 border-t border-slate-100">
            <label class="block mb-1 font-medium text-sm text-slate-600">Published Date</label>
            <p class="text-sm text-slate-700">{{ blog.published_at }}</p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { ref } from 'vue'

const props = defineProps({
  blog: Object,
})

const page = usePage()
const imagePreview = ref(null)

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
  title: props.blog?.title || '',
  description: props.blog?.description || '',
  content: props.blog?.content || '',
  image: null,
  read_time: props.blog?.read_time || '',
  status: props.blog?.status || 'draft',
  is_featured: props.blog?.is_featured || false,
  _token: page.props.csrf_token,
})

const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.image = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const submit = () => {
  // Update CSRF token before submission
  form._token = page.props.csrf_token
  
  if (props.blog) {
    form.post(`/admin/blogs/${props.blog.id}`, {
      preserveScroll: true,
      forceFormData: true,
    })
  } else {
    form.post('/admin/blogs', {
      preserveScroll: true,
      forceFormData: true,
    })
  }
}
</script>
