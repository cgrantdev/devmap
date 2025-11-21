<template>
  <FrontLayout>
      <!-- Hero Section Carousel -->
      <div class="w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 mb-10">
        <section class="relative w-full h-[663px] overflow-visible">
          <div 
            class="embla hero-carousel"
            @mouseenter="stopAutoplay"
            @mouseleave="startAutoplay"
          >
            <div class="embla__viewport" ref="emblaRef">
              <div class="embla__container">
                <div
                  v-for="(slide, index) in heroSlides"
                  :key="index"
                  class="embla__slide"
                  :class="{ 'slide-active': currentSlide === index }"
                >
                  <div class="carousel-slide">
                    <!-- Background Image -->
                    <div 
                      class="absolute inset-0 bg-cover bg-center rounded-[24px] overflow-hidden"
                      :style="{ backgroundImage: slide.image ? `url(${slide.image})` : 'none' }"
                    >
                      <div class="absolute inset-0 hero-overlay1"></div>
                      <div class="absolute inset-0 hero-overlay2"></div>
                    </div>
                    
                    <!-- Content -->
                      <div class="hero-text-container">
                        <div class="hero-text-content">
                          <h1 class="hero-heading">{{ slide.title }}</h1>
                          <p class="hero-subtitle">{{ slide.subtitle }}</p>
                        </div>
                        <button 
                          @click.stop="handleCtaClick(slide.ctaUrl)"
                          class="hero-cta-button"
                        >
                          {{ slide.ctaText }}
                        </button>
                      </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Custom Carousel Indicators -->
            <div class="carousel-indicators">
              <button
                v-for="(slide, index) in heroSlides"
                :key="index"
                @click="goToSlide(index)"
                :class="[
                  'carousel-dot',
                  currentSlide === index ? 'carousel-dot-active' : 'carousel-dot-inactive'
                ]"
                :aria-label="`Go to slide ${index + 1}`"
              ></button>
            </div>
          </div>
        </section>
      </div>

      <!-- Top Vendors Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="top-vendors-title">Top Vendors</h2>
          <div class="grid grid-cols-2 md:grid-cols-5 gap-x-[20px] gap-y-[80px] mb-20">
            <div
              v-for="vendor in topVendors"
              :key="vendor.id"
              class="vendor-card"
            >
              <!-- Logo Area -->
              <div class="vendor-logo-area">
                <img 
                  :src="`/images/vendors/${vendor.logo}`" 
                  :alt="vendor.name + ' logo'"
                  class="vendor-logo-image"
                  @error="handleLogoError($event, vendor.id)"
                />
                <div v-if="logoErrors.has(vendor.id)" class="vendor-logo-placeholder">
                  <span class="vendor-logo-initials">{{ vendor.initials }}</span>
                </div>
              </div>
              
              <!-- Location -->
              <div class="vendor-location">
                <svg class="location-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M8 8.5C9.10457 8.5 10 7.60457 10 6.5C10 5.39543 9.10457 4.5 8 4.5C6.89543 4.5 6 5.39543 6 6.5C6 7.60457 6.89543 8.5 8 8.5Z" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M8 13.5C8 13.5 13.5 10.5 13.5 6.5C13.5 4.01472 11.4853 2 9 2C6.51472 2 5 4.01472 5 6.5C5 10.5 8 13.5 8 13.5Z" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="location-text">{{ vendor.location }}</span>
              </div>
              
              <!-- Vendor Name -->
              <h3 class="vendor-name">{{ vendor.name }}</h3>
              
              <!-- Rating -->
              <div class="vendor-rating">
                <div class="rating-stars">
                  <svg v-for="i in 5" :key="i" class="star-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 0L9.79611 5.52786H15.6085L10.9062 8.94427L12.7023 14.4721L8 11.0557L3.29772 14.4721L5.09383 8.94427L0.391548 5.52786H6.20389L8 0Z" fill="#FBBF24"/>
                  </svg>
                </div>
                <div class="rating-text">
                  <span class="rating-value">{{ vendor.rating }}</span>
                  <span class="reviews-count">({{ vendor.reviews }})</span>
                </div>
              </div>
            </div>
          </div>
          <div class="text-center">
            <Link
              href="/vendors"
              class="inline-block px-8 py-3 bg-blue-700 text-white font-semibold rounded-lg hover:bg-blue-800 transition-colors"
            >
              View All Vendors
            </Link>
          </div>
        </div>
      </section>

      <!-- Discover & Research Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <div class="discover-research-banner">
            <div 
              class="discover-research-background"
              :style="{ backgroundImage: `url(/images/banners/1.jpg)` }"
            >
              <div class="discover-research-overlay"></div>
            </div>
            <div class="discover-research-content">
              <h2 class="discover-research-heading">Discover & Research with Confidence</h2>
              <p class="discover-research-subtitle">Behind the scenes, we're enabling custom combinations of RUO peptides for advanced lab applications.</p>
              <button class="discover-research-button">
                Read Details
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Learn About Peptides Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">Learn About Peptides</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
              v-for="article in peptideArticles"
              :key="article.id"
              class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
            >
              <div class="h-48 bg-amber-100 flex items-center justify-center">
                <!-- Placeholder for bottle image -->
                <div class="text-center">
                  <div class="w-16 h-20 mx-auto bg-amber-300 rounded-t-lg rounded-b-2xl border-2 border-amber-400 mb-2"></div>
                  <p class="text-xs text-amber-800 font-semibold">{{ article.title.split(' ')[0] }}</p>
                </div>
              </div>
              <div class="p-6">
                <h3 class="font-bold text-gray-800 mb-3">{{ article.title }}</h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ article.description }}</p>
                <button class="w-full bg-white border border-blue-700 text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
                  Read Details
                </button>
              </div>
            </div>
          </div>
          <div class="text-center">
            <Link
              href="/education"
              class="inline-block px-8 py-3 bg-blue-700 text-white font-semibold rounded-lg hover:bg-blue-800 transition-colors"
            >
              View All Articles
            </Link>
          </div>
        </div>
      </section>

      <!-- Advance Your Research Section -->
      <section class="relative w-full h-[500px] overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-l from-gray-900/80 to-gray-800/60">
          <!-- Placeholder for background image -->
          <div class="absolute inset-0 bg-gray-900"></div>
        </div>
        <div class="relative max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-end">
          <div class="text-white max-w-2xl text-right">
            <h2 class="text-5xl md:text-6xl font-bold mb-4">Advance Your Research with Trusted Peptides</h2>
            <p class="text-xl mb-8 text-gray-200">Behind the scenes, we're enabling custom combinations of RUC peptides for advanced lab applications.</p>
            <button class="bg-white text-blue-700 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
              Start Research
            </button>
          </div>
        </div>
      </section>

      <!-- Research Insights Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">Research Insights</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
              v-for="insight in researchInsights"
              :key="insight.id"
              class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
            >
              <div class="h-48 bg-gradient-to-br from-blue-100 to-purple-100 flex items-center justify-center relative">
                <!-- Placeholder for research image -->
                <div class="text-center text-gray-600">
                  <svg class="w-16 h-16 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="absolute bottom-2 left-2 flex gap-3 text-xs text-gray-600">
                  <span>19 Min Read</span>
                  <span>22 Aug 2005</span>
                </div>
              </div>
              <div class="p-6">
                <h3 class="font-bold text-gray-800 mb-3">{{ insight.title }}</h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ insight.description }}</p>
                <button class="w-full bg-white border border-blue-700 text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
                  Read Details
                </button>
              </div>
            </div>
          </div>
          <div class="text-center">
            <button class="inline-block px-8 py-3 bg-blue-700 text-white font-semibold rounded-lg hover:bg-blue-800 transition-colors">
              More Details
            </button>
          </div>
        </div>
      </section>
  </FrontLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import useEmblaCarousel from 'embla-carousel-vue'
