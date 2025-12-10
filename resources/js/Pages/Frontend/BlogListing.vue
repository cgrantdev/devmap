<template>
  <FrontLayout>
    <!-- Research Insights Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Research Insights</h2>
        
        <!-- Featured Article -->
        <div v-if="featured" class="mb-16">
          <div class="bg-white rounded-lg overflow-hidden shadow-lg flex flex-col md:flex-row gap-6">
            <!-- Left: Text Content -->
            <div class="flex-1 p-8 flex flex-col justify-between">
              <div>
                <span class="inline-block bg-gray-200 text-gray-800 px-3 py-1 rounded-full text-xs font-roboto font-medium mb-4">Featured Article</span>
                <div class="flex items-center gap-4 mb-4 text-sm text-gray-500 font-roboto">
                  <span class="flex items-center gap-1.5">
                    <svg class="flex-shrink-0 text-gray-500 w-4 h-4" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M8 14C11.3137 14 14 11.3137 14 8C14 4.68629 11.3137 2 8 2C4.68629 2 2 4.68629 2 8C2 11.3137 4.68629 14 8 14Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M8 5V8L10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ featured.readTime }}
                  </span>
                  <span>{{ featured.date }}</span>
                </div>
                <h1 class="font-hv-muse font-normal text-4xl md:text-5xl leading-tight tracking-normal text-gray-800 m-0 mb-6">{{ featured.title }}</h1>
                <p class="font-roboto font-normal text-base leading-relaxed text-gray-600 m-0 mb-4">{{ featured.description }}</p>
                <p class="font-roboto font-normal text-base leading-relaxed text-gray-600 m-0">{{ featured.description }}</p>
              </div>
              <button 
                @click="router.visit(`/blog/${featured.slug}`)"
                class="w-fit mt-8 py-3 px-8 rounded-[500px] bg-gray-800 font-roboto font-medium text-base leading-none tracking-normal text-white border-none cursor-pointer transition-colors duration-300 hover:bg-gray-700"
              >
                Read More
              </button>
            </div>
            <!-- Right: Image -->
            <div class="flex-1">
              <img 
                v-if="featured.image"
                :src="featured.image" 
                :alt="featured.title"
                class="w-full h-full object-cover"
                loading="lazy"
                @error="handleImageError($event)"
              />
              <div v-else class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">
                <span>No Image</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Grid of Articles -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <BlogPostCard
            v-for="blog in blogs.data"
            :key="blog.id"
            :title="blog.title"
            :description="blog.description"
            :image="blog.image"
            :read-time="blog.readTime"
            :date="blog.date"
            :to="`/blog/${blog.slug}`"
          />
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-8">
          <div class="font-roboto font-normal text-sm text-gray-600">
            Items {{ blogs.from }} to {{ blogs.to }} of {{ blogs.total }}
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="blogs.current_page > 1"
              @click="goToPage(blogs.current_page - 1)"
              class="px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-roboto font-normal text-sm"
            >
              ←
            </button>
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="page !== '...' ? goToPage(page) : null"
              :class="[
                'px-4 py-2 rounded font-roboto font-normal text-sm',
                page === blogs.current_page
                  ? 'bg-gray-800 text-white'
                  : page === '...'
                  ? 'bg-white text-gray-500 cursor-default'
                  : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <button
              v-if="blogs.current_page < blogs.last_page"
              @click="goToPage(blogs.current_page + 1)"
              class="px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-roboto font-normal text-sm"
            >
              →
            </button>
            <select
              v-model="perPage"
              @change="applyPerPage"
              class="ml-4 px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 font-roboto font-normal text-sm"
            >
              <option :value="10">Show 10</option>
              <option :value="20">Show 20</option>
              <option :value="50">Show 50</option>
            </select>
          </div>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import BlogPostCard from '@/components/BlogPostCard.vue'

const props = defineProps({
  featured: Object,
  blogs: Object,
})

const perPage = ref(props.blogs.per_page || 20)

const visiblePages = computed(() => {
  const current = props.blogs.current_page
  const last = props.blogs.last_page
  const pages = []
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 3) {
      for (let i = 1; i <= 4; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 2) {
      pages.push(1)
      pages.push('...')
      for (let i = last - 3; i <= last; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    }
  }
  
  return pages
})

const goToPage = (page) => {
  const params = new URLSearchParams(window.location.search)
  params.set('page', page)
  router.visit(`/blogs?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

const applyPerPage = () => {
  const params = new URLSearchParams(window.location.search)
  params.set('per_page', perPage.value)
  params.delete('page') // Reset to page 1
  router.visit(`/blogs?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
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
    placeholder.innerHTML = '<span class="text-sm">No Image</span>'
    event.target.parentElement.appendChild(placeholder)
  }
}
</script>

