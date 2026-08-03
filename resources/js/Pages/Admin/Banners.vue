<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Homepage Banners</h1>
      <p class="text-slate-600">Manage the hero carousel slides that appear at the top of the homepage.</p>
    </div>


    <!-- Hero Slides Section -->
    <div class="mt-12 space-y-6">
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

          <div class="mb-4 pb-4 border-b border-slate-100">
            <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Analytics Tag</label>
            <input v-model="slide.analytics_label" type="text" placeholder="e.g. certified-pep-spring-promo"
              class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p class="text-xs text-slate-500 mt-1">
              Short, stable name shown in <a href="/admin/analytics" class="text-blue-600 hover:underline">Analytics</a> and used to group this banner's impressions/clicks over time. Keep it consistent across edits — changing it starts a new row in analytics.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Eyebrow</label>
              <input v-model="slide.eyebrow" type="text" placeholder="e.g. Featured Partner"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p class="text-xs text-slate-500 mt-1">Small label above the title.</p>
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Badge</label>
              <input v-model="slide.badge" type="text" placeholder="e.g. Featured"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p class="text-xs text-slate-500 mt-1">Chip in the top-left corner.</p>
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Coupon Code</label>
              <input v-model="slide.coupon_code" type="text" placeholder="e.g. PMAP"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase" />
              <p class="text-xs text-slate-500 mt-1">Green copy-to-clipboard pill.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Title *</label>
              <input v-model="slide.title" type="text" required
                :class="['w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2', $page.props.errors[`hero_slides.${index}.title`] ? 'border-red-300 focus:ring-red-500' : 'border-slate-100 focus:ring-blue-500']" />
              <div v-if="$page.props.errors[`hero_slides.${index}.title`]" class="text-red-500 text-sm mt-1">
                {{ $page.props.errors[`hero_slides.${index}.title`] }}
              </div>
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Title Highlight</label>
              <input v-model="slide.title_highlight" type="text" placeholder="e.g. Certified Peptides"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p class="text-xs text-slate-500 mt-1">Substring inside the title to accent in a different color.</p>
            </div>
          </div>

          <div class="mt-4">
            <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Subtitle</label>
            <textarea v-model="slide.subtitle" rows="2"
              class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">CTA Text</label>
              <input v-model="slide.cta_text" type="text" placeholder="e.g. Browse catalog"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">CTA URL</label>
              <input v-model="slide.cta_url" type="text" placeholder="/brand/foo or https://â€¦"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p class="text-xs text-slate-500 mt-1">Whole slide links here.</p>
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Open link in</label>
              <select v-model="slide.target"
                class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="_self">Same tab</option>
                <option value="_blank">New tab</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Desktop background image</label>
              <input @change="handleSlideImageChange(index, $event)" type="file" accept="image/*"
                :class="['w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2', $page.props.errors[`hero_slides.${index}.image`] ? 'border-red-300 focus:ring-red-500' : 'border-slate-100 focus:ring-blue-500']" />
              <p class="text-xs text-slate-500 mt-1">Landscape. Max: <span class="font-semibold text-slate-700">{{ maxFileSize }}</span></p>
              <div v-if="$page.props.errors[`hero_slides.${index}.image`]" class="text-red-500 text-sm mt-1">
                {{ $page.props.errors[`hero_slides.${index}.image`] }}
              </div>
              <div v-if="slide.image_url || slide.imagePreview" class="mt-2">
                <img :src="slide.imagePreview || slide.image_url" alt="Preview"
                  class="w-full h-40 object-cover rounded-xl border border-slate-200" />
                <p v-if="slide.imagePreview" class="text-xs text-slate-500 mt-1">New image preview</p>
              </div>
              <p v-else class="mt-2 text-xs text-slate-400 italic">Leave empty for a gradient background.</p>
            </div>
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Mobile background image</label>
              <input @change="handleSlideMobileImageChange(index, $event)" type="file" accept="image/*"
                :class="['w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2', $page.props.errors[`hero_slides.${index}.image_mobile`] ? 'border-red-300 focus:ring-red-500' : 'border-slate-100 focus:ring-blue-500']" />
              <p class="text-xs text-slate-500 mt-1">Portrait crop shown under 768px. Falls back to desktop image.</p>
              <div v-if="$page.props.errors[`hero_slides.${index}.image_mobile`]" class="text-red-500 text-sm mt-1">
                {{ $page.props.errors[`hero_slides.${index}.image_mobile`] }}
              </div>
              <div v-if="slide.image_mobile_url || slide.imageMobilePreview" class="mt-2">
                <img :src="slide.imageMobilePreview || slide.image_mobile_url" alt="Mobile preview"
                  class="w-40 h-64 object-cover rounded-xl border border-slate-200 mx-auto" />
                <p v-if="slide.imageMobilePreview" class="text-xs text-slate-500 mt-1 text-center">New image preview</p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 items-end">
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800 text-sm">Order</label>
              <input v-model.number="slide.order" type="number" min="0"
                :class="['w-full border rounded-xl px-3 py-2 focus:outline-none focus:ring-2', $page.props.errors[`hero_slides.${index}.order`] ? 'border-red-300 focus:ring-red-500' : 'border-slate-100 focus:ring-blue-500']" />
              <p class="text-xs text-slate-500 mt-1">Lower shows first.</p>
            </div>
            <label class="flex items-center gap-2 pb-2">
              <input v-model="slide.is_active" type="checkbox" class="rounded" />
              <span class="text-sm text-slate-700 font-semibold">Active</span>
            </label>
            <label class="flex items-center gap-2 pb-2">
              <input v-model="slide.sponsored" type="checkbox" class="rounded" />
              <span class="text-sm text-slate-700 font-semibold">Sponsored (adds rel="sponsored")</span>
            </label>
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
import { useToast as useVueToastification } from 'vue-toastification'
import { useAdminLoading } from '../../composables/useAdminLoading'

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()
const { setLoading } = useAdminLoading()

