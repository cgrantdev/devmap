<template>
    <div
      @click="handleClick"
      class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-all cursor-pointer group"
    >
      <!-- Top Section: Product Image -->
      <div class="aspect-square bg-gray-50 p-6"> 
        <img
          v-if="imageUrl && !hasError"
          :src="imageUrl"
          :alt="name"
          class="w-full h-full object-contain rounded-lg flex items-center justify-center select-none"
          loading="lazy"
          @error="onError"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
          <svg
            class="w-32 h-40"
            viewBox="0 0 80 120"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <!-- Vial Body -->
            <rect x="20" y="20" width="40" height="80" rx="2" fill="white" stroke="#9CA3AF" stroke-width="1.5"/>
            
            <!-- Vial Cap -->
            <rect x="18" y="15" width="44" height="8" rx="1" fill="#4B5563"/>
            
            <!-- Liquid (fills ~2/3) -->
            <rect x="22" y="60" width="36" height="36" rx="1" fill="#93C5FD"/>
            
            <!-- Label on vial -->
            <rect x="26" y="70" width="28" height="16" rx="1" fill="white" opacity="0.9"/>
            <line x1="28" y1="74" x2="52" y2="74" stroke="#9CA3AF" stroke-width="0.5"/>
            <line x1="28" y1="78" x2="52" y2="78" stroke="#9CA3AF" stroke-width="0.5"/>
            <line x1="28" y1="82" x2="52" y2="82" stroke="#9CA3AF" stroke-width="0.5"/>
            
            <!-- Volume tick marks on right side -->
            <line x1="62" y1="30" x2="65" y2="30" stroke="#4B5563" stroke-width="1"/>
            <line x1="62" y1="45" x2="65" y2="45" stroke="#4B5563" stroke-width="1"/>
            <line x1="62" y1="60" x2="65" y2="60" stroke="#4B5563" stroke-width="1"/>
            <line x1="62" y1="75" x2="65" y2="75" stroke="#4B5563" stroke-width="1"/>
            <line x1="62" y1="90" x2="65" y2="90" stroke="#4B5563" stroke-width="1"/>
          </svg>
        </div>
      </div>
  
      <!-- Bottom Section: Product Information -->
      <div class="p-4">       
  
        <!-- Product Title -->
        <h3 class="text-sm text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
          {{ name }}
        </h3>
  
        <!-- Brand Name -->
        <p class="text-xs text-gray-600 mb-2">
          {{ brandName || 'Unknown Brand' }}
        </p>  
        
  
        <!-- Price -->
        <div class="text-lg text-gray-900">
          ${{ displayPrice }}
        </div>        
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed, ref } from 'vue'
  import { router } from '@inertiajs/vue3'
  
  const props = defineProps({
    name: { type: String, required: true },
    imageUrl: { type: String, default: null },
    price: { type: [String, Number], default: 0 },
    discountPrice: { type: [String, Number], default: null },
    brandName: { type: String, default: '' },    
    to: { type: String, required: true },    
  })
  
  const hasError = ref(false)
  
  const onError = () => {
    hasError.value = true
  } 
   
  // Display price
  const displayPrice = computed(() => {
    const price = props.discountPrice || props.price || 0
    return parseFloat(price).toFixed(2)
  })
    
  const handleClick = () => {
    router.visit(props.to)
  }
  </script>
  