import Autoplay from 'embla-carousel-autoplay'

// Hero Carousel
const currentSlide = ref(0)
const autoplayPluginInstance = Autoplay({
  delay: 5000,
  stopOnInteraction: true,
  stopOnMouseEnter: true,
})

const [emblaRef, emblaApi] = useEmblaCarousel(
  {
    align: 'center',
    loop: true,
    skipSnaps: false,
    dragFree: false,
    slidesToScroll: 1,
    containScroll: 'trimSnaps',
  },
  [autoplayPluginInstance]
)

// Hero slides data
// Images should be placed in: public/images/hero/
// Access them via: /images/hero/image-name.jpg
const heroSlides = ref([
  {
    title: 'Hello Hero',
    subtitle: 'Want your brand to stand out? Advertise with us right here!',
    ctaText: 'Contact Us to Advertise',
    ctaUrl: '/contact',
    image: '/images/hero/hero1.jpg' // Place your image in public/images/hero/hero-1.jpg
  },
  {
    title: 'Discover Research Peptides',
    subtitle: 'Explore our comprehensive collection of high-quality research peptides.',
    ctaText: 'Browse Products',
    ctaUrl: '/products',
    image: '/images/hero/hero2.jpg' // Place your image in public/images/hero/hero-2.jpg
  },
  {
    title: 'Trusted Vendors',
    subtitle: 'Connect with verified vendors in the peptide research community.',
    ctaText: 'View Vendors',
    ctaUrl: '/vendors',
    image: '/images/hero/hero3.jpg' // Place your image in public/images/hero/hero-3.jpg
  },
  {
    title: 'Research Education',
    subtitle: 'Learn about peptides, research protocols, and best practices.',
    ctaText: 'Learn More',
    ctaUrl: '/education',
    image: '/images/hero/hero4.jpg' // Place your image in public/images/hero/hero-4.jpg
  }
])

