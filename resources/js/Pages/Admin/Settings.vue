<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">Settings</h1>
      <p class="text-slate-500 mt-2">Manage static pages and homepage hero slides</p>
    </div>
    
    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
      {{ $page.props.flash.success }}
    </div>
    
    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
      {{ $page.props.flash.error }}
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
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Subtitle</label>
              <input 
                v-model="slide.subtitle" 
                type="text"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
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
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <img 
                v-if="slide.image_url || slide.imagePreview" 
                :src="slide.image_url || slide.imagePreview" 
                alt="Preview" 
                class="mt-2 w-full h-48 object-cover rounded-xl" 
              />
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Order</label>
              <input 
                v-model.number="slide.order" 
                type="number" 
                min="0"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
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
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  heroSlides: Array,
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
    heroSlides.value[index].image = file
    const reader = new FileReader()
    reader.onload = (e) => {
      heroSlides.value[index].imagePreview = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const saveHeroSlides = () => {
  const page = usePage()
  
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
    forceFormData: true,
    onSuccess: () => {
      // Reload to get updated data
      router.reload({ only: ['heroSlides'] })
    }
  })
}
</script>
