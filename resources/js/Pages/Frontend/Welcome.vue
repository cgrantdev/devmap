<template>
  <Head>
    <title>{{ seo?.title || 'PeptideSync - Your Trusted Source for Research Peptides' }}</title>
    <meta name="description" :content="seo?.description || 'Discover top-rated peptide vendors, compare products, and access comprehensive research information. Find the best deals on premium research peptides with verified discount codes.'" />
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" :content="seo?.url || $page.url" />
    <meta property="og:title" :content="seo?.title || 'PeptideSync - Your Trusted Source for Research Peptides'" />
    <meta property="og:description" :content="seo?.description || 'Discover top-rated peptide vendors, compare products, and access comprehensive research information. Find the best deals on premium research peptides with verified discount codes.'" />
    <meta property="og:image" :content="seo?.image || ''" />
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" :content="seo?.url || $page.url" />
    <meta name="twitter:title" :content="seo?.title || 'PeptideSync - Your Trusted Source for Research Peptides'" />
    <meta name="twitter:description" :content="seo?.description || 'Discover top-rated peptide vendors, compare products, and access comprehensive research information. Find the best deals on premium research peptides with verified discount codes.'" />
    <meta name="twitter:image" :content="seo?.image || ''" />
    
    <!-- Canonical URL -->
    <link rel="canonical" :href="seo?.url || $page.url" />
  </Head>
  <FrontLayout>
    <!-- Hero Section Carousel -->
    <section v-if="heroSlides.length > 0" class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="rounded-xl overflow-hidden shadow-lg">
          <div
            class="relative h-64 sm:h-80 md:h-96 bg-slate-800"
            @mouseenter="stopAutoplay"
            @mouseleave="startAutoplay"
          >
            <div
              v-for="(slide, index) in heroSlides"
              :key="index"
              class="absolute inset-0 transition-opacity duration-700 opacity-0"
              :class="currentSlide === index ? 'opacity-100' : 'opacity-0'"
            >
              <!-- Background Image -->
              <img
                v-if="slide.image"
                :src="slide.image"
                :alt="slide.title || 'Hero image'"
                class="absolute inset-0 bg-cover bg-center"
                @load="onImageLoad"
              >
              <div class="absolute inset-0 bg-gradient-to-r from-black/70 to-black/40"></div>

              <!-- Content -->
              <div class="relative h-full flex items-center px-4 sm:px-8 md:px-16">
                <div class="max-w-2xl text-white">
                  <div class="text-xs sm:text-sm uppercase tracking-wide mb-2 text-slate-300">
                    {{ slide.heading || 'PREMIUM RESEARCH PEPTIDES' }}
                  </div>
                  <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl mb-3 sm:mb-4">
                    {{ slide.title || 'Peptide Sciences' }}
                  </h2>
                  <p class="text-sm sm:text-base md:text-lg lg:text-xl mb-4 sm:mb-6 text-gray-200">
                    {{ slide.subtitle || '99%+ purity guaranteed. Third-party tested with COAs available for every batch.' }}
                  </p>

                  <div class="inline-flex items-center gap-2 sm:gap-3 bg-white/10 backdrop-blur-sm border border-white/30 px-3 sm:px-5 py-2 sm:py-3 rounded-lg mb-4 sm:mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag w-4 h-4 sm:w-5 sm:h-5 text-slate-300 flex-shrink-0">
                      <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path>
                      <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle>
                    </svg>
                    <span class="text-sm sm:text-base md:text-lg">Use code:</span>
                    <span class="text-base sm:text-lg md:text-xl text-slate-200 font-semibold">{{ slide.promoCode || 'PMAP' }}</span>
                  </div>

                  <div>
                    <Link
                      :href="slide.ctaUrl || '#'"
                      class="inline-block bg-slate-700 hover:bg-slate-800 text-white px-6 sm:px-8 py-2 sm:py-3 text-sm sm:text-base rounded-lg transition-colors"
                    >
                      Shop Now
                    </Link>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dots -->
            <div class="absolute bottom-3 sm:bottom-6 left-1/2 -translate-x-1/2 flex gap-2 z-10">
              <button
                v-for="(slide, index) in heroSlides"
                :key="index"
                @click="goToSlide(index)"
                :class="[
                  'h-1.5 rounded-full transition-all bg-white/50 w-1.5',
                  currentSlide === index ? 'bg-white w-8' : 'bg-white/50 w-1.5'
                ]"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    
    <!-- Top Rated Vendors Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 md:py-16">
      <div class="mb-6 sm:mb-8">
        <h2 class="text-2xl sm:text-3xl text-gray-900 mb-1">Top Rated Vendors</h2>
        <p class="text-sm sm:text-base text-gray-600">Browse the most trusted peptide suppliers.</p>
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
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
      <div class="bg-gradient-to-r from-slate-700 to-slate-600 rounded-lg p-6 sm:p-8 text-center text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-0 left-0 w-32 h-32 sm:w-64 sm:h-64 bg-white rounded-full -translate-x-16 sm:-translate-x-32 -translate-y-16 sm:-translate-y-32"></div>
          <div class="absolute bottom-0 right-0 w-32 h-32 sm:w-64 sm:h-64 bg-white rounded-full translate-x-16 sm:translate-x-32 translate-y-16 sm:translate-y-32"></div>
        </div>
        <div class="relative z-10">
          <div class="text-xs sm:text-sm uppercase tracking-wider mb-2 opacity-90">Sponsored</div>
          <h3 class="text-xl sm:text-2xl mb-2">Advertise Your Brand Here</h3>
          <p class="text-sm sm:text-base text-slate-100 mb-4">Reach thousands of peptide researchers and customers</p>
          <button class="bg-white text-slate-700 px-5 sm:px-6 py-2 text-sm sm:text-base rounded-lg hover:bg-slate-50 transition-colors">Learn More</button>
        </div>
      </div>
    </section>

    <!-- Peptide Encyclopedia Section -->
    <section class="bg-gradient-to-b from-slate-50 to-slate-100 border-y border-slate-200 py-8 sm:py-12 md:py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8 sm:mb-12">
          <div class="inline-flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 bg-slate-700 rounded-full mb-3 sm:mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-6 h-6 sm:w-8 sm:h-8 text-white">
              <path d="M12 7v14"></path>
              <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
            </svg>
          </div>
          <h2 class="text-2xl sm:text-3xl md:text-4xl text-gray-900 mb-2 sm:mb-3">Peptide Encyclopedia</h2>
          <p class="text-sm sm:text-base md:text-lg text-gray-600 max-w-2xl mx-auto px-4">
            Comprehensive information on popular research peptides with detailed profiles, research data, and vendor comparisons.
          </p>
        </div>

        <!-- Peptide Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
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
        <div class="text-center mt-6 sm:mt-10">
          <Link
            href="/encyclopedia"
            class="bg-slate-700 hover:bg-slate-800 text-white px-6 sm:px-10 py-3 sm:py-4 text-sm sm:text-base rounded-lg transition-colors inline-flex items-center gap-2 shadow-lg"
          >
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-4 h-4 sm:w-5 sm:h-5" aria-hidden="true">
            <path d="M12 7v14"></path>
            <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
          </svg>
          <span class="hidden sm:inline">View Full Encyclopedia</span>
          <span class="sm:hidden">View All</span>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-4 h-4 sm:w-5 sm:h-5" aria-hidden="true">
            <path d="m9 18 6-6-6-6"></path>
          </svg>
          </Link>
        </div>
      </div>
    </section>

    <!-- Premium Vendor Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
      <div class="bg-gradient-to-r from-slate-700 to-slate-600 rounded-lg p-6 sm:p-8 text-center text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-0 right-0 w-32 h-32 sm:w-64 sm:h-64 bg-white rounded-full translate-x-16 sm:translate-x-32 -translate-y-16 sm:-translate-y-32"></div>
          <div class="absolute bottom-0 left-0 w-32 h-32 sm:w-64 sm:h-64 bg-white rounded-full -translate-x-16 sm:-translate-x-32 translate-y-16 sm:translate-y-32"></div>
        </div>
        <div class="relative z-10">
          <div class="text-xs sm:text-sm uppercase tracking-wider mb-2 opacity-90">FEATURED SPOT</div>
          <h3 class="text-xl sm:text-2xl mb-2">Premium Vendor Placement Available</h3>
          <p class="text-sm sm:text-base text-slate-100 mb-4">Showcase your products to our growing community</p>
          <button class="bg-white text-slate-700 px-5 sm:px-6 py-2 text-sm sm:text-base rounded-lg hover:bg-slate-50 transition-colors">Get Started</button>
        </div>
      </div>
    </section>

    <!-- Limited Time Discounts Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 md:py-16">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0 mb-6 sm:mb-8">
        <div>
          <h2 class="text-2xl sm:text-3xl text-gray-900 mb-1">Limited Time Discounts</h2>
          <p class="text-sm sm:text-base text-gray-600">Save up to 25% with verified discount codes</p>
        </div>
        <Link
          href="/deals"
          class="text-slate-700 hover:text-slate-900 flex items-center gap-1 text-sm sm:text-base"
        >
          View All Deals
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-4 h-4" aria-hidden="true">
            <path d="m9 18 6-6-6-6"></path>
          </svg>
        </Link>
      </div>

      <!-- Discount Cards Grid -->
      <div v-if="discountDeals.length > 0" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
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
      <div v-else class="text-center py-8 sm:py-12">
        <p class="text-sm sm:text-base text-gray-500">No discount deals available at the moment.</p>
      </div>
    </section>

    <!-- Resources & Tools Section -->
    <section class="bg-white border-t border-gray-200 py-8 sm:py-12 md:py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8 sm:mb-12">
          <h2 class="text-2xl sm:text-3xl text-gray-900 mb-2">Resources & Tools</h2>
          <p class="text-sm sm:text-base text-gray-600">
            Everything you need to make informed decisions
          </p>
        </div>

        <!-- Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
          <ResourcesToolsCard
            title="Encyclopedia"
            description="Learn about different peptides and research information"
            href="/encyclopedia"
            cta-text="Explore"
            icon="book"
          />
          <ResourcesToolsCard
            title="Product Comparison"
            description="Compare products side-by-side to find the best option for your research"
            href="/compare"
            cta-text="Compare"
            icon="graph"
          />
          <ResourcesToolsCard
            title="Price Comparison"
            description="Compare prices across vendors to find the best deals on peptides"
            href="/compare"
            cta-text="Compare"
            icon="ribbon"
          />
        </div>
      </div>
    </section>

    <!-- Latest from Our Blog Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 md:py-16">
      <!-- Header -->
      <div class="text-center mb-8 sm:mb-12">
        <h2 class="text-2xl sm:text-3xl text-gray-900 mb-2">Latest from Our Blog</h2>
        <p class="text-sm sm:text-base text-gray-600">
          Stay updated with the latest peptide news and research.
        </p>
      </div>

      <!-- Blog Cards Grid -->
      <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
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
    
    <section class="bg-gradient-to-b from-slate-50 to-white border-y border-gray-200 py-8 sm:py-12 md:py-16">
      <!-- Cards Grid -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
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
import { Link, Head } from '@inertiajs/vue3'
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
  },
  seo: {
    type: Object,
    default: () => ({
      title: 'PeptideSync - Your Trusted Source for Research Peptides',
      description: 'Discover top-rated peptide vendors, compare products, and access comprehensive research information. Find the best deals on premium research peptides with verified discount codes.',
      image: null,
      url: null,
    })
  }
})

