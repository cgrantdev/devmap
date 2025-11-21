<template>
  <FrontLayout>
      <!-- Hero Section -->
      <section class="relative w-full h-[600px] overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-900/80 to-blue-800/60">
          <!-- Placeholder for hero image - replace with actual image -->
          <div class="absolute inset-0 bg-blue-900"></div>
        </div>
        <div class="relative max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center">
          <div class="text-white max-w-2xl">
            <h1 class="text-5xl md:text-6xl font-bold mb-4">Hello Hero</h1>
            <p class="text-xl mb-8 text-blue-100">Want your brand to stand out? Advertise with us right here!</p>
            <button class="bg-white text-blue-700 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
              Contact Us to Advertise
            </button>
          </div>
        </div>
        <!-- Carousel Indicators -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex gap-2">
          <div
            v-for="(_, index) in 5"
            :key="index"
            :class="[
              'w-2 h-2 rounded-full transition-all',
              index === 0 ? 'bg-blue-700 w-3' : 'bg-gray-600'
            ]"
          ></div>
        </div>
      </section>

      <!-- Top Vendors Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">Top Vendors</h2>
          <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-8">
            <div
              v-for="vendor in topVendors"
              :key="vendor.id"
              class="bg-white border border-gray-200 rounded-lg p-6 text-center hover:shadow-lg transition-shadow"
            >
              <div class="relative mb-4">
                <div class="w-20 h-20 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-3 relative">
                  <span class="text-blue-700 font-semibold text-sm">{{ vendor.initials }}</span>
                  <span
                    v-if="vendor.badge"
                    class="absolute -top-1 -right-1 text-xl"
                  >{{ vendor.badge }}</span>
                </div>
              </div>
              <p class="text-gray-500 text-xs mb-1">{{ vendor.location }}</p>
              <h3 class="font-semibold text-gray-800 mb-2">{{ vendor.name }}</h3>
              <div class="flex items-center justify-center gap-1 mb-1">
                <span class="text-yellow-400">★★★★★</span>
              </div>
              <p class="text-gray-600 text-sm">{{ vendor.rating }} ({{ vendor.reviews }})</p>
            </div>
          </div>
          <div class="text-center">
            <Link
              href="/vendors"
              class="inline-block px-8 py-3 bg-blue-700 text-white font-semibold rounded-lg hover:bg-blue-800 transition-colors"
            >
              View All Vendors
            </Link>
          </div>
        </div>
      </section>

      <!-- Discover & Research Section -->
      <section class="relative w-full h-[500px] overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-gray-900/80 to-gray-800/60">
          <!-- Placeholder for background image -->
          <div class="absolute inset-0 bg-gray-900"></div>
        </div>
        <div class="relative max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center">
          <div class="text-white max-w-2xl">
            <h2 class="text-5xl md:text-6xl font-bold mb-4">Discover & Research with Confidence</h2>
            <p class="text-xl mb-8 text-gray-200">Behind the scenes, we're enabling custom creations of RUC peptides for advanced lab applications.</p>
            <button class="bg-white text-blue-700 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
              Read Details
            </button>
          </div>
        </div>
      </section>

      <!-- Learn About Peptides Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">Learn About Peptides</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
              v-for="article in peptideArticles"
              :key="article.id"
              class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
            >
              <div class="h-48 bg-amber-100 flex items-center justify-center">
                <!-- Placeholder for bottle image -->
                <div class="text-center">
                  <div class="w-16 h-20 mx-auto bg-amber-300 rounded-t-lg rounded-b-2xl border-2 border-amber-400 mb-2"></div>
                  <p class="text-xs text-amber-800 font-semibold">{{ article.title.split(' ')[0] }}</p>
                </div>
              </div>
              <div class="p-6">
                <h3 class="font-bold text-gray-800 mb-3">{{ article.title }}</h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ article.description }}</p>
                <button class="w-full bg-white border border-blue-700 text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
                  Read Details
                </button>
              </div>
            </div>
          </div>
          <div class="text-center">
            <Link
              href="/education"
              class="inline-block px-8 py-3 bg-blue-700 text-white font-semibold rounded-lg hover:bg-blue-800 transition-colors"
            >
              View All Articles
            </Link>
          </div>
        </div>
      </section>

      <!-- Advance Your Research Section -->
      <section class="relative w-full h-[500px] overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-l from-gray-900/80 to-gray-800/60">
          <!-- Placeholder for background image -->
          <div class="absolute inset-0 bg-gray-900"></div>
        </div>
        <div class="relative max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-end">
          <div class="text-white max-w-2xl text-right">
            <h2 class="text-5xl md:text-6xl font-bold mb-4">Advance Your Research with Trusted Peptides</h2>
            <p class="text-xl mb-8 text-gray-200">Behind the scenes, we're enabling custom combinations of RUC peptides for advanced lab applications.</p>
            <button class="bg-white text-blue-700 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
              Start Research
            </button>
          </div>
        </div>
      </section>

      <!-- Research Insights Section -->
      <section class="py-16 bg-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">Research Insights</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
              v-for="insight in researchInsights"
              :key="insight.id"
              class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
            >
              <div class="h-48 bg-gradient-to-br from-blue-100 to-purple-100 flex items-center justify-center relative">
                <!-- Placeholder for research image -->
                <div class="text-center text-gray-600">
                  <svg class="w-16 h-16 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="absolute bottom-2 left-2 flex gap-3 text-xs text-gray-600">
                  <span>19 Min Read</span>
                  <span>22 Aug 2005</span>
                </div>
              </div>
              <div class="p-6">
                <h3 class="font-bold text-gray-800 mb-3">{{ insight.title }}</h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ insight.description }}</p>
                <button class="w-full bg-white border border-blue-700 text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
                  Read Details
                </button>
              </div>
            </div>
          </div>
          <div class="text-center">
            <button class="inline-block px-8 py-3 bg-blue-700 text-white font-semibold rounded-lg hover:bg-blue-800 transition-colors">
              More Details
            </button>
          </div>
        </div>
      </section>
  </FrontLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'

