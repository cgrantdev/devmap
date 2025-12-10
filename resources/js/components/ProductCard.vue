<template>
  <div
    class="bg-white rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 hover:shadow-lg cursor-pointer"
    @click="handleClick"
  >
    <div class="w-full aspect-square bg-gray-100 flex items-center justify-center p-6 overflow-hidden rounded-lg">
      <img
        v-if="imageUrl && !hasError"
        :src="imageUrl"
        :alt="name"
        class="w-full h-full object-contain object-center"
        loading="lazy"
        @error="onError"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
        <span class="text-sm">No Image</span>
      </div>
    </div>
    <div class="p-4 flex flex-col gap-2 flex-1 min-w-0">
      <h3 class="font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0 text-center truncate" :title="name">
        {{ name }}
      </h3>
      <div class="flex items-center justify-center gap-2">
        <p v-if="discountPrice" class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-400 m-0 line-through">
          ${{ price }}
        </p>
        <p class="font-roboto font-normal text-base leading-normal tracking-normal text-gray-800 m-0">
          ${{ discountPrice || price }}
        </p>
      </div>
      <p class="font-roboto font-normal text-sm leading-relaxed text-gray-500 m-0 text-center">
        From Seller
        <span class="inline-block bg-[#E0F2FE] text-gray-800 rounded-lg py-[3px] px-2">{{ brandName || 'Unknown' }}</span>
      </p>
      <div class="flex items-center justify-center gap-1">
        <div class="flex items-center">
          <svg
            v-for="i in 5"
            :key="i"
            class="w-4 h-4"
            :class="i <= Math.round(ratingAverage || 0) ? 'text-yellow-500' : 'text-gray-300'"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
            />
          </svg>
        </div>
        <span class="font-roboto font-normal text-xs leading-normal tracking-normal text-gray-500">
          ({{ ratingCount || 0 }} reviews)
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
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

const handleClick = () => {
  router.visit(props.to)
}

const onError = () => {
  hasError.value = true
}
</script>
