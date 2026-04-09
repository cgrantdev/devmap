<template>
  <ModernLayout>
  <div class="min-h-screen">
    <!-- Admin Warning Banner for Inactive Vendors -->
    <div v-if="(isAdmin || isOwnPage) && settings?.status === 0" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm">
            <strong v-if="isAdmin">Admin Preview:</strong>
            <strong v-else>Your Page Preview:</strong>
            This vendor is currently inactive and not visible to the public.
          </p>
        </div>
      </div>
    </div>
    <!-- Banner -->
    <div v-if="settings?.banner" class="w-full h-48 md:h-64 bg-gray-200 flex items-center justify-center overflow-hidden">
      <img :src="getImageUrl(settings.banner)" alt="Banner" class="object-cover w-full h-full" loading="lazy" @error="handleImageError($event)" />
    </div>
    <div v-else class="w-full h-48 md:h-64 bg-gray-200 flex items-center justify-center text-gray-400 text-2xl">No Banner</div>
    <!-- Company Info Row -->
    <div class="flex flex-col md:flex-row items-center justify-between bg-white shadow p-6 -mt-12 mx-4 rounded-lg relative z-10 max-w-6xl mx-auto">
      <!-- Logo -->
      <div class="flex-shrink-0 mb-4 md:mb-0">
        <img v-if="settings?.logo" :src="getImageUrl(settings.logo)" alt="Logo" class="h-24 w-24 object-contain rounded border bg-white" loading="lazy" @error="handleImageError($event)" />
        <div v-else class="h-24 w-24 flex items-center justify-center bg-gray-100 rounded border text-gray-400">No Logo</div>
      </div>
      <!-- Center: Name & Description -->
      <div class="flex-1 text-center md:text-left px-4">
        <h1 class="text-3xl font-bold mb-2">{{ vendor.name }}</h1>
        <p class="text-gray-600">{{ settings?.description || 'No description provided.' }}</p>
      </div>
      <!-- Right: Contact Info -->
      <div class="flex flex-col items-end space-y-1 text-sm">
        <div v-if="settings?.shop_url"><span class="font-semibold">Shop URL:</span> <a :href="settings.shop_url" class="text-blue-600 hover:underline" target="_blank">{{ settings.shop_url }}</a></div>
        <div v-if="settings?.contact_email"><span class="font-semibold">Email:</span> <a :href="`mailto:${settings.contact_email}`" class="text-blue-600 hover:underline">{{ settings.contact_email }}</a></div>
        <div v-if="settings?.phone_number"><span class="font-semibold">Phone:</span> {{ settings.phone_number }}</div>
      </div>
    </div>
    <!-- Vendor Details Row -->
    <div v-if="hasVendorDetails" class="max-w-6xl mx-auto px-4 mt-6">
      <div class="bg-white rounded-lg shadow p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 text-sm">
        <div v-if="settings?.payment_methods && settings.payment_methods.length">
          <div class="text-[10px] uppercase tracking-[0.1em] font-semibold text-gray-500 mb-2">Payment Methods</div>
          <div class="flex flex-wrap gap-1.5">
            <span v-for="method in settings.payment_methods" :key="method" class="px-2 py-1 bg-gray-100 rounded text-xs font-medium text-gray-700">{{ method }}</span>
          </div>
        </div>
        <div v-if="settings?.shipping_info">
          <div class="text-[10px] uppercase tracking-[0.1em] font-semibold text-gray-500 mb-2">Shipping Info</div>
          <p class="text-gray-700 leading-relaxed">{{ settings.shipping_info }}</p>
        </div>
        <div v-if="settings?.return_policy">
          <div class="text-[10px] uppercase tracking-[0.1em] font-semibold text-gray-500 mb-2">Return Policy</div>
          <p class="text-gray-700 leading-relaxed">{{ settings.return_policy }}</p>
        </div>
        <div v-if="settings?.coupon_code">
          <div class="text-[10px] uppercase tracking-[0.1em] font-semibold text-gray-500 mb-2">Discount Code</div>
          <div class="flex items-center gap-2">
            <span class="ui-mono font-bold text-base text-gray-900 bg-gray-100 px-3 py-1.5 rounded border border-dashed border-gray-300">{{ settings.coupon_code }}</span>
          </div>
        </div>
        <div v-if="settings?.business_hours">
          <div class="text-[10px] uppercase tracking-[0.1em] font-semibold text-gray-500 mb-2">Business Hours</div>
          <p class="text-gray-700">{{ settings.business_hours }}</p>
        </div>
        <div v-if="settings?.location">
          <div class="text-[10px] uppercase tracking-[0.1em] font-semibold text-gray-500 mb-2">Location</div>
          <p class="text-gray-700">{{ settings.location.name || settings.location }}</p>
        </div>
      </div>
    </div>

    <!-- Main Content: Filters & Items -->
    <div class="flex flex-col md:flex-row gap-6 mt-8 max-w-6xl mx-auto px-4">
      <!-- Filters (dummy) -->
      <aside class="w-full md:w-64 bg-white rounded-lg shadow p-4 mb-4 md:mb-0 self-start">
        <h2 class="text-lg font-semibold mb-2">Search</h2>
        <form @submit.prevent="onSearch" class="mb-4">
          <input
            v-model="searchInput"
            type="text"
            placeholder="Search by title..."
            class="w-full border rounded px-2 py-1"
          />
          <button type="submit" class="hidden">Search</button>
        </form>
        <h2 class="text-lg font-semibold mb-2">Filter by Cost</h2>
        <div class="space-y-2">
          <div v-for="(count, key) in costCounts" :key="key">
            <label :for="'cost-' + key" class="flex items-center cursor-pointer">
              <input
                type="radio"
                :id="'cost-' + key"
                name="cost"
                :value="key"
                :checked="selectedCost === key"
                @change="applyCostFilter(key)"
                class="mr-2"
              />
              <span>
                <span v-if="key === 'all'">All</span>
                <span v-else-if="key === '0-50'">$0 - $50</span>
                <span v-else-if="key === '50-100'">$50 - $100</span>
                <span v-else-if="key === '100-150'">$100 - $150</span>
                <span v-else-if="key === '150-200'">$150 - $200</span>
                <span v-else>$200+</span>
                <span class="ml-2 text-gray-500">({{ count }})</span>
              </span>
            </label>
          </div>
        </div>
      </aside>
      <!-- Items -->
      <section class="flex-1 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <div v-for="item in items" :key="item.id" class="bg-white rounded-lg shadow p-4 flex flex-col items-center">
          <img :src="getImageUrl(item.image_url)" alt="Item Image" class="h-32 w-32 object-cover rounded mb-2" loading="lazy" @error="handleImageError($event)" />
          <h3 class="font-semibold text-lg mb-1">
            <a v-if="item.product_url" :href="item.product_url" target="_blank" class="hover:underline text-blue-700">{{ item.name }}</a>
            <span v-else>{{ item.name }}</span>
          </h3>
          <div class="mb-2 flex flex-col items-center">
            <!-- Out of Stock logic -->
            <template v-if="!item.price && item.price !== 0">
              <span class="text-red-600 font-bold text-lg">Out of Stock</span>
            </template>
            <template v-else-if="item.second_price">
              <span class="text-blue-700 font-bold text-lg">
                ${{ formatPrice(item.price) }} - ${{ formatPrice(item.second_price) }}
              </span>
              <span v-if="item.discount_price" class="text-sm text-green-600 font-semibold mt-1">
                Discount: ${{ formatPrice(item.discount_price) }}
              </span>
            </template>
            <template v-else-if="item.discount_price">
              <span class="text-gray-500 line-through text-base">${{ formatPrice(item.price) }}</span>
              <span class="text-green-600 font-bold text-lg ml-2">${{ formatPrice(item.discount_price) }}</span>
            </template>
            <template v-else>
              <span class="text-blue-700 font-bold text-lg">${{ formatPrice(item.price) }}</span>
            </template>
          </div>
          <div v-if="item.description" class="text-gray-600 text-sm mb-2">{{ item.description }}</div>
        </div>
      </section>
    </div>
  </div>
  </ModernLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

