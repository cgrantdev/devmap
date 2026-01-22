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
            <MainButton
              text="Read Details"
              to="/education"
              bg-color="white"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- Discover Peptides Section -->
    <section class="min-h-screen bg-gray-50">
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <h1 class="text-4xl text-gray-900 mb-4">Peptide Categories</h1>
          <p class="text-xl text-gray-600">Browse by category to find the perfect peptides for your needs</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Search Bar -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search categories..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" aria-hidden="true">
              <path d="m21 21-4.34-4.34"></path>
              <circle cx="11" cy="11" r="8"></circle>
            </svg>
          </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
          <CategoryCard
            v-for="product in filteredProducts"
            :key="product.slug || product.name"
            :name="product.name"
            :image="product.image"
            :total-items="product.total_items"
            :to="`/product/${product.slug}`"
          />
        </div>
      </div>
    </section>

    <!-- Research Insights Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Research Insights</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <BlogPostCard
            v-for="insight in researchInsights"
            :key="insight.id"
            :title="insight.title"
            :description="insight.description"
            :image="insight.image ? `/images/blogs/${insight.image}` : null"
            :read-time="insight.readTime"
            :date="insight.date"
            :to="`/blog/${insight.slug || insight.id}`"
          />
        </div>
        <div class="justify-center flex">
          <MainButton
            text="More Details"
            to="/blogs"
            bg-color="gray-800"
          />
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import CategoryCard from '@/components/CategoryCard.vue'
import BlogPostCard from '@/components/BlogPostCard.vue'
import MainButton from '@/components/MainButton.vue'

const props = defineProps({
  productGroups: {
    type: Array,
    default: () => []
  }
})

// Hero background lazy loading
const heroBgRef = ref(null)
const heroBgLoaded = ref(false)

// Search functionality
const searchQuery = ref('')

const filteredProducts = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.productGroups
  }
  const query = searchQuery.value.toLowerCase().trim()
  return props.productGroups.filter(product => 
    product.name.toLowerCase().includes(query)
  )
})

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
  // Navigate to product listing page using slug
  router.visit(`/product/${product.slug}`)
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
