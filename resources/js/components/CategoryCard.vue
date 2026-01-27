<template>
  <div
    class="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all text-left group cursor-pointer flex flex-col overflow-hidden relative h-full"
    @click="handleClick"
  >
    <div class="bg-gray-500 w-16 h-16 rounded-lg flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform flex-shrink-0">
      <img
        v-if="image && !hasError"
        :src="image"
        :alt="name"
        class="w-full h-full object-contain object-center"
        loading="lazy"
        @error="onError"
      />
      <div v-else class="flex items-center justify-center text-white">
        <span class="text-sm">No Image</span>
      </div>
    </div>
    <div class="flex flex-col flex-1 min-w-0">
      <h3
        class="text-xl text-gray-900 mb-4 group-hover:text-blue-600 transition-colors w-full min-h-[3.5rem] flex items-center"
        :title="name"
      >
        <span class="line-clamp-2">{{ name }}</span>
      </h3>
      <p class="text-sm text-gray-600 flex-shrink-0">
        {{ totalItems }} products
      </p>
      <!-- <MainButton
        :to="to"
        text="Shop Now"
        size="second"
        bg-color="gray-200"
        @click.stop
        class="mt-auto"
      /> -->
    </div>
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down w-5 h-5 text-gray-400 group-hover:text-blue-600 rotate-[-90deg] transition-colors absolute bottom-4 right-4 flex-shrink-0" aria-hidden="true">
      <path d="m6 9 6 6 6-6"></path>
    </svg>
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
})

const hasError = ref(false)

const handleClick = () => {
  router.visit(props.to)
}

const onError = () => {
  hasError.value = true
}
</script>
