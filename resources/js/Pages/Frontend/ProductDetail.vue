<template>
  <ModernLayout>
    <div class="min-h-screen">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-6">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-[13px] text-[color:var(--color-ink-muted)] mb-6">
          <button @click="handleClick1" class="hover:text-[color:var(--color-ink)] transition-colors">Products</button>
          <span class="text-[color:var(--color-ink-subtle)]">/</span>
          <button @click="handleClick2" class="hover:text-[color:var(--color-ink)] transition-colors">{{ product.category?.name || 'N/A' }}</button>
          <span class="text-[color:var(--color-ink-subtle)]">/</span>
          <span class="text-[color:var(--color-ink)] font-medium">{{ product.name || 'N/A' }}</span>
        </div>
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
              <!-- Product Identifier Tag -->
              <div v-if="product.category" class="inline-block bg-slate-100 text-slate-700 px-3 py-1.5 rounded text-sm mb-3">
                {{ product.category.name }}
              </div>
  
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
                  Lab tested
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-[6px] bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-semibold">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2v6l-4 7c-1 1.8 0 4 2 4h10c2 0 3-2.2 2-4l-4-7V2"/><path d="M8 2h8"/></svg>
                  COA available
                </span>
                <span v-if="product.purity" class="inline-flex items-center gap-1 px-2 py-1 rounded-[6px] bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)] text-[11px] font-semibold ui-mono">
                  {{ product.purity }}% purity
                </span>
              </div>

              <!-- Divider before commerce block. Seller lives in the 'Sold by' card below the CTA. -->
              <div class="border-b border-gray-200 mb-5"></div>

              <!-- Price — monospace, prominent -->
              <div class="mb-6">
                <template v-if="discountedPrice">
                  <div class="flex items-baseline gap-3">
                    <span class="text-[12px] uppercase tracking-wide text-[color:var(--color-ink)] font-semibold">Retail</span>
                    <span class="ui-mono text-xl text-[color:var(--color-ink)] line-through font-medium">${{ retailPrice }}</span>
                  </div>
                  <div class="text-[12px] uppercase tracking-wide text-emerald-700 font-semibold mt-3 flex items-center gap-2">
                    <span>Price with code</span>
                    <button
                      @click="copyDiscountCode"
                      class="ui-focus inline-flex items-center gap-1.5 px-2 py-0.5 rounded-[6px] border border-dashed border-emerald-400 bg-emerald-50 hover:bg-emerald-100 transition-colors"
                      :title="couponCopied ? 'Copied' : 'Click to copy'"
                    >
                      <span class="ui-mono text-emerald-800">{{ effectiveCouponCode }}</span>
                      <svg v-if="couponCopied" class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                      <svg v-else class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                    </button>
                  </div>
                  <div class="ui-mono text-4xl font-bold text-emerald-700 leading-tight mt-1">${{ discountedPrice }}</div>
                </template>
                <template v-else>
                  <div class="flex items-baseline gap-3">
                    <span class="ui-mono text-4xl font-bold text-[color:var(--color-ink)]">
                      ${{ product.discount_price || product.price }}
                    </span>
                    <span
                      v-if="product.discount_price && product.discount_price < product.price"
                      class="ui-mono text-lg text-[color:var(--color-ink-subtle)] line-through"
                    >
                      ${{ product.price }}
                    </span>
                  </div>
                </template>
                <div v-if="product.size_mg" class="ui-mono text-sm text-[color:var(--color-ink-muted)] mt-1">
                  {{ /[a-zA-Z]/.test(String(product.size_mg)) ? product.size_mg : `${product.size_mg}mg` }} vial
                </div>
              </div>

              <!-- Purchase Button — primary CTA, clean -->
              <div v-if="product.product_url" class="mb-4">
                <a
                  :href="`/go/${product.id}`"
                  target="_blank"
                  rel="noopener noreferrer nofollow sponsored"
                  class="ui-focus w-full h-[52px] flex items-center justify-center gap-2 rounded-[13px] text-[15px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_1px_2px_rgba(10,11,14,0.08),0_10px_24px_-8px_rgba(79,70,229,0.4)] hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.22),0_2px_4px_rgba(10,11,14,0.1),0_14px_32px_-8px_rgba(79,70,229,0.55)] hover:-translate-y-[1px] active:translate-y-0 transition-all"
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
                  :href="`/compare#${product.category.slug || ''}`"
                  class="text-[13px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors inline-flex items-center gap-1"
                >
                  Compare {{ product.category.name }} across all vendors
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </a>
              </div>
  
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

              <!-- Seller Information -->
              <div v-if="brand" class="border-t border-[color:var(--color-hairline)] pt-5 mb-6">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] flex items-center justify-center overflow-hidden">
                    <img v-if="brand.logo" :src="brand.logo" :alt="brand.name" class="w-full h-full object-contain p-1" loading="lazy" />
                    <span v-else class="ui-display text-sm font-bold text-[color:var(--color-ink-muted)]">{{ brandInitials }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-[11px] text-[color:var(--color-ink-subtle)] uppercase tracking-[0.08em] font-semibold">Sold by</div>
                    <a
                      :href="`/brand/${brand.slug}`"
                      class="text-[14px] font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors"
                    >
                      {{ brand.name }}
                    </a>
                  </div>
                  <a
                    :href="`/brand/${brand.slug}`"
                    class="ui-focus flex items-center gap-1 text-[13px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors flex-shrink-0"
                  >
                    All products
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                  </a>
                </div>
                <a :href="reviewVendorHref" class="mt-3 inline-flex items-center gap-1 text-[12px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors">
                  Review {{ brand.name }} on Peptidemap
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </a>
              </div>

              <!-- Disclaimer -->
              <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
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
                This product undergoes third-party testing to ensure quality. Each batch comes with a certificate of analysis (COA) available upon request.
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
                          Verified via PMAP
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

        <!-- Related Products -->
        <div v-if="relatedProducts && relatedProducts.length > 0" class="bg-white rounded-lg border border-gray-200 p-8">
          <h2 class="text-2xl text-gray-900 mb-6">Related Products</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <ProductSimpleDetailCard
              v-for="relatedProduct in relatedProducts"
              :key="relatedProduct.id"
              :name="relatedProduct.name"
              :image-url="relatedProduct.image_url"
              :price="relatedProduct.price"
              :discount-price="relatedProduct.discount_price"
              :brand-name="relatedProduct.brand?.name"              
              :to="relatedProduct.brand?.slug
                ? `/product/${relatedProduct.brand.slug}/${relatedProduct.slug}/${relatedProduct.id}`
                : `/product/${relatedProduct.slug}/${relatedProduct.id}`"
            />
          </div>
        </div>
      </div>
    </div>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, watchEffect } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import ProductSimpleDetailCard from '@/components/ProductSimpleDetailCard.vue'
import WishlistHeart from '@/components/ui/WishlistHeart.vue'

const props = defineProps({
  product: Object,
  brand: Object,
  relatedProducts: Array,
  reviews: Array,
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

// Effective coupon code label — vendor's code if set, otherwise PMAP.
const effectiveCouponCode = computed(() => {
  const raw = (props.brand?.discount_code || '').trim()
  return (raw || 'PMAP').toUpperCase()
})

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
  // Use SEO title if provided, otherwise generate from product name
  if (props.seo?.title) {
    return props.seo.title
  }
  const siteName = page.props.site_name || 'PeptideMap'
  const vendorName = props.brand?.name || 'our store'
  return `Buy ${props.product?.name || 'Product'} from ${vendorName} - ${siteName}`
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
  return 'View detailed information about ' + (props.product?.name || 'this product') + '. Compare prices, read reviews, and find the best deals.'
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

const handleClick1 = () => {
  router.visit('/products')
}

const handleClick2 = () => {
  if (props.product?.category?.slug) {
    router.visit(`/product/${props.product.category.slug}`)
  }
}
</script>