const goToSlide = (index) => {
  if (emblaApi.value) {
    emblaApi.value.scrollTo(index)
  }
}

const onSelect = () => {
  if (emblaApi.value) {
    currentSlide.value = emblaApi.value.selectedScrollSnap()
  }
}

const stopAutoplay = () => {
  if (autoplayPluginInstance) {
    autoplayPluginInstance.stop()
  }
}

const startAutoplay = () => {
  if (autoplayPluginInstance) {
    autoplayPluginInstance.play()
  }
}

const handleCtaClick = (url) => {
  if (url) {
    router.visit(url)
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
  if (emblaApi.value) {
    emblaApi.value.on('select', onSelect)
    emblaApi.value.on('reInit', onSelect)
    onSelect() // Set initial slide
  }
})

onUnmounted(() => {
  if (emblaApi.value) {
    emblaApi.value.off('select', onSelect)
    emblaApi.value.off('reInit', onSelect)
  }
})

const topVendors = ref([
  { id: 1, name: 'Behemoth Labz', location: 'Beach Valley, California', initials: 'BL', rating: '5.00', reviews: 345, badge: '🍃' , logo: 'behemoth-labz.png'},
  { id: 2, name: 'Peptide Sciences', location: 'San Diego, California', initials: 'PS', rating: '5.00', reviews: 289, badge: '🔥' , logo: 'peptide-sciences.png'},
  { id: 3, name: 'Bella Corner', location: 'Los Angeles, California', initials: 'BC', rating: '5.00', reviews: 412 , logo: 'bella-corner.png'},
  { id: 4, name: 'Chemyo', location: 'Austin, Texas', initials: 'CH', rating: '5.00', reviews: 356 , logo: 'chemyo.png'},
  { id: 5, name: 'Organic Food', location: 'Seattle, Washington', initials: 'OF', rating: '5.00', reviews: 298 , logo: 'organic-food.png'},
  { id: 6, name: 'Core Peptides', location: 'Miami, Florida', initials: 'CP', rating: '5.00', reviews: 324 , logo: 'core-peptides.png'},
  { id: 7, name: 'AA Health', location: 'Chicago, Illinois', initials: 'AA', rating: '5.00', reviews: 267 , logo: 'aa-health.png'},
  { id: 8, name: 'Wholeness in Health', location: 'Denver, Colorado', initials: 'WH', rating: '5.00', reviews: 389 , logo: 'wholeness-in-health.png'},
  { id: 9, name: 'Health Net', location: 'Boston, Massachusetts', initials: 'HN', rating: '5.00', reviews: 445 , logo: 'health-net.png'},
  { id: 10, name: 'Dental Plus', location: 'Portland, Oregon', initials: 'DP', rating: '5.00', reviews: 312 , logo: 'dental-plus.png'},
])

const peptideArticles = ref([
  {
    id: 1,
    title: 'What is BPC-157?',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 2,
    title: 'Safe Handling',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 3,
    title: 'Stacking with Others',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 4,
    title: 'Dosage Guidelines',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 5,
    title: 'Storage Methods',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 6,
    title: 'Research Applications',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 7,
    title: 'Safety Protocols',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 8,
    title: 'Quality Standards',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
])

const researchInsights = ref([
  {
    id: 1,
    title: 'Safe Handling',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 2,
    title: 'Research Guidelines',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 3,
    title: 'Benefits of BPC-157',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 4,
    title: 'BPC-157 in Sport Medicine',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 5,
    title: 'Safety and Usage',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 6,
    title: 'Laboratory Protocols',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 7,
    title: 'Advanced Applications',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 8,
    title: 'Research Methodologies',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
])
</script>

