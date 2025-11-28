<template>
  <FrontLayout>
    <!-- Education Post Detail Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <button 
          @click="router.visit('/education')"
          class="mb-8 flex items-center gap-2 text-gray-600 hover:text-gray-800 font-roboto font-normal text-sm transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Education
        </button>

        <!-- Main Content: Image and Details -->
        <div class="flex flex-col md:flex-row gap-8 mb-12">
          <!-- Left: Product Image -->
          <div class="flex-1 flex items-start justify-center">
            <div class="w-full max-w-md aspect-square bg-gray-100 rounded-lg flex items-center justify-center p-6 overflow-hidden">
              <img 
                v-if="post.image"
                :src="`/images/peptides/${post.image}`" 
                :alt="post.title"
                class="w-full h-full object-contain object-center"
                loading="lazy"
                @error="handleImageError($event)"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <span>No Image</span>
              </div>
            </div>
          </div>

          <!-- Right: Product Details -->
          <div class="flex-1 flex flex-col gap-6">
            <!-- Title -->
            <h1 class="font-hv-muse font-normal text-4xl md:text-5xl leading-tight tracking-normal text-gray-800 m-0">{{ post.title }}</h1>

            <!-- Rating -->
            <div class="flex items-center gap-2">
              <div class="flex items-center">
                <svg 
                  v-for="i in 5" 
                  :key="i"
                  class="w-5 h-5"
                  :class="i <= Math.round(parseFloat(post.rating) || 0) ? 'text-yellow-500' : 'text-gray-300'"
                  fill="currentColor" 
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
              <span class="font-roboto font-normal text-base text-gray-800">{{ post.rating }} ({{ post.rating_count }} reviews)</span>
            </div>

            <!-- Overview -->
            <div>
              <h2 class="font-roboto font-bold text-lg text-gray-800 mb-3">Overview</h2>
              <p class="font-roboto font-normal text-base leading-relaxed text-gray-700 m-0" v-html="post.overview || post.description"></p>
            </div>

            <!-- Key Effects -->
            <div v-if="post.key_effects && post.key_effects.length > 0">
              <h2 class="font-roboto font-bold text-lg text-gray-800 mb-3">Key Effects</h2>
              <ul class="space-y-3">
                <li 
                  v-for="(effect, index) in post.key_effects" 
                  :key="index"
                  class="flex items-start gap-3"
                >
                  <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span class="font-roboto font-normal text-base leading-relaxed text-gray-700">{{ effect }}</span>
                </li>
              </ul>
            </div>

            <!-- Informational Disclaimer -->
            <div class="bg-gray-100 rounded-lg p-4">
              <p class="font-roboto font-normal text-sm text-gray-600 m-0">This content is for informational purposes only.</p>
            </div>

            <!-- Shop Now Button -->
            <button 
              v-if="post.shop_url"
              @click="window.open(post.shop_url, '_blank')"
              class="w-full md:w-auto py-3 px-8 rounded-[500px] bg-blue-600 font-roboto font-medium text-base leading-none tracking-normal text-white border-none cursor-pointer transition-colors duration-300 hover:bg-blue-700"
            >
              Shop {{ post.title }} Now
            </button>
          </div>
        </div>

        <!-- General Disclaimer Section -->
        <div class="mb-12 p-6 bg-gray-50 rounded-lg">
          <p class="font-roboto font-normal text-sm leading-relaxed text-gray-600 m-0 mb-3">
            The reported effects mentioned above are based on user reviews and research findings. This information is not intended as medical advice. Please consult with a qualified healthcare professional before using any peptide products.
          </p>
          <p class="font-roboto font-normal text-sm leading-relaxed text-gray-600 m-0">
            This content is for informational purposes only. Products shown are based on site activity and user engagement. Individual results may vary.
          </p>
        </div>

        <!-- Accordion Sections -->
        <div class="space-y-4">
          <div
            v-for="(section, index) in post.accordion_sections"
            :key="index"
            class="border border-gray-200 rounded-lg overflow-hidden"
          >
            <button
              @click="toggleAccordion(index)"
              class="w-full flex items-center justify-between p-6 bg-white hover:bg-gray-50 transition-colors text-left"
            >
              <h3 class="font-roboto font-bold text-lg text-gray-800 m-0">{{ section.title }}</h3>
              <svg 
                class="w-6 h-6 text-gray-600 transition-transform"
                :class="{ 'rotate-180': expandedSections.has(index) }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              v-show="expandedSections.has(index)"
              class="p-6 bg-white border-t border-gray-200"
            >
              <div 
                class="font-roboto font-normal text-base leading-relaxed text-gray-700 prose max-w-none"
                v-html="section.content"
              ></div>
            </div>
          </div>
        </div>

        <!-- Related Posts -->
        <div v-if="related && related.length > 0" class="mt-16">
          <h2 class="font-hv-muse font-normal text-3xl leading-normal tracking-normal text-gray-800 mb-8">Related Topics</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
              v-for="item in related"
              :key="item.id"
              class="bg-white rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 hover:shadow-lg cursor-pointer"
              @click="router.visit(`/education/${item.slug}`)"
            >
              <div class="w-full aspect-square bg-gray-100 flex items-center justify-center p-6 overflow-hidden">
                <img 
                  v-if="item.image"
                  :src="`/images/peptides/${item.image}`" 
                  :alt="item.title"
                  class="w-full h-full object-contain object-center"
                  loading="lazy"
                  @error="handleImageError($event)"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                  <span class="text-sm">No Image</span>
                </div>
              </div>
              <div class="p-6 flex flex-col gap-4 flex-1">
                <h3 class="text-center font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0">{{ item.title }}</h3>
                <p class="font-roboto font-normal text-sm leading-relaxed text-gray-500 text-center m-0 flex-1">{{ item.description }}</p>
                <button class="w-full py-3 px-11 rounded-[500px] bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 cursor-pointer transition-colors duration-300 mt-auto hover:bg-gray-300">
                  Read Details
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'

const props = defineProps({
  post: Object,
  related: Array,
})

const expandedSections = ref(new Set([0])) // First section expanded by default

const toggleAccordion = (index) => {
  if (expandedSections.value.has(index)) {
    expandedSections.value.delete(index)
  } else {
    expandedSections.value.add(index)
  }
}

const handleImageError = (event) => {
  if (event.target.dataset.failed) {
    return
  }
  event.target.dataset.failed = 'true'
  event.target.style.display = 'none'
  if (event.target.parentElement) {
    const placeholder = document.createElement('div')
    placeholder.className = 'w-full h-full flex items-center justify-center text-gray-400'
    placeholder.innerHTML = '<span>No Image</span>'
    event.target.parentElement.appendChild(placeholder)
  }
}
</script>