const topVendors = ref([
  { id: 1, name: 'Behemoth Labz', location: 'Beach Valley, California', initials: 'BL', rating: '5.00', reviews: 345, badge: '🍃' },
  { id: 2, name: 'Peptide Sciences', location: 'San Diego, California', initials: 'PS', rating: '5.00', reviews: 289, badge: '🔥' },
  { id: 3, name: 'Bella Corner', location: 'Los Angeles, California', initials: 'BC', rating: '5.00', reviews: 412 },
  { id: 4, name: 'Chemyo', location: 'Austin, Texas', initials: 'CH', rating: '5.00', reviews: 356 },
  { id: 5, name: 'Organic Food', location: 'Seattle, Washington', initials: 'OF', rating: '5.00', reviews: 298 },
  { id: 6, name: 'Core Peptides', location: 'Miami, Florida', initials: 'CP', rating: '5.00', reviews: 324 },
  { id: 7, name: 'AA Health', location: 'Chicago, Illinois', initials: 'AA', rating: '5.00', reviews: 267 },
  { id: 8, name: 'Wholeness in Health', location: 'Denver, Colorado', initials: 'WH', rating: '5.00', reviews: 389 },
  { id: 9, name: 'Health Net', location: 'Boston, Massachusetts', initials: 'HN', rating: '5.00', reviews: 445 },
  { id: 10, name: 'Dental Plus', location: 'Portland, Oregon', initials: 'DP', rating: '5.00', reviews: 312 },
])

const peptideArticles = ref([
  {
    id: 1,
    title: 'What is BPC-157?',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 2,
    title: 'Safe Handling',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 3,
    title: 'Stacking with Others',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 4,
    title: 'Dosage Guidelines',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 5,
    title: 'Storage Methods',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 6,
    title: 'Research Applications',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 7,
    title: 'Safety Protocols',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 8,
    title: 'Quality Standards',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
])

const researchInsights = ref([
  {
    id: 1,
    title: 'Safe Handling',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 2,
    title: 'Research Guidelines',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 3,
    title: 'Benefits of BPC-157',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 4,
    title: 'BPC-157 in Sport Medicine',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 5,
    title: 'Safety and Usage',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 6,
    title: 'Laboratory Protocols',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 7,
    title: 'Advanced Applications',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
  {
    id: 8,
    title: 'Research Methodologies',
    description: 'BPC-157 is a lab-made peptide studied for its healing properties. It may support tissue repair, reduce inflammation...'
  },
])
</script>

