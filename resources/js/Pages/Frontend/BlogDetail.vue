<template>
  <FrontLayout>
    <!-- Blog Detail Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <button 
          @click="router.visit('/blogs')"
          class="mb-8 flex items-center gap-2 text-gray-600 hover:text-gray-800 font-roboto font-normal text-sm transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Research Insights
        </button>

        <!-- Blog Header -->
        <div class="mb-8">
          <div class="flex items-center gap-4 mb-4 text-sm text-gray-500 font-roboto">
            <span class="flex items-center gap-1.5">
              <svg class="flex-shrink-0 text-gray-500 w-4 h-4" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 14C11.3137 14 14 11.3137 14 8C14 4.68629 11.3137 2 8 2C4.68629 2 2 4.68629 2 8C2 11.3137 4.68629 14 8 14Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 5V8L10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              {{ blog.readTime }}
            </span>
            <span>{{ blog.date }}</span>
          </div>
          <h1 class="font-hv-muse font-normal text-4xl md:text-5xl leading-tight tracking-normal text-gray-800 m-0 mb-4">{{ blog.title }}</h1>
          <p class="font-roboto font-normal text-lg leading-relaxed text-gray-600 m-0">{{ blog.description }}</p>
        </div>

        <!-- Featured Image -->
        <div class="mb-8 rounded-lg overflow-hidden">
          <img 
            v-if="blog.image"
            :src="`/images/blogs/${blog.image}`" 
            :alt="blog.title"
            class="w-full h-auto object-cover"
            loading="lazy"
            @error="handleImageError($event)"
          />
          <div v-else class="w-full h-64 bg-gray-200 flex items-center justify-center text-gray-400">
            <span>No Image</span>
          </div>
        </div>

        <!-- Blog Content -->
        <div class="prose prose-lg max-w-none mb-12">
          <div 
            v-if="blog.content"
            class="font-roboto font-normal text-base leading-relaxed text-gray-700"
            v-html="blog.content"
          ></div>
          <div v-else class="font-roboto font-normal text-base leading-relaxed text-gray-700">
            <p>{{ blog.description }}</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
            <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
            <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>
          </div>
        </div>

        <!-- Related Articles -->
        <div v-if="related && related.length > 0" class="mt-16">
          <h2 class="font-hv-muse font-normal text-3xl leading-normal tracking-normal text-gray-800 mb-8">Related Articles</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
              v-for="item in related"
              :key="item.id"
              class="bg-white border border-gray-200 rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 h-full hover:shadow-lg cursor-pointer"
              @click="router.visit(`/blog/${item.slug}`)"
            >
              <div class="w-full aspect-[325/404] overflow-hidden bg-gray-100 rounded-t-lg">
                <img 
                  v-if="item.image"
                  :src="`/images/blogs/${item.image}`" 
                  :alt="item.title"
                  class="w-full h-full object-cover object-center block"
                  loading="lazy"
                  @error="handleImageError($event)"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                  <span class="text-sm">No Image</span>
                </div>
              </div>
              <div class="flex justify-between items-center py-3 px-4 font-roboto font-normal text-xs leading-relaxed text-gray-500 bg-white">
                <span class="flex items-center gap-1.5">
                  <svg class="flex-shrink-0 text-gray-500 w-4 h-4" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 14C11.3137 14 14 11.3137 14 8C14 4.68629 11.3137 2 8 2C4.68629 2 2 4.68629 2 8C2 11.3137 4.68629 14 8 14Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 5V8L10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  {{ item.readTime }}
                </span>
                <span class="text-gray-500">{{ item.date }}</span>
              </div>
              <div class="p-5 flex flex-col gap-3 flex-1">
                <h3 class="font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0 mb-1 text-center">{{ item.title }}</h3>
                <p class="font-roboto font-normal text-sm leading-loose text-gray-500 m-0 flex-1 mb-2 text-center">{{ item.description }}</p>
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
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'

const props = defineProps({
  blog: Object,
  related: Array,
})

const handleImageError = (event) => {
  if (event.target.dataset.failed) {
    return
  }
  event.target.dataset.failed = 'true'
  event.target.style.display = 'none'
  if (event.target.parentElement) {
    const placeholder = document.createElement('div')
    placeholder.className = 'w-full h-full flex items-center justify-center text-gray-400'
    placeholder.innerHTML = '<span class="text-sm">No Image</span>'
    event.target.parentElement.appendChild(placeholder)
  }
}
</script>

