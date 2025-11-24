<template>
  <AdminLayout>
    <div class="mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">All Products</h1>
      </div>
      <div class="bg-white rounded-lg shadow p-8">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50">
                <td class="px-4 py-2">
                  <img v-if="product.image_url" :src="product.image_url" alt="Product" class="h-12 w-12 object-cover rounded" loading="lazy" />
                  <span v-else class="text-gray-400 text-xs">No Image</span>
                </td>
                <td class="px-4 py-2">{{ product.name }}</td>
                <td class="px-4 py-2">{{ product.price }}</td>
                <td class="px-4 py-2">{{ product.vendor_name }}</td>
                <td class="px-4 py-2">
                  <a :href="`/product/${product.id}/${product.slug}`" target="_blank" class="text-blue-600 hover:underline">View</a>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="products.data.length === 0" class="p-4 text-center text-gray-500">No products found.</div>
        </div>
        <!-- Pagination -->
        <div v-if="products.meta && products.meta.last_page > 1" class="mt-4 flex justify-center space-x-2">
          <button
            v-for="page in products.meta.last_page"
            :key="page"
            @click="goToPage(page)"
            :class="['px-3 py-1 rounded', page === products.meta.current_page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700']"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { usePage, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  products: Object
})

function goToPage(page) {
  router.get('/admin/products', { page }, { preserveState: true, preserveScroll: true })
}
</script> 