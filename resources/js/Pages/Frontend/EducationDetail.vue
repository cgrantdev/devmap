<template>
  <FrontLayout>
    <!-- Education Post Detail Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
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
            <div v-if="post.overview || post.description">
              <p class="font-roboto font-normal text-base leading-relaxed text-gray-700 m-0" v-html="post.overview || post.description"></p>
            </div>

            <!-- Key Effects -->
            <div v-if="post.key_effects && post.key_effects.length > 0">
              <p class="font-roboto font-normal text-base leading-relaxed text-gray-700 mb-4">Researchers have investigated {{ post.title }} for its effects on:</p>
              <ul class="space-y-3 mb-6">
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
      </div> 
    </section>
    <section class="py-16 bg-white">
        <!-- General Disclaimer Section -->
      <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12">
          <p class="font-roboto font-normal text-sm leading-relaxed text-gray-600 m-0">
            Reported effects are generated from user reviews and are not intended as medical advice. Please consult your physician before changing any medical treatment. Description and grow content is for informational purposes only. We show products that we think you'll be interested in based on your site activity.
          </p>
        </div>

        <!-- Accordion Sections -->
        <div class="space-y-0">
          <div
            v-for="(section, index) in post.accordion_sections"
            :key="index"
            class="overflow-hidden mb-5"
          >
            <button
              @click="toggleAccordion(index)"
              class="w-full flex items-center justify-between text-left transition-colors"
              :style="{
                backgroundColor: '#F1F5F9',
                borderRadius: '50px',
                padding: '20px 50px',
                borderBottom: index < post.accordion_sections.length - 1 ? '1px solid #E2E8F0' : 'none',
                gap: '12px'
              }"
            >
              <h3 class="font-roboto font-bold text-lg text-gray-800 m-0">{{ section.title }}</h3>
              <svg 
                class="w-6 h-6 text-gray-600 transition-transform flex-shrink-0"
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
              class="p-6 bg-white"
            >
              <div 
                class="font-roboto font-normal text-base leading-relaxed text-gray-700 prose max-w-none"
                v-html="section.content"
              ></div>
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

