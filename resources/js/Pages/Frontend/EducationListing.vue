<template>
  <FrontLayout>
    <!-- Education Posts Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-hv-muse font-normal text-5xl leading-normal tracking-normal text-gray-800 text-center mb-12 p-0 w-full">Learn About Peptides</h2>
        
        <!-- Grid of Education Posts -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 gap-x-[20px] gap-y-[80px]">
          <div
            v-for="post in posts.data"
            :key="post.id"
            class="bg-white rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 hover:shadow-lg cursor-pointer"
            @click="router.visit(`/education/${post.slug}`)"
          >
            <div class="w-full aspect-square bg-gray-100 flex items-center justify-center p-6 overflow-hidden">
              <img 
                v-if="post.image"
                :src="`/images/peptides/${post.image}`" 
                :alt="post.title"
                class="w-full h-full object-contain object-center"
                loading="lazy"
                @error="handleImageError($event)"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <span class="text-sm">No Image</span>
              </div>
            </div>
            <div class="p-6 flex flex-col gap-4 flex-1">
              <h3 class="text-center font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0">{{ post.title }}</h3>
              <p class="font-roboto font-normal text-sm leading-relaxed text-gray-500 text-center m-0 flex-1">{{ post.description }}</p>
              <button class="w-full py-3 px-11 rounded-[500px] bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 cursor-pointer transition-colors duration-300 mt-auto hover:bg-gray-300">
                Read Details
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-8">
          <div class="font-roboto font-normal text-sm text-gray-600">
            Items {{ posts.from }} to {{ posts.to }} of {{ posts.total }}
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="posts.current_page > 1"
              @click="goToPage(posts.current_page - 1)"
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
                page === posts.current_page
                  ? 'bg-gray-800 text-white'
                  : page === '...'
                  ? 'bg-white text-gray-500 cursor-default'
                  : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <button
              v-if="posts.current_page < posts.last_page"
              @click="goToPage(posts.current_page + 1)"
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

const props = defineProps({
  posts: Object,
})

const perPage = ref(props.posts.per_page || 20)

const visiblePages = computed(() => {
  const current = props.posts.current_page
  const last = props.posts.last_page
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
  router.visit(`/education?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

const applyPerPage = () => {
  const params = new URLSearchParams(window.location.search)
  params.set('per_page', perPage.value)
  params.delete('page')
  router.visit(`/education?${params.toString()}`, {
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

