<template>
    <Layout>
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Products</h1>
          <p class="text-gray-600 mt-2">Manage your vendor product catalog.</p>
        </div>
  
        <Link
          href="/vendor/import"
          class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition"
        >
          Import / Update Products
        </Link>
      </div>
  
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-sm text-gray-500">Total Products</div>
          <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.totalProducts || 0 }}</div>
        </div>
  
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-sm text-gray-500">Active Products</div>
          <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.activeProducts || 0 }}</div>
        </div>
  
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-sm text-gray-500">Hidden Products</div>
          <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.hiddenProducts || 0 }}</div>
        </div>
  
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-sm text-gray-500">Average Rating</div>
          <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.averageRating || '0.0' }}</div>
        </div>
      </div>
  
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900">All Products</h2>
          <p class="text-sm text-gray-600 mt-1">A clean list of all products belonging to this vendor.</p>
        </div>
  
        <div v-if="products.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 p-6">
          <div
            v-for="product in products"
            :key="product.id"
            class="rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition bg-white"
          >
            <div class="aspect-[4/3] bg-gray-100">
              <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="w-full h-full object-cover"
                loading="lazy"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-sm text-gray-400">
                No image
              </div>
            </div>
  
            <div class="p-5">
              <div class="flex items-start justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-900 line-clamp-2">
                  {{ product.name }}
                </h3>
  
                <span
                  class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="product.hidden ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                >
                  {{ product.hidden ? 'Hidden' : (product.status || 'Active') }}
                </span>
              </div>
  
              <div class="mt-3 flex items-center gap-2">
                <span class="text-lg font-bold text-gray-900">{{ formatPrice(product.price) }}</span>
                <span v-if="product.original_price" class="text-sm text-gray-400 line-through">
                  {{ formatPrice(product.original_price) }}
                </span>
              </div>
  
              <div class="mt-3 flex items-center gap-3 text-sm text-gray-600">
                <span>⭐ {{ product.rating_average || '0.0' }}</span>
                <span>•</span>
                <span>{{ product.rating_count || 0 }} reviews</span>
              </div>
  
              <div class="mt-5 flex items-center gap-3">
                <a
                  v-if="product.product_url"
                  :href="product.product_url"
                  target="_blank"
                  class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                  View Product
                </a>
  
                <Link
                  href="/vendor/import"
                  class="inline-flex items-center px-3 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-black"
                >
                  Edit Catalog
                </Link>
              </div>
            </div>
          </div>
        </div>
  
        <div v-else class="px-6 py-12 text-center">
          <div class="text-lg font-semibold text-gray-900">No products found</div>
          <p class="mt-2 text-sm text-gray-600">Start by importing products into your vendor catalog.</p>
  
          <Link
            href="/vendor/import"
            class="inline-flex items-center mt-5 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700"
          >
            Import Products
          </Link>
        </div>
      </div>
    </Layout>
  </template>
  
  <script setup>
  import { Link } from '@inertiajs/vue3'
  import Layout from './Layout.vue'
  
  defineProps({
    stats: {
      type: Object,
      default: () => ({
        totalProducts: 0,
        activeProducts: 0,
        hiddenProducts: 0,
        averageRating: '0.0'
      })
    },
    products: {
      type: Array,
      default: () => []
    }
  })
  
  function formatPrice(value) {
    const number = Number(value)
  
    if (Number.isNaN(number) || number <= 0) {
      return '$0.00'
    }
  
    return `$${number.toFixed(2)}`
  }
  </script>