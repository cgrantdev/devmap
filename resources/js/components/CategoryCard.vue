<template>
  <div
    class="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all text-left group cursor-pointer"
    @click="handleClick"
  >
    <div class="aspect-square bg-gray-50 p-0 flex items-center justify-center mb-6">
      <img
        v-if="image && !hasError"
        :src="image"
        :alt="name"
        class="w-full h-full object-contain select-none flex items-center justify-center"
        loading="lazy"
        @error="onError"
      />
      <div v-else class="flex items-center justify-center text-gray-400">
        <span class="text-sm">No Image</span>
      </div>
    </div>
    <h3
      class="pt-6 border-t border-slate-200 text-xl text-gray-900 mb-1 group-hover:text-blue-600 transition-colors"
      :title="name"
    >
      {{ name }}
    </h3>
    <!-- Research Area -->
    <div v-if="researchArea" class="text-slate-600 text-xs italic justify-between mb-6">      
      {{ researchArea }}
    </div>
    
    <!-- Learn More Link (Fixed position at bottom) -->
    <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
      <span class="text-gray-900 text-xs italic underline">View All</span>
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-4 h-4 text-slate-500 group-hover:text-slate-700 transition-colors" aria-hidden="true">
        <path d="m9 18 6-6-6-6"></path>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
// import MainButton from '@/components/MainButton.vue'

const props = defineProps({
  name: { type: String, required: true },
  image: { type: String, default: null },
  totalItems: { type: [String, Number], default: 0 },
  to: { type: String, required: true },
  researchArea: { type: String, default: null },
})

const hasError = ref(false)

const handleClick = () => {
  router.visit(props.to)
}

const onError = () => {
  hasError.value = true
}
</script>
