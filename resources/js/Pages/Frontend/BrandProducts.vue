<template>
  <ModernLayout>
    <!-- Vendor Detail -->
    <div class="min-h-screen">
      <!-- Cover banner -->
      <div class="relative h-36 md:h-52 overflow-hidden" :style="{ background: coverGradient }">
        <img v-if="brand.banner || brand.banner_image_url" :src="brand.banner || brand.banner_image_url" :alt="brand.name" class="absolute inset-0 w-full h-full object-cover" loading="lazy" />
        <div class="absolute inset-0 bg-gradient-to-r from-[#0F172A]/60 via-[#0F172A]/20 to-transparent" />
        <div class="absolute inset-0 opacity-[0.03]" :style="{ backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)', backgroundSize: '28px 28px' }" />
        <div v-if="brand.is_partner" class="absolute top-4 right-4 z-10">
          <span class="ui-mono text-[10px] uppercase tracking-[0.12em] px-2.5 py-1 bg-white/15 backdrop-blur-sm text-white border border-white/15 font-semibold">Partner</span>
        </div>
      </div>

      <!-- Vendor header — directly below banner, no overlap, no floating cards -->
      <div class="border-b border-[color:var(--color-hairline)] bg-white">
        <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-6">
          <div class="flex flex-col md:flex-row md:items-start gap-5">
            <!-- Logo -->
            <div class="w-16 h-16 flex-shrink-0 border border-[color:var(--color-hairline)] bg-white flex items-center justify-center overflow-hidden" :style="{ background: brand.logo ? 'white' : coverGradient }">
              <img v-if="brand.logo" :src="brand.logo" :alt="brand.name" class="w-full h-full object-contain p-1.5" loading="lazy" />
              <span v-else class="text-xl font-bold text-white select-none ui-display">{{ brand.initials }}</span>
            </div>

            <!-- Name + meta -->
            <div class="flex-1 min-w-0">
              <h1 class="ui-display text-2xl md:text-3xl font-semibold tracking-tight text-[color:var(--color-ink)] mb-2">{{ brand.name }}</h1>
              <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-[color:var(--color-ink-muted)]">
                <div class="flex items-center gap-1.5">
                  <svg v-for="n in 5" :key="n" class="w-3.5 h-3.5" :class="n <= Math.round(brand.rating || 0) ? 'text-[color:var(--color-caution)]' : 'text-[color:var(--color-hairline)]'" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                  <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ brand.rating || '0.0' }}</span>
                  <span>({{ brand.reviews || totalReviews }})</span>
                </div>
                <span v-if="brand.location" class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 00-8 8c0 5.5 8 12 8 12s8-6.5 8-12a8 8 0 00-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                  {{ brand.location }}
                </span>
                <span v-if="brand.contact_email" class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m22 7-8.991 5.727a2 2 0 01-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                  {{ brand.contact_email }}
                </span>
                <span v-if="brand.phone_number" class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                  {{ brand.phone_number }}
                </span>
              </div>
              <p v-if="brand.description" class="mt-3 text-sm text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl">
                {{ truncateToSentences(brand.description, 3) }}
              </p>
            </div>

            <!-- CTAs -->
            <div class="flex items-center gap-3 flex-shrink-0 md:pt-1">
              <a
                :href="brand.affiliate_visit_url || brand.shop_url || '#'"
                :target="(brand.affiliate_visit_url || brand.shop_url) ? '_blank' : '_self'"
                rel="noopener noreferrer nofollow sponsored"
                class="ui-focus inline-flex items-center gap-2 h-10 px-5 text-[14px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_1px_2px_rgba(10,11,14,0.08),0_8px_20px_-8px_rgba(79,70,229,0.4)] hover:-translate-y-[1px] transition-all"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
                Visit website
              </a>
              <button
                @click="copyDiscountCode"
                class="ui-focus group inline-flex items-center gap-3 h-10 pl-3 pr-4 border-2 border-dashed border-emerald-300 bg-emerald-50 hover:border-emerald-400 hover:bg-emerald-100 transition-all"
              >
                <span class="text-[10px] uppercase tracking-[0.08em] font-semibold text-emerald-600">Coupon</span>
                <span class="ui-mono text-[15px] font-bold text-emerald-800 tracking-wide">{{ brand.discount_code || 'PMAP' }}</span>
                <svg class="w-3.5 h-3.5 text-emerald-500 group-hover:text-emerald-700 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Product Listing Section -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <!-- Left Main Content Area (2/3) -->
          <div class="lg:col-span-3 space-y-8">
            <section>
              <!-- Products Section Header -->
              <div class="flex items-center gap-3 mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-6 h-6 text-gray-900" aria-hidden="true">
                  <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
                  <path d="M12 22V12"></path>
                  <polyline points="3.29 7 12 12 20.71 7"></polyline>
                  <path d="m7.5 4.27 9 5.15"></path>
                </svg>
                <h2 class="text-2xl text-gray-900">Products </h2>
                <span class="text-gray-500">({{ products.total }})</span>
              </div>
              <div class="mb-6 space-y-4">
                <div class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true">
                    <path d="m21 21-4.34-4.34"></path>
                    <circle cx="11" cy="11" r="8"></circle>
                  </svg>

                  <input
                    v-model="searchQuery"
                    @input="handleSearchInput"
                    type="text"
                    placeholder="Search products..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                  />                  
                </div>
                <div class="flex items-center gap-2">
                  <!-- Sort by Options -->
                  <span class="text-sm text-gray-600">Sort by:</span>
                  <div class="flex gap-2 flex-wrap">
                    <button
                      @click="applySort('featured', 'desc')"
                      :class="[
                        'px-4 py-2 rounded-lg text-sm transition-all',
                        currentSort === 'featured' ? 'bg-slate-700 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:border-slate-300'
                      ]"
                    >
                      Featured
                    </button>
                    <button
                      @click="applySort('price', 'asc')"
                      :class="[
                        'px-4 py-2 rounded-lg text-sm transition-all',
                        currentSort === 'price' && currentSortDir === 'asc' ? 'bg-slate-700 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:border-slate-300'
                      ]"
                    >
                      Price: Low to High
                    </button>
                    <button
                      @click="applySort('price', 'desc')"
                      :class="[
                        'px-4 py-2 rounded-lg text-sm transition-all',
                        currentSort === 'price' && currentSortDir === 'desc' ? 'bg-slate-700 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:border-slate-300'
                      ]"
                    >
                      Price: High to Low
                    </button>
                    <button
                      @click="applySort('rating', 'desc')"
                      :class="[
                        'px-4 py-2 rounded-lg text-sm transition-all',
                        currentSort === 'rating' ? 'bg-slate-700 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:border-slate-300'
                      ]"
                    >
                      Highest Rated
                    </button>
                    <button
                      @click="applySort('reviews', 'desc')"
                      :class="[
                        'px-4 py-2 rounded-lg text-sm transition-all',
                        currentSort === 'reviews' ? 'bg-slate-700 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:border-slate-300'
                      ]"
                    >
                      Most Reviews
                    </button>
                    <button
                      @click="applySort('name', 'asc')"
                      :class="[
                        'px-4 py-2 rounded-lg text-sm transition-all',
                        currentSort === 'name' ? 'bg-slate-700 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:border-slate-300'
                      ]"
                    >
                      Name A-Z
                    </button>
                  </div>
                </div>
              </div>

              <!-- Product Grid -->
              <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <ProductSimpleCard
                  v-for="product in products.data"
                  :key="product.id"
                  :name="product.name"
                  :image-url="product.image_url"
                  :price="product.price"
                  :discount-price="product.discount_price"
                  :rating-average="product.rating_average"
                  :rating-count="product.rating_count"
                  :to="`/product/${product.slug}/${product.id}`"
                />
              </div>
            </section>
            <section>
              <h2 class="text-2xl text-gray-900 mb-6">Customer Reviews</h2>
              <!-- Customer Reviews Section -->
              <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">

                <!-- Leave feedback CTA (customers only) -->
                <div v-if="isCustomer" class="space-y-3 mb-6">
                  <button
                    type="button"
                    @click="showLeaveFeedback = !showLeaveFeedback"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium transition-colors"
                  >
                    Leave feedback?
                  </button>

                  <div v-if="showLeaveFeedback" class="mt-2 bg-white rounded-lg">
                    <VendorGrading
                      :shipping-time="0"
                      :customer-service="0"
                      :quality="0"
                      :cost="0"
                      :packaging="0"
                      :is-loading="isSubmittingReview"
                      cancel-text="Cancel"
                      @submit="handleGradingSubmit"
                      @cancel="showLeaveFeedback = false"
                    />
                  </div>
                </div>

                <!-- Overall Rating -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div class="text-center">
                    <div class="text-5xl text-gray-900 mb-2">{{ formattedOverallRating }}</div>
                    <div class="flex items-center justify-center gap-1 mb-2">
                      <svg 
                        v-for="i in 5" 
                        :key="i" 
                        xmlns="http://www.w3.org/2000/svg" 
                        width="24" 
                        height="24" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round" 
                        :class="i <= Math.round(props.brand.rating || 0) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'"
                      >
                        <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                      </svg>
                    </div>
                    <div class="text-gray-600">{{ totalReviews }} {{ totalReviews === 1 ? 'review' : 'reviews' }}</div>
                  </div>
                  
                  <!-- Star Breakdown -->
                  <div class="space-y-2">
                    <div v-for="star in 5" :key="star" class="flex items-center gap-3">
                      <span class="text-sm text-gray-600 w-12">{{ 6 - star }} stars</span>
                      <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div 
                          class="h-full bg-yellow-400 transition-all" 
                          :style="{ width: starBreakdown[6 - star] ? `${(starBreakdown[6 - star] / totalReviews) * 100}%` : '0%' }"
                        ></div>
                      </div>
                      <span class="text-sm text-gray-600 w-8">{{ starBreakdown[6 - star] || 0 }}</span>
                    </div>
                  </div>
                </div>  
              </div>

              <div v-if="reviews && reviews.length > 0" class="mt-5 space-y-4">
                <div 
                  v-for="review in reviews" 
                  :key="review.id"
                  class="bg-white border border-gray-200 rounded-lg p-6"
                >
                  <div class="flex items-start justify-between mb-4">
                    <div>
                      <div class="flex items-center gap-2 mb-2">
                        <span class="text-gray-900 font-medium">{{ review.user_name }}</span>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                          Verified Purchase
                        </span>
                      </div>
                      <div class="flex items-center gap-1">
                        <svg 
                          v-for="i in 5" 
                          :key="i" 
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round" 
                          :class="i <= review.rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'"
                          class="lucide lucide-star w-4 h-4"
                        >
                          <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                        </svg>
                      </div>
                    </div>
                    <span class="text-sm text-gray-500">{{ review.created_at }}</span>
                  </div>
                  <p class="text-gray-700 mb-2">{{ review.review }}</p>
                  <p class="text-sm text-gray-500">Product: BPC-157 5mg</p>
                </div>
              </div>

              <!-- No Reviews Message -->
              <div v-else class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-gray-500">No reviews yet</p>
              </div>
            </section> 
          </div>

          <!-- Right Sidebar (1/3) -->
          <aside class="lg:col-span-1">
            <div class="space-y-6 sticky top-24">
              <!-- Business Details Panel -->
              <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg text-gray-900 mb-4">Business Details</h3>                
                <div class="space-y-3">
                  <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-4 h-4 text-gray-500 mt-0.5" aria-hidden="true">
                      <path d="M8 2v4"></path>
                      <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                      <path d="M16 2v4"></path>
                      <path d="M3 10h18"></path>
                    </svg>
                    <div>
                      <div class="text-xs text-gray-500">Established</div>
                      <div class="text-sm text-gray-900">{{ brand.founded_year || '2013' }}</div>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-4 h-4 text-gray-500 mt-0.5" aria-hidden="true">
                      <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                    </svg>
                    <div>
                      <div class="text-xs text-gray-500">Rating</div>
                      <div class="text-sm text-gray-900">{{ formattedOverallRating }} / 5.0 ({{ props.brand.reviews || totalReviews }} {{ (props.brand.reviews || totalReviews) === 1 ? 'review' : 'reviews' }})</div>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-4 h-4 text-gray-500 mt-0.5" aria-hidden="true">
                      <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
                      <polyline points="3.29 7 12 12 20.71 7"></polyline>
                      <path d="m7.5 4.27 9 5.15"></path>
                    </svg>
                    <div>
                      <div class="text-xs text-gray-500">Produts</div>
                      <div class="text-sm text-gray-900">{{ products.total }} listed</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Certifications Panel -->
              <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center gap-2 mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-5 h-5 text-gray-900" aria-hidden="true">
                    <path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path>
                    <circle cx="12" cy="8" r="6"></circle>
                  </svg>
                  <h3 class="text-lg text-gray-900">Certifications</h3>
                </div>
                <ul class="space-y-2">
                  <li v-for="cert in certifications" :key="cert" class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    <span class="text-sm text-gray-700">{{ cert }}</span>
                  </li>
                </ul>
              </div>

              <!-- Policies Panel -->
              <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg text-gray-900 mb-4">Policies</h3>                
                <div class="space-y-4">
                  <div>
                    <div class="flex items-center gap-2 mb-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-truck w-4 h-4 text-blue-600" aria-hidden="true">
                        <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path>
                        <path d="M15 18H9"></path>
                        <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path>
                        <circle cx="17" cy="18" r="2"></circle>
                        <circle cx="7" cy="18" r="2"></circle>
                      </svg>
                      <div class="text-sm text-gray-900">Shipping</div>
                    </div>
                    <p class="text-sm text-gray-600 pl-6">{{ props.brand.shipping_info || 'Free shipping on orders over $150. Same-day processing for orders before 2PM EST.' }}</p>
                  </div>
                  <div>
                    <div class="flex items-center gap-2 mb-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rotate-ccw w-4 h-4 text-green-600" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path>
                      </svg>
                      <div class="text-sm text-gray-900">Returns</div>
                    </div>
                    <p class="text-sm text-gray-600 pl-6">{{ props.brand.return_policy || '30-day satisfaction guarantee. Unopened products eligible for return.' }}</p>
                  </div>
                  <div>
                    <div class="flex items-center gap-2 mb-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-4 h-4 text-purple-600" aria-hidden="true">
                        <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                      </svg>
                      <div class="text-sm text-gray-900">Payment Methods</div>
                    </div>
                    <div v-if="paymentMethods.length > 0" class="flex flex-wrap gap-2 pl-6">
                      <span v-for="method in paymentMethods" :key="method" class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">{{ method }}</span>
                    </div>
                    <p v-else class="text-sm text-gray-600 pl-6">Not specified</p>
                  </div>
                </div>
              </div>

              <!-- Why Choose Panel -->
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg text-gray-900 mb-4">Why Choose {{ props.brand.name || 'Peptide Sciences' }}?</h3>
                <ul class="space-y-2 text-sm text-gray-700">
                  <li v-for="benefit in whyChooseBenefits" :key="benefit" class="flex items-start gap-2">
                    <span class="text-gray-900 mt-0.5">✓</span>
                    <span>{{ benefit }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </aside>
        </div>
      </div>

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
    </div>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, h, defineComponent, watchEffect } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import MainButton from '@/components/MainButton.vue'
