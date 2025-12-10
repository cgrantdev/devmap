<template>
  <div
    class="bg-white rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 cursor-pointer"
    @click="handleClick"
  >
    <div class="w-full aspect-square bg-gray-100 flex items-center justify-center p-6 overflow-hidden">
      <img
        v-if="image && !hasError"
        :src="image"
        :alt="name"
        class="w-full h-full object-contain object-center"
        loading="lazy"
        @error="onError"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
        <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
          />
        </svg>
      </div>
    </div>
    <div class="p-6 flex flex-col gap-4 flex-1 min-w-0">
      <h3
        class="text-center font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0 truncate w-full"
        :title="name"
      >
        {{ name }}
      </h3>
      <p class="font-roboto font-normal text-sm leading-relaxed text-gray-500 text-center m-0">
        Total items {{ totalItems }}
      </p>
      <SecondButton
        :to="to"
        @click.stop
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import SecondButton from '@/components/SecondButton.vue'

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
