<template>
  <FrontLayout>
      <!-- Hero Section Carousel -->
      <section class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div class="rounded-xl overflow-hidden shadow-lg">
            <div
              class="relative h-96"
              @mouseenter="stopAutoplay"
              @mouseleave="startAutoplay"
            >
              <div
                v-for="(slide, index) in heroSlides"
                :key="index"
                class="absolute inset-0 transition-opacity duration-700 opacity-100"
                :class="currentSlide === index ? 'opacity-100 z-10' : 'opacity-0 z-0'"
              >
                <!-- Background Image -->
                <img
                  :src="slide.image"
                  :alt="slide.title || 'Hero image'"
                  class="absolute inset-0 w-full h-full object-cover"
                  @load="onImageLoad(slide.image)"
                />
                <div class="absolute inset-0 bg-black/60"></div>

                <!-- Content -->
                <div class="relative z-10 h-full flex items-center">
                  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <div class="max-w-2xl">
                      <p class="text-white/90 uppercase text-sm font-semibold tracking-wider mb-3">
                        {{ slide.heading || 'PREMIUM RESEARCH PEPTIDES' }}
                      </p>

                      <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-white mb-4">
                        {{ slide.title || 'Peptide Sciences' }}
                      </h1>

                      <p class="text-white/90 text-lg md:text-xl mb-8 leading-relaxed">
                        {{ slide.subtitle || '99%+ purity guaranteed. Third-party tested with COAs available for every batch.' }}
                      </p>

                      <div class="flex flex-col sm:flex-row gap-4">
                        <button class="px-6 py-3 bg-transparent border-2 border-white/80 text-white rounded-lg font-medium hover:bg-white/10 transition-colors flex items-center justify-center gap-2">
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                            <line x1="7" x2="7.01" y1="7" y2="7"></line>
                          </svg>
                          <span>Use code: {{ slide.promoCode || 'PMAP' }}</span>
                        </button>

                        <Link
                          :to="slide.ctaUrl || '#'"
                          class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors text-center"
                        >
                          {{ slide.ctaText || 'Shop Now' }}
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Dots -->
              <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                <button
                  v-for="(slide, index) in heroSlides"
                  :key="index"
                  @click="goToSlide(index)"
                  :class="[
                    'h-2 rounded-full transition-all',
                    currentSlide === index ? 'bg-white w-4' : 'bg-white/50 w-2'
                  ]"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      
      <!-- Top Rated Vendors Section -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="mb-8">
          <h2 class="text-3xl text-gray-900 mb-1">Top Rated Vendors</h2>
          <p class="text-gray-600">Browse the most trusted peptide suppliers.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
          <TopRatedVendorCard
            v-for="vendor in topVendors"
            :key="vendor.id"
            :id="vendor.id"
            :name="vendor.name"
            :slug="vendor.slug"
            :logo="vendor.logo"
            :initials="vendor.initials"
            :location="vendor.location"
            :rating="vendor.rating"
            :reviews="vendor.reviews"
          />
        </div>          
      </section>

      <!-- Your Brand Section -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-gradient-to-r from-slate-700 to-slate-600 rounded-lg p-8 text-center text-white relative overflow-hidden">
          <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-64 h-64 bg-white rounded-full -translate-x-32 -translate-y-32"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white rounded-full translate-x-32 translate-y-32"></div>
          </div>
          <div class="relative z-10">
            <div class="text-sm uppercase tracking-wider mb-2 opacity-90">Sponsored</div>
            <h3 class="text-2xl mb-2">Advertise Your Brand Here</h3>
            <p class="text-slate-100 mb-4">Reach thousands of peptide researchers and customers</p>
            <button class="bg-white text-slate-700 px-6 py-2 rounded-lg hover:bg-slate-50 transition-colors">Learn More</button>
          </div>
        </div>
      </section>

      <!-- Peptide Encyclopedia Section -->
      <section class="bg-gradient-to-b from-slate-50 to-slate-100 border-y border-slate-200 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <!-- Header -->
          <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-700 rounded-full mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-8 h-8 text-white">
                <path d="M12 7v14"></path>
                <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
              </svg>
            </div>
            <h2 class="text-4xl text-gray-900 mb-3">Peptide Encyclopedia</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
              Comprehensive information on popular research peptides with detailed profiles, research data, and vendor comparisons.
            </p>
          </div>

          <!-- Peptide Cards Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <PeptideEncyclopediaCard
              v-for="peptide in displayedEncyclopediaPeptides"
              :key="peptide.id"
              :id="peptide.id"
              :name="peptide.name"
              :description="peptide.description"
              :image="peptide.image"
              :slug="peptide.slug"
              :category-tag="peptide.categoryTag"
            />
          </div>

          <!-- Footer Button -->
          <div class="text-center mt-10">
            <Link
              to="/products"
              class="bg-slate-700 hover:bg-slate-800 text-white px-10 py-4 rounded-lg transition-colors inline-flex items-center gap-2 shadow-lg"
            >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-5 h-5" aria-hidden="true">
              <path d="M12 7v14"></path>
              <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
            </svg>
            View Full Encyclopedia
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-5 h-5" aria-hidden="true">
              <path d="m9 18 6-6-6-6"></path>
            </svg>
            </Link>
          </div>
        </div>
      </section>

      <!-- Premium Vendor Section -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-gradient-to-r from-slate-700 to-slate-600 rounded-lg p-8 text-center text-white relative overflow-hidden">
          <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full translate-x-32 -translate-y-32"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full -translate-x-32 translate-y-32"></div>
          </div>
          <div class="relative z-10">
            <div class="text-sm uppercase tracking-wider mb-2 opacity-90">FEATURED SPOT</div>
            <h3 class="text-2xl mb-2">Premium Vendor Placement Available</h3>
            <p class="text-slate-100 mb-4">Showcase your products to our growing community</p>
            <button class="bg-white text-slate-700 px-6 py-2 rounded-lg hover:bg-slate-50 transition-colors">Get Started</button>
          </div>
        </div>
      </section>

      <!-- Limited Time Discounts Section -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-3xl text-gray-900 mb-1">Limited Time Discounts</h2>
            <p class="text-gray-600">Save up to 25% with verified discount codes</p>
          </div>
          <Link
            href="/deals"
            class="text-slate-700 hover:text-slate-900 flex items-center gap-1"
          >
            View All Deals
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-4 h-4" aria-hidden="true">
              <path d="m9 18 6-6-6-6"></path>
            </svg>
          </Link>
        </div>

        <!-- Discount Cards Grid -->
        <div v-if="discountDeals.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <LimitedTimeDiscountCard
            v-for="deal in discountDeals"
            :key="deal.id"
            :id="deal.id"
            :name="deal.name"
            :slug="deal.slug"
            :logo="deal.logo"
            :initials="deal.initials"
            :rating="deal.rating"
            :reviews="deal.reviews"
            :discount="deal.discount"
            :code="deal.code"
          />
        </div>
        <div v-else class="text-center py-12">
          <p class="text-gray-500">No discount deals available at the moment.</p>
        </div>
      </section>

      <!-- Resources & Tools Section -->
      <section class="bg-white border-t border-gray-200 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <!-- Header -->
          <div class="text-center mb-12">
            <h2 class="text-3xl text-gray-900 mb-2">Resources & Tools</h2>
            <p class="text-gray-600">
              Everything you need to make informed decisions
            </p>
          </div>

          <!-- Cards Grid -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <ResourcesToolsCard
              title="Education Hub"
              description="Learn about different peptides and research information"
              to="/education"
              cta-text="Explore"
              icon="book"
            />
            <ResourcesToolsCard
              title="Product Comparison"
              description="Compare products side-by-side to find the best option for your research"
              to="/products"
              cta-text="Compare"
              icon="graph"
            />
            <ResourcesToolsCard
              title="Price Comparison"
              description="Compare prices across vendors to find the best deals on peptides"
              to="/products"
              cta-text="Compare"
              icon="ribbon"
            />
          </div>
        </div>
      </section>

      <!-- Latest from Our Blog Section -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Header -->
        <div class="text-center mb-12">
          <h2 class="text-3xl text-gray-900 mb-2">Latest from Our Blog</h2>
          <p class="text-gray-600">
            Stay updated with the latest peptide news and research.
          </p>
        </div>

        <!-- Blog Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <LatestBlogCard
            v-for="blog in latestBlogs"
            :key="blog.id"
            :title="blog.title"
            :description="blog.description"
            :image="blog.image"
            :date="blog.date"
            :to="`/blog/${blog.slug}`"
          />
        </div>
      </section>  
      
      <section class="bg-gradient-to-b from-slate-50 to-white border-y border-gray-200 py-16">
        <!-- Cards Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <ResourcesToolsSimpleCard
              title="Top Peptides"
              description="Discover the most popular research peptides"
              icon="graph"
            />
            <ResourcesToolsSimpleCard
              title="Best Deals"
              description="Find the best prices on peptides"
              icon="ribbon"
            />
            <ResourcesToolsSimpleCard
              title="Educational Resources"
              description="Access comprehensive peptide information"
              icon="book"
            />
          </div>
        </div>
      </section>

     
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import MainButton from '@/components/MainButton.vue'
import TopRatedVendorCard from '@/components/TopRatedVendorCard.vue'
import EducationPostCard from '@/components/EducationPostCard.vue'
import BlogPostCard from '@/components/BlogPostCard.vue'
import PeptideEncyclopediaCard from '@/components/PeptideEncyclopediaCard.vue'
import LimitedTimeDiscountCard from '@/components/LimitedTimeDiscountCard.vue'
import ResourcesToolsCard from '@/components/ResourcesToolsCard.vue'
import ResourcesToolsSimpleCard from '@/components/ResourcesToolsSimpleCard.vue'
import LatestBlogCard from '@/components/LatestBlogCard.vue'

