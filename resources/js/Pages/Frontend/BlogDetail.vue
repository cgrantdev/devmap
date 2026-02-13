<template>
  <FrontLayout>
    <!-- Blog Detail Section -->
    <div class="min-h-screen bg-gray-50">
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <!-- Back Button -->
          <button 
            @click="router.visit('/news')"
            class="text-gray-600 hover:text-gray-900 flex items-center gap-2 mb-6 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-4 h-4" aria-hidden="true">
              <path d="m12 19-7-7 7-7"></path>
              <path d="M19 12H5"></path>
            </svg>
            Back to Knowledge Center
          </button>
          <div class="flex items-center gap-2 mb-4">
            <span 
              v-if="blog.categoryTag"
              :class="[
                'text-xs px-3 py-1 rounded-full',
                getCategoryTagClass(blog.categoryTag)
              ]"
            >
              {{ blog.categoryTag }}
            </span>
            <span 
              v-if="blog.is_featured"
              class="text-xs px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 flex items-center gap-1.5"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#facc15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" aria-hidden="true">
                  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
              </svg>  
              Featured
            </span>
          </div>
          <h1 class="text-4xl text-gray-900 mb-4">{{ blog.title }}</h1>
          <div class="flex items-center gap-6 text-gray-600 mb-6">
            <div v-if="blog.author_name" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-4 h-4" aria-hidden="true">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              <span>Dr. {{ blog.author_name }}
                <span v-if="blog.author_job" class="text-gray-500"> • {{ blog.author_job }}</span>
              </span>
            </div>
            <div v-else class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-4 h-4" aria-hidden="true">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              <span>PeptideMap Editorial</span>
            </div>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-4 h-4" aria-hidden="true">
                <path d="M8 2v4"></path>
                <path d="M16 2v4"></path>
                <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                <path d="M3 10h18"></path>
              </svg>
              <span>{{ blog.date }}</span>
            </div>
            <div class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-4 h-4" aria-hidden="true">
                <path d="M12 6v6l4 2"></path>
                <circle cx="12" cy="12" r="10"></circle>
              </svg>
              <span>{{ blog.readTime }}</span>
            </div>            
          </div>
          <div class="flex items-center gap-3">
            <button class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share2 lucide-share-2 w-4 h-4" aria-hidden="true">
                <circle cx="18" cy="5" r="3"></circle>
                <circle cx="6" cy="12" r="3"></circle>
                <circle cx="18" cy="19" r="3"></circle>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
              </svg>
              Share
            </button>
            <button class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bookmark w-4 h-4" aria-hidden="true">
                <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
              </svg>
              Save
            </button>
          </div>
        </div>
      </div>

      <div v-if="blog.image" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <img 
          :src="blog.image" 
          :alt="blog.title"
          class="w-full h-96 object-cover rounded-lg shadow-lg"
          loading="lazy"
          @error="handleImageError($event)"
        />
      </div>
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm p-8">
          <p v-if="blog.outline" class="text-xl text-gray-700 mb-8 pb-8 border-b border-gray-200 italic">
            {{ blog.outline}}
          </p>
          <div class="prose prose-lg max-w-none">
            <div v-if="blog.description" class="mb-8">
              <p class="text-gray-700 whitespace-pre-line">{{ blog.description }}</p>
            </div>
            
            <div v-if="blog.introduction" class="mb-8">
              <h2 class="text-2xl text-gray-900 mt-8 mb-4">Introduction</h2>
              <p class="text-gray-700 mb-4 whitespace-pre-line">{{ blog.introduction }}</p>
            </div>
            
            <div v-if="blog.key_points && blog.key_points.length > 0" class="mb-8">
              <h2 class="text-2xl text-gray-900 mt-8 mb-4">Key Points</h2>
              <ul class="list-disc pl-6 mb-6 text-gray-700 space-y-2">
                <li v-for="(point, index) in blog.key_points" :key="index">{{ point }}</li>
              </ul>
            </div>
            
            <div v-if="blog.detailed_analysis" class="mb-8">
              <h2 class="text-2xl text-gray-900 mt-8 mb-4">Detailed Analysis</h2>
              <p class="text-gray-700 mb-4 whitespace-pre-line">{{ blog.detailed_analysis }}</p>
            </div>
            
            <div v-if="blog.conclusion" class="mb-8">
              <h2 class="text-2xl text-gray-900 mt-8 mb-4">Conclusion</h2>
              <p class="text-gray-700 mb-4 whitespace-pre-line">{{ blog.conclusion }}</p>
            </div>
            
            <!-- Fallback to content if new fields are not available -->
            <div v-if="!blog.introduction && !blog.detailed_analysis && blog.content" class="blog-content-html" v-html="blog.content"></div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
              <p class="text-sm text-blue-900">
                <strong>Note:</strong>
                This article is for informational purposes only. Always consult with a qualified healthcare professional before starting any new supplement or peptide regimen.
              </p>
            </div>
          </div>
        </div>
        <div v-if="blog.tags && blog.tags.length > 0" class="mt-8 pt-8 border-t border-gray-200">
          <h3 class="text-sm text-gray-500 mb-3">Tags:</h3>
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="(tag, index) in blog.tags" 
              :key="index"
              class="text-sm bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 cursor-pointer transition-colors"
            >
              {{ tag }}
            </span>
          </div>
        </div>
        <!-- Related Articles -->
        <div v-if="related && related.length > 0" class="mt-12">
          <h2 class="text-2xl text-gray-900 mb-6">Related Articles</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <BlogPostCard
              v-for="item in related"
              :key="item.id"
              :title="item.title"
              :description="item.outline"
              :image="item.image"
              :read-time="item.readTime"
              :date="item.date"
              :to="`/blog/${item.slug}`"
            />
          </div>
        </div>
      </div>

    </div>
  </FrontLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import BlogPostCard from '@/components/BlogPostCard.vue'

