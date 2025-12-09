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
                  :class="{ 'slide-active': isSlideActive(index) }"
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
                v-for="(slide, index) in props.heroSlides"
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
              class="bg-white flex flex-col gap-[5px] transition-shadow duration-300 items-center hover:shadow-md cursor-pointer"
              @click="router.visit(`/brand/${vendor.slug}/products`)"
            >
              <!-- Logo Area -->
              <div class="w-full aspect-square bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden mb-1">
                <img 
                  v-if="vendor.logo"
                  :src="vendor.logo" 
                  :alt="vendor.name + ' logo'"
                  class="w-full h-full object-contain p-3"
                  loading="lazy"
                  @error="handleLogoError($event, vendor.id)"
                />
                <div v-if="logoErrors.has(vendor.id) || !vendor.logo" class="w-full h-full flex items-center justify-center">
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
                <div class="flex items-baseline gap-1">
                  <span class="font-roboto font-normal text-xs leading-relaxed text-gray-800">{{ vendor.rating }}</span>
                  <span class="font-roboto font-normal text-xs leading-relaxed text-gray-400">({{ vendor.reviews }})</span>
                </div>
              </div>
            </div>
          </div>
          <div class="text-center">
            <Link
            href="/brands"
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

      <!-- Learn About Peptides Section (education categories) -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Learn About Peptides</h2>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
            <div
              v-for="product in displayedProducts"
              :key="product.slug"
              class="bg-white rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 hover:shadow-lg cursor-pointer"
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

          <div class="text-center">
            <button 
              @click="router.visit('/education')"
              class="py-2.5 px-20 rounded-[500px] bg-gray-800 font-roboto font-medium text-xl leading-none tracking-normal text-white border-none cursor-pointer transition-colors duration-300 shadow-[0_2px_4px_rgba(0,0,0,0.1)] hover:bg-gray-700"
            >
              View All Education
            </button>
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
              class="bg-white border border-gray-200 rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 h-full hover:shadow-lg cursor-pointer"
              @click="router.visit('/blogs')"
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
                <button 
                  @click="router.visit('/blogs')"
                  class="w-full py-3 px-11 rounded-[500px] bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 cursor-pointer transition-colors duration-300 mt-auto hover:bg-gray-300"
                >
                  Read Details
                </button>
              </div>
            </div>
          </div>
          <div class="text-center">
            <button 
              @click="router.visit('/blogs')"
              class="py-[10px] px-20 rounded-[500px] bg-gray-800 font-roboto font-medium text-xl leading-none tracking-normal text-white border-none cursor-pointer transition-colors duration-300 shadow-[0_2px_4px_rgba(0,0,0,0.1)] hover:bg-gray-700"
            >
              More Details
            </button>
          </div>
        </div>
      </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
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
  }
})

// Process slides and duplicate if needed to ensure looping works with 1-2 slides
const processSlides = (slides) => {
  const processed = slides.map(slide => ({
    title: slide.title,
    subtitle: slide.subtitle,
    ctaText: slide.ctaText,
    ctaUrl: slide.ctaUrl,
    image: slide.image
  }))
  
  // If we have fewer than 3 slides, duplicate them to ensure looping works
  if (processed.length > 0 && processed.length < 3) {
    // Duplicate slides until we have at least 3
    const needed = 3 - processed.length
    for (let i = 0; i < needed; i++) {
      processed.push({ ...processed[i % processed.length] })
    }
  }
  
  return processed
}

const heroSlides = ref(processSlides(props.heroSlides))

const goToSlide = (index) => {
  if (emblaApi.value) {
    // Get the original slide count (before duplication)
    const originalCount = props.heroSlides.length
    // If we have fewer than 3 original slides, map the index to the correct position
    // Since slides are duplicated, we can scroll to any duplicate of the target slide
    if (originalCount > 0 && originalCount < 3) {
      // Find the first occurrence of this slide in the duplicated array
      const targetIndex = index % originalCount
      // Scroll to the first occurrence
      emblaApi.value.scrollTo(targetIndex)
    } else {
      emblaApi.value.scrollTo(index)
    }
  }
}

const onSelect = () => {
  if (emblaApi.value) {
    const selectedIndex = emblaApi.value.selectedScrollSnap()
    // Get the original slide count (before duplication)
    const originalCount = props.heroSlides.length
    // If we have fewer than 3 original slides, map to the original index
    if (originalCount > 0 && originalCount < 3) {
      currentSlide.value = selectedIndex % originalCount
    } else {
      currentSlide.value = selectedIndex
    }
  }
}

// Check if a slide (by its index in the duplicated array) should be active
const isSlideActive = (index) => {
  const originalCount = props.heroSlides.length
  if (originalCount > 0 && originalCount < 3) {
    // For duplicated slides, check if this index maps to the current slide
    return (index % originalCount) === currentSlide.value
  } else {
    // For normal slides, direct comparison
    return currentSlide.value === index
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

const topVendors = computed(() => props.topBrands || [])

// Education categories (from server) - show first 8, link to full page
const displayedProducts = computed(() => {
  return props.productGroups.slice(0, 8)
})

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