// Process slides - ensure we have valid slides with images
const processSlides = (slides) => {
  if (!slides || !slides.length) {
    return []
  }

  // Headings for each slide in order
  const headings = [
    'PREMIUM RESEARCH PEPTIDES',
    'Lab-Tested Excellence',
    'Trusted Since 2018'
  ]

  return slides.map((slide, index) => ({
    title: slide.title || 'Peptide Sciences',
    subtitle: slide.subtitle || '99%+ purity guaranteed. Third-party tested with COAs available for every batch.',
    heading: slide.heading || headings[index] || headings[0],
    ctaText: slide.ctaText || 'Shop Now',
    ctaUrl: slide.ctaUrl || '#',
    promoCode: slide.promoCode || 'PMAP',
    image: slide.image || null
  }))
}

const heroSlides = computed(() => processSlides(props.heroSlides))

// Preload all carousel images
const onImageLoad = () => {
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
  if (heroSlides.value.length > 0) {
    currentSlide.value = (currentSlide.value + 1) % heroSlides.value.length
  }
}

const stopAutoplay = () => {
  if (interval) {
    clearInterval(interval)
    interval = null
  }
}

const startAutoplay = () => {
  stopAutoplay()
  if (heroSlides.value.length > 1) {
    interval = setInterval(nextSlide, 5000)
  }
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
})

onUnmounted(() => {
  stopAutoplay()
})

const topVendors = computed(() => (props.topBrands || []).slice(0, 10))

// Peptide Encyclopedia - show first 8 categories with category tags
const displayedEncyclopediaPeptides = computed(() => {
  return props.productGroups.slice(0, 8).map(category => {
    // Determine category tag based on name/description
    const categoryTag = getCategoryTag(category.name, category.description)
    console.log(category)
    
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

// Limited Time Discounts
const discountDeals = computed(() => props.discountDeals || [])

// Latest from Our Blog
const latestBlogs = computed(() => props.latestBlogs || [])
</script>

