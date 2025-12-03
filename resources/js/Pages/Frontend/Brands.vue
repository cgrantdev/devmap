<template>
  <FrontLayout>
    <!-- Hero Section -->
    <section class="pt-0 pb-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-[1360px] h-[574px] rounded-[24px] overflow-hidden relative mx-auto">
          <!-- Background Image -->
          <div 
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style="background-image: url('/images/hero/hero1.jpg')"
          >
            <div 
              class="absolute inset-0 pointer-events-none"
              style="background: linear-gradient(260deg, rgba(0, 0, 0, 0.4) 45.49%, rgba(0, 0, 0, 0.81) 79.33%)"
            ></div>
          </div>
          
          <!-- Content -->
          <div class="relative p-10 h-full flex flex-col justify-center max-w-[800px] gap-6">
            <div class="flex flex-col gap-6">
              <h1 class="font-hv-muse font-normal text-6xl leading-tight tracking-normal text-white m-0">Trusted Brands</h1>
              <p class="font-roboto font-normal text-lg leading-loose tracking-normal text-white m-0">Explore verified brands in the peptide research community.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Brands Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 w-full max-w-[360px] mx-auto">All Brands</h2>
        
        <div v-if="brands.length > 0" class="grid grid-cols-2 md:grid-cols-5 gap-x-[20px] gap-y-[80px] mb-20">
          <div
            v-for="brand in brands"
            :key="brand.id"
            class="bg-white flex flex-col gap-[5px] transition-shadow duration-300 items-center hover:shadow-md cursor-pointer"
            @click="navigateToBrand(brand)"
          >
            <!-- Logo Area -->
            <div class="w-full aspect-square bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden mb-1 p-4">
              <div v-if="brand.logo" class="w-full h-full flex items-center justify-center p-4">
                <img 
                  :src="brand.logo" 
                  :alt="brand.name"
                  class="w-full h-full object-contain"
                  @error="handleImageError"
                />
              </div>
              <div v-else class="w-full h-full flex items-center justify-center">
                <span class="font-roboto font-semibold text-2xl text-gray-500">{{ brand.initials }}</span>
              </div>
            </div>
            
            <!-- Location -->
            <div class="flex items-center gap-1 mb-1">
              <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 8.5C9.10457 8.5 10 7.60457 10 6.5C10 5.39543 9.10457 4.5 8 4.5C6.89543 4.5 6 5.39543 6 6.5C6 7.60457 6.89543 8.5 8 8.5Z" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 13.5C8 13.5 13.5 10.5 13.5 6.5C13.5 4.01472 11.4853 2 9 2C6.51472 2 5 4.01472 5 6.5C5 10.5 8 13.5 8 13.5Z" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span class="font-roboto font-normal text-sm leading-relaxed text-gray-500">{{ brand.location || 'Location not available' }}</span>
            </div>
            
            <!-- Brand Name -->
            <h3 class="font-roboto font-normal text-2xl leading-none tracking-normal text-gray-800 m-0 mb-1">{{ brand.name }}</h3>
            
            <!-- Rating -->
            <div class="flex flex-row gap-2">
              <div class="flex gap-0.5">
                <svg v-for="i in 5" :key="i" class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M8 0L9.79611 5.52786H15.6085L10.9062 8.94427L12.7023 14.4721L8 11.0557L3.29772 14.4721L5.09383 8.94427L0.391548 5.52786H6.20389L8 0Z" fill="#FBBF24"/>
                </svg>
              </div>
              <div class="flex items-baseline gap-1">
                <span class="font-roboto font-normal text-xs leading-relaxed text-gray-800">{{ brand.rating || '0.00' }}</span>
                <span class="font-roboto font-normal text-xs leading-relaxed text-gray-400">({{ brand.reviews || 0 }})</span>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="text-center py-20">
          <p class="font-roboto font-normal text-lg text-gray-500">No brands available at the moment.</p>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'

const props = defineProps({
  brands: {
    type: Array,
    default: () => []
  }
})

const navigateToBrand = (brand) => {
  // Navigate to products page filtered by brand
  router.visit(`/brand/${brand.slug}/products`)
}

const handleImageError = (event) => {
  // Hide broken image and show initials fallback
  const img = event.target
  const container = img.closest('.aspect-square')
  if (container) {
    img.style.display = 'none'
    const fallback = container.querySelector('span')
    if (fallback) {
      fallback.style.display = 'block'
    }
  }
}
</script>

