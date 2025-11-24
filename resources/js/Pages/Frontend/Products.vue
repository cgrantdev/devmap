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
              <h1 class="font-hv-muse font-normal text-6xl leading-tight tracking-normal text-white m-0">The Science Behind Peptides</h1>
              <p class="font-roboto font-normal text-lg leading-loose tracking-normal text-white m-0">Everything You Need to Know About Peptides</p>
            </div>
            <button 
              @click="handleCtaClick('/education')"
              class="w-fit py-2.5 px-20 rounded-[500px] bg-white font-roboto font-medium text-xl leading-none tracking-normal text-gray-800 border-none cursor-pointer transition-colors duration-300 flex items-center justify-center hover:bg-gray-100"
            >
              Read Details
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Discover Peptides Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Discover Peptides</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
          <div
            v-for="product in displayedProducts"
            :key="product.name"
            class="bg-white rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 hover:shadow-lg"
          >
            <div class="w-full aspect-square bg-gray-100 flex items-center justify-center p-6 overflow-hidden">
              <img 
                :src="product.image" 
                :alt="product.name"
                class="w-full h-full object-contain object-center"
                loading="lazy"
                @error="handleImageError($event)"
              />
            </div>
            <div class="p-6 flex flex-col gap-4 flex-1">
              <h3 class="text-center font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0">{{ product.name }}</h3>
              <p class="font-roboto font-normal text-sm leading-relaxed text-gray-500 text-center m-0">Total items {{ product.total_items }}</p>
              <button 
                @click="handleShopClick(product)"
                class="w-full py-3 px-11 rounded-[500px] bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 cursor-pointer transition-colors duration-300 mt-auto hover:bg-gray-300"
              >
                Shop Now
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

    <!-- Research Insights Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Research Insights</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div
            v-for="insight in researchInsights"
            :key="insight.id"
            class="bg-white border border-gray-200 rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 h-full hover:shadow-lg"
          >
            <div class="w-full aspect-[325/404] overflow-hidden bg-gray-100 rounded-t-lg">
              <img 
                :src="`/images/blogs/${insight.image}`" 
                :alt="insight.title"
                class="w-full h-full object-cover object-center block"
                loading="lazy"
              />
            </div>
            <div class="flex justify-between items-center py-3 px-4 font-roboto font-normal text-xs leading-relaxed text-gray-500 bg-white">
              <span class="flex items-center gap-1.5">
                <svg class="flex-shrink-0 text-gray-500 w-4 h-4" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M8 14C11.3137 14 14 11.3137 14 8C14 4.68629 11.3137 2 8 2C4.68629 2 2 4.68629 2 8C2 11.3137 4.68629 14 8 14Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M8 5V8L10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ insight.readTime }}
              </span>
              <span class="text-gray-500">{{ insight.date }}</span>
            </div>
            <div class="p-5 flex flex-col gap-3 flex-1">
              <h3 class="font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0 mb-1 text-center">{{ insight.title }}</h3>
              <p class="font-roboto font-normal text-sm leading-loose text-gray-500 m-0 flex-1 mb-2 text-center">{{ insight.description }}</p>
              <button class="w-full py-3 px-11 rounded-[500px] bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 cursor-pointer transition-colors duration-300 mt-auto hover:bg-gray-300">
                Read Details
              </button>
            </div>
          </div>
        </div>
        <div class="text-center">
          <button class="py-[10px] px-20 rounded-[500px] bg-gray-800 font-roboto font-medium text-xl leading-none tracking-normal text-white border-none cursor-pointer transition-colors duration-300 shadow-[0_2px_4px_rgba(0,0,0,0.1)] hover:bg-gray-700">
            More Details
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

// Research Insights (same as Welcome page)
const researchInsights = ref([
  {
    id: 1,
    title: 'Safe Handling',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...',
    image: '1.jpg',
    readTime: '19 Min Read',
    date: '22 Aug 2025'
  },
  {
    id: 2,
    title: 'Research Guidelines',
    description: 'Understanding proper research protocols and guidelines for peptide studies in laboratory settings.',
    image: '1.jpg',
    readTime: '15 Min Read',
    date: '20 Aug 2025'
  },
  {
    id: 3,
    title: 'Benefits of BPC-157',
    description: 'Exploring the potential benefits and research applications of BPC-157 in scientific studies.',
    image: '1.jpg',
    readTime: '12 Min Read',
    date: '18 Aug 2025'
  },
  {
    id: 4,
    title: 'Peptide Storage',
    description: 'Best practices for storing and handling peptides to maintain their integrity and effectiveness.',
    image: '1.jpg',
    readTime: '10 Min Read',
    date: '15 Aug 2025'
  }
])

// Handlers
const handleCtaClick = (url) => {
  router.visit(url)
}

const handleShopClick = (product) => {
  // Navigate to products page with filter/search for this product name
  router.visit(`/products?search=${encodeURIComponent(product.name)}`)
}

const handleImageError = (event) => {
  event.target.src = '/images/peptides/default.png'
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
