<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">Settings</h1>
      <p class="text-slate-500 mt-2">Manage static pages and homepage hero slides</p>
    </div>
    
    <!-- Flash messages are now handled by toast notifications -->
    
    <!-- Validation Errors Summary -->
    <div v-if="Object.keys($page.props.errors || {}).length > 0" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
      <p class="font-semibold mb-2">Please fix the following errors:</p>
      <ul class="list-disc list-inside space-y-1">
        <li v-for="(error, field) in $page.props.errors" :key="field" class="text-sm">
          <strong>{{ field }}:</strong> {{ Array.isArray(error) ? error[0] : error }}
        </li>
      </ul>
    </div>

    <!-- Hero Slides -->
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-slate-800">Homepage Hero Slides</h2>
        <button
          @click="addHeroSlide"
          class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
        >
          + Add New Slide
        </button>
      </div>

      <form @submit.prevent="saveHeroSlides" class="space-y-6">
        <div v-for="(slide, index) in heroSlides" :key="index" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-slate-800">Slide {{ index + 1 }}</h3>
            <button
              type="button"
              @click="removeHeroSlide(index)"
              class="px-3 py-1 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors text-sm"
            >
              Remove
            </button>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Title *</label>
              <input 
                v-model="slide.title" 
                type="text" 
                required
                :class="['w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2', $page.props.errors[`hero_slides.${index}.title`] ? 'border-red-300 focus:ring-red-500' : 'border-slate-100 focus:ring-blue-500']"
              />
              <div v-if="$page.props.errors[`hero_slides.${index}.title`]" class="text-red-500 text-sm mt-1">
                {{ $page.props.errors[`hero_slides.${index}.title`] }}
              </div>
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Subtitle</label>
              <input 
                v-model="slide.subtitle" 
                type="text"
                :class="['w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2', $page.props.errors[`hero_slides.${index}.subtitle`] ? 'border-red-300 focus:ring-red-500' : 'border-slate-100 focus:ring-blue-500']"
              />
              <div v-if="$page.props.errors[`hero_slides.${index}.subtitle`]" class="text-red-500 text-sm mt-1">
                {{ $page.props.errors[`hero_slides.${index}.subtitle`] }}
              </div>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">CTA Text</label>
              <input 
                v-model="slide.cta_text" 
                type="text"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">CTA URL</label>
              <input 
                v-model="slide.cta_url" 
                type="text"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Image</label>
              <input 
                @change="handleSlideImageChange(index, $event)"
                type="file" 
                accept="image/*"
                :class="['w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2', $page.props.errors[`hero_slides.${index}.image`] ? 'border-red-300 focus:ring-red-500' : 'border-slate-100 focus:ring-blue-500']"
              />
              <p class="text-xs text-slate-500 mt-1">
                Maximum file size: <span class="font-semibold text-slate-700">{{ maxFileSize }}</span>
              </p>
              <div v-if="$page.props.errors[`hero_slides.${index}.image`]" class="text-red-500 text-sm mt-1">
                {{ $page.props.errors[`hero_slides.${index}.image`] }}
              </div>
              <div v-if="slide.image_url || slide.imagePreview" class="mt-2">
                <img 
                  :src="slide.imagePreview || slide.image_url" 
                  alt="Preview" 
                  class="w-full h-48 object-cover rounded-xl border border-slate-200" 
                />
                <p v-if="slide.imagePreview" class="text-xs text-slate-500 mt-1">New image preview</p>
              </div>
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Order</label>
              <input 
                v-model.number="slide.order" 
                type="number" 
                min="0"
                :class="['w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2', $page.props.errors[`hero_slides.${index}.order`] ? 'border-red-300 focus:ring-red-500' : 'border-slate-100 focus:ring-blue-500']"
              />
              <div v-if="$page.props.errors[`hero_slides.${index}.order`]" class="text-red-500 text-sm mt-1">
                {{ $page.props.errors[`hero_slides.${index}.order`] }}
              </div>
              <div class="mt-2">
                <label class="flex items-center gap-2">
                  <input 
                    v-model="slide.is_active" 
                    type="checkbox"
                    class="rounded"
                  />
                  <span class="text-sm text-slate-700">Active</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div v-if="heroSlides.length === 0" class="bg-slate-50 border border-slate-200 rounded-xl p-8 text-center">
          <p class="text-slate-600">No hero slides configured. Click "Add New Slide" to get started.</p>
        </div>

        <div v-if="heroSlides.length > 0" class="flex justify-end">
          <button 
            type="submit" 
            class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
          >
            Save All Hero Slides
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { useAdminLoading } from '../../composables/useAdminLoading'

