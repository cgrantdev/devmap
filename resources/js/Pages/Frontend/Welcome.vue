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
              class="main-btn inline-block text-white"
            >
              View All Vendors
            </Link>
          </div>
        </div>
      </section>

      <!-- Discover & Research Section -->
      <section class="py-8 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <div class="discover-research-banner">
            <div 
              class="discover-research-background"
              :style="{ backgroundImage: `url(/images/banners/1.jpg)` }"
            >
              <div class="discover-research-overlay"></div>
            </div>
            <div class="discover-research-content gap-8 lg:left-[99px]">
              <div class="discover-research-text-container gap-6">
                  <h2 class="discover-research-heading">Discover & Research with Confidence</h2>
                  <p class="discover-research-subtitle">Behind the scenes, we're enabling custom combinations of RUO peptides for advanced lab applications.</p>
                </div>
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
          <h2 class="learn-about-peptides-title">Learn About Peptides</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 gap-x-[20px] gap-y-[80px]">
            <div
              v-for="article in peptideArticles"
              :key="article.id"
              class="peptide-article-card"
            >
              <div class="peptide-article-image">
                <img 
                  :src="`/images/peptides/${article.image}`" 
                  :alt="article.title"
                  class="peptide-bottle-image"
                />
              </div>
              <div class="peptide-article-content">
                <h3 class="peptide-article-title">{{ article.title }}</h3>
                <p class="peptide-article-description">{{ article.description }}</p>
                <button class="peptide-article-button">
                  Read Details
                </button>
              </div>
            </div>
          </div>
          <div class="text-center">
            <Link
              href="/education"
              class="main-btn inline-block text-white"
            >
              View All Vendors
            </Link>
          </div>
        </div>
      </section>

      <!-- Advance Your Research Section -->
      <section class="py-8 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <div class="advance-research-banner">
            <div 
              class="advance-research-background"
              :style="{ backgroundImage: `url(/images/banners/2.jpg)` }"
            >
              <div class="advance-research-overlay"></div>
            </div>
            <div class="advance-research-content">
              <h2 class="advance-research-heading">Advance Your<br>Research with Trusted<br>Peptides</h2>
              <p class="advance-research-subtitle">Behind the scenes, we're enabling custom combinations of RUO peptides for advanced lab applications.</p>
              <button class="advance-research-button">
                Start Research
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Research Insights Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="research-insights-title">Research Insights</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 research-insights-grid">
            <div
              v-for="insight in researchInsights"
              :key="insight.id"
              class="research-insight-card"
            >
              <div class="research-insight-image">
                <img 
                  :src="`/images/blogs/${insight.image}`" 
                  :alt="insight.title"
                  class="research-insight-img"
                />
              </div>
              <div class="research-insight-meta">
                <span class="research-insight-read-time">
                  <svg class="research-insight-clock-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 14C11.3137 14 14 11.3137 14 8C14 4.68629 11.3137 2 8 2C4.68629 2 2 4.68629 2 8C2 11.3137 4.68629 14 8 14Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 5V8L10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  {{ insight.readTime }}
                </span>
                <span class="research-insight-date">{{ insight.date }}</span>
              </div>
              <div class="research-insight-content">
                <h3 class="research-insight-title">{{ insight.title }}</h3>
                <p class="research-insight-description">{{ insight.description }}</p>
                <button class="research-insight-button">
                  Read Details
                </button>
              </div>
            </div>
          </div>
          <div class="text-center">
            <button class="footer-more-details-btn">
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
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: 'bpc-157.png'
  },
  {
    id: 2,
    title: 'Safe Handling',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: 'bpc-157.png'
  },
  {
    id: 3,
    title: 'Stacking with Others',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: 'bpc-157.png'
  },
  {
    id: 4,
    title: 'Dosage Guidelines',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: 'bpc-157.png'
  },
  {
    id: 5,
    title: 'Storage Methods',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: 'bpc-157.png'
  },
  {
    id: 6,
    title: 'Research Applications',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: 'bpc-157.png'
  },
  {
    id: 7,
    title: 'Safety Protocols',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: 'bpc-157.png'
  },
  {
    id: 8,
    title: 'Quality Standards',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: 'bpc-157.png'
  },
])

const researchInsights = ref([
  {
    id: 1,
    title: 'Safe Handling',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: '1.jpg',
    readTime: '19 Min Read',
    date: '22 Aug 2025'
  },
  {
    id: 2,
    title: 'Research Guidelines',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: '1.jpg',
    readTime: '19 Min Read',
    date: '22 Aug 2025'
  },
  {
    id: 3,
    title: 'Benefits of BPC-157',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: '1.jpg',
    readTime: '19 Min Read',
    date: '22 Aug 2025'
  },
  {
    id: 4,
    title: 'BPC-157 in Sport Medicine',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: '1.jpg',
    readTime: '19 Min Read',
    date: '22 Aug 2025'
  },
  {
    id: 5,
    title: 'Safety and Usage',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: '1.jpg',
    readTime: '19 Min Read',
    date: '22 Aug 2025'
  },
  {
    id: 6,
    title: 'Laboratory Protocols',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: '1.jpg',
    readTime: '19 Min Read',
    date: '22 Aug 2025'
  },
  {
    id: 7,
    title: 'Advanced Applications',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: '1.jpg',
    readTime: '19 Min Read',
    date: '22 Aug 2025'
  },
  {
    id: 8,
    title: 'Research Methodologies',
    description: 'BPC-157 is a lab-made peptide studied for its healing propertie. It may support tissue repair, reduce inflammation...',
    image: '1.jpg',
    readTime: '19 Min Read',
    date: '22 Aug 2025'
  },
])
</script>