// Carousel images
const carouselImages = [
  'https://images.unsplash.com/photo-1606206605628-0a09580d44a1?w=1600&h=600&fit=crop',
  'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1600&h=600&fit=crop',
  'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=1600&h=600&fit=crop'
]

// Lazy loading for background images
const discoverBannerLoaded = ref(false)
const advanceBannerLoaded = ref(false)
const discoverBannerRef = ref(null)
const advanceBannerRef = ref(null)

// Hero Carousel - Fade type (no sliding)
const currentSlide = ref(0)
let interval = null

// Hero slides data from database
const props = defineProps({
  heroSlides: {
    type: Array,
    default: () => []
  },
  productGroups: {
    type: Array,
    default: () => []
  },
  topBrands: {
    type: Array,
    default: () => []
  },
  topBlogs: {
    type: Array,
    default: () => []
  },
  discountDeals: {
    type: Array,
    default: () => []
  },
  latestBlogs: {
    type: Array,
    default: () => []
  }
})

// Process slides
const processSlides = (slides) => {
  if (!slides.length) {
    return carouselImages.map(image => ({
      title: 'Peptide Sciences',
      subtitle: '99%+ purity guaranteed. Third-party tested with COAs available for every batch.',
      heading: 'PREMIUM RESEARCH PEPTIDES',
      ctaText: 'Shop Now',
      ctaUrl: '#',
      promoCode: 'PMAP',
      image
    }))
  }

  return slides.map((slide, index) => ({
    ...slide,
    image: slide.image || carouselImages[index % carouselImages.length]
  }))
}
const heroSlides = ref(processSlides(props.heroSlides))

