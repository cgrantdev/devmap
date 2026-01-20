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
      <!-- Add Scraping Config Modal -->
      <div v-if="showConfigModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-2xl p-6">
          <h2 class="text-xl font-semibold mb-4">Add Scraping Config</h2>
          <form @submit.prevent="submitConfig">
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Vendor</label>
                <select v-model.number="form.vendor_id" class="w-full border rounded px-2 py-1">
                  <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Frequency</label>
                <select v-model="form.frequency" class="w-full border rounded px-2 py-1">
                  <option value="hourly">Hourly</option>
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                </select>
              </div>
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-slate-700 mb-1">Products URL</label>
              <input type="text" v-model="form.products_url" class="w-full border rounded px-2 py-1" />
            </div>

            <h3 class="text-sm font-medium text-slate-700 mb-2">CSS Selectors</h3>
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-xs text-slate-600 mb-1">Product Container</label>
                <input type="text" v-model="form.selectors.product_container" class="w-full border rounded px-2 py-1" />
              </div>
              <div>
                <label class="block text-xs text-slate-600 mb-1">Name</label>
                <input type="text" v-model="form.selectors.name" class="w-full border rounded px-2 py-1" />
              </div>
              <div>
                <label class="block text-xs text-slate-600 mb-1">Price</label>
                <input type="text" v-model="form.selectors.price" class="w-full border rounded px-2 py-1" />
              </div>
              <div>
                <label class="block text-xs text-slate-600 mb-1">Dosage</label>
                <input type="text" v-model="form.selectors.dosage" class="w-full border rounded px-2 py-1" />
              </div>
              <div>
                <label class="block text-xs text-slate-600 mb-1">Image</label>
                <input type="text" v-model="form.selectors.image" class="w-full border rounded px-2 py-1" />
              </div>
              <div>
                <label class="block text-xs text-slate-600 mb-1">Link</label>
                <input type="text" v-model="form.selectors.link" class="w-full border rounded px-2 py-1" />
              </div>
              <div>
                <label class="block text-xs text-slate-600 mb-1">Stock</label>
                <input type="text" v-model="form.selectors.stock" class="w-full border rounded px-2 py-1" />
              </div>
            </div>

            <div class="flex justify-end gap-2">
              <button type="button" @click="showConfigModal = false" class="px-4 py-2 bg-slate-200 rounded hover:bg-slate-300">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-slate-700 text-white rounded hover:bg-slate-600">Save</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Scraping Config Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-2xl p-6">
          <h2 class="text-xl font-semibold mb-4">Edit Scraping Config</h2>

          <form @submit.prevent="updateConfig">
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Vendor</label>
                <select v-model.number="editForm.vendor_id" class="w-full border rounded px-2 py-1">
                  <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Frequency</label>
                <select v-model="editForm.frequency" class="w-full border rounded px-2 py-1">
                  <option value="hourly">Hourly</option>
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                </select>
              </div>
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-slate-700 mb-1">Products URL</label>
              <input type="text" v-model="editForm.products_url" class="w-full border rounded px-2 py-1" />
            </div>

            <h3 class="text-sm font-medium text-slate-700 mb-2">CSS Selectors</h3>

            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-xs text-slate-600 mb-1">Product Container</label>
                <input type="text" v-model="editForm.selectors.product_container" class="w-full border rounded px-2 py-1" />
              </div>

              <div>
                <label class="block text-xs text-slate-600 mb-1">Name</label>
                <input type="text" v-model="editForm.selectors.name" class="w-full border rounded px-2 py-1" />
              </div>

              <div>
                <label class="block text-xs text-slate-600 mb-1">Price</label>
                <input type="text" v-model="editForm.selectors.price" class="w-full border rounded px-2 py-1" />
              </div>

              <div>
                <label class="block text-xs text-slate-600 mb-1">Dosage</label>
                <input type="text" v-model="editForm.selectors.dosage" class="w-full border rounded px-2 py-1" />
              </div>

              <div>
                <label class="block text-xs text-slate-600 mb-1">Image</label>
                <input type="text" v-model="editForm.selectors.image" class="w-full border rounded px-2 py-1" />
              </div>

              <div>
                <label class="block text-xs text-slate-600 mb-1">Link</label>
                <input type="text" v-model="editForm.selectors.link" class="w-full border rounded px-2 py-1" />
              </div>

              <div>
                <label class="block text-xs text-slate-600 mb-1">Stock</label>
                <input type="text" v-model="editForm.selectors.stock" class="w-full border rounded px-2 py-1" />
              </div>
            </div>

            <div class="flex justify-end gap-2">
              <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-200 rounded hover:bg-slate-300">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-slate-700 text-white rounded hover:bg-slate-600">Update</button>
            </div>
          </form>
        </div>
      </div>


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
              <button
                @click="openEdit(config)"                
                class="p-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors"
                title="Edit config"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 4h6a1 1 0 011 1v6m-7 7H4a1 1 0 01-1-1v-7l11-11 7 7-11 11z"
                  />
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

          <!-- View CSS Selectors -->
          <!-- Toggle title -->
          <button
            class="text-sm text-gray-700 mt-3 flex items-center"
            @click="toggleSelectors(config.id)"
          >
            <span class="mr-1">
              {{ openSelectors[config.id] ? '▾' : '▸' }}
            </span>
            View CSS Selectors
          </button>

          <!-- Collapsible content -->
          <div
            v-if="openSelectors[config.id]"
            class="mt-2 bg-gray-50 p-3"
          >
            <pre class="text-xs text-slate-600">
          product_container: {{ config.selectors?.product_container }}
          name: {{ config.selectors?.name }}
          price: {{ config.selectors?.price }}
          dosage: {{ config.selectors?.dosage }}
          image: {{ config.selectors?.image }}
          link: {{ config.selectors?.link }}
          stock: {{ config.selectors?.stock }}
            </pre>
          </div>

        </div>
      </div>
    </div>

    <!-- Scraped Products Tab -->
    <div v-if="activeTab === 'products'" class="space-y-4">
      <div class="flex justify-between items-center mb-4">
        <div class="text-sm text-slate-600">
          {{ filteredProducts.length }} product{{ filteredProducts.length !== 1 ? 's' : '' }} found
        </div>
        <div class="flex items-center gap-3">
          <!-- Vendor Filter -->
          <select
            v-model="selectedVendor"
            class="px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent"
          >
            <option value="">All Vendors</option>
            <option v-for="vendor in uniqueVendors" :key="vendor" :value="vendor">
              {{ vendor }}
            </option>
          </select>
          <!-- Status Filter -->
          <select
            v-model="selectedStatus"
            class="px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent"
          >
            <option value="">All Status</option>
            <option value="auto">Auto</option>
            <option value="override">Override</option>
            <option value="both">Both</option>
          </select>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="products.length === 0" class="bg-white border border-slate-200 rounded-lg p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="text-lg font-medium text-slate-800 mb-2">No Scraped Products Yet</h3>
        <p class="text-sm text-slate-600 mb-4">
          Products will appear here once scraping configurations are run successfully.
        </p>
        <button
          @click="activeTab = 'configs'"
          class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-colors"
        >
          View Scraping Configs
        </button>
      </div>

      <!-- Products Table -->
      <div v-else class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase">Product</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase">Vendor</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase">Price</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase">Stock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase">Last Scraped</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-700 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-slate-50 transition-colors">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-12 h-12 bg-slate-100 rounded flex items-center justify-center border border-slate-200">
                      <img
                        v-if="product.image_url && !imageErrors[product.id]"
                        :src="product.image_url"
                        :alt="product.name || 'Product image'"
                        class="w-12 h-12 rounded object-cover"
                        @error="handleImageError(product.id)"
                      />
                      <svg v-else class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                      <div class="text-sm font-medium text-slate-800 truncate">
                        {{ product.name || 'Unnamed Product' }}
                      </div>
                      <div v-if="product.dosage" class="text-xs text-slate-600 mt-0.5">
                        {{ product.dosage }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm text-slate-700">
                  {{ product.vendor_name || 'Unknown' }}
                </td>
                <td class="px-4 py-3 text-sm font-medium text-slate-800">
                  <span v-if="product.price">${{ formatPrice(product.price) }}</span>
                  <span v-else class="text-slate-400">N/A</span>
                </td>
                <td class="px-4 py-3">
                  <span :class="[
                    'px-2 py-1 rounded text-xs font-medium',
                    product.stock_status === 'in-stock' ? 'bg-green-100 text-green-800' :
                    product.stock_status === 'low-stock' ? 'bg-yellow-100 text-yellow-800' :
                    product.stock_status === 'out-of-stock' ? 'bg-red-100 text-red-800' :
                    'bg-slate-100 text-slate-800'
                  ]">
                    {{ product.stock_status || 'unknown' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">
                  {{ product.last_scraped_at ? formatDate(product.last_scraped_at) : 'Never' }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span v-if="product.auto_scraped" class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 font-medium">
                      Auto
                    </span>
                    <span v-if="product.manual_override" class="px-2 py-1 rounded text-xs bg-purple-100 text-purple-800 flex items-center gap-1 font-medium">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                      </svg>
                      Override
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <!-- Lock/Override Button -->
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
                    <!-- Edit Button -->
                    <button
                      @click="editProduct(product)"
                      class="p-1.5 bg-slate-100 text-slate-700 rounded hover:bg-slate-200 transition-colors"
                      title="Edit product"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <!-- External Link Button -->
                    <a
                      v-if="product.source_url"
                      :href="product.source_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="p-1.5 bg-slate-100 text-slate-700 rounded hover:bg-slate-200 transition-colors"
                      title="View source page"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                    </a>
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
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { Inertia } from '@inertiajs/inertia'

const form = useForm({
  vendor_id: '',
  products_url: '',
  frequency: 'daily',
  selectors: {
    product_container: '',
    name: '',
    price: '',
    dosage: '',
    image: '',
    link: '',
    stock: '',
  },
  _token: usePage().props.csrf_token // include CSRF token explicitly
})

const showEditModal = ref(false)
const editingConfig = ref(null)

const editForm = useForm({
  id: null,
  vendor_id: null,
  frequency: 'daily',
  products_url: '',
  selectors: {
    product_container: '',
    name: '',
    price: '',
    dosage: '',
    image: '',
    link: '',
    stock: '',
  }, 
  _token: usePage().props.csrf_token
})

const props = defineProps({
  scrapingConfigs: {
    type: Array,
    default: () => []
  },
  products: {
    type: Array,
    default: () => []
  },
  vendors: {
    type: Array,
    default: () => []
  }
})

const vendors = computed(() => props.vendors)
const scrapingConfigs = computed(() => props.scrapingConfigs)
const products = computed(() => props.products)
const csrf = usePage().props.csrf_token
const activeTab = ref('configs')
const showConfigModal = ref(false)

// Log props values
// console.log('Props:', props)

// Filter states
const selectedVendor = ref('')
const selectedStatus = ref('')

const openSelectors = ref({})
const imageErrors = ref({})

// Get unique vendor names from products
const uniqueVendors = computed(() => {
  const vendorSet = new Set()
  products.value.forEach(product => {
    if (product.vendor_name) {
      vendorSet.add(product.vendor_name)
    }
  })
  return Array.from(vendorSet).sort()
})

// Filter products based on selected filters
const filteredProducts = computed(() => {
  let filtered = products.value

  // Filter by vendor
  if (selectedVendor.value) {
    filtered = filtered.filter(product => product.vendor_name === selectedVendor.value)
  }

  // Filter by status
  if (selectedStatus.value) {
    if (selectedStatus.value === 'auto') {
      filtered = filtered.filter(product => product.auto_scraped && !product.manual_override)
    } else if (selectedStatus.value === 'override') {
      filtered = filtered.filter(product => product.manual_override)
    } else if (selectedStatus.value === 'both') {
      filtered = filtered.filter(product => product.auto_scraped && product.manual_override)
    }
  }

  return filtered
})

function toggleSelectors(id) {
  openSelectors.value[id] = !openSelectors.value[id]
}


function submitConfig() {
  form.post('/admin/product-scraping/configs', {
    preserveScroll: true,
    onSuccess: () => {      
      // Reset form after save
      form.reset()  // automatically resets to initial state
      showConfigModal.value = false
    },
    onError: (errors) => {
      console.error(errors)
    }
  })
}

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString()
}

function formatPrice(price) {
  if (!price) return '0.00'
  return parseFloat(price).toFixed(2)
}

function toggleConfig(configId) {
  router.post(`/admin/product-scraping/${configId}/toggle`, {
    _token: csrf
  }, { preserveScroll: true })
}

function scrapeNow(configId) {
  router.post(`/admin/product-scraping/${configId}/scrape`, {
    _token: csrf
  }, { 
    preserveScroll: true,
    onSuccess: () => {
      // console.log(props)
    }
  })
}

function openEdit(config) {
  editingConfig.value = config
  showEditModal.value = true

  editForm.id = config.id
  editForm.vendor_id = config.vendor_id
  editForm.frequency = config.frequency
  editForm.products_url = config.products_url

  editForm.selectors = {
    product_container: config.selectors?.product_container || '',
    name: config.selectors?.name || '',
    price: config.selectors?.price || '',
    dosage: config.selectors?.dosage || '',
    image: config.selectors?.image || '',
    link: config.selectors?.link || '',
    stock: config.selectors?.stock || '',
  }
}

function updateConfig() {
  editForm.put(`/admin/product-scraping/configs/${editForm.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      // console.log(vendors)
      showEditModal.value = false
    }
  })
}

function toggleOverride(productId) {
  router.post(`/admin/product-scraping/products/${productId}/toggle-override`, {
    _token: csrf
  }, {
    preserveScroll: true
  })
}

function editProduct(product) {
  // Navigate to product edit page
  router.visit(`/admin/products/${product.id}/edit`)
}

function handleImageError(productId) {
  // Mark this product's image as failed to load
  imageErrors.value[productId] = true
}
</script>

