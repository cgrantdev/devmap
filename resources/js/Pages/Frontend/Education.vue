<template>
  <ModernLayout>
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
              <h1 class="font-hv-muse font-normal text-6xl leading-tight tracking-normal text-white m-0">Learn About Peptides</h1>
              <p class="font-roboto font-normal text-lg leading-loose tracking-normal text-white m-0">Educational resources and research insights</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Discover Peptides Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Learn About Peptides</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
          <EducationPostCard
            v-for="product in displayedProducts"
            :key="product.slug || product.name"
            :name="product.name"
            :image="product.image"
            :total-items="product.total_items"
            :to="`/education/${product.slug}`"
          />
        </div>

        <!-- Load More Button -->
        <div v-if="hasMoreProducts" class="justify-center flex">
          <MainButton
            text="Load More"
            bg-color="gray-800"
            @click="loadMore"
          />
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import EducationPostCard from '@/components/EducationPostCard.vue'
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
