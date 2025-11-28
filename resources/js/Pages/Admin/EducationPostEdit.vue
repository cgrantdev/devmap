<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">{{ post ? 'Edit Education Post' : 'Create New Education Post' }}</h1>
      <p class="text-slate-500 mt-2">{{ post ? 'Update education post details' : 'Add a new education post' }}</p>
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
              <label class="block mb-1.5 font-semibold text-slate-800">Overview</label>
              <QuillEditor
                v-model:content="form.overview"
                contentType="html"
                theme="snow"
                :toolbar="toolbarOptions"
                class="bg-white"
              />
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Content</label>
              <QuillEditor
                v-model:content="form.content"
                contentType="html"
                theme="snow"
                :toolbar="toolbarOptions"
                class="bg-white"
              />
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Shop URL</label>
              <input v-model="form.shop_url" type="url" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="https://..." />
            </div>
            
            <!-- Key Effects -->
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Key Effects</label>
              <div v-for="(effect, index) in form.key_effects" :key="index" class="flex gap-2 mb-2">
                <input v-model="form.key_effects[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter effect" />
                <button type="button" @click="removeKeyEffect(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
              </div>
              <button type="button" @click="addKeyEffect" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Effect</button>
            </div>
            
            <!-- Accordion Sections -->
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Accordion Sections</label>
              <div v-for="(section, index) in form.accordion_sections" :key="index" class="mb-4 p-4 border border-slate-100 rounded-xl">
                <div class="flex justify-between items-center mb-2">
                  <h3 class="font-medium text-slate-700">Section {{ index + 1 }}</h3>
                  <button type="button" @click="removeAccordionSection(index)" class="px-3 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-sm font-medium">Remove</button>
                </div>
                <div class="mb-2">
                  <label class="block mb-1.5 text-sm font-semibold text-slate-800">Title *</label>
                  <input v-model="form.accordion_sections[index].title" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required />
                </div>
                <div>
                  <label class="block mb-1 text-sm">Content *</label>
                  <QuillEditor
                    v-model:content="form.accordion_sections[index].content"
                    contentType="html"
                    theme="snow"
                    :toolbar="toolbarOptions"
                    class="bg-white"
                  />
                </div>
              </div>
              <button type="button" @click="addAccordionSection" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">+ Add Accordion Section</button>
            </div>
          </div>
        </form>
      </div>
      
      <!-- Right Sidebar -->
      <div class="w-80 flex-shrink-0 space-y-6">
        <!-- Save Button -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex gap-3">
            <Link href="/admin/education-posts" class="flex-1 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-center transition-all duration-200 font-medium">Cancel</Link>
            <button 
              @click="submit" 
              :disabled="form.processing" 
              class="flex-1 px-4 py-2.5 rounded-xl bg-blue-500 text-white hover:bg-blue-600 font-medium transition-all duration-200 shadow-sm"
            >
              {{ form.processing ? 'Saving...' : (post ? 'Update' : 'Create') }}
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
            <label class="block mb-2 font-semibold text-slate-800">Featured Image</label>
            <div v-if="imagePreview" class="mb-4">
              <img :src="imagePreview" alt="Preview" class="w-full h-48 object-cover rounded-xl border border-slate-100 shadow-sm" />
            </div>
            <div v-else-if="post?.image" class="mb-4">
              <img :src="post.image" alt="Current" class="w-full h-48 object-cover rounded-xl border border-slate-100 shadow-sm" />
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
            <label class="block mb-1.5 font-semibold text-slate-800">Rating</label>
            <input v-model.number="form.rating" type="number" step="0.01" min="0" max="5" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
          </div>
          
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">Rating Count</label>
            <input v-model.number="form.rating_count" type="number" min="0" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
          </div>
          
          <div v-if="post?.published_at" class="pt-4 border-t border-slate-100">
            <label class="block mb-1 font-medium text-sm text-slate-600">Published Date</label>
            <p class="text-sm text-slate-700">{{ post.published_at }}</p>
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
  post: Object,
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
  title: props.post?.title || '',
  description: props.post?.description || '',
  overview: props.post?.overview || '',
  content: props.post?.content || '',
  image: null,
  rating: props.post?.rating || 0,
  rating_count: props.post?.rating_count || 0,
  key_effects: props.post?.key_effects || [],
  accordion_sections: props.post?.accordion_sections || [],
  shop_url: props.post?.shop_url || '',
  status: props.post?.status || 'draft',
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

const addKeyEffect = () => {
  form.key_effects.push('')
}

const removeKeyEffect = (index) => {
  form.key_effects.splice(index, 1)
}

const addAccordionSection = () => {
  form.accordion_sections.push({
    title: '',
    content: ''
  })
}

const removeAccordionSection = (index) => {
  form.accordion_sections.splice(index, 1)
}

const submit = () => {
  // Filter out empty key effects
  form.key_effects = form.key_effects.filter(effect => effect.trim() !== '')
  
  // Update CSRF token before submission
  form._token = page.props.csrf_token
  
  if (props.post) {
    form.post(`/admin/education-posts/${props.post.id}`, {
      preserveScroll: true,
      forceFormData: true,
    })
  } else {
    form.post('/admin/education-posts', {
      preserveScroll: true,
      forceFormData: true,
    })
  }
}
</script>
