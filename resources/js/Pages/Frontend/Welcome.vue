<template>
  <FrontLayout>
      <!-- Hero Section Carousel -->
      <div class="w-full mb-10">
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
                      :ref="el => { if (el) heroImageRefs[index] = el }"
                      class="absolute inset-0 bg-cover bg-center rounded-[24px] overflow-hidden"
                      :data-bg-image="slide.image || ''"
                      :style="{ backgroundImage: slide.image && heroImagesLoaded.has(slide.image) ? `url(${slide.image})` : 'none' }"
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
          <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 w-full max-w-[360px] mx-auto">Top Vendors</h2>
          <div class="grid grid-cols-2 md:grid-cols-5 gap-x-[20px] gap-y-[80px] mb-20">
            <div
              v-for="vendor in topVendors"
              :key="vendor.id"
              class="bg-white flex flex-col gap-[5px] transition-shadow duration-300 items-center hover:shadow-md"
            >
              <!-- Logo Area -->
              <div class="w-full aspect-square bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden mb-1">
                <img 
                  :src="`/images/vendors/${vendor.logo}`" 
                  :alt="vendor.name + ' logo'"
                  class="w-full h-full object-contain p-3"
                  loading="lazy"
                  @error="handleLogoError($event, vendor.id)"
                />
                <div v-if="logoErrors.has(vendor.id)" class="w-full h-full flex items-center justify-center">
                  <span class="font-roboto font-semibold text-2xl text-gray-500">{{ vendor.initials }}</span>
                </div>
              </div>
              
              <!-- Location -->
              <div class="flex items-center gap-1 mb-1">
                <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M8 8.5C9.10457 8.5 10 7.60457 10 6.5C10 5.39543 9.10457 4.5 8 4.5C6.89543 4.5 6 5.39543 6 6.5C6 7.60457 6.89543 8.5 8 8.5Z" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M8 13.5C8 13.5 13.5 10.5 13.5 6.5C13.5 4.01472 11.4853 2 9 2C6.51472 2 5 4.01472 5 6.5C5 10.5 8 13.5 8 13.5Z" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="font-roboto font-normal text-sm leading-relaxed text-gray-500">{{ vendor.location }}</span>
              </div>
              
              <!-- Vendor Name -->
              <h3 class="font-roboto font-normal text-2xl leading-none tracking-normal text-gray-800 m-0 mb-1">{{ vendor.name }}</h3>
              
              <!-- Rating -->
              <div class="flex flex-row gap-2">
                <div class="flex gap-0.5">
                  <svg v-for="i in 5" :key="i" class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 0L9.79611 5.52786H15.6085L10.9062 8.94427L12.7023 14.4721L8 11.0557L3.29772 14.4721L5.09383 8.94427L0.391548 5.52786H6.20389L8 0Z" fill="#FBBF24"/>
                  </svg>
                </div>
                <div class="flex items-baseline gap-1">
                  <span class="font-roboto font-normal text-xs leading-relaxed text-gray-800">{{ vendor.rating }}</span>
                  <span class="font-roboto font-normal text-xs leading-relaxed text-gray-400">({{ vendor.reviews }})</span>
                </div>
              </div>
            </div>
          </div>
          <div class="text-center">
            <Link
              href="/vendors"
              class="py-[10px] px-20 rounded-[500px] bg-gray-800 font-roboto font-medium text-xl leading-none tracking-normal text-white inline-block opacity-100"
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
              ref="discoverBannerRef"
              class="discover-research-background"
              data-bg-image="/images/banners/1.jpg"
              :style="{ backgroundImage: discoverBannerLoaded ? `url(/images/banners/1.jpg)` : 'none' }"
            >
              <div class="discover-research-overlay"></div>
            </div>
            <div class="discover-research-content gap-8 lg:left-[99px]">
              <div class="flex flex-col gap-6">
                  <h2 class="font-hv-muse font-normal text-6xl leading-[110%] tracking-normal text-white">Discover & Research with Confidence</h2>
                  <p class="font-roboto font-normal text-lg leading-loose tracking-normal text-white">Behind the scenes, we're enabling custom combinations of RUO peptides for advanced lab applications.</p>
                </div>
                <button class="w-fit py-[10px] px-20 rounded-[500px] bg-slate-50 font-roboto font-medium text-xl leading-none tracking-normal text-gray-700 opacity-100 hover:bg-gray-100 transition-colors">
                  Read Details
                </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Learn About Peptides Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Learn About Peptides</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 gap-x-[20px] gap-y-[80px]">
            <div
              v-for="article in peptideArticles"
              :key="article.id"
              class="bg-white rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 hover:shadow-lg"
            >
              <div class="w-full aspect-square bg-gray-100 flex items-center justify-center p-6 overflow-hidden">
                <img 
                  :src="`/images/peptides/${article.image}`" 
                  :alt="article.title"
                  class="w-full h-full object-contain object-center"
                  loading="lazy"
                  @error="handleImageError($event)"
                />
              </div>
              <div class="p-6 flex flex-col gap-4 flex-1">
                <h3 class="text-center font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0">{{ article.title }}</h3>
                <p class="font-roboto font-normal text-sm leading-relaxed text-gray-500 text-center m-0 flex-1">{{ article.description }}</p>
                <button class="w-full py-3 px-11 rounded-[500px] bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 cursor-pointer transition-colors duration-300 mt-auto hover:bg-gray-300">
                  Read Details
                </button>
              </div>
            </div>
          </div>
          <div class="text-center">
            <Link
              href="/education"
              class="py-[10px] px-20 rounded-[500px] bg-gray-800 font-roboto font-medium text-xl leading-none tracking-normal text-white inline-block opacity-100"
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
              ref="advanceBannerRef"
              class="advance-research-background"
              data-bg-image="/images/banners/2.jpg"
              :style="{ backgroundImage: advanceBannerLoaded ? `url(/images/banners/2.jpg)` : 'none' }"
            >
              <div class="advance-research-overlay"></div>
            </div>
            <div class="advance-research-content">
              <h2 class="font-hv-muse font-normal text-6xl leading-[110%] tracking-normal text-white text-center m-0">Advance Your<br>Research with Trusted<br>Peptides</h2>
              <p class="font-roboto font-normal text-lg leading-loose tracking-normal text-white m-0">Behind the scenes, we're enabling custom combinations of RUO peptides for advanced lab applications.</p>
              <button class="w-fit py-[10px] px-20 rounded-[500px] bg-white font-roboto font-medium text-xl leading-none tracking-normal text-gray-800 border-none cursor-pointer transition-colors duration-300 shadow-[0_2px_4px_rgba(0,0,0,0.1)] hover:bg-gray-100">
                Start Research
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Research Insights Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Research Insights</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 research-insights-grid">
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
                  @error="handleImageError($event)"
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
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import useEmblaCarousel from 'embla-carousel-vue'
import Autoplay from 'embla-carousel-autoplay'

