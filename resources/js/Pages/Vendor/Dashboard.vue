<template>
  <Layout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Vendor Dashboard</h1>
      <p class="text-gray-600 mt-2">Welcome back, {{ $page.props.auth.user.name }}!</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <Link href="/vendor/storefront-analytics" class="bg-white rounded-lg border border-gray-200 p-6 hover:border-blue-500 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
          <div class="bg-blue-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-store w-6 h-6 text-white">
              <path d="M15 21v-5a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v5"></path>
              <path d="M17.774 10.31a1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.451 0 1.12 1.12 0 0 0-1.548 0 2.5 2.5 0 0 1-3.452 0 1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.77-3.248l2.889-4.184A2 2 0 0 1 7 2h10a2 2 0 0 1 1.653.873l2.895 4.192a2.5 2.5 0 0 1-3.774 3.244"></path>
              <path d="M4 10.95V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.05"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Storefront Analytics</h3>
            <p class="text-sm text-gray-600">Track your storefront performance</p>
          </div>
        </div>
      </Link>

      <Link href="/vendor/advertisement-analytics" class="bg-white rounded-lg border border-gray-200 p-6 hover:border-blue-500 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
          <div class="bg-green-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone w-6 h-6 text-white">
              <path d="m3 11 18-5v12L3 14v-3z"></path>
              <path d="m11.6 16.8 3.5 2.7 3.5-2.7"></path>
              <path d="m11.6 16.8 3.5 2.7v-6.2l-3.5-2.7"></path>
              <path d="m8.1 10.7 3.5 2.7 3.5-2.7"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Advertisement Analytics</h3>
            <p class="text-sm text-gray-600">Monitor ad performance</p>
          </div>
        </div>
      </Link>

      <Link href="/vendor/products" class="bg-white rounded-lg border border-gray-200 p-6 hover:border-blue-500 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
          <div class="bg-purple-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-white">
              <path d="M6 2l3 6"></path>
              <path d="M18 2l-3 6"></path>
              <path d="M3 7h18"></path>
              <path d="M4 7l1 13a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2l1-13"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Products</h3>
            <p class="text-sm text-gray-600">View and manage your catalog</p>
          </div>
        </div>
      </Link>

      <Link href="/vendor/reviews" class="bg-white rounded-lg border border-gray-200 p-6 hover:border-blue-500 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
          <div class="bg-yellow-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square w-6 h-6 text-white">
              <path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"></path>
                </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Reviews</h3>
            <p class="text-sm text-gray-600">Manage customer reviews</p>
          </div>
        </div>
      </Link>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-8">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-sm text-gray-500">Total Products</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.totalProducts || 0 }}</div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-sm text-gray-500">Active Products</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.activeProducts || 0 }}</div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-sm text-gray-500">Storefront Views</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.totalViews || 0 }}</div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-sm text-gray-500">Total Reviews</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.totalReviews || 0 }}</div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-sm text-gray-500">Average Rating</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.averageRating || '0.0' }}</div>
      </div>
    </div>

    <!-- Recent Products -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
        <div>
          <h2 class="text-xl font-semibold text-gray-900">Recent Products</h2>
          <p class="text-sm text-gray-600 mt-1">Quick preview of your latest products</p>
        </div>

        <Link
          href="/vendor/products"
          class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition"
        >
          View All Products
        </Link>
      </div>

      <div v-if="recentProducts.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 p-6">
        <div
          v-for="product in recentProducts"
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
        <div class="text-lg font-semibold text-gray-900">No products yet</div>
        <p class="mt-2 text-sm text-gray-600">Import or add products so they appear here.</p>

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

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalProducts: 0,
      activeProducts: 0,
      totalViews: 0,
      totalReviews: 0,
      averageRating: '0.0',
      flaggedReviews: 0
    })
  },
  recentProducts: {
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
