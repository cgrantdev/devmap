<template>
  <FrontLayout>
    <section class="py-16 bg-white">
      <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-8">{{ page?.title || 'Page' }}</h1>
        
        <!-- Dynamic content from database -->
        <div v-if="page?.content" class="page-content prose prose-lg max-w-none text-gray-700">
          <div v-html="page.content"></div>
        </div>
        
        <!-- Fallback content if page doesn't exist -->
        <div v-else class="prose prose-lg max-w-none text-gray-700">
          <p class="text-gray-600">No content available for this page.</p>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import FrontLayout from '../Layouts/FrontLayout.vue'

defineProps({
  page: Object
})
</script>

<style scoped>
.page-content :deep(h1),
.page-content :deep(h2),
.page-content :deep(h3),
.page-content :deep(h4),
.page-content :deep(h5),
.page-content :deep(h6) {
  font-weight: 600;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
  color: #1f2937;
}

.page-content :deep(h1) {
  font-size: 2.25em;
}

.page-content :deep(h2) {
  font-size: 1.875em;
}

.page-content :deep(h3) {
  font-size: 1.5em;
}

.page-content :deep(h4) {
  font-size: 1.25em;
}

.page-content :deep(h5) {
  font-size: 1.125em;
}

.page-content :deep(h6) {
  font-size: 1em;
}

.page-content :deep(p) {
  margin-bottom: 1em;
  line-height: 1.75;
  color: #4b5563;
}

.page-content :deep(ul),
.page-content :deep(ol) {
  margin: 1em 0;
  padding-left: 2em;
}

.page-content :deep(li) {
  margin: 0.5em 0;
  line-height: 1.75;
}

.page-content :deep(a) {
  color: #2563eb;
  text-decoration: underline;
}

.page-content :deep(a:hover) {
  color: #1d4ed8;
}

.page-content :deep(strong) {
  font-weight: 600;
  color: #1f2937;
}

.page-content :deep(em) {
  font-style: italic;
}

.page-content :deep(blockquote) {
  border-left: 4px solid #e5e7eb;
  padding-left: 1em;
  margin: 1.5em 0;
  color: #6b7280;
  font-style: italic;
}

.page-content :deep(img) {
  max-width: 100%;
  height: auto;
  margin: 1.5em 0;
  border-radius: 0.5rem;
}

.page-content :deep(code) {
  background-color: #f3f4f6;
  padding: 0.125em 0.375em;
  border-radius: 0.25rem;
  font-size: 0.875em;
  font-family: monospace;
}

.page-content :deep(pre) {
  background-color: #1f2937;
  color: #f9fafb;
  padding: 1em;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 1.5em 0;
}

.page-content :deep(pre code) {
  background-color: transparent;
  padding: 0;
  color: inherit;
}

.page-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1.5em 0;
}

.page-content :deep(th),
.page-content :deep(td) {
  border: 1px solid #e5e7eb;
  padding: 0.75em;
  text-align: left;
}

.page-content :deep(th) {
  background-color: #f9fafb;
  font-weight: 600;
}

.page-content :deep(hr) {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 2em 0;
}
</style>
