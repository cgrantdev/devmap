<template>
  <Head :title="title">
    <meta name="description" :content="description" />
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" :content="url" />
    <meta property="og:title" :content="ogTitle" />
    <meta property="og:description" :content="ogDescription" />
    <meta v-if="ogImage" property="og:image" :content="ogImage" />
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" :content="url" />
    <meta name="twitter:title" :content="ogTitle" />
    <meta name="twitter:description" :content="ogDescription" />
    <meta v-if="ogImage" name="twitter:image" :content="ogImage" />
    
    <!-- Canonical URL -->
    <link rel="canonical" :href="url" />
  </Head>
  <ModernLayout>
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
        
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
          <CategoryCard
            v-for="product in filteredProducts"
            :key="product.slug || product.name"
            :name="product.name"
            :image="product.image"
            :total-items="product.total_items"
            :to="`/product/${product.slug}`"
            :research-area="product.research_area"
          />
        </div>
      </div>
    </section>   
  </ModernLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watchEffect } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import CategoryCard from '@/components/CategoryCard.vue'
import BlogPostCard from '@/components/BlogPostCard.vue'
import MainButton from '@/components/MainButton.vue'

const props = defineProps({
  productGroups: {
    type: Array,
    default: () => []
  },
  seo: {
    type: Object,
    default: () => ({
      title: null,
      description: null,
      og_title: null,
      og_description: null,
      og_image: null,
      image: null,
      url: null,
    })
  }
})

const page = usePage()

// Computed values for reactive SEO updates
const title = computed(() => {
  const baseTitle = props.seo?.title || 'Research Peptides - Browse All Products'
  const siteName = page.props.site_name || 'Peptidemap'
  return `${baseTitle} - ${siteName}`
})

const description = computed(() => {
  return props.seo?.description || 'Browse our comprehensive collection of research peptides. Compare products, prices, and vendors to find the best peptides for your research needs.'
})

const url = computed(() => {
  return props.seo?.url || page.url
})

const ogTitle = computed(() => {
  return props.seo?.og_title || title.value
})

const ogDescription = computed(() => {
  return props.seo?.og_description || description.value
})

const ogImage = computed(() => {
  return props.seo?.og_image || props.seo?.image || null
})

// Watch for title changes and update document title immediately
watchEffect(() => {
  document.title = title.value
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
