<template>
  <div
    :class="[
      'bg-white rounded-lg border-2 p-4 hover:shadow-md transition-all cursor-pointer',
      isSelected ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
    ]"
    @click="handleToggle"
  >
    <div class="flex items-center gap-4">
      <!-- Checkbox -->
      <div class="flex-shrink-0">
        <div
          :class="[
            'w-6 h-6 rounded border-2 flex items-center justify-center transition-colors',
            isSelected 
              ? 'bg-blue-600 border-blue-600' 
              : 'border-gray-300 bg-white'
          ]"
        >
          <svg
            v-if="isSelected"
            xmlns="http://www.w3.org/2000/svg"
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="white"
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>
      </div>

      <!-- Product Image -->
      <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
        <img
          v-if="product.image_url && !hasError"
          :src="product.image_url"
          :alt="product.name"
          class="w-full h-full object-contain"
          @error="onError"
        />
        <div v-else class="text-gray-400 text-xs text-center px-2">No Image</div>
      </div>

      <!-- Product Info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
          <h3 class="font-semibold text-gray-900 truncate">{{ product.name }}</h3>
          <span
            v-if="typeChip"
            :class="['flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold', typeChip.classes]"
          >{{ typeChip.label }}</span>
        </div>
        <div class="flex items-center gap-4 text-sm text-gray-600">
          <span v-if="product.brand_name">{{ product.brand_name }}</span>
          <span v-if="product.purity">{{ product.purity }}% purity</span>
          <span>${{ formatPrice(product.discount_price || product.price) }}</span>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

// Color-coded format chip; Capsule = blue, Nasal Spray = green.
// Peptide (the default) renders no chip.
const typeChip = computed(() => {
  switch (props.product?.product_type) {
    case 'Capsule':
      return { label: 'Capsule', classes: 'bg-blue-100 text-blue-800 border border-blue-200' }
    case 'Nasal Spray':
      return { label: 'Nasal Spray', classes: 'bg-emerald-100 text-emerald-800 border border-emerald-200' }
    default:
      return null
  }
})

const emit = defineEmits(['toggle'])

const hasError = ref(false)

const handleToggle = () => {
  emit('toggle', props.product)
}

const onError = () => {
  hasError.value = true
}

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2)
}
</script>
