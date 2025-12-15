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
                        <MainButton 
                          :text="slide.ctaText"
                          :to="slide.ctaUrl"
                          bg-color="white"
                          class="self-start"
                          @click.stop
                        />
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
            <VendorCard
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
          <div class="flex justify-center">
            <MainButton 
              text="View All Vendors"
              to="/brands"
              bg-color="gray-800"
            />
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
                <MainButton 
                  text="Read Details"
                  to="/blogs"
                  bg-color="slate-50"
                />
            </div>
          </div>
        </div>
      </section>

      <!-- Learn About Peptides Section (education categories) -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Learn About Peptides</h2>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
            <EducationPostCard
              v-for="product in displayedProducts"
              :key="product.slug"
              :name="product.name"
              :image="product.image"
              :total-items="product.total_items"
              :to="`/education/${product.slug}`"
            />
          </div>

          <div class="flex justify-center">
            <MainButton 
              text="View All Education"
              to="/education"
              bg-color="gray-800"
            />
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
              <MainButton 
                text="Start Research"
                to="/products"
                bg-color="white"
                class="mx-auto"
              />
            </div>
          </div>
        </div>
      </section>

      <!-- Research Insights Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Research Insights</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20 research-insights-grid">
            <BlogPostCard
              v-for="insight in researchInsights"
              :key="insight.id"
              :title="insight.title"
              :description="insight.description"
              :image="insight.image"
              :read-time="insight.readTime"
              :date="insight.date"
              :to="`/blog/${insight.slug}`"
            />
          </div>
          <div class="flex justify-center">
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
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import useEmblaCarousel from 'embla-carousel-vue'
import Autoplay from 'embla-carousel-autoplay'
import MainButton from '@/components/MainButton.vue'
import VendorCard from '@/components/VendorCard.vue'
import EducationPostCard from '@/components/EducationPostCard.vue'
import BlogPostCard from '@/components/BlogPostCard.vue'

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
  // [autoplayPluginInstance]
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
  },
  topBlogs: {
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

const researchInsights = computed(() => props.topBlogs || [])
</script>