// Preload all carousel images
const onImageLoad = (imageUrl) => {
  // Image loaded successfully
}

// Navigate to specific slide
const goToSlide = (index) => {
  currentSlide.value = index
  // Reset autoplay
  stopAutoplay()
  startAutoplay()
}

// Next slide
const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % heroSlides.value.length
}

const stopAutoplay = () => {
  if (interval) {
    clearInterval(interval)
    interval = null
  }
}

const startAutoplay = () => {
  stopAutoplay()
  interval = setInterval(nextSlide, 5000)
}

// Intersection Observer for lazy loading background images
const setupLazyBackgroundImages = () => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const bgImage = entry.target.getAttribute('data-bg-image')
        if (bgImage) {
          // Preload the image
          const img = new Image()
          img.onload = () => {
            if (entry.target === discoverBannerRef.value) {
              discoverBannerLoaded.value = true
            } else if (entry.target === advanceBannerRef.value) {
              advanceBannerLoaded.value = true
            }
          }
          img.src = bgImage
        }
        observer.unobserve(entry.target)
      }
    })
  }, {
    rootMargin: '50px' // Start loading 50px before entering viewport
  })

  // Observe banner images
  if (discoverBannerRef.value) {
    observer.observe(discoverBannerRef.value)
  }
  if (advanceBannerRef.value) {
    observer.observe(advanceBannerRef.value)
  }
}


const logoErrors = ref(new Set())

const handleLogoError = (event, vendorId) => {
  const img = event.target
  if (vendorId) {
    logoErrors.value.add(vendorId)
  }
  img.style.display = 'none'
}


onMounted(() => {
  // Preload all carousel images
  heroSlides.value.forEach(slide => {
    if (slide.image) {
      const img = new Image()
      img.src = slide.image
    }
  })
  
  startAutoplay()
  setupLazyBackgroundImages()
})

onUnmounted(() => {
  stopAutoplay()
})

const topVendors = computed(() => props.topBrands || [])

// Education categories (from server) - show first 8, link to full page
const displayedProducts = computed(() => {
  return props.productGroups.slice(0, 8)
})

// Peptide Encyclopedia - show first 8 categories with category tags
const displayedEncyclopediaPeptides = computed(() => {
  return props.productGroups.slice(0, 8).map(category => {
    // Determine category tag based on name/description
    const categoryTag = getCategoryTag(category.name, category.description)
    
    return {
      ...category,
      categoryTag
    }
  })
})

// Helper function to determine category tag
const getCategoryTag = (name, description) => {
  const nameLower = (name || '').toLowerCase()
  const descLower = (description || '').toLowerCase()
  const combined = `${nameLower} ${descLower}`
  
  // Healing & Recovery
  if (combined.includes('bpc') || combined.includes('tb-500') || combined.includes('healing') || combined.includes('recovery')) {
    return 'Healing & Recovery'
  }
  
  // Growth & Recovery
  if (combined.includes('cjc') || combined.includes('ipamorelin') || combined.includes('ghrp') || combined.includes('growth')) {
    return 'Growth & Recovery'
  }
  
  // Weight Management
  if (combined.includes('semaglutide') || combined.includes('tirzepatide') || combined.includes('weight') || combined.includes('glp')) {
    return 'Weight Management'
  }
  
  // Cosmetic
  if (combined.includes('melanotan') || combined.includes('tanning') || combined.includes('cosmetic')) {
    return 'Cosmetic'
  }
  
  // Default fallback
  return 'Research Peptide'
}

const researchInsights = computed(() => props.topBlogs || [])

// Limited Time Discounts
const discountDeals = computed(() => props.discountDeals || [])

// Latest from Our Blog
const latestBlogs = computed(() => props.latestBlogs || [])
</script>

