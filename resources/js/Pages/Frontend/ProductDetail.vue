<template>
  <ModernLayout>
    <!-- Bottom padding on phones keeps the sticky buy bar off the last
         block of content. -->
    <div class="min-h-screen pb-24 lg:pb-0">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-6">
        <!-- Breadcrumb: real links, so they open in new tabs and crawl. -->
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 min-w-0 text-[13px] text-[color:var(--color-ink-muted)] mb-6">
          <Link href="/products" class="flex-shrink-0 hover:text-[color:var(--color-ink)] transition-colors">Products</Link>
          <template v-if="product.category?.slug">
            <span class="text-[color:var(--color-ink-subtle)]" aria-hidden="true">/</span>
            <Link :href="`/product/${product.category.slug}`" class="flex-shrink-0 hover:text-[color:var(--color-ink)] transition-colors">{{ product.category.name }}</Link>
          </template>
          <span class="text-[color:var(--color-ink-subtle)]" aria-hidden="true">/</span>
          <span class="text-[color:var(--color-ink)] font-medium truncate">{{ product.name || 'N/A' }}</span>
        </nav>
        <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-sm)] mb-8">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6 md:p-8">
            <!-- Left: Product Image -->
            <div>
              <div class="aspect-square bg-[color:var(--color-bg)] border border-[color:var(--color-hairline)] p-8 sticky top-24 flex items-center justify-center">
                <img
                  v-if="product.image_url"
                  :src="product.image_url"
                  :alt="product.name"
                  class="max-w-full max-h-full object-contain select-none"
                  loading="lazy"
                />
                <div v-else class="text-center">
                  <svg class="w-20 h-20 text-[color:var(--color-ink-subtle)] mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 2v6l-4 7c-1 1.8 0 4 2 4h10c2 0 3-2.2 2-4l-4-7V2"/><path d="M8 2h8"/>
                  </svg>
                  <span class="text-sm text-[color:var(--color-ink-subtle)]">No image available</span>
                </div>
              </div>
            </div>
  
            <!-- Right: Product Details -->
            <div>
              <!-- Compound chip: links to every vendor's listings for it. -->
              <Link
                v-if="product.category?.slug"
                :href="`/product/${product.category.slug}`"
                class="inline-flex items-center gap-1 h-7 px-3 mb-3 rounded-full bg-[color:var(--color-hairline-soft)] text-[12px] font-semibold text-[color:var(--color-ink-muted)] hover:bg-[color:var(--color-accent-50)] hover:text-[color:var(--color-accent-700)] transition-colors"
              >
                {{ product.category.name }}
                <span class="font-normal text-[color:var(--color-ink-subtle)]">· all vendors</span>
              </Link>
  
              <!-- Product Title + Type chip + Wishlist -->
              <div class="flex items-start gap-3 mb-3">
                <div class="flex items-start gap-2 flex-wrap flex-1 min-w-0">
                  <h1 class="ui-display text-[28px] md:text-3xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)]">{{ product.name }}</h1>
                  <span
                    v-if="productTypeChip"
                    :class="['inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold mt-2', productTypeChip.classes]"
                  >{{ productTypeChip.label }}</span>
                </div>
                <div class="flex-shrink-0 mt-1">
                  <WishlistHeart type="product" :id="product.id" size="lg" />
                </div>
              </div>

              <!-- Who sells it, up top: the seller is half of what a listing
                   page is about. -->
              <div v-if="brand" class="mb-4 flex flex-wrap items-center gap-x-2.5 gap-y-1.5 min-w-0">
                <VendorLogo :src="brand.logo" :name="brand.name" :alt="brand.name" size="xs" radius="sm" fallback="subtle" />
                <span class="text-[13px] text-[color:var(--color-ink-muted)]">by</span>
                <Link :href="`/brand/${brand.slug}`" class="text-[15px] font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-700)] transition-colors truncate">{{ brand.name }}</Link>
                <!-- The name already links there; phones skip the extra link
                     so the row stays on one line. -->
                <span class="hidden sm:inline text-[color:var(--color-ink-subtle)]" aria-hidden="true">·</span>
                <Link :href="`/brand/${brand.slug}`" class="hidden sm:inline text-[13px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors">All their products</Link>
                <span class="text-[color:var(--color-ink-subtle)]" aria-hidden="true">·</span>
                <a :href="reviewVendorHref" class="text-[13px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors">Reviews</a>
              </div>

              <!-- Trust badges + stock — one chip row instead of stock floating on its own -->
              <div class="flex flex-wrap gap-2 mb-4">
                <span
                  :class="[
                    'inline-flex items-center gap-1 px-2 py-1 rounded-[6px] text-[11px] font-semibold',
                    product.availability === 'in_stock'
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-red-50 text-red-700'
                  ]"
                >
                  <span :class="['w-1.5 h-1.5 rounded-full', product.availability === 'in_stock' ? 'bg-emerald-500' : 'bg-red-500']"></span>
                  {{ product.availability === 'in_stock' ? 'In stock' : 'Out of stock' }}
                </span>
                <span v-if="product.lab_tested" class="inline-flex items-center gap-1 px-2 py-1 rounded-[6px] bg-[color:var(--color-verified-bg)] text-[color:var(--color-verified)] text-[11px] font-semibold">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  Tested (vendor-reported)
                </span>
                <span v-if="product.purity" class="inline-flex items-center gap-1 px-2 py-1 rounded-[6px] bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)] text-[11px] font-semibold ui-mono">
                  {{ product.purity }}% purity
                </span>
              </div>

              <div class="border-b border-[color:var(--color-hairline)] mb-5"></div>

              <!-- Price — monospace, prominent -->
              <div class="mb-6">
                <template v-if="discountedPrice">
                  <div class="flex items-baseline gap-3">
                    <span class="text-[12px] uppercase tracking-wide text-[color:var(--color-ink)] font-semibold">Retail</span>
                    <span class="ui-mono text-xl text-[color:var(--color-ink)] line-through font-medium">{{ product.currency_symbol || '$' }}{{ retailPrice }}</span>
                  </div>
                  <div class="text-[12px] uppercase tracking-wide text-emerald-700 font-semibold mt-3">
                    Price with code <span class="ui-mono">{{ effectiveCouponCode }}</span>
                  </div>
                  <div class="ui-mono text-4xl font-bold text-emerald-700 leading-tight mt-1">{{ product.currency_symbol || '$' }}{{ discountedPrice }}</div>
                </template>
                <template v-else>
                  <div class="flex items-baseline gap-3">
                    <span class="ui-mono text-4xl font-bold text-[color:var(--color-ink)]">
                      {{ product.currency_symbol || '$' }}{{ product.discount_price || product.price }}
                    </span>
                    <span
                      v-if="product.discount_price && product.discount_price < product.price"
                      class="ui-mono text-lg text-[color:var(--color-ink-subtle)] line-through"
                    >
                      {{ product.currency_symbol || '$' }}{{ product.price }}
                    </span>
                  </div>
                </template>
                <div v-if="product.size_mg || product.price_per_mg" class="mt-1 flex items-center gap-x-2 gap-y-1 flex-wrap">
                  <span v-if="product.size_mg" class="ui-mono text-sm text-[color:var(--color-ink-muted)]">
                    {{ /[a-zA-Z]/.test(String(product.size_mg)) ? product.size_mg : `${product.size_mg}mg` }} vial
                  </span>
                  <span v-if="product.size_mg && product.price_per_mg" class="text-[color:var(--color-ink-subtle)]" aria-hidden="true">·</span>
                  <PricePerMg
                    v-if="product.price_per_mg"
                    :value="product.price_per_mg"
                    :currency-symbol="product.currency_symbol || '$'"
                    :size-label="detailSizeLabel"
                    :with-code="!!discountedPrice"
                    size="md"
                  />
                </div>
                <!-- Short form of the 90-day history, next to the price. -->
                <div
                  v-if="historyLowestLabel"
                  class="mt-2 inline-flex items-center gap-1 h-6 px-2 rounded-full border border-emerald-200 bg-emerald-50 text-[11px] font-semibold text-emerald-800"
                >
                  <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                  {{ historyLowestLabel }}
                </div>
              </div>

              <!-- Copy the code, then go buy: the ticket sits right above
                   the button that uses it. -->
              <div v-if="ticketCode" class="mb-3">
                <CouponTicket :code="ticketCode" :percent="brand?.discount_percent" />
              </div>

              <!-- Purchase Button — primary CTA, clean -->
              <div v-if="product.product_url" ref="mainCta" class="mb-4">
                <a
                  :href="withSrc(`/go/${product.id}`)"
                  target="_blank"
                  rel="noopener noreferrer nofollow sponsored"
                  class="ui-focus w-full h-[52px] flex items-center justify-center gap-2 rounded-[13px] text-[15px] font-semibold text-white bg-gradient-to-b from-[#0284C7] to-[#0369A1] shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_1px_2px_rgba(10,11,14,0.08),0_10px_24px_-8px_rgba(2,132,199,0.4)] hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.22),0_2px_4px_rgba(10,11,14,0.1),0_14px_32px_-8px_rgba(2,132,199,0.55)] hover:-translate-y-[1px] active:translate-y-0 transition-all"
                >
                  Visit {{ brand.name }} to purchase
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 17L17 7M17 7H7M17 7v10" />
                  </svg>
                </a>
              </div>

              <!-- Compare link — secondary utility below CTA. Coupon lives in the price block above; no need to duplicate. -->
              <div v-if="product.category" class="mb-6">
                <a
                  :href="compareHref"
                  class="text-[13px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors inline-flex items-center gap-1"
                >
                  Compare {{ product.category.name }} across all vendors
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </a>
              </div>

              <!-- 90-day retail price history (change-based snapshots). -->
              <PriceSparkline v-if="priceHistory" :history="priceHistory" class="mb-6" />

              <!-- Discord discussion chip — small ask, not competing with the primary CTA -->
              <a
                href="https://discord.gg/uYj2M9XKa5"
                target="_blank"
                rel="noopener"
                class="ui-focus mb-6 group flex items-center justify-between gap-3 px-4 py-3 rounded-[10px] border border-[color:var(--color-hairline)] bg-[color:var(--color-hairline-soft)] hover:border-[#5865F2]/40 hover:bg-[#5865F2]/[0.04] transition-colors"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="flex-shrink-0 w-8 h-8 rounded-[8px] bg-[#5865F2]/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#5865F2]" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03z"/></svg>
                  </div>
                  <div class="min-w-0">
                    <div class="text-[13px] font-semibold text-[color:var(--color-ink)]">Discuss {{ product.category?.name || product.name }} on Discord</div>
                    <div class="text-[11px] text-[color:var(--color-ink-subtle)] truncate">Real reviews, price alerts, and stack ideas from the community</div>
                  </div>
                </div>
                <svg class="w-4 h-4 flex-shrink-0 text-[color:var(--color-ink-subtle)] group-hover:text-[#5865F2] transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </a>

              <!-- Disclaimer -->
              <div class="bg-[color:var(--color-caution-bg)] border border-amber-200 rounded-[10px] p-3">
                <div class="flex items-start gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert w-4 h-4 text-yellow-600 flex-shrink-0 mt-0.5" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" x2="12" y1="8" y2="12"></line>
                    <line x1="12" x2="12.01" y1="16" y2="16"></line>
                  </svg>
                  <div class="text-xs text-yellow-800">
                    <p>
                      Research purposes only. Not intended for human consumption.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Related Products -->
        <div v-if="relatedProducts && relatedProducts.length > 0" class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] p-5 md:p-8 mb-8">
          <div class="flex flex-wrap items-end justify-between gap-2 mb-5">
            <div>
              <h2 class="ui-display text-xl md:text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">Compare {{ product.category?.name || 'this compound' }} at other vendors</h2>
              <p class="mt-1 text-[13px] text-[color:var(--color-ink-muted)]">Same size first, cheapest per mg first, each with its vendor's code price.</p>
            </div>
            <a v-if="product.category?.slug" :href="`/product/${product.category.slug}`" class="text-[13px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors">All listings →</a>
          </div>
          <!-- Same vial size first, cheapest per mg first, with each
               vendor's code price. -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
            <ProductSimpleCard
              v-for="relatedProduct in relatedProducts"
              :key="relatedProduct.id"
              :product-id="relatedProduct.id"
              :name="relatedProduct.name"
              :raw-name="relatedProduct.raw_name || ''"
              :image-url="relatedProduct.image_url"
              :price="relatedProduct.price"
              :discount-price="relatedProduct.discount_price"
              :brand-name="relatedProduct.brand?.name || ''"
              :rating-average="relatedProduct.rating_average"
              :rating-count="relatedProduct.rating_count"
              :product-type="relatedProduct.product_type"
              :brand-discount-percent="relatedProduct.brand_discount_percent"
              :brand-coupon-code="relatedProduct.brand_coupon_code"
              :currency-symbol="relatedProduct.currency_symbol || '$'"
              :size-mg="relatedProduct.size_mg"
              :price-per-mg="relatedProduct.price_per_mg ?? null"
              :best-value="!!relatedProduct.best_value"
              :to="relatedProduct.brand?.slug
                ? `/product/${relatedProduct.brand.slug}/${relatedProduct.slug}/${relatedProduct.id}`
                : `/product/${relatedProduct.slug}/${relatedProduct.id}`"
            />
          </div>
        </div>

        <!-- Tabs Section -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-8">
          <!-- Tabs -->
          <div class="border-b border-gray-200">
            <div class="flex">
              <button
                @click="activeTab = 'description'"
                :class="activeTab === 'description' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                class="px-6 py-4 text-sm transition-colors relative"
              >
                Description
                <div v-if="activeTab === 'description'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600"></div>
              </button>
              <button
                @click="activeTab = 'reviews'"
                :class="activeTab === 'reviews' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                class="px-6 py-4 text-sm transition-colors relative"
              >
                Vendor Reviews ({{ (reviews || []).length }})
                <div v-if="activeTab === 'reviews'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600"></div>
              </button>
            </div>
          </div>

          <!-- Tab Content -->
          <div class="p-8">
            <!-- Description Tab -->
            <div v-if="activeTab === 'description'" class="text-gray-700 space-y-4 max-w-4xl">
              <p>
                {{ product.name }} from {{ brand?.name || 'our store' }} is a research peptide designed for scientific and research purposes only.
              </p>
              <p>
                Peptidemap does not test this product. Any certificate of analysis is published by the vendor.
              </p>
              <div class="bg-slate-50 rounded-lg p-6 mt-6">
                <h3 class="text-lg text-gray-900 mb-4">Product Information</h3>
                <div class="space-y-3">
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Category:</span>
                    <span class="text-gray-900">{{ product.category?.name || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Brand:</span>
                    <span class="text-gray-900">{{ brand?.name || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between py-2">
                    <span class="text-gray-600">Stock Status:</span>
                    <span
                      :class="product.availability === 'in_stock' ? 'text-green-600' : 'text-red-600'"
                      class="font-medium"
                    >
                      {{ product.availability === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Vendor Reviews Tab — these are reviews of the seller (brand),
                 not the individual product. We removed per-product reviews
                 because the rating numbers were scraped, not authored on PM. -->
            <div v-if="activeTab === 'reviews'" class="max-w-4xl">
              <div v-if="brand" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-900">
                These are reviews of <strong>{{ brand.name }}</strong> as a vendor, not of this specific product.
                <Link :href="`/brand/${brand.slug}`" class="text-blue-700 underline font-medium ml-1">See the full vendor profile →</Link>
              </div>
              <!-- Reviews List -->
              <div v-if="reviews && reviews.length > 0" class="space-y-6">
                <div 
                  v-for="review in reviews" 
                  :key="review.id"
                  class="bg-white border border-gray-200 rounded-lg p-6"
                >
                  <div class="flex items-start justify-between mb-4">
                    <div>
                      <div class="flex items-center gap-2 mb-2">
                        <span class="text-gray-900 font-medium">{{ review.user_name }}</span>
                        <span v-if="review.verified" class="inline-flex items-center gap-1 bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">
                          <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1l8 4v6c0 5.5-3.4 9.9-8 11-4.6-1.1-8-5.5-8-11V5l8-4z"/></svg>
                          Visited via Peptidemap
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
                  <p v-if="review.review" class="text-gray-700">{{ review.review }}</p>
                </div>
              </div>
              
              <!-- No Reviews Message -->
              <div v-else class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-12 h-12 text-gray-300 mx-auto mb-4" aria-hidden="true">
                  <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                </svg>
                <p class="text-gray-500 mb-2">No reviews yet</p>
                <p class="text-sm text-gray-400">Be the first to review this product!</p>
              </div>
            </div>
          </div>
        </div>        

      </div>
    </div>

    <!-- Phones: the image pushes the buy button below the fold, so keep
         price, code and button on screen once the main button scrolls
         away. -->
    <div
      v-if="product.product_url && showStickyBuy"
      class="lg:hidden fixed inset-x-0 bottom-0 z-40 border-t border-[color:var(--color-hairline)] bg-white/95 backdrop-blur px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_-12px_rgba(10,11,14,0.18)]"
    >
      <div class="flex items-center gap-3 max-w-[640px] mx-auto">
        <div class="min-w-0">
          <div class="ui-display text-[20px] font-bold leading-none tracking-tight" :class="discountedPrice ? 'text-emerald-700' : 'text-[color:var(--color-ink)]'">
            {{ product.currency_symbol || '$' }}{{ discountedPrice || retailPrice }}
          </div>
          <button
            v-if="ticketCode"
            type="button"
            class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700"
            @click="copyDiscountCode"
          >
            {{ couponCopied ? 'Copied' : 'Code' }} <span class="ui-mono">{{ ticketCode }}</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
          </button>
        </div>
        <a
          :href="withSrc(`/go/${product.id}`)"
          target="_blank"
          rel="noopener noreferrer nofollow sponsored"
          class="ui-focus ml-auto flex-1 max-w-[260px] h-12 flex items-center justify-center gap-1.5 rounded-[12px] text-[14px] font-semibold text-white bg-gradient-to-b from-[#0284C7] to-[#0369A1] shadow-md"
        >
          <span class="truncate">Buy at {{ brand?.name || 'vendor' }}</span>
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7M17 7H7M17 7v10" /></svg>
        </a>
      </div>
    </div>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, watchEffect, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import { stripSiteSuffix } from '@/utils/pageTitle'
import { withSrc } from '@/composables/useOutbound'
import ProductSimpleCard from '@/components/ProductSimpleCard.vue'
import PricePerMg from '@/components/price/PricePerMg.vue'
import PriceSparkline from '@/components/price/PriceSparkline.vue'
import WishlistHeart from '@/components/ui/WishlistHeart.vue'
import CouponTicket from '@/components/ui/CouponTicket.vue'
import VendorLogo from '@/components/ui/VendorLogo.vue'

const props = defineProps({
  product: Object,
  brand: Object,
  relatedProducts: Array,
  reviews: Array,
  // { points, lowest, highest, current, is_lowest, change_pct,
  //   currency_symbol, days } or null when there is nothing to plot.
  priceHistory: { type: Object, default: null },
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

const page = usePage()

// Retail price (vendor's listed price; uses discount_price if vendor's own
// sale is active). When the brand has a configured PeptideMap discount %,
// compute and surface the discounted figure as the primary.
const retailPrice = computed(() => {
  const base = parseFloat(props.product?.discount_price || props.product?.price || 0)
  return base.toFixed(2)
})

const discountedPrice = computed(() => {
  const pct = parseFloat(props.brand?.discount_percent)
  if (!pct || pct <= 0 || pct >= 100) return null
  const base = parseFloat(props.product?.discount_price || props.product?.price || 0)
  if (!base || base <= 0) return null
  return (base * (1 - pct / 100)).toFixed(2)
})

const detailSizeLabel = computed(() => {
  const s = props.product?.size_mg
  if (s === null || s === undefined || s === '') return ''
  return /[a-zA-Z]/.test(String(s)) ? String(s) : `${s}mg`
})

// "Lowest in 90 days" next to the price: only when the price actually
// moved in the window and today's price is the low. A history that
// starts inside the window says "since <date>" instead.
const historyLowestLabel = computed(() => {
  const h = props.priceHistory
  if (!h || !h.is_lowest || !(Number(h.highest) - Number(h.lowest) >= 0.005)) return null
  const first = Array.isArray(h.points) && h.points.length ? String(h.points[0].date || '') : ''
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(first)
  if (!m) return null
  const start = Date.UTC(Number(m[1]), Number(m[2]) - 1, Number(m[3]))
  const lastDate = String(h.points[h.points.length - 1].date || '')
  const l = /^(\d{4})-(\d{2})-(\d{2})/.exec(lastDate)
  const end = l ? Date.UTC(Number(l[1]), Number(l[2]) - 1, Number(l[3])) : start
  const days = Number(h.days) || 90
  if ((end - start) / 86400000 >= days - 1) return `Lowest price in ${days} days`
  const since = new Date(start).toLocaleDateString('en-US', { month: 'short', day: 'numeric', timeZone: 'UTC' })
  return `Lowest price since ${since}`
})

// Effective coupon code label — vendor's code if set, otherwise PMAP.
const effectiveCouponCode = computed(() => {
  const raw = (props.brand?.discount_code || '').trim()
  return (raw || 'PMAP').toUpperCase()
})

// Code on the big ticket: the vendor's own code, else PMAP when the
// vendor has a Peptidemap discount but no code of its own.
const ticketCode = computed(() => {
  const raw = (props.brand?.discount_code || '').trim()
  if (raw) return raw.toUpperCase()
  return discountedPrice.value ? 'PMAP' : null
})

// Per-compound compare table (/compare/{slug}); the slug is the
// route-safe form CompareSlug::canonical() produces.
const compareHref = computed(() => {
  const raw = String(props.product?.category?.slug || '')
  const slug = raw.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
  return slug ? `/compare/${slug}` : '/compare'
})

// Sticky phone buy bar: shown whenever the main buy button is off screen
// (below the fold on first load, or scrolled past).
const mainCta = ref(null)
const showStickyBuy = ref(false)
let ctaObserver = null
onMounted(() => {
  if (!mainCta.value || typeof IntersectionObserver === 'undefined') return
  ctaObserver = new IntersectionObserver(([entry]) => {
    showStickyBuy.value = !entry.isIntersecting
  })
  ctaObserver.observe(mainCta.value)
})
onUnmounted(() => ctaObserver?.disconnect())

// "Review {vendor} on Peptidemap" CTA — drives storefront visits from the
// product page even though there's no review form here.
const reviewVendorHref = computed(() => {
  const slug = props.brand?.slug
  return slug ? `/brand/${slug}#reviews` : '#'
})

// Color-coded format chip next to the title. Mirrors the convention
// used on ProductCard / ProductSimpleCard for consistency across the
// site. Only Capsule + Nasal Spray render — Peptide is implicit.
const productTypeChip = computed(() => {
  switch (props.product?.product_type) {
    case 'Capsule':
      return { label: 'Capsule', classes: 'bg-blue-100 text-blue-800 border border-blue-200' }
    case 'Nasal Spray':
      return { label: 'Nasal Spray', classes: 'bg-emerald-100 text-emerald-800 border border-emerald-200' }
    case 'Kit':
      return { label: 'Kit', classes: 'bg-amber-100 text-amber-800 border border-amber-200' }
    default:
      return null
  }
})

// Computed values for reactive SEO updates (automatically from product data)
const title = computed(() => {
  if (props.seo?.title) {
    return stripSiteSuffix(props.seo.title, page.props.site_name)
  }
  const vendorName = props.brand?.name || ''
  const productName = props.product?.name || 'Product'
  const withVendor = vendorName ? `${productName} — ${vendorName} Listing` : `${productName} Listing`
  if (withVendor.length <= 60) return withVendor
  const shorter = vendorName ? `${productName} — ${vendorName}` : productName
  return shorter.length <= 60 ? shorter : productName
})

const description = computed(() => {
  // Use SEO description if provided, otherwise generate from product description
  if (props.seo?.description) {
    return props.seo.description
  }
  if (props.product?.description) {
    // Truncate to ~155 chars
    const desc = props.product.description.replace(/\s+/g, ' ').trim()
    return desc.length > 155 ? desc.substring(0, 155) + '...' : desc
  }
  return 'View detailed information about ' + (props.product?.name || 'this product') + '. Compare listed prices across vendors. Catalog data only: RUO.'
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
  // Use SEO og_image if provided, otherwise use product image
  return props.seo?.og_image || props.seo?.image || props.product?.image_url || null
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

const activeTab = ref('description')

// Calculate brand initials from brand name
const brandInitials = computed(() => {
  try {
    if (!props.brand || !props.brand.name) {
      return 'PS'
    }
    
    // Always calculate from brand name to ensure correct initials
    const name = String(props.brand.name).trim()
    if (!name || name.length === 0) {
      return 'PS'
    }
    
    // Split by spaces and filter out empty strings
    const words = name.split(/\s+/).filter(word => word && word.length > 0)
    
    if (words.length >= 2) {
      // Take first letter of first two words
      const firstWord = words[0]
      const secondWord = words[1]
      const firstLetter = firstWord && firstWord.length > 0 ? firstWord[0].toUpperCase() : ''
      const secondLetter = secondWord && secondWord.length > 0 ? secondWord[0].toUpperCase() : ''
      
      if (firstLetter && secondLetter) {
        return firstLetter + secondLetter
      }
    }
    
    if (words.length === 1) {
      // If only one word, take first two characters
      const word = words[0]
      if (word && word.length >= 2) {
        return word.substring(0, 2).toUpperCase()
      } else if (word && word.length === 1) {
        // If word is only one character, repeat it
        return (word[0] + word[0]).toUpperCase()
      }
    }
    
    // Fallback: take first two characters of the name
    if (name.length >= 2) {
      return name.substring(0, 2).toUpperCase()
    } else if (name.length === 1) {
      return (name[0] + name[0]).toUpperCase()
    }
    
    return 'PS'
  } catch (error) {
    console.error('Error calculating brand initials:', error)
    return 'PS'
  }
})

const couponCopied = ref(false)
const copyDiscountCode = async () => {
  const code = effectiveCouponCode.value || props.brand?.discount_code || 'PMAP'
  let ok = false
  if (navigator.clipboard?.writeText) {
    try { await navigator.clipboard.writeText(code); ok = true } catch {}
  }
  if (!ok) {
    // execCommand fallback for non-HTTPS contexts + older browsers
    try {
      const ta = document.createElement('textarea')
      ta.value = code
      ta.style.cssText = 'position:fixed;left:-999px;top:-999px'
      document.body.appendChild(ta); ta.select()
      ok = document.execCommand('copy')
      document.body.removeChild(ta)
    } catch {}
  }
  if (ok) {
    couponCopied.value = true
    setTimeout(() => { couponCopied.value = false }, 2000)
  } else {
    prompt('Copy this discount code:', code)
  }
}

</script>
