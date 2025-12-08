<template>
  <FrontLayout>
    <!-- Hero Section -->
    <section class="pt-0 pb-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-[1360px] h-[574px] rounded-[24px] overflow-hidden relative mx-auto">
          <!-- Background Image -->
          <div 
            ref="heroBgRef"
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            data-bg-image="/images/hero/hero1.jpg"
            :style="{ backgroundImage: heroBgLoaded ? `url(/images/hero/hero1.jpg)` : 'none' }"
          >
            <div 
              class="absolute inset-0 pointer-events-none"
              :style="{ background: 'linear-gradient(260deg, rgba(0, 0, 0, 0.4) 45.49%, rgba(0, 0, 0, 0.81) 79.33%)' }"
            ></div>
          </div>
          
          <!-- Content -->
          <div class="relative p-10 h-full flex flex-col justify-center max-w-[800px] gap-6">
            <div class="flex flex-col gap-6">
              <h1 class="font-hv-muse font-normal text-6xl leading-tight tracking-normal text-white m-0">Learn About Peptides</h1>
              <p class="font-roboto font-normal text-lg leading-loose tracking-normal text-white m-0">Educational resources and research insights</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Discover Peptides Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Learn About Peptides</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
          <div
            v-for="product in displayedProducts"
            :key="product.name"
            class="bg-white rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 hover:shadow-lg"
          >
            <div class="w-full aspect-square bg-gray-100 flex items-center justify-center p-6 overflow-hidden">
              <img 
                v-if="product.image"
                :src="product.image" 
                :alt="product.name"
                class="w-full h-full object-contain object-center"
                loading="lazy"
                @error="handleImageError($event)"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
            <div class="p-6 flex flex-col gap-4 flex-1">
              <h3 class="text-center font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0">{{ product.name }}</h3>
              <p class="font-roboto font-normal text-sm leading-relaxed text-gray-500 text-center m-0">Total items {{ product.total_items }}</p>
              <button 
                @click="handleLearnClick(product)"
                class="w-full py-3 px-11 rounded-[500px] bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 cursor-pointer transition-colors duration-300 mt-auto hover:bg-gray-300"
              >
                Learn More
              </button>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div v-if="hasMoreProducts" class="text-center">
          <button 
            @click="loadMore"
            class="py-2.5 px-20 rounded-[500px] bg-gray-800 font-roboto font-medium text-xl leading-none tracking-normal text-white border-none cursor-pointer transition-colors duration-300 shadow-[0_2px_4px_rgba(0,0,0,0.1)] hover:bg-gray-700"
          >
            Load More
          </button>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'

const props = defineProps({
  productGroups: {
    type: Array,
    default: () => []
  }
})

// Hero background lazy loading
const heroBgRef = ref(null)
const heroBgLoaded = ref(false)

// Products display
const itemsPerPage = 8
const displayedCount = ref(itemsPerPage)

const displayedProducts = computed(() => {
  return props.productGroups.slice(0, displayedCount.value)
})

const hasMoreProducts = computed(() => {
  return displayedCount.value < props.productGroups.length
})

const loadMore = () => {
  displayedCount.value += itemsPerPage
}

// Handlers
const handleLearnClick = (product) => {
  // Always navigate to education post page using category slug
  router.visit(`/education/${product.slug}`)
}

const handleImageError = (event) => {
  // Prevent infinite loop - stop trying to load images if we've already failed
  if (event.target.dataset.failed) {
    return
  }
  // Mark as failed to prevent retry
  event.target.dataset.failed = 'true'
  // Hide the broken image and show placeholder
  event.target.style.display = 'none'
  if (event.target.parentElement) {
    const placeholder = document.createElement('div')
    placeholder.className = 'w-full h-full flex items-center justify-center text-gray-400'
    placeholder.innerHTML = '<span class="text-sm">No Image</span>'
    event.target.parentElement.appendChild(placeholder)
  }
}

// Setup lazy loading for hero background
onMounted(() => {
  nextTick(() => {
    if (heroBgRef.value) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const bgImage = entry.target.getAttribute('data-bg-image')
            if (bgImage) {
              const img = new Image()
              img.onload = () => {
                heroBgLoaded.value = true
              }
              img.src = bgImage
            }
            observer.unobserve(entry.target)
          }
        })
      }, {
        rootMargin: '50px'
      })
      observer.observe(heroBgRef.value)
    }
  })
})
</script>
