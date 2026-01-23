<template>
  <FrontLayout>
    <!-- Brand Detail Section -->
    <section class="py-8 bg-white">
      <div class="relative overflow-hidden bg-white">
        <div class="absolute inset-0">
          <img src="https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=1600&h=400&fit=crop" class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-white/90"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 flex">
              <div v-if="brand.is_partner" class="absolute top-0 right-0">
                <div class="px-4 py-2 rounded-bl-xl rounded-tr-xl text-sm shadow-sm bg-slate-100 text-slate-700">Partner</div>
              </div>
              <div class="flex flex-col md:flex-row gap-6">
                <div class="flex-shrink-0">
                  <div class="relative">
                    <img 
                      v-if="brand.logo" 
                      :src="brand.logo" 
                      :alt="brand.name + ' logo'"                      
                      loading="lazy"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center relative">
                      <!-- Background oval shape -->
                      <div class="absolute w-[200px] h-[280px] rounded-full" style="background-color: #D8CFC6; opacity: 0.05;"></div>
                      <!-- Initials -->
                      <span class="relative z-10 font-roboto font-semibold text-6xl text-gray-700">{{ brand.initials }}</span>
                    </div>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex flex-wrap items-center gap-3 mb-3">
                    <h1 class="text-3xl text-slate-800">{{ brand.name }}</h1>
                  </div>
                  <div class="flex flex-wrap items-center gap-4 mb-4">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-yellow-50">
                      <!-- Rating -->
                      <RatingDisplay :rating="brand.rating || 0" :reviews="brand.reviews || 0" />
                    </div>
                    <div class="flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-full text-gray-600 bg-gray-50">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucid lucid-map-pin w-4 h-4" aria-hidden="true">
                        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                      </svg>
                      {{ brand.location }}
                    </div>
                  </div>
                  <p class="text-gray-600 mb-5 line-clamp-3">
                    {{ brand.description }}
                  </p>
                  <div class="flex flex-wrap gap-3">
                    <a href="brand.shop_url || '#'" :target="brand.shop_url ? '_blank' : '_self'" target="_blank" rel="noopener noreferrer" class="px-6 py-3 rounded-xl flex items-center gap-2 transition-all shadow-md hover:shadow-lg bg-slate-700 hover:bg-slate-600 text-white">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link w-4 h-4" aria-hidden="true">
                        <path d="M15 3h6v6"></path>
                        <path d="M10 14 21 3"></path>
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                      </svg>
                      Visit Website
                    </a>
                    <button class="px-6 py-3 rounded-lg flex items-center gap-3 transition-all border-[3px] border-dashed border-green-600 bg-green-50 hover:bg-green-100 text-green-700 hover:text-green-800 group">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag w-5 h-5" aria-hidden="true">
                        <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path>
                        <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle>
                      </svg>
                      <span class="text-sm font-semibold">Use Code:</span>
                      <span class="font-mono tracking-wider font-bold">PMAP</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="lg:col-span-1 flex">
              <div class="rounded-2xl shadow-lg p-6 w-full bg-white">
                <div class="flex items-center gap-2 mb-4">
                  <div class="p-2 rounded-lg bg-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info w-4 h-4 text-white" aria-hidden="true">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="M12 16v-4"></path>
                      <path d="M12 8h.01"></path>
                    </svg>
                  </div>
                  <h3 class="text-slate-800">Contact Info</h3>
                </div>
                <div class="space-y-2">
                  <a href="`mailto:${brand.contact_email || 'info@peptidexyz.com'}`" class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-slate-50 transition-colors group">
                    <div class="bg-slate-100 p-2 rounded-lg group-hover:bg-slate-700 transition-colors">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail w-4 h-4 text-slate-700 group-hover:text-white" aria-hidden="true">
                        <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path>
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                      </svg>
                    </div>
                    <span class="text-sm text-slate-600 group-hover:text-slate-700 truncate">{{ brand.contact_email || 'info@peptidexyz.com' }}</span>
                  </a>
                  <a href="`tel:${brand.phone_number || '222-222-2222'}`" class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-slate-50 transition-colors group">
                    <div class="bg-slate-100 p-2 rounded-lg group-hover:bg-slate-700 transition-colors">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-4 h-4 text-slate-700 group-hover:text-white" aria-hidden="true">
                        <path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path>
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                      </svg>
                    </div>
                    <span class="text-sm text-slate-600 group-hover:text-slate-700">{{ brand.phone_number || '222-222-2222' }}</span>
                  </a>
                  <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50">
                    <div class="bg-slate-200 p-2 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin w-4 h-4 text-slate-700" aria-hidden="true">
                        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                      </svg>
                    </div>
                    <span class="text-sm text-slate-600">{{ brand.location }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div> 
      </div>
    </section>

    <!-- Product Listing Section -->
    <section class="py-8 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Left Main Content Area (2/3) -->
          <div class="lg:col-span-2">
            <!-- Products Section Header -->
            <div class="flex items-center gap-2 mb-6">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-700">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
              </svg>
              <h2 class="text-xl font-semibold text-gray-900">Products ({{ products.total }})</h2>
            </div>

            <!-- Search Bar -->
            <div class="relative mb-6">
              <input
                v-model="searchQuery"
                @input="handleSearchInput"
                type="text"
                placeholder="Search products..."
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <!-- Sort by Options -->
            <div class="flex items-center gap-2 mb-6 flex-wrap">
              <span class="text-sm text-gray-600">Sort by:</span>
              <button
                @click="applySort('featured', 'desc')"
                :class="[
                  'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                  currentSort === 'featured' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Featured
              </button>
              <button
                @click="applySort('price', 'asc')"
                :class="[
                  'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                  currentSort === 'price' && currentSortDir === 'asc' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Price: Low to High
              </button>
              <button
                @click="applySort('price', 'desc')"
                :class="[
                  'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                  currentSort === 'price' && currentSortDir === 'desc' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Price: High to Low
              </button>
              <button
                @click="applySort('rating', 'desc')"
                :class="[
                  'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                  currentSort === 'rating' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Highest Rated
              </button>
              <button
                @click="applySort('reviews', 'desc')"
                :class="[
                  'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                  currentSort === 'reviews' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Most Reviews
              </button>
              <button
                @click="applySort('name', 'asc')"
                :class="[
                  'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                  currentSort === 'name' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                Name A-Z
              </button>
            </div>

            <!-- Product Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
              <ProductCard
                v-for="product in products.data"
                :key="product.id"
                :name="product.name"
                :image-url="product.image_url"
                :price="product.price"
                :discount-price="product.discount_price"
                :brand-name="product.brand?.name"
                :rating-average="product.rating_average"
                :rating-count="product.rating_count"
                :category-name="product.category?.name || ''"
                :size-mg="product.size_mg"
                :availability="product.availability"
                :to="`/product/${product.slug}/${product.id}`"
              />
            </div>

            <!-- Pagination -->
            <Pagination
              :pagination="products"
              :get-page-url="getPageUrl"
              :per-page-options="[10, 20, 50, 100]"
              :on-per-page-change="handlePerPageChange"
            />

            <!-- Customer Reviews Section -->
            <div class="mt-12 bg-white rounded-lg border border-gray-200 p-6">
              <h3 class="text-xl font-semibold text-gray-900 mb-6">Customer Reviews</h3>
              
              <!-- Overall Rating -->
              <div class="flex items-start gap-8 mb-6">
                <div class="text-center">
                  <div class="text-5xl font-bold text-gray-900 mb-2">{{ formattedOverallRating }}</div>
                  <div class="flex items-center justify-center gap-1 mb-2">
                    <svg v-for="i in 5" :key="i" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="i <= Math.floor(props.brand.rating) ? 'text-yellow-400 fill-yellow-400' : i === Math.ceil(props.brand.rating) && props.brand.rating % 1 !== 0 ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'">
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                  </div>
                  <p class="text-sm text-gray-600">{{ props.brand.reviews || 0 }} reviews</p>
                </div>
                
                <!-- Star Breakdown -->
                <div class="flex-1 space-y-2">
                  <div v-for="star in 5" :key="star" class="flex items-center gap-3">
                    <span class="text-sm text-gray-700 w-12">{{ 6 - star }} stars</span>
                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-gray-300" style="width: 0%"></div>
                    </div>
                    <span class="text-sm text-gray-500 w-8 text-right">0</span>
                  </div>
                </div>
              </div>

              <!-- No Reviews Message -->
              <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-gray-500">No reviews yet</p>
              </div>
            </div>
          </div>

          <!-- Right Sidebar (1/3) -->
          <div class="lg:col-span-1">
            <div class="space-y-6">
              <!-- Business Details Panel -->
              <div class="bg-gray-100 rounded-lg p-6">
                <div class="flex items-center gap-2 mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-700">
                    <rect width="20" height="14" x="2" y="7" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                  </svg>
                  <h3 class="text-lg font-semibold text-gray-900">Business Details</h3>
                </div>
                <div class="space-y-3">
                  <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-gray-500">
                      <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span class="text-sm text-gray-700">Established: {{ brand.founded_year || '2013' }}</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-gray-500">
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <span class="text-sm text-gray-700">Rating: {{ formattedOverallRating }} / 5.0 ({{ props.brand.reviews || 0 }} reviews)</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-gray-500">
                      <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                      <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                      <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    <span class="text-sm text-gray-700">{{ products.total }} listed</span>
                  </div>
                </div>
              </div>

              <!-- Certifications Panel -->
              <div class="bg-gray-100 rounded-lg p-6">
                <div class="flex items-center gap-2 mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-700">
                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                    <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                    <path d="M10 9H8"></path>
                    <path d="M16 13H8"></path>
                    <path d="M16 17H8"></path>
                    <path d="M10 5H8"></path>
                  </svg>
                  <h3 class="text-lg font-semibold text-gray-900">Certifications</h3>
                </div>
                <ul class="space-y-2">
                  <li v-for="cert in certifications" :key="cert" class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    <span class="text-sm text-gray-700">{{ cert }}</span>
                  </li>
                </ul>
              </div>

              <!-- Policies Panel -->
              <div class="bg-gray-100 rounded-lg p-6">
                <div class="flex items-center gap-2 mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-700">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <path d="M14 2v6h6"></path>
                    <path d="M16 13H8"></path>
                    <path d="M16 17H8"></path>
                    <path d="M10 9H8"></path>
                  </svg>
                  <h3 class="text-lg font-semibold text-gray-900">Policies</h3>
                </div>
                <div class="space-y-4">
                  <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-gray-500 mt-0.5">
                      <path d="M5 18H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2"></path>
                      <path d="M19 18h2a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path>
                      <rect width="14" height="10" x="5" y="6" rx="2"></rect>
                    </svg>
                    <span class="text-sm text-gray-700">{{ props.brand.shipping_info || 'Free 2-day shipping on orders over $200.' }}</span>
                  </div>
                  <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-gray-500 mt-0.5">
                      <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                      <path d="M21 3v5h-5"></path>
                      <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                      <path d="M3 21v-5h5"></path>
                    </svg>
                    <span class="text-sm text-gray-700">{{ props.brand.return_policy || '60-day satisfaction guarantee.' }}</span>
                  </div>
                  <div>
                    <div class="flex items-center gap-2 mb-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-gray-500">
                        <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                      </svg>
                      <span class="text-sm font-medium text-gray-700">Payment Methods:</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                      <span v-for="method in paymentMethods" :key="method" class="px-3 py-1 bg-white rounded-full text-xs text-gray-700 border border-gray-300">{{ method }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Why Choose Panel -->
              <div class="bg-gray-100 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Why Choose {{ props.brand.name }}?</h3>
                <ul class="space-y-3">
                  <li v-for="benefit in whyChooseBenefits" :key="benefit" class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5">
                      <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <span class="text-sm text-gray-700">{{ benefit }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Success/Error Messages -->
    <div
      v-if="reviewMessage"
      :class="[
        'fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg max-w-md',
        reviewMessageType === 'success' 
          ? 'bg-green-50 border border-green-200 text-green-800' 
          : 'bg-red-50 border border-red-200 text-red-800'
      ]"
    >
      <div class="flex items-start gap-3">
        <svg
          v-if="reviewMessageType === 'success'"
          class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <svg
          v-else
          class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <div class="flex-1">
          <p class="font-roboto font-medium text-sm leading-relaxed">
            {{ reviewMessage }}
          </p>
        </div>
        <button
          @click="reviewMessage = null"
          class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Filter Sidebar Modal Overlay -->
    <div 
      v-if="showSidebar"
      class="fixed inset-0 z-50 flex"
      @click="showSidebar = false"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/50"></div>
      
      <!-- Sidebar -->
      <div class="w-full sm:w-96 bg-white shadow-xl overflow-y-auto ml-auto relative z-10" @click.stop>
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="font-hv-muse font-normal text-2xl leading-normal tracking-normal text-teal-700 m-0">Filter Products</h3>
            <button 
              @click="showSidebar = false"
              class="text-gray-500 hover:text-gray-700"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        <div class="p-6 space-y-4">
          <!-- Use Filter -->
          <div>
            <button 
              @click="expandedFilters.use = !expandedFilters.use"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Use</span>
              <svg class="w-5 h-5" :class="expandedFilters.use ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.use" class="mt-2 space-y-2">
              <label 
                v-for="use in filterOptions.uses"
                :key="use.id"
                class="flex items-center gap-2 cursor-pointer"
              >
                <input 
                  type="radio"
                  :value="use.id"
                  v-model="selectedFilters.use"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                  name="use-filter"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">{{ use.name }}</span>
              </label>
            </div>
          </div>

          <!-- Type Filter -->
          <div>
            <button 
              @click="expandedFilters.type = !expandedFilters.type"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Type</span>
              <svg class="w-5 h-5" :class="expandedFilters.type ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.type" class="mt-2 space-y-2">
              <label 
                v-for="type in filterOptions.types"
                :key="type.id"
                class="flex items-center gap-2 cursor-pointer"
              >
                <input 
                  type="radio"
                  :value="type.id"
                  v-model="selectedFilters.type"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                  name="type-filter"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">{{ type.name }}</span>
              </label>
            </div>
          </div>

          <!-- Cost Filter -->
          <div>
            <button 
              @click="expandedFilters.cost = !expandedFilters.cost"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Cost</span>
              <svg class="w-5 h-5" :class="expandedFilters.cost ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.cost" class="mt-2 space-y-2">
              <div class="flex items-center gap-2">
                <input 
                  type="number"
                  v-model.number="selectedFilters.cost_min"
                  @change="applyFilters"
                  placeholder="Min"
                  class="w-full px-3 py-2 border border-gray-300 rounded-[500px] font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-600">to</span>
                <input 
                  type="number"
                  v-model.number="selectedFilters.cost_max"
                  @change="applyFilters"
                  placeholder="Max"
                  class="w-full px-3 py-2 border border-gray-300 rounded-[500px] font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800"
                />
              </div>
            </div>
          </div>

          <!-- Location Filter -->
          <div>
            <button 
              @click="expandedFilters.location = !expandedFilters.location"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Location</span>
              <svg class="w-5 h-5" :class="expandedFilters.location ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.location" class="mt-2 space-y-2">
              <label 
                v-for="location in filterOptions.locations"
                :key="location.id"
                class="flex items-center gap-2 cursor-pointer"
              >
                <input 
                  type="radio"
                  :value="location.id"
                  v-model="selectedFilters.location"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                  name="location-filter"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">{{ location.name }}</span>
              </label>
            </div>
          </div>

          <!-- Verification Filter -->
          <div>
            <button 
              @click="expandedFilters.verification = !expandedFilters.verification"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Verification</span>
              <svg class="w-5 h-5" :class="expandedFilters.verification ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.verification" class="mt-2 space-y-2">
              <label class="flex items-center gap-2 cursor-pointer">
                <input 
                  type="radio"
                  value=""
                  v-model="selectedFilters.verification"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">All</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input 
                  type="radio"
                  value="1"
                  v-model="selectedFilters.verification"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">Verified</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input 
                  type="radio"
                  value="0"
                  v-model="selectedFilters.verification"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">Not Verified</span>
              </label>
            </div>
          </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex gap-4">
          <button 
            @click="clearFilters"
            class="flex-1 py-2 px-4 rounded-[500px] bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 hover:bg-gray-300 flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Clear Filters
          </button>
          <button 
            @click="applyFilters"
            class="flex-1 py-2 px-4 rounded-[500px] bg-teal-700 font-roboto font-medium text-sm leading-none tracking-normal text-white hover:bg-teal-800 flex items-center justify-center gap-2"
          >
            Show ({{ products.total }})
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, h, defineComponent } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import MainButton from '@/components/MainButton.vue'
import ProductCard from '@/components/ProductCard.vue'
import RatingDisplay from '@/components/RatingDisplay.vue'
import VendorGrading from '@/components/VendorGrading.vue'
import Pagination from '@/components/Pagination.vue'

const props = defineProps({
  brand: Object,
  products: Object,
  filterOptions: Object,
  priceRange: Object,
  filters: Object,
  sort: String,
  sortDir: String,
  search: String,
})

const ArrowRightIcon = defineComponent({
  name: 'ArrowRightIcon',
  setup() {
    return () =>
      h(
        'svg',
        {
          width: 20,
          height: 16,
          viewBox: '0 0 20 16',
          fill: 'none',
          xmlns: 'http://www.w3.org/2000/svg',
          class: 'w-5 h-4',
        },
        [
          h('path', {
            d: 'M12 1L19 8M19 8L12 15M19 8L1 8',
            stroke: 'currentColor',
            'stroke-width': 2,
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
          }),
        ],
      )
  },
})

// Hero background lazy loading
const heroBgRef = ref(null)
const heroBgLoaded = ref(false)

// Sort dropdown
const showSortDropdown = ref(false)
// Filter sidebar
const showSidebar = ref(false)
// Initialize searchQuery from URL parameters or props
const searchQuery = ref(props.search || '')
const perPage = ref(props.products.per_page || 20)
const currentSort = ref(props.sort || 'featured')
const currentSortDir = ref(props.sortDir || 'desc')

// Certifications (mock data - can be moved to backend)
const certifications = ref(['ISO 9001', 'cGMP Compliant', 'FDA Registered'])

// Payment Methods
const paymentMethods = ref(['Credit Card', 'PayPal', 'Cryptocurrency', 'Bank Transfer'])

// Why Choose Benefits
const whyChooseBenefits = ref([
  'Third-party lab tested products',
  'Fast & reliable shipping',
  'Responsive customer service',
  'Verified customer reviews',
  '13+ years in business'
])

// Formatted overall rating
const formattedOverallRating = computed(() => {
  return (props.brand.rating || 0).toFixed(1)
})

// Sort icon component
const sortIcon = computed(() => {
  if (props.sortDir === 'asc') {
    return h('svg', {
      width: '20',
      height: '18',
      viewBox: '0 0 20 18',
      fill: 'none',
      xmlns: 'http://www.w3.org/2000/svg'
    }, [
      h('path', {
        d: 'M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13',
        stroke: '#1F2937',
        'stroke-width': '2',
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round'
      })
    ])
  } else {
    return h('svg', {
      width: '20',
      height: '18',
      viewBox: '0 0 20 18',
      fill: 'none',
      xmlns: 'http://www.w3.org/2000/svg'
    }, [
      h('g', {
        transform: 'translate(0,18) scale(1,-1)'
      }, [
        h('path', {
          d: 'M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13',
          stroke: '#1F2937',
          'stroke-width': '2',
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round'
        })
      ])
    ])
  }
})

// Expanded filters
const expandedFilters = ref({
  use: false,
  type: false,
  cost: false,
  location: props.filters?.location ? true : false,
  verification: false,
})

// Selected filters (no brand filter since we're already filtering by brand)
const selectedFilters = ref({
  use: props.filters?.use ? parseInt(props.filters.use) : null,
  type: props.filters?.type ? parseInt(props.filters.type) : null,
  location: props.filters?.location ? parseInt(props.filters.location) : null,
  verification: props.filters?.verification || '',
  cost_min: props.filters?.cost_min || '',
  cost_max: props.filters?.cost_max || '',
})

// Quick filters (no brand filter)
const quickFilters = [
  { key: 'use', label: 'Use' },
  { key: 'type', label: 'Type' },
  { key: 'cost', label: 'Cost' },
  { key: 'location', label: 'Location' },
  { key: 'verification', label: 'Verification' },
]

// Active filters for quick filter buttons
const activeFilters = computed(() => {
  return {
    use: selectedFilters.value.use !== null,
    type: selectedFilters.value.type !== null,
    cost: selectedFilters.value.cost_min || selectedFilters.value.cost_max,
    location: selectedFilters.value.location !== null,
    verification: selectedFilters.value.verification !== '',
  }
})

const getFilterCount = (key) => {
  if (key === 'use') return selectedFilters.value.use !== null ? 1 : 0
  if (key === 'type') return selectedFilters.value.type !== null ? 1 : 0
  if (key === 'location') return selectedFilters.value.location !== null ? 1 : 0
  if (key === 'verification') return selectedFilters.value.verification !== '' ? 1 : 0
  if (key === 'cost') return (selectedFilters.value.cost_min || selectedFilters.value.cost_max) ? 1 : 0
  return 0
}

const toggleQuickFilter = (key) => {
  showSidebar.value = true
  if (key === 'use' || key === 'type' || key === 'location' || key === 'verification' || key === 'cost') {
    expandedFilters.value[key] = true
  }
}

const applyFilters = () => {
  const params = new URLSearchParams()
  
  // Preserve search query
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  }
  
  if (selectedFilters.value.use !== null) {
    params.set('use', selectedFilters.value.use)
  }
  if (selectedFilters.value.type !== null) {
    params.set('type', selectedFilters.value.type)
  }
  if (selectedFilters.value.location !== null) {
    params.set('location', selectedFilters.value.location)
  }
  if (selectedFilters.value.verification) {
    params.set('verification', selectedFilters.value.verification)
  }
  if (selectedFilters.value.cost_min) {
    params.set('cost_min', selectedFilters.value.cost_min)
  }
  if (selectedFilters.value.cost_max) {
    params.set('cost_max', selectedFilters.value.cost_max)
  }
  if (props.sort) {
    params.set('sort', props.sort)
  }
  if (props.sortDir) {
    params.set('sort_dir', props.sortDir)
  }
  if (perPage.value) {
    params.set('per_page', perPage.value)
  }

  router.visit(`/brand/${props.brand.slug}/products?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  selectedFilters.value = {
    use: null,
    type: null,
    location: null,
    verification: '',
    cost_min: '',
    cost_max: '',
  }
  applyFilters()
}

const applySort = (sort, dir) => {
  currentSort.value = sort
  currentSortDir.value = dir
  const params = new URLSearchParams(window.location.search)
  
  // Preserve search query
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  }
  
  // Preserve filters
  if (selectedFilters.value.use !== null) {
    params.set('use', selectedFilters.value.use)
  }
  if (selectedFilters.value.type !== null) {
    params.set('type', selectedFilters.value.type)
  }
  if (selectedFilters.value.location !== null) {
    params.set('location', selectedFilters.value.location)
  }
  if (selectedFilters.value.verification) {
    params.set('verification', selectedFilters.value.verification)
  }
  if (selectedFilters.value.cost_min) {
    params.set('cost_min', selectedFilters.value.cost_min)
  }
  if (selectedFilters.value.cost_max) {
    params.set('cost_max', selectedFilters.value.cost_max)
  }
  
  params.set('sort', sort)
  params.set('sort_dir', dir)
  if (perPage.value) {
    params.set('per_page', perPage.value)
  }
  
  router.visit(`/brand/${props.brand.slug}/products?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

let searchTimeout = null

const handleSearchInput = () => {
  // Debounce search to avoid too many requests
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applySearch()
  }, 500) // Wait 500ms after user stops typing
}

