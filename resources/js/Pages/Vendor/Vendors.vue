<template>
  <Header />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50 py-10">
    <div class="max-w-7xl mx-auto px-4">
      <h1 class="text-4xl font-extrabold text-center text-gray-800 mb-10 drop-shadow">Our Trusted Vendors</h1>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        <div
          v-for="vendor in vendors"
          :key="vendor.id"
          class="group bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-shadow duration-300 relative border border-gray-100"
        >
          <div class="h-32 w-full bg-gray-200 flex items-center justify-center overflow-hidden">
            <img v-if="vendor.banner" :src="vendor.banner" alt="Banner" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300" />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-2xl">No Banner</div>
          </div>
          <div class="flex flex-col items-center -mt-10 pb-6 px-4">
            <div class="h-20 w-20 rounded-full border-4 border-white bg-white shadow -mb-2 flex items-center justify-center overflow-hidden">
              <img v-if="vendor.logo" :src="vendor.logo" alt="Logo" class="object-contain h-16 w-16" />
              <div v-else class="h-16 w-16 flex items-center justify-center text-gray-400 text-3xl font-bold">{{ vendor.company_name.charAt(0) }}</div>
            </div>
            <div class="mt-4 text-center">
              <h2 class="text-xl font-semibold text-gray-800 group-hover:text-blue-700 transition-colors">{{ vendor.company_name }}</h2>
              <p class="text-gray-500 text-sm mt-1">@{{ vendor.name }}</p>
            </div>
            <a
              class="mt-4 px-6 py-2 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-full font-semibold shadow hover:from-blue-600 hover:to-purple-600 transition-colors"
              :href="`/shop/${vendor.slug}`"
              target="_blank"
              rel="noopener noreferrer"
              @click.stop
            >
              View Shop
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Header from '../Header.vue'
import { defineProps } from 'vue'

const props = defineProps({
  vendors: Array
})
</script>

<style scoped>
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}
</style> 