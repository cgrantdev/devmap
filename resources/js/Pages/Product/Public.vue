<template>
  <div class="min-h-screen bg-white">
    <!-- Banner/Header -->
    <div class="w-full h-48 md:h-64 bg-gray-200 flex items-center justify-center relative">
      <img v-if="vendor.banner_url" :src="vendor.banner_url" alt="Shop Banner" class="absolute inset-0 w-full h-full object-cover" loading="lazy" />
      <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-2xl font-bold">No Banner</div>
    </div>
    <div class="w-full max-w-5xl mx-auto px-2 md:px-0 -mt-16 md:-mt-24 relative z-10">
      <div class="flex flex-col md:flex-row gap-8 bg-white rounded-lg shadow-lg p-8 relative">
        <!-- Shop Button -->
        <a
          v-if="product.product_url"
          :href="`/go/${product.id}`"
          target="_blank"
          rel="noopener noreferrer nofollow sponsored"
          class="absolute top-8 right-8 px-5 py-2 bg-gray-900 text-white rounded hover:bg-gray-700 font-semibold shadow"
        >
          Shop {{ product.name }}
        </a>
        <!-- Left: Product Image -->
        <div class="flex-1 flex items-start justify-center">
          <img
            v-if="product.image_url"
            :src="product.image_url"
            :alt="product.name"
            class="rounded shadow-lg max-w-full max-h-[420px] object-contain bg-gray-50"
            loading="lazy"
            @error="handleImageError($event)"
          />
          <div v-else class="w-96 h-96 flex items-center justify-center bg-gray-200 rounded text-gray-400">No Image</div>
        </div>
        <!-- Right: Product Info -->
        <div class="flex-1 flex flex-col gap-4">
          <h1 class="text-3xl font-bold">{{ product.name }}</h1>
          <div class="text-2xl text-gray-800 font-semibold mb-2">${{ product.price }}</div>
          <div class="text-xs text-gray-500 mb-2">
            Pay in 4 interest-free payments of ${{ (product.price/4).toFixed(2) }} with <span class="font-semibold">PayPal</span>. <a href="#" class="underline">Learn more</a>
          </div>
          <!-- Highlights -->
          <ul class="list-disc pl-5 text-gray-700 mb-2 space-y-1">
            <li>Free UPS Ground Shipping on orders over $175.</li>
            <li>All our products are third party tested for quality.</li>
            <li><a href="#" class="underline">Certificates of Authentication available below.</a></li>
          </ul>
          <!-- Shipping Info -->
          <div class="bg-blue-900 text-white rounded p-4 font-semibold text-center">
            FREE SHIPPING ON ALL ORDERS OVER $175<br />
            <span class="font-normal text-sm">Receive free shipping on any order total over $175 USD</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  product: Object,
  vendor: Object
})

const handleImageError = (event) => {
  // Prevent infinite loop - stop trying to load images if we've already failed
  if (event.target.dataset.failed) {
    return
  }
  // Mark as failed to prevent retry
  event.target.dataset.failed = 'true'
  // Hide the broken image - the v-else div will show "No Image"
  event.target.style.display = 'none'
}
</script> 