<template>
  <FrontLayout>
    <!-- Hero Section -->
    <section class="pt-0 pb-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-[1360px] h-[334px] rounded-[24px] overflow-hidden relative mx-auto">
          <!-- Background Image -->
          <div 
            ref="heroBgRef"
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            :data-bg-image="brand.banner || '/images/hero/hero1.jpg'"
            :style="{ backgroundImage: heroBgLoaded ? `url(${brand.banner || '/images/hero/hero1.jpg'})` : 'none' }"
          >
            <div 
              class="absolute inset-0 pointer-events-none"
              :style="{ background: 'linear-gradient(260deg, rgba(0, 0, 0, 0.4) 45.49%, rgba(0, 0, 0, 0.81) 79.33%)' }"
            ></div>
          </div>
          
          <!-- Content -->
          <div class="relative p-6 h-full flex flex-col justify-center max-w-[800px] gap-4">
            <div class="flex flex-col gap-3">
              <h1 class="font-hv-muse font-normal text-[52px] leading-loose tracking-normal text-white m-0">Harmony Through Discovery</h1>
              <p class="font-roboto font-normal text-lg leading-loose tracking-normal text-white m-0">Everything You Need to Know About Peptides</p>
            </div>
            <button 
              @click="handleCtaClick('/education')"
              class="w-fit py-2.5 px-20 rounded-[500px] bg-white font-roboto font-medium text-xl leading-none tracking-normal text-gray-800 border-none cursor-pointer transition-colors duration-300 flex items-center justify-center hover:bg-gray-100"
            >
              Read Details
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Brand Detail Section -->
    <section class="py-8 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg p-8 flex flex-col md:flex-row gap-8">
          <!-- Left: Brand Logo -->
          <div class="flex-shrink-0 w-full md:w-[400px] flex items-center justify-center">
            <div class="w-[400px] h-[400px] rounded-[24px] flex items-center justify-center overflow-hidden" style="background-color: #F3F3F3;">
              <img 
                v-if="brand.logo" 
                :src="brand.logo" 
                :alt="brand.name + ' logo'"
                class="w-full h-full object-contain p-4"
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

          <!-- Right: Brand Info (2 columns layout) -->
          <div class="flex-1 flex flex-col gap-4">
            <!-- First Row: Title and Shop Now Button -->
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <h2 class="font-hv-muse font-normal text-4xl leading-normal tracking-normal text-gray-800 m-0 mb-2">{{ brand.name }}</h2>
                <!-- Rating -->
                <div class="flex flex-row gap-2 items-center">
                  <div class="flex gap-0.5">
                    <svg v-for="i in 5" :key="i" class="flex-shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M8 0L9.79611 5.52786H15.6085L10.9062 8.94427L12.7023 14.4721L8 11.0557L3.29772 14.4721L5.09383 8.94427L0.391548 5.52786H6.20389L8 0Z" fill="#FBBF24"/>
                    </svg>
                  </div>
                  <div class="flex items-baseline gap-1">
                    <span class="font-roboto font-normal text-sm leading-relaxed text-gray-800">{{ brand.rating || '0.00' }}</span>
                    <span class="font-roboto font-normal text-sm leading-relaxed text-gray-400">({{ brand.reviews || 0 }})</span>
                  </div>
                </div>
              </div>
              <!-- Shop Now Button -->
              <a
                :href="brand.shop_url || '#'"
                :target="brand.shop_url ? '_blank' : '_self'"
                :rel="brand.shop_url ? 'noopener noreferrer' : ''"
                class="flex-shrink-0 py-[10px] px-[80px] rounded-[500px] text-white font-roboto font-medium text-base leading-none tracking-normal text-center hover:opacity-90 transition-colors flex items-center justify-center gap-[10px]"
                style="background-color: #1F2937;"
              >
                Shop Now
                <svg width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 1L19 8M19 8L12 15M19 8L1 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
            </div>

            

            <!-- Second Row: Description and Social Links -->
            <div class="flex flex-col md:flex-row gap-6 items-start">
              <!-- Description -->
              <div class="flex-1 flex flex-col gap-4">

                <p class="font-roboto font-normal text-base leading-relaxed text-gray-600 m-0">
                  {{ brand.description }}
                </p>

                <!-- Promotional Banner -->
                <div class="relative rounded-[50px] h-[91px] flex items-center justify-between mb-4 overflow-hidden" style="padding-left: 23px; padding-right: 36px; max-width: 489px; background-image: url('/images/promo.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">
                  <!-- Gradient Overlay -->
                  <div class="absolute inset-0 rounded-[50px] overflow-hidden" style="background: linear-gradient(135deg, rgba(254, 193, 133, 0.8) 0%, rgba(232, 154, 90, 0.8) 100%);"></div>
                  
                  <!-- Content - Horizontal Layout -->
                  <div class="flex flex-col items-end relative w-full z-10">
                    <!-- Left Side: WITH PEPSYNC30 CODE -->
                    <div class="flex items-center gap-2">
                      <span class="font-roboto font-medium text-sm text-white">WITH</span>
                      <span class="bg-white border-dashed font-extrabold font-roboto leading-[20px] px-2 py-0.5 rounded text-2xl text-[#075985]" style="border-color: #0EA5E9;">PEPSYNC30</span>
                      <span class="font-roboto font-medium text-sm text-white">CODE</span>
                    </div>
                    <!-- Right Side: New Customers - 30% OFF -->
                    <div class="flex items-center gap-1">
                      <span class="font-roboto font-medium text-xs text-white">New Customers</span>
                      <span class="font-roboto font-bold text-sm text-white">-</span>
                      <span class="font-roboto font-bold text-lg" style="color: #AF5858;">30% OFF</span>
                    </div>
                  </div>
                </div>

              </div>
              
              <!-- Contact Information -->
              <div class="flex-shrink-0 rounded-lg space-y-[10px] min-w-[250px]" style="background-color: #F8FAFC; padding: 50px 20px; border-radius: 7px;">
                <!-- Website Link -->
                <div class="flex items-center gap-[10px]">
                  <div class="w-[38px] h-[38px] rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(7, 89, 133, 0.44);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                  </div>
                  <a :href="brand.shop_url || '#'" :target="brand.shop_url ? '_blank' : '_self'" class="text-gray-800 hover:underline font-roboto font-normal text-sm underline">{{ brand.shop_url ? brand.shop_url.replace(/^https?:\/\//, '') : 'www.peptidexyz.com' }}</a>
                </div>
                <!-- Email -->
                <div class="flex items-center gap-[10px]">
                  <div class="w-[38px] h-[38px] rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(7, 89, 133, 0.44);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <a :href="`mailto:${brand.contact_email || 'info@peptidexyz.com'}`" class="text-gray-800 hover:underline font-roboto font-normal text-sm underline">{{ brand.contact_email || 'info@peptidexyz.com' }}</a>
                </div>
                <!-- Phone -->
                <div class="flex items-center gap-[10px]">
                  <div class="w-[38px] h-[38px] rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(7, 89, 133, 0.44);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <a :href="`tel:${brand.phone_number || '222-222-2222'}`" class="text-gray-800 hover:underline font-roboto font-normal text-sm underline">{{ brand.phone_number || '222-222-2222' }}</a>
                </div>
                <!-- Shipping -->
                <div class="flex items-center gap-[10px]">
                  <div class="w-[38px] h-[38px] rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(7, 89, 133, 0.44);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M13 16V6C13 5.44772 12.5523 5 12 5H4C3.44772 5 3 5.44772 3 6V16C3 16.5523 3.44772 17 4 17H5M13 16C13 16.5523 12.5523 17 12 17H9M13 16L13 8C13 7.44772 13.4477 7 14 7H16.5858C16.851 7 17.1054 7.10536 17.2929 7.29289L20.7071 10.7071C20.8946 10.8946 21 11.149 21 11.4142V16C21 16.5523 20.5523 17 20 17H19M13 16C13 16.5523 13.4477 17 14 17H15M5 17C5 18.1046 5.89543 19 7 19C8.10457 19 9 18.1046 9 17M5 17C5 15.8954 5.89543 15 7 15C8.10457 15 9 15.8954 9 17M15 17C15 18.1046 15.8954 19 17 19C18.1046 19 19 18.1046 19 17M15 17C15 15.8954 15.8954 15 17 15C18.1046 15 19 15.8954 19 17" stroke="white" stroke-width="2"/>
                    </svg>
                  </div>
                  <span class="text-gray-800 font-roboto font-normal text-sm underline">Free Shipping Over $200</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Product Listing Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Brand Name Title -->
        <h2 class="font-hv-muse font-normal text-center text-5xl leading-normal tracking-normal text-gray-800 m-0 mb-6">{{ brand.name }} Offers</h2>

        <!-- Main Content Area (Full Width) -->
        <div class="w-full">
            <!-- Search Bar, Item Count, and Sort (First Row) -->
            <div class="mb-4 flex items-center gap-4">
              <!-- Search Bar -->
              <div class="relative flex-1 min-w-[200px] max-w-md">
                <input
                  v-model="searchQuery"
                  @input="handleSearchInput"
                  type="text"
                  placeholder="Search Peptide..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-[500px] font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500"
                />
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>

              <!-- Item Count and Sort (on the right) -->
              <div class="flex items-center gap-4 ml-auto">
                <span class="font-roboto font-normal text-base leading-normal tracking-normal text-gray-800">{{ products.total }} Items</span>
                <div class="relative">
                  <button 
                    @click="showSortDropdown = !showSortDropdown"
                    class="flex items-center gap-2 py-2 px-4 rounded-lg bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 cursor-pointer hover:bg-gray-300"
                  >
                    Sort Items
                    <svg v-if="props.sortDir === 'asc'" width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13" stroke="#1F2937" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <svg v-else width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <g transform="translate(0,18) scale(1,-1)">
                        <path d="M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13" stroke="#1F2937" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </g>
                    </svg>
                  </button>
                  <div 
                    v-if="showSortDropdown"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-10 border border-gray-200"
                  >
                    <button 
                      @click="applySort('price', 'asc')"
                      class="w-full text-left px-4 py-2 hover:bg-gray-100 font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 flex items-center gap-2"
                    >
                      <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0">
                        <path d="M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13" stroke="#1F2937" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      Low to High
                    </button>
                    <button 
                      @click="applySort('price', 'desc')"
                      class="w-full text-left px-4 py-2 hover:bg-gray-100 font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 flex items-center gap-2"
                    >
                      <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0">
                        <g transform="translate(0,18) scale(1,-1)">
                          <path d="M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13" stroke="#1F2937" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                      </svg>
                      High to Low
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Filter By Label and Filter Buttons (Second Row) -->
            <div class="mb-6 flex items-center gap-2 flex-wrap">
              <span class="font-roboto font-medium text-sm leading-none tracking-normal text-gray-800">Filter By</span>
              <button 
                v-for="filter in quickFilters"
                :key="filter.key"
                @click="toggleQuickFilter(filter.key)"
                :class="[
                  'py-2 px-4 rounded-[500px] font-roboto font-medium text-sm leading-none tracking-normal cursor-pointer transition-colors',
                  activeFilters[filter.key] 
                    ? 'bg-gray-800 text-white hover:bg-gray-700' 
                    : 'bg-gray-200 text-gray-800 hover:bg-gray-300'
                ]"
              >
                {{ filter.label }}
                <span v-if="getFilterCount(filter.key) > 0" class="ml-1 bg-orange-500 text-white px-2 py-0.5 text-xs">
                  {{ getFilterCount(filter.key) }}
                </span>
              </button>
            </div>

            <!-- Product Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8">
              <div
                v-for="product in products.data"
                :key="product.id"
                class="bg-white rounded-lg overflow-hidden flex flex-col transition-shadow duration-300 hover:shadow-lg"
              >
                <div class="w-full aspect-square bg-gray-100 flex items-center justify-center p-6 overflow-hidden rounded-lg">
                  <img 
                    v-if="product.image_url"
                    :src="product.image_url" 
                    :alt="product.name"
                    class="w-full h-full object-contain object-center"
                    loading="lazy"
                    @error="handleImageError($event)"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                    <span class="text-sm">No Image</span>
                  </div>
                </div>
                <div class="p-4 flex flex-col gap-2 flex-1">
                  <h3 class="font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0 text-center">{{ product.name }}</h3>
                  <div class="flex items-center justify-center gap-2">
                    <p v-if="product.discount_price" class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-400 m-0 line-through">${{ product.price }}</p>
                    <p class="font-roboto font-normal text-base leading-normal tracking-normal text-gray-800 m-0">${{ product.discount_price || product.price }}</p>
                  </div>
                  <p class="font-roboto font-normal text-sm leading-relaxed text-gray-500 m-0 text-center">
                    From Seller 
                    <span class="inline-block bg-[#E0F2FE] text-gray-800 rounded-lg py-[3px] px-2">{{ product.brand?.name || 'Unknown' }}</span>
                  </p>
                  <div class="flex items-center justify-center gap-1">
                    <div class="flex items-center">
                      <svg 
                        v-for="i in 5" 
                        :key="i"
                        class="w-4 h-4"
                        :class="i <= Math.round(product.rating_average || 0) ? 'text-yellow-500' : 'text-gray-300'"
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    </div>
                    <span class="font-roboto font-normal text-xs leading-normal tracking-normal text-gray-500">
                      ({{ product.rating_count || 0 }} reviews)
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-center mt-8">
              <div class="flex items-center gap-2 bg-gray-200 rounded-[100px] px-4 py-2">
                <Link
                  v-if="products.prev_page_url"
                  :href="products.prev_page_url"
                  class="flex items-center justify-center w-8 h-8 rounded-[500px] bg-gray-200 hover:bg-gray-300 transition-colors"
                >
                  <svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.84853 11.6485C6.3799 12.1172 5.6201 12.1172 5.15147 11.6485L0.351472 6.84853C-0.117157 6.3799 -0.117157 5.6201 0.351472 5.15147L5.15147 0.351472C5.6201 -0.117157 6.3799 -0.117157 6.84853 0.351472C7.31716 0.820101 7.31716 1.5799 6.84853 2.04853L4.09706 4.8L13.2 4.8C13.8627 4.8 14.4 5.33726 14.4 6C14.4 6.66274 13.8627 7.2 13.2 7.2H4.09706L6.84853 9.95147C7.31716 10.4201 7.31716 11.1799 6.84853 11.6485Z" fill="#475569"/>
                  </svg>
                </Link>
                <span
                  v-else
                  class="flex items-center justify-center w-8 h-8 rounded-[500px] bg-gray-200 cursor-not-allowed"
                >
                  <svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.84853 11.6485C6.3799 12.1172 5.6201 12.1172 5.15147 11.6485L0.351472 6.84853C-0.117157 6.3799 -0.117157 5.6201 0.351472 5.15147L5.15147 0.351472C5.6201 -0.117157 6.3799 -0.117157 6.84853 0.351472C7.31716 0.820101 7.31716 1.5799 6.84853 2.04853L4.09706 4.8L13.2 4.8C13.8627 4.8 14.4 5.33726 14.4 6C14.4 6.66274 13.8627 7.2 13.2 7.2H4.09706L6.84853 9.95147C7.31716 10.4201 7.31716 11.1799 6.84853 11.6485Z" fill="#94A3B8"/>
                  </svg>
                </span>
                <template v-for="page in visiblePages" :key="page">
                  <Link
                    v-if="page !== '...'"
                    :href="getPageUrl(page)"
                    :class="[
                      'flex items-center justify-center min-w-[32px] h-8 px-2 rounded-[500px] font-roboto font-medium text-sm leading-none tracking-normal transition-colors',
                      page === products.current_page
                        ? 'bg-white text-gray-800'
                        : 'text-gray-800 hover:text-gray-600'
                    ]"
                  >
                    {{ page }}
                  </Link>
                  <span v-else class="flex items-center justify-center min-w-[32px] h-8 px-2 text-gray-800">...</span>
                </template>
                <Link
                  v-if="products.next_page_url"
                  :href="products.next_page_url"
                  class="flex items-center justify-center w-8 h-8 rounded-[500px] bg-gray-200 hover:bg-gray-300 transition-colors"
                >
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.3513 6.35147C12.8199 5.88284 13.5797 5.88284 14.0483 6.35147L18.8483 11.1515C19.317 11.6201 19.317 12.3799 18.8483 12.8485L14.0483 17.6485C13.5797 18.1172 12.8199 18.1172 12.3513 17.6485C11.8826 17.1799 11.8826 16.4201 12.3513 15.9515L15.1028 13.2L5.99981 13.2C5.33706 13.2 4.7998 12.6627 4.7998 12C4.7998 11.3373 5.33706 10.8 5.99981 10.8H15.1028L12.3513 8.04853C11.8826 7.5799 11.8826 6.8201 12.3513 6.35147Z" fill="#475569"/>
                  </svg>
                </Link>
                <span
                  v-else
                  class="flex items-center justify-center w-8 h-8 rounded-[500px] bg-gray-200 cursor-not-allowed"
                >
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.3513 6.35147C12.8199 5.88284 13.5797 5.88284 14.0483 6.35147L18.8483 11.1515C19.317 11.6201 19.317 12.3799 18.8483 12.8485L14.0483 17.6485C13.5797 18.1172 12.8199 18.1172 12.3513 17.6485C11.8826 17.1799 11.8826 16.4201 12.3513 15.9515L15.1028 13.2L5.99981 13.2C5.33706 13.2 4.7998 12.6627 4.7998 12C4.7998 11.3373 5.33706 10.8 5.99981 10.8H15.1028L12.3513 8.04853C11.8826 7.5799 11.8826 6.8201 12.3513 6.35147Z" fill="#94A3B8"/>
                  </svg>
                </span>
              </div>
            </div>
          </div>
        </div>
    </section>

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
import { ref, computed, onMounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'

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
  showSortDropdown.value = false
  const params = new URLSearchParams(window.location.search)
  
  // Preserve search query
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  }
  
  params.set('sort', sort)
  params.set('sort_dir', dir)
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

const visiblePages = computed(() => {
  const current = props.products.current_page
  const last = props.products.last_page
  const pages = []
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 3) {
      for (let i = 1; i <= 4; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 2) {
      pages.push(1)
      pages.push('...')
      for (let i = last - 3; i <= last; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    }
  }
  
  return pages
})

const handleCtaClick = (url) => {
  router.visit(url)
}

const handleImageError = (event) => {
  // Prevent infinite loop - stop trying to load images if we've already failed
  if (event.target.dataset.failed) {
    return
  }
  // Mark as failed to prevent retry
  event.target.dataset.failed = 'true'
  // Hide the broken image and show placeholder
  event.target.style.display = 'none'
  if (event.target.parentElement) {
    const placeholder = document.createElement('div')
    placeholder.className = 'w-full h-full flex items-center justify-center text-gray-400'
    placeholder.innerHTML = '<span class="text-sm">No Image</span>'
    event.target.parentElement.appendChild(placeholder)
  }
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

