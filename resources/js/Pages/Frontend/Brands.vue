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
          <VendorCard
            v-for="brand in brands"
            :key="brand.id"
            :id="brand.id"
            :name="brand.name"
            :slug="brand.slug"
            :logo="brand.logo"
            :initials="brand.initials"
            :location="brand.location"
            :rating="brand.rating || '0.00'"
            :reviews="brand.reviews || 0"
          />
        </div>
        
        <div v-else class="text-center py-20">
          <p class="font-roboto font-normal text-lg text-gray-500">No brands available at the moment.</p>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import FrontLayout from '../Layouts/FrontLayout.vue'
import VendorCard from '@/components/VendorCard.vue'

const props = defineProps({
  brands: {
    type: Array,
    default: () => []
  }
})
</script>