const props = defineProps({
  blog: Object,
  related: Array,
})

const getCategoryTagClass = (category) => {
  const classes = {
    'Regulation': 'bg-red-100 text-red-800',
    'Research': 'bg-blue-100 text-blue-800',
    'Industry': 'bg-green-100 text-green-800',
    'Guides': 'bg-purple-100 text-purple-800',
    'Community': 'bg-yellow-100 text-yellow-800',
  }
  return classes[category] || 'bg-gray-100 text-gray-800'
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
}

</script>

<style scoped>
.blog-content-html {
  font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  font-size: 17px;
  line-height: 1.6;
  color: #374151;
}

.blog-content-html :deep(p) {
  margin: 0 0 1em 0;
  font-size: 17px;
  line-height: 1.6;
  color: #374151;
}

.blog-content-html :deep(h1),
.blog-content-html :deep(h2),
.blog-content-html :deep(h3),
.blog-content-html :deep(h4),
.blog-content-html :deep(h5),
.blog-content-html :deep(h6) {
  font-family: 'HV Muse', serif;
  font-weight: normal;
  margin: 1.5em 0 0.75em 0;
  color: #1F2937;
  line-height: 1.3;
}

.blog-content-html :deep(h1) {
  font-size: 36px;
}

.blog-content-html :deep(h2) {
  font-size: 30px;
}

.blog-content-html :deep(h3) {
  font-size: 24px;
}

.blog-content-html :deep(h4) {
  font-size: 20px;
}

.blog-content-html :deep(h5) {
  font-size: 18px;
}

.blog-content-html :deep(h6) {
  font-size: 17px;
}

.blog-content-html :deep(ul),
.blog-content-html :deep(ol) {
  margin: 1em 0;
  padding-left: 2em;
}

.blog-content-html :deep(li) {
  margin: 0.5em 0;
  line-height: 1.6;
}

.blog-content-html :deep(strong),
.blog-content-html :deep(b) {
  font-weight: 600;
  color: #1F2937;
}

.blog-content-html :deep(em),
.blog-content-html :deep(i) {
  font-style: italic;
}

.blog-content-html :deep(u) {
  text-decoration: underline;
}

.blog-content-html :deep(s),
.blog-content-html :deep(strike) {
  text-decoration: line-through;
}

.blog-content-html :deep(a) {
  color: #2563eb;
  text-decoration: underline;
  transition: color 0.2s;
}

.blog-content-html :deep(a:hover) {
  color: #1d4ed8;
}

.blog-content-html :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  margin: 1.5em 0;
}

.blog-content-html :deep(blockquote) {
  border-left: 4px solid #E5E7EB;
  padding-left: 1.5em;
  margin: 1.5em 0;
  font-style: italic;
  color: #6B7280;
}

.blog-content-html :deep(code) {
  background-color: #F3F4F6;
  padding: 0.2em 0.4em;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 0.9em;
}

.blog-content-html :deep(pre) {
  background-color: #F3F4F6;
  padding: 1em;
  border-radius: 8px;
  overflow-x: auto;
  margin: 1.5em 0;
}

.blog-content-html :deep(pre code) {
  background-color: transparent;
  padding: 0;
}

.blog-content-html :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1.5em 0;
}

.blog-content-html :deep(table th),
.blog-content-html :deep(table td) {
  border: 1px solid #E5E7EB;
  padding: 0.75em;
  text-align: left;
}

.blog-content-html :deep(table th) {
  background-color: #F9FAFB;
  font-weight: 600;
}

.blog-content-html :deep(hr) {
  border: none;
  border-top: 1px solid #E5E7EB;
  margin: 2em 0;
}
</style>

