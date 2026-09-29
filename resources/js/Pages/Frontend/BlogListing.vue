<template>
  <ModernLayout>
    <!-- Research Insights Section -->
    <section class="pt-8 pb-16">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10">
        <div class="border-b border-[color:var(--color-hairline)] pb-10 mb-12">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-3">Research & education</div>
          <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)]">Research Insights</h1>
        </div>

        <!-- Newest posts first. No featured carve-out. -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <BlogPostCard
            v-for="blog in blogs.data"
            :key="blog.id"
            :title="blog.title"
            :description="blog.outline || blog.description"
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
  </ModernLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import BlogPostCard from '@/components/BlogPostCard.vue'

const props = defineProps({
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
</script>