const props = defineProps({
  heroSlides: { type: Array, default: () => [] },
  maxFileSize: { type: String, default: '2 MB' },
})

// Hero Slides functionality
const heroSlides = ref(props.heroSlides.map((slide, index) => ({
  analytics_label: slide.analytics_label || '',
  eyebrow: slide.eyebrow || '',
  badge: slide.badge || '',
  title: slide.title || '',
  title_highlight: slide.title_highlight || '',
  subtitle: slide.subtitle || '',
  cta_text: slide.cta_text || '',
  cta_url: slide.cta_url || '',
  coupon_code: slide.coupon_code || '',
  target: slide.target || '_self',
  sponsored: slide.sponsored ?? false,
  image: slide.image || null,
  image_url: slide.image_url || null,
  imagePreview: null,
  image_mobile: slide.image_mobile || null,
  image_mobile_url: slide.image_mobile_url || null,
  imageMobilePreview: null,
  order: slide.order !== undefined ? slide.order : index,
  is_active: slide.is_active !== undefined ? slide.is_active : true,
})))

const addHeroSlide = () => {
  heroSlides.value.push({
    analytics_label: '',
    eyebrow: '',
    badge: '',
    title: '',
    title_highlight: '',
    subtitle: '',
    cta_text: '',
    cta_url: '',
    coupon_code: '',
    target: '_self',
    sponsored: false,
    image: null,
    image_url: null,
    imagePreview: null,
    image_mobile: null,
    image_mobile_url: null,
    imageMobilePreview: null,
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
    heroSlides.value[index].image_url = null
    heroSlides.value[index].imagePreview = null
    const reader = new FileReader()
    reader.onload = (e) => { heroSlides.value[index].imagePreview = e.target.result }
    reader.onerror = () => { heroSlides.value[index].imagePreview = null }
    reader.readAsDataURL(file)
  } else {
    heroSlides.value[index].imagePreview = null
  }
}

const handleSlideMobileImageChange = (index, event) => {
  const file = event.target.files[0]
  if (file) {
    heroSlides.value[index].image_mobile = file
    heroSlides.value[index].image_mobile_url = null
    heroSlides.value[index].imageMobilePreview = null
    const reader = new FileReader()
    reader.onload = (e) => { heroSlides.value[index].imageMobilePreview = e.target.result }
    reader.onerror = () => { heroSlides.value[index].imageMobilePreview = null }
    reader.readAsDataURL(file)
  } else {
    heroSlides.value[index].imageMobilePreview = null
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
    const put = (k, v) => formData.append(`hero_slides[${index}][${k}]`, v ?? '')
    put('analytics_label', slide.analytics_label)
    put('title', slide.title)
    put('title_highlight', slide.title_highlight)
    put('eyebrow', slide.eyebrow)
    put('badge', slide.badge)
    put('subtitle', slide.subtitle)
    put('cta_text', slide.cta_text)
    put('cta_url', slide.cta_url)
    put('coupon_code', slide.coupon_code)
    put('target', slide.target || '_self')
    put('order', slide.order !== undefined ? slide.order : index)
    put('is_active', slide.is_active ? '1' : '0')
    put('sponsored', slide.sponsored ? '1' : '0')

    // Desktop image
    if (slide.image instanceof File) {
      formData.append(`hero_slides[${index}][image]`, slide.image)
    } else if (typeof slide.image === 'string' && slide.image) {
      formData.append(`hero_slides[${index}][existing_image]`, slide.image)
    }
    // Mobile image
    if (slide.image_mobile instanceof File) {
      formData.append(`hero_slides[${index}][image_mobile]`, slide.image_mobile)
    } else if (typeof slide.image_mobile === 'string' && slide.image_mobile) {
      formData.append(`hero_slides[${index}][existing_image_mobile]`, slide.image_mobile)
    }
  })
  
  // Use router to post form data
  router.post('/admin/banners/hero-slides', formData, {
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