const props = defineProps({
  settings: Object,
  vendor: Object,
  items: Array,
  isAdmin: {
    type: Boolean,
    default: false
  },
  isOwnPage: {
    type: Boolean,
    default: false
  },
  costCounts: Object,
  selectedCost: String,
  search: String,
})

const searchInput = ref(props.search || '')

const hasVendorDetails = computed(() => {
  const s = props.settings
  if (!s) return false
  return !!(
    (s.payment_methods && s.payment_methods.length) ||
    s.shipping_info ||
    s.return_policy ||
    s.coupon_code ||
    s.business_hours ||
    s.location
  )
})

function getImageUrl(path) {
  if (!path) return null
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) return path
  return `/storage/${path}`
}

function formatPrice(val) {
  if (val === null || val === undefined || val === '') return '-'
  return Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function onSearch() {
  router.get(window.location.pathname, { cost: props.selectedCost, search: searchInput.value }, { preserveScroll: true })
}

function applyCostFilter(key) {
  router.get(window.location.pathname, { cost: key, search: searchInput.value }, { preserveScroll: true })
}

const handleImageError = (event) => {
  // Prevent infinite loop - stop trying to load images if we've already failed
  if (event.target.dataset.failed) {
    return
  }
  // Mark as failed to prevent retry
  event.target.dataset.failed = 'true'
  // Hide the broken image
  event.target.style.display = 'none'
}

watch(searchInput, (val, oldVal) => {
  if (val.length >= 2) {
    onSearch()
  } else if (val === '') {
    onSearch()
  }
})
</script> 