// Lazy loading for background images
const heroImagesLoaded = ref(new Set())
const discoverBannerLoaded = ref(false)
const advanceBannerLoaded = ref(false)
const heroImageRefs = ref({})
const discoverBannerRef = ref(null)
const advanceBannerRef = ref(null)

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
            // Check if it's a hero slide by checking if the ref exists in heroImageRefs
            const isHeroSlide = Object.values(heroImageRefs.value).includes(entry.target)
            if (isHeroSlide) {
              heroImagesLoaded.value.add(bgImage)
            } else if (entry.target === discoverBannerRef.value) {
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

  // Observe hero images - load first slide immediately, others lazily
  const heroRefsArray = Object.values(heroImageRefs.value).filter(ref => ref)
  if (heroRefsArray.length > 0) {
    // Load first slide immediately (it's visible)
    const firstRef = heroRefsArray[0]
    const firstBgImage = firstRef.getAttribute('data-bg-image')
    if (firstBgImage) {
      const img = new Image()
      img.onload = () => {
        heroImagesLoaded.value.add(firstBgImage)
      }
      img.src = firstBgImage
    }
    // Observe others for lazy loading
    heroRefsArray.slice(1).forEach(ref => {
      if (ref) observer.observe(ref)
    })
  }

  // Observe banner images
  if (discoverBannerRef.value) {
    observer.observe(discoverBannerRef.value)
  }
  if (advanceBannerRef.value) {
    observer.observe(advanceBannerRef.value)
  }
}

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

onMounted(() => {
  if (emblaApi.value) {
    emblaApi.value.on('select', onSelect)
    emblaApi.value.on('reInit', onSelect)
    onSelect() // Set initial slide
  }
  
  // Setup lazy loading for background images
  nextTick(() => {
    setupLazyBackgroundImages()
  })
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

