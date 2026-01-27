<template>
    <div
      class="bg-white border border-gray-200 rounded-lg hover:shadow-lg transition-all cursor-pointer overflow-hidden group flex flex-col h-full"
      @click="handleClick"
    >
      <!-- Top Section: Product Image -->
      <div class="aspect-square bg-gray-50 p-4 border-b border-gray-200 flex-shrink-0"> 
        <img
          v-if="imageUrl && !hasError"
          :src="imageUrl"
          :alt="name"
          class="w-full h-full"
          loading="lazy"
          @error="onError"
        />
        <div v-else>
          <svg
            class="w-full h-full"
            viewBox="0 0 200 200"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <rect x="70" y="30" width="60" height="15" fill="#687280" stroke="#374151" stroke-width="2"></rect>
            <rect x="75" y="45" width="50" height="10" fill="#9CA3AF" stroke="#374151" stroke-width="2"></rect>
            <rect x="60" y="55" width="80" height="110" rx="8" fill="#E5E7EB" stroke="#374151" stroke-width="2"></rect>
            <rect x="65" y="100" width= "70" height="60" rx="6" fill="#3B82F6" fill-opacity= "0.3"></rect>
            <rect x="70" y="80" width="60" height="30" fill="white" stroke="#9CA3AF" stroke-width="1"></rect>
            <line x1="75" y1="88" x2="125" y2="88" stroke="#D1D5DB" stroke-width="2"></line>
            <line x1="75" y1="95" x2="115" y2="95" stroke="#D1D5DB" stroke-width="2"></line>
            <line x1="75" y1="102" x2="120" y2="102" stroke="#D1D5DB" stroke-width="2"></line>
            <line x1="145" y1="70" x2="150" y2="70" stroke="#9CA3AF" stroke-width="1"></line>
            <line x1="145" y1="90" x2="150" y2="90" stroke="#9CA3AF" stroke-width="1"></line>
            <line x1="145" y1="110" x2="150" y2="110" stroke="#9CA3AF" stroke-width="1"></line>
            <line x1="145" y1="130" x2="150" y2="130" stroke="#9CA3AF" stroke-width="1"></line>
            <line x1="145" y1="150" x2="150" y2="150" stroke="#9CA3AF" stroke-width="1"></line>
          </svg>
        </div>
      </div>
  
      <!-- Bottom Section: Product Information -->
      <div class="p-4 flex flex-col flex-1 min-h-0">  
        <!-- Product Title -->
        <h3 class="text-sm text-gray-900 mb-2 group-hover:text-blue-600 transition-colors h-10 flex items-center">
          <span class="line-clamp-2">{{ name }}</span>
        </h3>  
  
        <!-- Rating -->
        <div class="flex items-center gap-1 mb-2 h-6 flex-shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-3 h-3 fill-yellow-400 text-yellow-400" aria-hidden="true">
            <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
          </svg>
          <span class="text-xs text-gray-900">
            {{ formattedRating }}
          </span>
          <span class="text-xs text-gray-500">
            ({{ ratingCount || 0 }})
          </span>
        </div>        
  
        <!-- Spacer to push price to bottom -->
        <div class="flex-grow"></div>
  
        <!-- Price -->
        <div class="text-lg text-gray-900 h-7 flex items-center flex-shrink-0">
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
    ratingAverage: { type: [String, Number], default: 0 },
    ratingCount: { type: [String, Number], default: 0 },
    to: { type: String, required: true },
  })
  
  const hasError = ref(false)
  
  const onError = () => {
    hasError.value = true
  }
    
  // Format rating
  const formattedRating = computed(() => {
    const rating = parseFloat(props.ratingAverage) || 0
    return rating.toFixed(1)
  })
  
  // Display price
  const displayPrice = computed(() => {
    const price = props.discountPrice || props.price || 0
    return parseFloat(price).toFixed(2)
  }) 
  
  const handleClick = () => {
    router.visit(props.to)
  }
  </script>
  