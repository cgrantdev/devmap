<template>
  <article
    class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow cursor-pointer"
    @click="handleClick"
  >
    <!-- Image -->
    <img
      :src="image && !hasError ? image : placeholder"
      :alt="title"
      class="w-full h-40 object-cover"
      loading="lazy"
      @error="onError"
    />

    <!-- Content -->
    <div class="p-4">
      <!-- Date -->
      <div class="text-xs text-gray-500 mb-2">
        {{ date }}
      </div>

      <!-- Title -->
      <h3 class="text-base text-gray-900 mb-2 line-clamp-2">
        {{ title }}
      </h3>

      <!-- Description -->
      <p class="text-sm text-gray-600 line-clamp-2">
        {{ description }}
      </p>
    </div>
  </article>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  image: {
    type: String,
    default: null
  },
  date: {
    type: String,
    default: ''
  },
  to: {
    type: String,
    required: true
  }
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