const { setLoading } = useAdminLoading()

const props = defineProps({
  heroSlides: Array,
  maxFileSize: String,
})

// Initialize hero slides
const heroSlides = ref(props.heroSlides.map((slide, index) => ({
  title: slide.title || '',
  subtitle: slide.subtitle || '',
  cta_text: slide.cta_text || '',
  cta_url: slide.cta_url || '',
  image: slide.image || null,
  image_url: slide.image_url || null,
  imagePreview: null,
  order: slide.order !== undefined ? slide.order : index,
  is_active: slide.is_active !== undefined ? slide.is_active : true,
})))

const addHeroSlide = () => {
  heroSlides.value.push({
    title: '',
    subtitle: '',
    cta_text: '',
    cta_url: '',
    image: null,
    image_url: null,
    imagePreview: null,
    order: heroSlides.value.length,
    is_active: true,
  })
}

const removeHeroSlide = (index) => {
  if (confirm('Are you sure you want to remove this hero slide?')) {
    heroSlides.value.splice(index, 1)
  }
}

const handleSlideImageChange = (index, event) => {
  const file = event.target.files[0]
  if (file) {
    // Set the new file
    heroSlides.value[index].image = file
    // Clear the old image_url so preview takes precedence
    heroSlides.value[index].image_url = null
    // Clear any existing preview first
    heroSlides.value[index].imagePreview = null
    
    const reader = new FileReader()
    reader.onload = (e) => {
      heroSlides.value[index].imagePreview = e.target.result
    }
    reader.onerror = () => {
      console.error('Failed to read file')
      heroSlides.value[index].imagePreview = null
    }
    reader.readAsDataURL(file)
  } else {
    // If no file selected, clear preview
    heroSlides.value[index].imagePreview = null
  }
}

const saveHeroSlides = () => {
  const page = usePage()
  
  // Show loading overlay
  setLoading(true, 'Saving hero slides, please wait...')
  
  // Prepare form data
  const formData = new FormData()
  
  // Add CSRF token
  formData.append('_token', page.props.csrf_token)
  
  // Append each slide's data in the format Laravel expects for validation
  heroSlides.value.forEach((slide, index) => {
    formData.append(`hero_slides[${index}][title]`, slide.title || '')
    formData.append(`hero_slides[${index}][subtitle]`, slide.subtitle || '')
    formData.append(`hero_slides[${index}][cta_text]`, slide.cta_text || '')
    formData.append(`hero_slides[${index}][cta_url]`, slide.cta_url || '')
    formData.append(`hero_slides[${index}][order]`, slide.order !== undefined ? slide.order : index)
    formData.append(`hero_slides[${index}][is_active]`, slide.is_active ? '1' : '0')
    
    // Only append image if it's a File (new upload)
    // Don't send existing image strings - the controller will preserve them
    if (slide.image && slide.image instanceof File) {
      formData.append(`hero_slides[${index}][image]`, slide.image)
    }
    
    // Store existing image filename separately so controller knows to keep it
    if (slide.image && typeof slide.image === 'string') {
      formData.append(`hero_slides[${index}][existing_image]`, slide.image)
    }
  })
  
  // Use router to post form data
  router.post('/admin/settings/hero-slides', formData, {
    preserveScroll: true,
    preserveState: true,
    forceFormData: true,
    onStart: () => {
      // Ensure loading is shown when request starts
      setLoading(true, 'Saving hero slides, please wait...')
    },
    onSuccess: () => {
      // Reload to get updated data
      router.reload({ 
        only: ['heroSlides'],
        onFinish: () => {
          setLoading(false)
        }
      })
    },
    onError: (errors) => {
      // Hide loading overlay on error
      setLoading(false)
    },
    onFinish: () => {
      // Ensure loading overlay is hidden when request finishes
      setLoading(false)
    }
  })
}
</script>
