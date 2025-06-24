<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Admin Warning Banner for Inactive Vendors -->
    <div v-if="isAdmin && settings?.status === 0" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm">
            <strong>Admin Preview:</strong> This vendor is currently inactive and not visible to the public.
          </p>
        </div>
      </div>
    </div>

    <!-- Banner -->
    <div v-if="settings?.banner" class="w-full h-48 md:h-64 bg-gray-200 flex items-center justify-center overflow-hidden">
      <img :src="getImageUrl(settings.banner)" alt="Banner" class="object-cover w-full h-full" />
    </div>
    <div v-else class="w-full h-48 md:h-64 bg-gray-200 flex items-center justify-center text-gray-400 text-2xl">No Banner</div>

    <!-- Company Info Row -->
    <div class="flex flex-col md:flex-row items-center justify-between bg-white shadow p-6 -mt-12 mx-4 rounded-lg relative z-10">
      <!-- Logo -->
      <div class="flex-shrink-0 mb-4 md:mb-0">
        <img v-if="settings?.logo" :src="getImageUrl(settings.logo)" alt="Logo" class="h-24 w-24 object-contain rounded border bg-white" />
        <div v-else class="h-24 w-24 flex items-center justify-center bg-gray-100 rounded border text-gray-400">No Logo</div>
      </div>
      <!-- Center: Name & Description -->
      <div class="flex-1 text-center md:text-left px-4">
        <h1 class="text-3xl font-bold mb-2">{{ settings?.company_name || vendor.name }}</h1>
        <p class="text-gray-600">{{ settings?.company_detail || 'No description provided.' }}</p>
      </div>
      <!-- Right: Contact Info -->
      <div class="flex flex-col items-end space-y-1 text-sm">
        <div v-if="settings?.url"><span class="font-semibold">URL:</span> <a :href="settings.url" class="text-blue-600 hover:underline" target="_blank">{{ settings.url }}</a></div>
        <div v-if="settings?.contact_email"><span class="font-semibold">Email:</span> <a :href="`mailto:${settings.contact_email}`" class="text-blue-600 hover:underline">{{ settings.contact_email }}</a></div>
        <div v-if="settings?.phone_number"><span class="font-semibold">Phone:</span> {{ settings.phone_number }}</div>
      </div>
    </div>

    <!-- Main Content: Filters & Items -->
    <div class="flex flex-col md:flex-row gap-6 mt-8 px-4 max-w-6xl mx-auto">
      <!-- Filters (dummy) -->
      <aside class="w-full md:w-64 bg-white rounded-lg shadow p-4 mb-4 md:mb-0">
        <h2 class="text-lg font-semibold mb-2">Filters</h2>
        <div class="space-y-2">
          <div><input type="checkbox" id="filter1" /> <label for="filter1">Filter 1</label></div>
          <div><input type="checkbox" id="filter2" /> <label for="filter2">Filter 2</label></div>
          <div><input type="checkbox" id="filter3" /> <label for="filter3">Filter 3</label></div>
        </div>
      </aside>
      <!-- Items (dummy) -->
      <section class="flex-1 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <div v-for="item in items" :key="item.id" class="bg-white rounded-lg shadow p-4 flex flex-col items-center">
          <img :src="item.image" alt="Item Image" class="h-32 w-32 object-cover rounded mb-2" />
          <h3 class="font-semibold text-lg mb-1">{{ item.name }}</h3>
          <div class="text-blue-600 font-bold mb-2">${{ item.price }}</div>
          <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">View</button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  settings: Object,
  vendor: Object,
  items: Array,
  isAdmin: {
    type: Boolean,
    default: false
  }
})

function getImageUrl(path) {
  if (!path) return null
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) return path
  return `/storage/${path}`
}
</script> 