const applySearch = () => {
  const params = new URLSearchParams(window.location.search)
  
  // Update search parameter
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  } else {
    params.delete('search')
  }
  
  // Reset to first page when searching
  params.set('page', '1')
  
  // Preserve other filters
  if (selectedFilters.value.use !== null) {
    params.set('use', selectedFilters.value.use)
  }
  if (selectedFilters.value.type !== null) {
    params.set('type', selectedFilters.value.type)
  }
  if (selectedFilters.value.location !== null) {
    params.set('location', selectedFilters.value.location)
  }
  if (selectedFilters.value.verification) {
    params.set('verification', selectedFilters.value.verification)
  }
  if (selectedFilters.value.cost_min) {
    params.set('cost_min', selectedFilters.value.cost_min)
  }
  if (selectedFilters.value.cost_max) {
    params.set('cost_max', selectedFilters.value.cost_max)
  }
  if (props.sort) {
    params.set('sort', props.sort)
  }
  if (props.sortDir) {
    params.set('sort_dir', props.sortDir)
  }
  if (perPage.value) {
    params.set('per_page', perPage.value)
  }
  
  router.visit(`/brand/${props.brand.slug}/products?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

const getPageUrl = (page) => {
  const params = new URLSearchParams(window.location.search)
  params.set('page', page)
  return `/brand/${props.brand.slug}/products?${params.toString()}`
}

const handlePerPageChange = (perPage) => {
  const params = new URLSearchParams(window.location.search)
  params.set('per_page', perPage)
  params.set('page', 1) // Reset to first page when changing per page
  router.visit(`/brand/${props.brand.slug}/products?${params.toString()}`, {
    preserveState: true,
    preserveScroll: false,
  })
}

const handleCtaClick = (url) => {
  router.visit(url)
}

const page = usePage()

// Review submission state
const isSubmittingReview = ref(false)
const reviewMessage = ref(null)
const reviewMessageType = ref('success') // 'success' or 'error'

const handleGradingSubmit = (gradingData) => {
  isSubmittingReview.value = true
  reviewMessage.value = null

  const form = useForm({
    shipping_time: gradingData.shipping_time,
    customer_service: gradingData.customer_service,
    quality: gradingData.quality,
    cost: gradingData.cost,
    packaging: gradingData.packaging,
    user_name: gradingData.user_name,
    user_email: gradingData.user_email,
    review: gradingData.review || null,
    _token: page.props.csrf_token,
  })

  form.post(`/brands/${props.brand.id}/reviews`, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmittingReview.value = false
      reviewMessageType.value = 'success'
      reviewMessage.value = 'Thank you for your review! Your rating has been submitted successfully.'
      
      // Auto-hide success message after 5 seconds
      setTimeout(() => {
        reviewMessage.value = null
      }, 5000)

      // Refresh the page to show updated ratings
      router.reload({ only: ['brand'] })
    },
    onError: (errors) => {
      isSubmittingReview.value = false
      reviewMessageType.value = 'error'
      
      // Helper function to get error message (handles both array and string formats)
      const getErrorMessage = (error) => {
        if (Array.isArray(error)) {
          return error[0]
        }
        if (typeof error === 'string') {
          return error
        }
        return null
      }
      
      // Build error message from validation errors
      let errorText = 'Failed to submit review. '
      
      if (errors.user_name) {
        errorText = getErrorMessage(errors.user_name) || 'Please enter your name.'
      } else if (errors.user_email) {
        errorText = getErrorMessage(errors.user_email) || 'Please enter a valid email address.'
      } else if (errors.shipping_time || errors.customer_service || errors.quality || errors.cost || errors.packaging) {
        errorText = 'Please rate all categories before submitting.'
      } else if (errors.message) {
        errorText = getErrorMessage(errors.message) || errors.message
      } else if (errors.rating) {
        errorText = getErrorMessage(errors.rating) || 'Please provide a rating.'
      } else {
        // Check for any other error fields
        const firstError = Object.values(errors)[0]
        if (firstError) {
          errorText = getErrorMessage(firstError) || 'Please check your information and try again.'
        } else {
          errorText = 'Please check your information and try again.'
        }
      }
      
      reviewMessage.value = errorText
      
      // Auto-hide error message after 7 seconds
      setTimeout(() => {
        reviewMessage.value = null
      }, 7000)
    }
  })
}


// Setup lazy loading for hero background
onMounted(() => {
  nextTick(() => {
    if (heroBgRef.value) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const bgImage = entry.target.getAttribute('data-bg-image')
            if (bgImage) {
              const img = new Image()
              img.onload = () => {
                heroBgLoaded.value = true
              }
              img.src = bgImage
            }
            observer.unobserve(entry.target)
          }
        })
      }, {
        rootMargin: '50px'
      })
      observer.observe(heroBgRef.value)
    }
  })
})

// Close dropdowns when clicking outside
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      showSortDropdown.value = false
    }
  })
})
</script>

