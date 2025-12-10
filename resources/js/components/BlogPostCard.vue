<template>
  <div
    class="bg-white border border-gray-200 rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 h-full cursor-pointer"
    @click="handleClick"
  >
    <div class="w-full aspect-[325/404] overflow-hidden bg-gray-100 rounded-t-lg">
      <img
        :src="image && !hasError ? image : placeholder"
        :alt="title"
        class="w-full h-full object-cover object-center block"
        loading="lazy"
        @error="onError"
      />
    </div>
    <div class="flex justify-between items-center py-3 px-4 font-roboto font-normal text-xs leading-relaxed text-gray-500 bg-white">
      <span class="flex items-center gap-1.5">
        <svg class="flex-shrink-0 text-gray-500 w-4 h-4" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M8 14C11.3137 14 14 11.3137 14 8C14 4.68629 11.3137 2 8 2C4.68629 2 2 4.68629 2 8C2 11.3137 4.68629 14 8 14Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M8 5V8L10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        {{ readTime }}
      </span>
      <span class="text-gray-500">{{ date }}</span>
    </div>
    <div class="p-5 flex flex-col gap-3 flex-1">
      <h3 class="font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0 mb-1 text-center">{{ title }}</h3>
      <p class="font-roboto font-normal text-sm leading-loose text-gray-500 m-0 flex-1 mb-2 text-center">{{ description }}</p>
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
  title: { type: String, required: true },
  description: { type: String, default: '' },
  image: { type: String, default: null },
  readTime: { type: String, default: '' },
  date: { type: String, default: '' },
  to: { type: String, required: true },
})

const hasError = ref(false)
const placeholder = '/images/blogs/1.jpg'

const handleClick = () => {
  router.visit(props.to)
}

const onError = () => {
  hasError.value = true
}
</script>
