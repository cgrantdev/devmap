<template>
  <ModernLayout>
    <!-- Header Section with Dark Blue Background -->
    <section class="bg-gradient-to-br from-slate-700 to-slate-600 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Title and Description -->
        <div class="flex items-center gap-3 mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag w-12 h-12" aria-hidden="true">
            <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path>
            <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle>
          </svg>
          <h1 class="text-5xl">Limited Time Discounts</h1>
        </div>
        <p class="text-xl text-slate-100 mb-4 max-w-3xl">
          Save up to 25% on premium research peptides from verified vendors. All discounts verified and updated regularly.
        </p>
        <div class="flex items-center gap-2 text-slate-200">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-5 h-5" aria-hidden="true">
            <path d="M12 6v6l4 2"></path>
            <circle cx="12" cy="12" r="10"></circle>
          </svg>
          <span class="text-sm">Verified discounts • Use code PMAP at checkout</span>
        </div>
      </div>
    </section>

    <!-- Informational Banner -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="mb-8 bg-slate-50 border border-slate-200 rounded-lg p-6">
        <div class="flex items-start gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert w-5 h-5 text-slate-600 flex-shrink-0 mt-0.5" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <div>
            <h3 class="text-slate-900 mb-1">Verified Exclusive Discounts</h3>
            <p class="text-sm text-slate-700">
              All discount codes and pricing are verified by our team. Use coupon code 
              <span class="font-mono bg-slate-700 text-white px-2 py-0.5 rounded">PMAP</span>
              at checkout for the displayed discount percentage on your entire order.
            </p>
          </div>
        </div>
      </div>
      
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
          <p class="text-gray-600">{{ dealsCount }} deals available</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-sm text-gray-600">Sort by:</span>
              <div class="flex gap-2">
                <button
                  @click="handleSort('best_discount')"
                  :class="[
                    'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                    sortBy === 'best_discount'
                      ? 'bg-gray-700 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  ]"
                >
                  Best Discount
                </button>
                <button
                  @click="handleSort('top_rated')"
                  :class="[
                    'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                    sortBy === 'top_rated'
                      ? 'bg-gray-700 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  ]"
                >
                  Top Rated
                </button>
                <button
                  @click="handleSort('a_z')"
                  :class="[
                    'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                    sortBy === 'a_z'
                      ? 'bg-gray-700 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  ]"
                >
                  A-Z
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Deals and Sorting Section -->

    <!-- Discount Cards Grid -->
    <section class="bg-gray-50 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div v-if="deals.length > 0" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <DealCard
            v-for="deal in deals"
            :key="deal.id"
            :id="deal.id"
            :name="deal.name"
            :slug="deal.slug"
            :logo="deal.logo"
            :initials="deal.initials"
            :rating="deal.rating"
            :reviews="deal.reviews"
            :discount="deal.discount"
            :code="deal.code"
            :description="deal.description"
          />
        </div>
        <div v-else class="text-center py-12">
          <p class="text-gray-500">No discount deals available at the moment.</p>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import DealCard from '@/components/DealCard.vue'

const props = defineProps({
  deals: {
    type: Array,
    default: () => []
  },
  sortBy: {
    type: String,
    default: 'best_discount'
  },
  dealsCount: {
    type: Number,
    default: 0
  }
})

const sortBy = ref(props.sortBy)

const handleSort = (sort) => {
  sortBy.value = sort
  router.get('/deals', { sort }, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>
