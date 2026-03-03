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
              <label class="block mb-1.5 font-semibold text-slate-800">Blog Type *</label>
              <select v-model="form.blog_type" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required>
                <option :value="null">-- Select Blog Type --</option>
                <option value="Regulation">Regulation</option>
                <option value="Research">Research</option>
                <option value="Community">Community</option>
                <option value="Guides">Guides</option>
                <option value="Industry">Industry</option>
              </select>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Author Name</label>
              <input v-model="form.author_name" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter author name" />
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Author Job</label>
              <input v-model="form.author_job" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter author job title" />
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Outline</label>
              <textarea v-model="form.outline" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="3" placeholder="Enter blog outline"></textarea>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Description</label>
              <textarea v-model="form.description" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="3"></textarea>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Introduction</label>
              <textarea v-model="form.introduction" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Enter introduction"></textarea>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Key Points</label>
              <div v-for="(point, index) in form.key_points" :key="index" class="flex gap-2 mb-2">
                <input v-model="form.key_points[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter key point" />
                <button type="button" @click="removeKeyPoint(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
              </div>
              <button type="button" @click="addKeyPoint" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Key Point</button>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Detailed Analysis</label>
              <textarea v-model="form.detailed_analysis" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="8" placeholder="Enter detailed analysis"></textarea>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Conclusion</label>
              <textarea v-model="form.conclusion" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Enter conclusion"></textarea>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Tags</label>
              <div v-for="(tag, index) in form.tags" :key="index" class="flex gap-2 mb-2">
                <input v-model="form.tags[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter tag" />
                <button type="button" @click="removeTag(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
              </div>
              <button type="button" @click="addTag" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Tag</button>
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
          
          <!-- SEO Data Section -->
          <div class="border-t border-slate-200 pt-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">SEO Data</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm text-slate-700 mb-2">Page Title</label>
                <input
                  v-model="form.seo_page_title"
                  type="text"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter page title for SEO"
                />
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">Description</label>
                <textarea
                  v-model="form.seo_description"
                  rows="3"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter meta description for SEO"
                ></textarea>
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">OG:Title</label>
                <input
                  v-model="form.seo_og_title"
                  type="text"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter Open Graph title"
                />
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">OG:Image</label>
                <input
                  v-model="form.seo_og_image"
                  type="url"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter Open Graph image URL"
                />
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">OG:Description</label>
                <textarea
                  v-model="form.seo_og_description"
                  rows="3"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter Open Graph description"
                ></textarea>
              </div>
            </div>
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
import { ref } from 'vue'

const props = defineProps({
  blog: Object,
})

const page = usePage()
const imagePreview = ref(null)

const form = useForm({
  title: props.blog?.title || '',
  blog_type: props.blog?.blog_type || null,
  author_name: props.blog?.author_name || '',
  author_job: props.blog?.author_job || '',
  outline: props.blog?.outline || '',
  description: props.blog?.description || '',
  introduction: props.blog?.introduction || '',
  key_points: props.blog?.key_points || [],
  detailed_analysis: props.blog?.detailed_analysis || '',
  conclusion: props.blog?.conclusion || '',
  tags: props.blog?.tags || [],
  image: null,
  read_time: props.blog?.read_time || '',
  status: props.blog?.status || 'draft',
  is_featured: props.blog?.is_featured || false,
  seo_page_title: props.blog?.seo_page_title || '',
  seo_description: props.blog?.seo_description || '',
  seo_og_title: props.blog?.seo_og_title || '',
  seo_og_description: props.blog?.seo_og_description || '',
  seo_og_image: props.blog?.seo_og_image || '',
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

const addKeyPoint = () => {
  form.key_points.push('')
}

const removeKeyPoint = (index) => {
  form.key_points.splice(index, 1)
}

const addTag = () => {
  form.tags.push('')
}

const removeTag = (index) => {
  form.tags.splice(index, 1)
}

const submit = () => {
  // Filter out empty Key Points
  form.key_points = form.key_points.filter(point => point.trim() !== '')
  
  // Filter out empty Tags
  form.tags = form.tags.filter(tag => tag.trim() !== '')
  
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