import ProductSimpleCard from '@/components/ProductSimpleCard.vue'
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
  reviews: {
    type: Array,
    default: () => []
  },
  seo: {
    type: Object,
    default: () => ({
      title: null,
      description: null,
      og_title: null,
      og_description: null,
      og_image: null,
      image: null,
      url: null,
      canonical: null,
    })
  }
})

// Deterministic gradient for the cover photo banner
const coverGradient = computed(() => {
  const palette = [
    ['#1E293B', '#4F46E5'], ['#0F172A', '#6366F1'], ['#1E3A8A', '#3B82F6'],
    ['#0C4A6E', '#0EA5E9'], ['#134E4A', '#14B8A6'], ['#312E81', '#4F46E5'],
    ['#111827', '#7C3AED'], ['#164E63', '#06B6D4'],
  ]
  const n = (props.brand?.name || '?').trim()
  let h = 0
  for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
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

const page = usePage()

// Customer auth (shared via Inertia middleware)
const authUser = computed(() => page.props.auth?.user ?? null)
const isCustomer = computed(() => authUser.value?.role === 'customer')

// Computed values for reactive SEO updates (automatically from vendor data)
const title = computed(() => {
  // Use SEO title if provided, otherwise generate from vendor name
  if (props.seo?.title) {
    return props.seo.title
  }
  const siteName = page.props.site_name || 'PeptideMap'
  return `${props.brand?.name || 'Vendor'}: Coupon Codes & Reviews - ${siteName}`
})

const description = computed(() => {
  // Use SEO description if provided, otherwise generate from vendor description
  if (props.seo?.description) {
    return props.seo.description
  }
  if (props.brand?.description) {
    // Truncate to ~155 chars
    const desc = props.brand.description.replace(/\s+/g, ' ').trim()
    return desc.length > 155 ? desc.substring(0, 155) + '...' : desc
  }
  return 'Browse products from ' + (props.brand?.name || 'this vendor') + '. Read reviews, compare prices, and find the best deals.'
})

const url = computed(() => {
  return props.seo?.url || page.url
})

const ogTitle = computed(() => {
  return props.seo?.og_title || title.value
})

const ogDescription = computed(() => {
  return props.seo?.og_description || description.value
})

const ogImage = computed(() => {
  // Use SEO og_image if provided, otherwise use brand logo
  return props.seo?.og_image || props.seo?.image || props.brand?.logo || null
})

const canonical = computed(() => {
  return props.seo?.canonical || url.value
})

// Watch for SEO changes and update document title and meta tags immediately
watchEffect(() => {
  // Update document title
  document.title = title.value
  
  // Update meta description
  let metaDescription = document.querySelector('meta[name="description"]')
  if (!metaDescription) {
    metaDescription = document.createElement('meta')
    metaDescription.setAttribute('name', 'description')
    document.head.appendChild(metaDescription)
  }
  metaDescription.setAttribute('content', description.value)
  
  // Update canonical link
  let canonicalLink = document.querySelector('link[rel="canonical"]')
  if (!canonicalLink) {
    canonicalLink = document.createElement('link')
    canonicalLink.setAttribute('rel', 'canonical')
    document.head.appendChild(canonicalLink)
  }
  canonicalLink.setAttribute('href', canonical.value)
  
  // Update Open Graph tags
  const updateMetaTag = (property, content) => {
    if (!content) return // Don't set empty values
    let meta = document.querySelector(`meta[property="${property}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('property', property)
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', content)
  }
  
  updateMetaTag('og:title', ogTitle.value)
  updateMetaTag('og:description', ogDescription.value)
  updateMetaTag('og:url', url.value)
  if (ogImage.value) {
    updateMetaTag('og:image', ogImage.value)
  }
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

// Payment Methods - use from database, fallback to empty array
const paymentMethods = computed(() => {
  return props.brand?.payment_methods || []
})

// Calculate years in business from founded year
const yearsInBusiness = computed(() => {
  if (props.brand?.founded_year) {
    const currentYear = new Date().getFullYear()
    const foundedYear = parseInt(props.brand.founded_year)
    if (!isNaN(foundedYear) && foundedYear > 0) {
      const years = currentYear - foundedYear
      return years > 0 ? `${years}+` : '1+'
    }
  }
  return '13+' // Default fallback
})

// Why Choose Benefits
const whyChooseBenefits = computed(() => [
  'Third-party lab tested products',
  'Fast & reliable shipping',
  'Responsive customer service',
  'Verified customer reviews',
  `${yearsInBusiness.value} years in business`
])

// Formatted overall rating
const formattedOverallRating = computed(() => {
  if (!props.reviews || props.reviews.length === 0) {
  return (props.brand.rating || 0).toFixed(1)
  }

  const avg =
    props.reviews.reduce((sum, review) => sum + (Number(review.rating) || 0), 0) /
    props.reviews.length

  return avg.toFixed(1)
})

// Calculate total reviews count
const totalReviews = computed(() => {
  return props.reviews?.length || 0
})

// Calculate star breakdown
const starBreakdown = computed(() => {
  const breakdown = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
  if (props.reviews && props.reviews.length > 0) {
    props.reviews.forEach(review => {
      const rating = Math.round(review.rating)
      if (rating >= 1 && rating <= 5) {
        breakdown[rating] = (breakdown[rating] || 0) + 1
      }
    })
  }
  return breakdown
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

function truncateToSentences(text, maxSentences = 3) {
  if (!text) return ''
  const sentences = text.match(/[^.!?]+[.!?]+/g)
  if (!sentences) return text
  return sentences.slice(0, maxSentences).join(' ').trim()
}

const copyDiscountCode = async () => {
  const code = props.brand?.discount_code || 'PMAP'
  
  // Try modern Clipboard API first (requires secure context)
  if (navigator.clipboard && navigator.clipboard.writeText) {
    try {
      await navigator.clipboard.writeText(code)
      alert('Discount code copied to clipboard!')
      return
    } catch (err) {
      console.warn('Clipboard API failed, trying fallback:', err)
    }
  }
  
  // Fallback method for non-secure contexts or older browsers
  try {
    // Create a temporary textarea element
    const textarea = document.createElement('textarea')
    textarea.value = code
    textarea.style.position = 'fixed'
    textarea.style.left = '-999999px'
    textarea.style.top = '-999999px'
    document.body.appendChild(textarea)
    textarea.focus()
    textarea.select()
    
    // Try to copy using execCommand (deprecated but widely supported)
    const successful = document.execCommand('copy')
    document.body.removeChild(textarea)
    
    if (successful) {
      alert('Discount code copied to clipboard!')
    } else {
      // Last resort: show the code and ask user to copy manually
      prompt('Please copy this discount code:', code)
    }
  } catch (err) {
    console.error('Fallback copy method failed:', err)
    // Last resort: show the code and ask user to copy manually
    prompt('Please copy this discount code:', code)
  }
}

// const page = usePage()

// Review submission state
const isSubmittingReview = ref(false)
const reviewMessage = ref(null)
const reviewMessageType = ref('success') // 'success' or 'error'
const showLeaveFeedback = ref(false)

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


// Debug: Log reviews data to check structure
// onMounted(() => {
//   if (props.reviews && props.reviews.length > 0) {
//     console.log('Reviews data:', props.reviews)
//     console.log('First review:', props.reviews[0])
//     console.log('First review.review field:', props.reviews[0]?.review)
//   }
// })

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

