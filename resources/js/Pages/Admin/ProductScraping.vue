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
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-4 h-4" aria-hidden="true">
              <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915" />
              <circle cx="12" cy="12" r="3"></circle>
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
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-refresh-cw w-4 h-4" aria-hidden="true">
              <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
              <path d="M21 3v5h-5" />
              <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
              <path d="M8 16H3v5" />
            </svg>
            Scraped Products
          </div>
        </button>
      </div>
    </div>

    <!-- Scraping Configs Tab -->
    <div v-if="activeTab === 'configs'" class="space-y-4">
      <!-- Add Scraping Config Modal -->
      <div v-if="showConfigModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
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
      <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
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
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-4 h-4" aria-hidden="true">
            <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915" />
            <circle cx="12" cy="12" r="3"></circle>
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
                <svg v-if="config.enabled" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pause w-4 h-4" aria-hidden="true">
                  <rect x="14" y="3" width="5" height="18" rx="1"></rect>
                  <rect x="5" y="3" width="5" height="18" rx="1"></rect>                  
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
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-refresh-cw w-4 h-4" aria-hidden="true">
                  <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                  <path d="M21 3v5h-5" />
                  <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                  <path d="M8 16H3v5" />
                </svg>
              </button>
              <button
                @click="openEdit(config)"                
                class="p-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors"
                title="Edit config"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pen w-4 h-4" aria-hidden="true">
                  <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />                  
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
          <details class="mt-4">
            <summary
              class="text-sm text-slate-600 cursor-pointer hover:text-slate-800 flex items-center gap-2"
              @click="toggleSelectors(config.id)"
            >
              <svg 
                v-if="openSelectors[config.id]"
                class="w-5 h-5" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
              <svg 
                v-else
                class="w-5 h-5" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>              
              View CSS Selectors
            </summary>

            <!-- Collapsible content -->
            <div
              v-if="openSelectors[config.id]"
              class="mt-2 bg-slate-50 rounded-lg p-3 font-mono text-xs text-slate-700"
            >
              <div class="mb-1">
                <span class="text-slate-500">product_container:</span>
                {{ config.selectors?.product_container }}
              </div>
              <div class="mb-1">
                <span class="text-slate-500">name:</span>
                {{ config.selectors?.name }}
              </div>
              <div class="mb-1">
                <span class="text-slate-500">price:</span>
                {{ config.selectors?.price }}
              </div>
              <div class="mb-1">
                <span class="text-slate-500">dosage:</span>
                {{ config.selectors?.dosage }}
              </div>
              <div class="mb-1">
                <span class="text-slate-500">image:</span>
                {{ config.selectors?.image }}
              </div>
              <div class="mb-1">
                <span class="text-slate-500">link:</span>
                {{ config.selectors?.link }}
              </div>
              <div class="mb-1">
                <span class="text-slate-500">stock:</span>
                {{ config.selectors?.stock }}
              </div>
            </div>
          </details>
        </div>
      </div>
    </div>

    <!-- Scraped Products Tab -->
    <div v-if="activeTab === 'products'" class="space-y-4">
      <div class="flex justify-between items-center mb-4">
        <div class="text-sm text-slate-600">
          {{ filteredProducts.length }} product{{ filteredProducts.length !== 1 ? 's' : '' }} found
        </div>
        <div class="flex gap-2">
          <!-- Vendor Filter -->
          <select
            v-model="selectedVendor"
            class="px-4 py-2 border border-slate-300 rounded-lg text-sm"
          >
            <option value="">All Vendors</option>
            <option v-for="vendor in uniqueVendors" :key="vendor" :value="vendor">
              {{ vendor }}
            </option>
          </select>
          <!-- Status Filter -->
          <select
            v-model="selectedStatus"
            class="px-4 py-2 border border-slate-300 rounded-lg text-sm"
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
            <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-slate-50">
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
                  <div>
                    <div class="text-sm text-slate-800">
                      {{ product.name || 'Unnamed Product' }}
                    </div>
                    <div v-if="product.dosage" class="text-xs text-slate-600">
                      {{ product.dosage }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-slate-700">
                {{ product.vendor_name || 'Unknown' }}
              </td>
              <td class="px-4 py-3 text-sm text-slate-800">
                ${{ formatPrice(product.price) }}                
              </td>
              <td class="px-4 py-3">
                <span :class="[
                  'px-2 py-1 rounded text-xs',
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
                <div class="flex items-center gap-2">
                  <span v-if="product.auto_scraped" class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
                    Auto
                  </span>
                  <span v-if="product.manual_override" class="px-2 py-1 rounded text-xs bg-purple-100 text-purple-800 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock w-3 h-3" aria-hidden="true">
                      <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4" />                  
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
                    <svg v-if="product.manual_override" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock w-4 h-4" aria-hidden="true">
                      <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4" />                  
                    </svg>                    
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock-open w-4 h-4" aria-hidden="true">
                      <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 9.9-1" />                  
                    </svg>
                  </button>
                  <!-- Edit Button -->
                  <button
                    @click="editProduct(product)"
                    class="p-1.5 bg-slate-100 text-slate-700 rounded hover:bg-slate-200 transition-colors"
                    title="Edit product"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pen w-4 h-4" aria-hidden="true">
                      <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />                  
                    </svg>
                  </button>
                  <!-- External Link Button -->
                  <a
                    v-if="product.source_url"
                    :href="product.source_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="p-1.5 bg-slate-100 text-slate-700 rounded hover:bg-slate-200 transition-colors"
                    title="View on vendor site"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link w-4 h-4" aria-hidden="true">
                      <path d="M15 3h6v6" />                  
                      <path d="M10 14 21 3" />                  
                      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />                  
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Product Modal -->
    <div
      v-if="showProductEditModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeProductEditModal"
    >
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-2xl text-gray-900">Edit Product</h2>
        </div>

        <form @submit.prevent="submitProductEdit" class="p-6 space-y-6">
            <!-- Product Name -->
            <div>
              <label class="block text-sm text-gray-700 mb-2">
                Product Name *
              </label>
              <input
                v-model="productEditForm.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Brand -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-2">
                  Brand *
                </label>
                <select
                  v-model="productEditForm.brand_id"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Select Brand</option>
                  <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                    {{ brand.name }}
                  </option>
                </select>
              </div>

              <!-- Category -->
              <div>
                <label class="block text-sm text-gray-700 mb-2">
                  Category *
                </label>
                <select
                  v-model="productEditForm.product_category_id"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Select Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Price -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-2">
                  Price *
                </label>
                <input
                  v-model="productEditForm.price"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Dosage -->
              <div>
                <label class="block text-sm text-gray-700 mb-2">
                  Dosage *
                </label>
                <input
                  v-model="productEditForm.size_mg"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <!-- On Sale -->
            <div class="border-t border-gray-200 pt-4">
              <label for="on_sale" class="flex items-center gap-2 mb-4">
                <input
                  v-model="productEditForm.on_sale"
                  type="checkbox"
                  id="on_sale"
                  class="h-4 w-4 text-blue-600"
                />
                <span class="text-sm text-gray-700">On Sale</span>
              </label>
              <div v-if="productEditForm.on_sale" class="grid grid-cols-2 gap-4">
                <!-- Original Price (shown when On Sale is checked) -->
                <div>
                  <label class="block text-sm text-gray-700 mb-2">
                    Original Price
                  </label>
                  <input
                    v-model="productEditForm.original_price"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
    
                <!-- Discount % (shown when On Sale is checked) -->
                <div>
                  <label class="block text-sm text-gray-700 mb-2">
                    Discount %
                  </label>
                  <input
                    v-model="productEditForm.discount_percent"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @input="calculateProductDiscount"
                  />
                </div>
              </div>
            </div>

            <!-- Purity -->
            <div class="border-t border-gray-200 pt-4">
              <label class="block text-sm text-gray-700 mb-2">
                Purity (%)
              </label>
              <input
                v-model="productEditForm.purity"
                type="number"
                step="0.1"
                min="0"
                max="100"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="e.g., 99.2"
              />
            </div>

            <!-- Checkboxes Section -->
            <div class="border-t border-gray-200 pt-4">
              <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center gap-2">
                  <input
                    v-model="productEditForm.featured"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600"
                  />
                  <span class="text-sm text-gray-700">Featured</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    v-model="productEditForm.hidden"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600"
                  />
                  <span class="text-sm text-gray-700">Hidden</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    v-model="productEditForm.lab_tested"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600"
                  />
                  <span class="text-sm text-gray-700">Lab Tested</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    v-model="productEditForm.first_timer_deals"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600"
                  />
                  <span class="text-sm text-gray-700">First-Timer Deals</span>
                </label>
              </div>
            </div>

            <!-- Error Messages -->
            <div v-if="Object.keys(productEditForm.errors).length > 0" class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
              <p class="font-medium mb-2">Please fix the following errors:</p>
              <ul class="list-disc list-inside text-sm">
                <li v-for="(error, field) in productEditForm.errors" :key="field">
                  {{ Array.isArray(error) ? error[0] : error }}
                </li>
              </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
              <button
                type="button"
                @click="closeProductEditModal"
                class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="productEditForm.processing"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50"
              >
                {{ productEditForm.processing ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
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
const showProductEditModal = ref(false)
const editingProduct = ref(null)

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
  },
  brands: {
    type: Array,
    default: () => []
  },
  categories: {
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

// Product Edit Form
const productEditForm = useForm({
  name: '',
  brand_id: null,
  product_category_id: null,
  price: '',
  size_mg: '',
  on_sale: false,
  original_price: '',
  discount_percent: '',
  purity: '',
  featured: false,
  hidden: false,
  lab_tested: false,
  first_timer_deals: false,
  _token: usePage().props.csrf_token
})

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
  editingProduct.value = product
  
  // Populate form with product data
  productEditForm.name = product.name || ''
  productEditForm.brand_id = product.brand_id || null
  productEditForm.product_category_id = product.product_category_id || null
  productEditForm.price = product.price || ''
  productEditForm.size_mg = product.size_mg || product.dosage || ''
  productEditForm.purity = product.purity || ''
  productEditForm.featured = !!product.featured
  productEditForm.hidden = !!product.hidden
  productEditForm.lab_tested = !!product.lab_tested
  productEditForm.first_timer_deals = !!product.first_timer_deals
  
  // Determine if product is on sale
  // If discount_price exists, that means price is original and discount_price is sale price
  const hasDiscount = product.discount_price && product.price && product.discount_price < product.price
  productEditForm.on_sale = hasDiscount
  
  if (hasDiscount) {
    // price is original, discount_price is sale price
    productEditForm.original_price = product.price
    productEditForm.price = product.discount_price
    productEditForm.discount_percent = Math.round(((product.price - product.discount_price) / product.price) * 100)
  } else {
    productEditForm.original_price = ''
    productEditForm.discount_percent = ''
  }
  
  showProductEditModal.value = true
}

function closeProductEditModal() {
  showProductEditModal.value = false
  editingProduct.value = null
  productEditForm.reset()
  productEditForm.clearErrors()
}

function calculateProductDiscount() {
  if (productEditForm.original_price && productEditForm.discount_percent) {
    const discount = (parseFloat(productEditForm.original_price) * parseFloat(productEditForm.discount_percent)) / 100
    productEditForm.price = (parseFloat(productEditForm.original_price) - discount).toFixed(2)
  }
}

// Watch for changes in original_price or discount_percent when on_sale is true
watch([() => productEditForm.original_price, () => productEditForm.discount_percent], () => {
  if (productEditForm.on_sale && productEditForm.original_price && productEditForm.discount_percent) {
    calculateProductDiscount()
  }
})

// Watch for on_sale changes - clear sale fields when unchecked
watch(() => productEditForm.on_sale, (isOnSale) => {
  if (!isOnSale) {
    productEditForm.original_price = ''
    productEditForm.discount_percent = ''
  }
})

function submitProductEdit() {
  // Prepare data for submission
  const formData = {
    name: productEditForm.name,
    brand_id: productEditForm.brand_id,
    product_category_id: productEditForm.product_category_id,
    price: parseFloat(productEditForm.price),
    size_mg: productEditForm.size_mg,
    purity: productEditForm.purity ? parseFloat(productEditForm.purity) : null,
    featured: productEditForm.featured,
    hidden: productEditForm.hidden,
    lab_tested: productEditForm.lab_tested,
    first_timer_deals: productEditForm.first_timer_deals,
    _token: productEditForm._token
  }
  
  // If on sale, set original_price or calculate from discount
  if (productEditForm.on_sale) {
    if (productEditForm.original_price) {
      formData.original_price = parseFloat(productEditForm.original_price)
    } else if (productEditForm.discount_percent && productEditForm.price) {
      // Calculate original price from discount percent
      const price = parseFloat(productEditForm.price)
      const discountPercent = parseFloat(productEditForm.discount_percent)
      formData.original_price = (price / (1 - discountPercent / 100)).toFixed(2)
    }
  } else {
    // If not on sale, clear original_price
    formData.original_price = null
  }
  
  // Update product
  productEditForm.transform(() => formData).put(`/admin/products/${editingProduct.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      closeProductEditModal()
      // Refresh the page to get updated product data
      router.reload({ only: ['products'] })
    },
    onError: (errors) => {
      console.error('Update error:', errors)
    }
  })
}

function handleImageError(productId) {
  // Mark this product's image as failed to load
  imageErrors.value[productId] = true
}
</script>

