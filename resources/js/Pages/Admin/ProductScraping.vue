<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-2xl text-slate-800 mb-2">Product Scraping Management</h1>
      <p class="text-slate-600">
        Manage automated product scraping from vendor websites. Products are auto-updated unless manually overridden.
      </p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200 mb-6">
      <div class="flex gap-4">
        <button
          @click="activeTab = 'configs'"
          :class="[
            'px-4 py-2 border-b-2 transition-colors',
            activeTab === 'configs' ? 'border-slate-700 text-slate-800' : 'border-transparent text-slate-600 hover:text-slate-800'
          ]"
        >
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Scraping Configs
          </div>
        </button>
        <button
          @click="activeTab = 'products'"
          :class="[
            'px-4 py-2 border-b-2 transition-colors',
            activeTab === 'products' ? 'border-slate-700 text-slate-800' : 'border-transparent text-slate-600 hover:text-slate-800'
          ]"
        >
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Scraped Products
          </div>
        </button>
      </div>
    </div>

    <!-- Scraping Configs Tab -->
    <div v-if="activeTab === 'configs'" class="space-y-4">
      <div class="flex justify-between items-center mb-4">
        <div class="text-sm text-slate-600">
          {{ scrapingConfigs.length }} vendor{{ scrapingConfigs.length !== 1 ? 's' : '' }} configured
        </div>
        <button
          @click="showConfigModal = true"
          class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Add Scraping Config
        </button>
      </div>

      <div class="grid grid-cols-1 gap-4">
        <div v-for="config in scrapingConfigs" :key="config.id" class="bg-white border border-slate-200 rounded-lg p-6">
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <h3 class="text-lg text-slate-800">{{ config.vendor_name }}</h3>
                <span :class="[
                  'px-2 py-1 rounded text-xs',
                  config.enabled ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800'
                ]">
                  {{ config.enabled ? 'Enabled' : 'Disabled' }}
                </span>
                <span class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-800">
                  {{ config.frequency }}
                </span>
              </div>
              <a
                :href="config.products_url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-sm text-slate-600 hover:text-slate-800 flex items-center gap-1"
              >
                {{ config.products_url }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </a>
            </div>

            <div class="flex items-center gap-2">
              <button
                @click="toggleConfig(config.id)"
                :class="[
                  'p-2 rounded-lg transition-colors',
                  config.enabled ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
                :title="config.enabled ? 'Disable scraping' : 'Enable scraping'"
              >
                <svg v-if="config.enabled" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </button>
              <button
                @click="scrapeNow(config.id)"
                class="p-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-colors"
                title="Scrape now"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-4 gap-4 mb-4">
            <div class="bg-slate-50 rounded-lg p-3">
              <div class="text-xs text-slate-600 mb-1">Last Run</div>
              <div class="text-sm text-slate-800">
                {{ config.last_run_at ? formatDate(config.last_run_at) : 'Never' }}
              </div>
            </div>
            <div class="bg-slate-50 rounded-lg p-3">
              <div class="text-xs text-slate-600 mb-1">Next Run</div>
              <div class="text-sm text-slate-800">
                {{ config.next_run_at ? formatDate(config.next_run_at) : 'Not scheduled' }}
              </div>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
              <div class="text-xs text-green-700 mb-1 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Success
              </div>
              <div class="text-sm text-green-800">{{ config.success_count || 0 }} runs</div>
            </div>
            <div class="bg-red-50 rounded-lg p-3">
              <div class="text-xs text-red-700 mb-1 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Errors
              </div>
              <div class="text-sm text-red-800">{{ config.error_count || 0 }} errors</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Scraped Products Tab -->
    <div v-if="activeTab === 'products'" class="space-y-4">
      <div class="flex justify-between items-center mb-4">
        <div class="text-sm text-slate-600">
          {{ products.length }} product{{ products.length !== 1 ? 's' : '' }} found
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="px-4 py-3 text-left text-xs text-slate-700">Product</th>
                <th class="px-4 py-3 text-left text-xs text-slate-700">Vendor</th>
                <th class="px-4 py-3 text-left text-xs text-slate-700">Price</th>
                <th class="px-4 py-3 text-left text-xs text-slate-700">Stock</th>
                <th class="px-4 py-3 text-left text-xs text-slate-700">Last Scraped</th>
                <th class="px-4 py-3 text-left text-xs text-slate-700">Status</th>
                <th class="px-4 py-3 text-left text-xs text-slate-700">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr v-for="product in products" :key="product.id" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <img
                      v-if="product.image_url"
                      :src="product.image_url"
                      :alt="product.name"
                      class="w-12 h-12 rounded object-cover"
                    />
                    <div>
                      <div class="text-sm text-slate-800">{{ product.name }}</div>
                      <div class="text-xs text-slate-600">{{ product.dosage }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ product.vendor_name }}</td>
                <td class="px-4 py-3 text-sm text-slate-800">${{ product.price }}</td>
                <td class="px-4 py-3">
                  <span :class="[
                    'px-2 py-1 rounded text-xs',
                    product.stock_status === 'in-stock' ? 'bg-green-100 text-green-800' :
                    product.stock_status === 'low-stock' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-red-100 text-red-800'
                  ]">
                    {{ product.stock_status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">
                  {{ product.last_scraped_at ? formatDate(product.last_scraped_at) : 'Never' }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <span v-if="product.auto_scraped" class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
                      Auto
                    </span>
                    <span v-if="product.manual_override" class="px-2 py-1 rounded text-xs bg-purple-100 text-purple-800 flex items-center gap-1">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                      </svg>
                      Override
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <button
                      @click="toggleOverride(product.id)"
                      :class="[
                        'p-1.5 rounded transition-colors',
                        product.manual_override ? 'bg-purple-100 text-purple-700 hover:bg-purple-200' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                      :title="product.manual_override ? 'Remove override protection' : 'Protect from auto-updates'"
                    >
                      <svg v-if="product.manual_override" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                      </svg>
                      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  scrapingConfigs: {
    type: Array,
    default: () => []
  },
  products: {
    type: Array,
    default: () => []
  }
})

const activeTab = ref('configs')
const showConfigModal = ref(false)

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString()
}

function toggleConfig(configId) {
  router.post(`/admin/product-scraping/${configId}/toggle`, {}, {
    preserveScroll: true
  })
}

function scrapeNow(configId) {
  router.post(`/admin/product-scraping/${configId}/scrape`, {}, {
    preserveScroll: true
  })
}

function toggleOverride(productId) {
  router.post(`/admin/product-scraping/products/${productId}/toggle-override`, {}, {
    preserveScroll: true
  })
}
</script>

