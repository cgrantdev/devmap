<template>
  <AdminLayout>
    <FormPage
      :title="vendor ? vendor.name : 'New Vendor'"
      back-label="Vendors"
      back-href="/admin/vendors"
      :tabs="[
        { id: 'general', label: 'General' },
        { id: 'contact', label: 'Contact' },
        { id: 'policies', label: 'Policies' },
        { id: 'marketing', label: 'Marketing' },
        { id: 'integration', label: 'Integration' },
        { id: 'seo', label: 'SEO' },
        { id: 'products', label: `Products (${(products || []).length})` },
      ]"
      v-model:active-tab="activeTab"
      :saving="editForm.processing"
      :saved="justSaved"
      @save="submitEditVendor"
    >
      <template #actions>
        <a
          v-if="vendor"
          :href="`/brand/${vendor.slug || vendor.name?.toLowerCase().replace(/\\s+/g, '-')}/products`"
          target="_blank"
          class="h-9 px-4 text-[13px] font-medium text-[color:var(--color-ink-muted)] border border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink)] transition-all flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
          View storefront
        </a>
      </template>
      <!-- Error banner -->
      <div v-if="Object.keys(editForm.errors).length > 0" class="mb-6 px-4 py-3 bg-[color:var(--color-danger-bg)] border border-[#FECACA] text-[#991B1B] text-sm">
        <span v-for="(error, field) in editForm.errors" :key="field">{{ Array.isArray(error) ? error[0] : error }} </span>
      </div>

      <form @submit.prevent="submitEditVendor">

        <!-- GENERAL TAB -->
        <div v-show="activeTab === 'general'">
          <FormSection title="Basic Information" :columns="2">
            <FormField label="Vendor Name" required>
              <input v-model="editForm.name" type="text" required class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Website">
              <input v-model="editForm.shop_url" type="url" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>

          <FormSection title="Description">
            <FormField label="About this vendor">
              <textarea v-model="editForm.description" rows="4" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>

          <FormSection title="Location & Details" :columns="2">
            <FormField label="Location">
              <input v-model="editForm.location" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Founded Year">
              <input v-model.number="editForm.founded_year" type="number" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>

          <FormSection title="Status">
            <div class="flex flex-wrap gap-6">
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="editForm.is_active" class="w-4 h-4 accent-[color:var(--color-accent-600)]" /> Active</label>
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="editForm.top_vendor" class="w-4 h-4 accent-[color:var(--color-accent-600)]" /> Top Vendor</label>
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="editForm.featured" class="w-4 h-4 accent-[color:var(--color-accent-600)]" /> Featured</label>
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="editForm.is_partner" class="w-4 h-4 accent-[color:var(--color-accent-600)]" /> Partner</label>
            </div>
          </FormSection>

          <FormSection title="Media" :columns="2">
            <FormField label="Banner Image">
              <input type="file" accept="image/*" @change="handleFileChange($event, 'banner')" class="text-sm" />
              <img v-if="bannerPreview || currentBannerUrl" :src="bannerPreview || currentBannerUrl" class="mt-2 h-20 object-cover border border-[color:var(--color-hairline)]" />
            </FormField>
            <FormField label="Logo (PNG)">
              <input type="file" accept=".png,image/png" @change="handleFileChange($event, 'logo')" class="text-sm" />
              <img v-if="logoPreview || currentLogoUrl" :src="logoPreview || currentLogoUrl" class="mt-2 h-16 object-contain border border-[color:var(--color-hairline)]" />
            </FormField>
          </FormSection>
        </div>

        <!-- CONTACT TAB -->
        <div v-show="activeTab === 'contact'">
          <FormSection title="Contact Information" :columns="2">
            <FormField label="Email">
              <input v-model="editForm.contact_email" type="email" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Phone">
              <input v-model="editForm.phone_number" type="tel" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Business Hours">
            <FormField label="Hours" hint="e.g., Mon-Fri: 9AM-6PM EST">
              <input v-model="editForm.business_hours" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
        </div>

        <!-- POLICIES TAB -->
        <div v-show="activeTab === 'policies'">
          <FormSection title="Shipping">
            <FormField label="Shipping Information">
              <textarea v-model="editForm.shipping_info" rows="3" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Returns">
            <FormField label="Return Policy">
              <textarea v-model="editForm.return_policy" rows="3" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Payment Methods">
            <div class="flex flex-wrap gap-6">
              <label v-for="method in ['Credit Card', 'PayPal', 'Cryptocurrency', 'Bank Transfer']" :key="method" class="flex items-center gap-2 text-sm">
                <input type="checkbox" :value="method" v-model="editForm.payment_methods" class="w-4 h-4 accent-[color:var(--color-accent-600)]" />
                {{ method }}
              </label>
            </div>
          </FormSection>
        </div>

        <!-- MARKETING TAB -->
        <div v-show="activeTab === 'marketing'">
          <FormSection title="Coupon & Affiliate" :columns="2">
            <FormField label="Coupon Code" hint="Promo code visitors will use at this vendor's checkout">
              <input v-model="editForm.coupon_code" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] ui-mono focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Discount %" hint="What the coupon takes off retail. Drives the 'PeptideMap Price' shown across the site.">
              <div class="relative">
                <input
                  v-model.number="editForm.coupon_discount_percent"
                  type="number"
                  min="0"
                  max="99"
                  step="0.5"
                  placeholder="e.g. 15"
                  class="w-full h-10 pl-3 pr-8 text-sm border border-[color:var(--color-hairline)] ui-mono focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15"
                />
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-[color:var(--color-ink-subtle)] pointer-events-none">%</span>
              </div>
            </FormField>
            <FormField label="Banner Image URL" class="md:col-span-2">
              <input v-model="editForm.banner_image_url" type="url" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Affiliate Tracking" :columns="2">
            <FormField label="Affiliate URL Template" hint="Placeholders: {product_url}, {slug}, {id}, {affiliate_tag}">
              <input v-model="editForm.affiliate_url_template" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] ui-mono focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" placeholder="https://vendor.com/{slug}?ref={affiliate_tag}" />
            </FormField>
            <FormField label="Affiliate Tag">
              <input v-model="editForm.affiliate_tag" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" placeholder="peptidemap" />
            </FormField>
          </FormSection>
        </div>

        <!-- INTEGRATION TAB -->
        <div v-show="activeTab === 'integration'">
          <FormSection title="Platform" :columns="2">
            <FormField label="E-Commerce Platform" hint="Select the vendor's store platform for automatic product sync">
              <select v-model="editForm.api_platform" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15">
                <option :value="null">None (manual only)</option>
                <option value="woocommerce">WooCommerce</option>
                <option value="medusa">Medusa</option>
                <option value="shopify">Shopify</option>
                <option value="custom">Custom API</option>
                <option value="page_scrape">Page Scraper</option>
              </select>
            </FormField>
            <FormField label="Store URL" hint="Base URL of the vendor's store">
              <input v-model="editForm.shop_url" type="url" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" placeholder="https://store.example.com" />
            </FormField>
          </FormSection>

          <FormSection v-if="editForm.api_platform && editForm.api_platform !== 'page_scrape'" title="API Credentials">
            <FormField label="API Key" hint="Publishable key, consumer key, or access token. Stored encrypted.">
              <input v-model="editForm.api_key" type="password" autocomplete="off" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15 ui-mono" placeholder="Enter API key..." />
            </FormField>
            <p class="text-[12px] text-[color:var(--color-ink-subtle)] mt-2">
              <span v-if="editForm.api_platform === 'woocommerce'">WooCommerce REST API consumer key + secret (format: ck_xxx / cs_xxx). Enter the consumer key here.</span>
              <span v-else-if="editForm.api_platform === 'medusa'">Medusa publishable API key (format: pk_xxx).</span>
              <span v-else-if="editForm.api_platform === 'shopify'">Shopify Admin API access token.</span>
              <span v-else>API key or access token for the vendor's API.</span>
            </p>
          </FormSection>

          <FormSection v-if="editForm.api_platform === 'page_scrape'" title="Page Scraper" description="Automatically fetches each product's page URL and extracts current prices, images, and stock status from the HTML.">
            <div class="bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] p-4 text-[13px] text-[color:var(--color-ink-muted)] space-y-2">
              <p>The page scraper will visit each product's URL and extract data using:</p>
              <ol class="list-decimal list-inside space-y-1 text-[12px]">
                <li>JSON-LD structured data (most reliable)</li>
                <li>Open Graph meta tags (og:image, og:title, product:price)</li>
                <li>Common HTML patterns as fallback</li>
              </ol>
              <p class="text-[12px] text-[color:var(--color-ink-subtle)]">Products need a valid product URL set. Images are pulled directly from product pages.</p>
            </div>
          </FormSection>

          <!-- Scraping Status -->
          <FormSection v-if="vendor" title="Scraping Status">
            <!-- Action buttons -->
            <div class="flex flex-wrap gap-2 mb-4">
              <button
                @click="triggerScrape"
                :disabled="scrapeLoading"
                class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all disabled:opacity-50 flex items-center gap-2"
              >
                <svg v-if="!scrapeLoading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                {{ scrapeLoading ? 'Running...' : 'Update Products' }}
              </button>
              <button
                @click="discoverProducts"
                :disabled="discoverLoading"
                class="h-9 px-4 text-[13px] font-semibold text-[color:var(--color-ink)] border border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)] transition-all disabled:opacity-50 flex items-center gap-2"
              >
                <svg v-if="!discoverLoading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                {{ discoverLoading ? 'Discovering...' : 'Discover Products' }}
              </button>
              <a
                v-if="editForm.api_platform === 'woocommerce' || !editForm.api_platform"
                :href="`/admin/vendors/${vendor?.id}/woo-connect`"
                class="h-9 px-4 text-[13px] font-semibold text-white bg-[#7F54B3] hover:bg-[#6B42A0] transition-all flex items-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                Connect WooCommerce
              </a>
            </div>

            <div v-if="scrapingStatus" class="space-y-4">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] p-3">
                  <div class="text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-1">Status</div>
                  <div class="text-[14px] font-semibold" :class="scrapingStatus.enabled ? 'text-[color:var(--color-verified)]' : 'text-[color:var(--color-ink-muted)]'">{{ scrapingStatus.enabled ? 'Enabled' : 'Disabled' }}</div>
                </div>
                <div class="bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] p-3">
                  <div class="text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-1">Last Run</div>
                  <div class="text-[13px] text-[color:var(--color-ink)] ui-mono">{{ scrapingStatus.last_run_at ? new Date(scrapingStatus.last_run_at).toLocaleDateString() : 'Never' }}</div>
                </div>
                <div class="bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] p-3">
                  <div class="text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-1">Runs</div>
                  <div class="text-[14px] font-semibold text-[color:var(--color-ink)]">
                    <span class="text-[color:var(--color-verified)]">{{ scrapingStatus.success_count }}</span>
                    <span v-if="scrapingStatus.error_count" class="text-[color:var(--color-danger)]"> / {{ scrapingStatus.error_count }} errors</span>
                  </div>
                </div>
                <div class="bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] p-3">
                  <div class="text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-1">Staged Products</div>
                  <div class="text-[14px] font-semibold text-[color:var(--color-ink)]">{{ scrapingStatus.staged_count }}</div>
                </div>
              </div>

              <div v-if="scrapingStatus.last_error" class="px-4 py-3 bg-[color:var(--color-danger-bg)] border border-red-200 text-[#991B1B] text-[13px]">
                <span class="font-semibold">Last error:</span> {{ scrapingStatus.last_error }}
              </div>

            </div>

            <div v-else class="text-[13px] text-[color:var(--color-ink-subtle)]">
              No scraping config yet. Save with a platform selected to create one, or click "Run Scrape Now" after saving.
            </div>
          </FormSection>
        </div>

        <!-- SEO TAB -->
        <div v-show="activeTab === 'seo'">
          <FormSection title="Search Engine Optimization">
            <FormField label="Page Title">
              <input v-model="editForm.seo_page_title" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Meta Description">
              <textarea v-model="editForm.seo_description" rows="3" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Open Graph" :columns="2">
            <FormField label="OG Title">
              <input v-model="editForm.seo_og_title" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="OG Image URL">
              <input v-model="editForm.seo_og_image" type="url" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="OG Description" class="md:col-span-2">
              <textarea v-model="editForm.seo_og_description" rows="2" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
        </div>

      </form>

      <!-- PRODUCTS TAB -->
      <div v-show="activeTab === 'products'">
        <FormSection :title="`${(products || []).length} products listed`">
          <p v-if="!products || products.length === 0" class="text-sm text-[color:var(--color-ink-subtle)] py-4">No products imported yet.</p>
          <div v-else class="text-sm text-[color:var(--color-ink-muted)]">
            View and manage this vendor's products below. Use the Products section in the sidebar for bulk management.
          </div>
        </FormSection>
      </div>

      <!-- DANGER ZONE — visible on every tab so it's always reachable -->
      <div v-if="vendor" class="mt-10 border border-[#FECACA] bg-[#FEF2F2] p-5">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="text-sm font-semibold text-[#991B1B]">Delete vendor</h3>
            <p class="mt-1 text-[13px] text-[#7F1D1D]">
              Permanently removes the brand, all
              {{ (products || []).length }} product{{ (products || []).length === 1 ? '' : 's' }},
              vendor settings, reviews, scraping config, click logs, banners, and the owning user account.
              The email <span class="ui-mono">{{ vendor.contact_email || vendor.email || '—' }}</span> will be freed up for future signups. This cannot be undone.
            </p>
          </div>
          <button
            type="button"
            @click="confirmDeleteVendor"
            class="h-9 px-4 text-[13px] font-semibold text-white bg-[#DC2626] hover:bg-[#B91C1C] flex-shrink-0 transition-colors"
          >
            Delete vendor
          </button>
        </div>
      </div>

    </FormPage>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage, Link, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import FormPage from '@/components/admin/FormPage.vue'
import FormSection from '@/components/admin/FormSection.vue'
import FormField from '@/components/admin/FormField.vue'
import { useAdminLoading } from '../../composables/useAdminLoading'
import { useToast as useVueToastification } from 'vue-toastification'

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()
const toastError = (message) => toast.error(message, { timeout: 4000 })

const props = defineProps({
  vendor: Object,
  products: {
    type: Array,
    default: () => []
  },
  locations: {
    type: Array,
    default: () => []
  },
  scrapingStatus: {
    type: Object,
    default: null,
  },
})

const activeTab = ref('general')
const justSaved = ref(false)
const scrapeLoading = ref(false)
const discoverLoading = ref(false)

function triggerScrape() {
  if (!props.vendor) return
  scrapeLoading.value = true
  router.post(`/admin/vendors/${props.vendor.id}/scrape`, { _token: usePage().props.csrf_token }, {
    preserveScroll: true,
    onFinish: () => { scrapeLoading.value = false },
  })
}

function discoverProducts() {
  if (!props.vendor) return
  discoverLoading.value = true
  router.post(`/admin/vendors/${props.vendor.id}/discover-products`, { _token: usePage().props.csrf_token }, {
    preserveScroll: true,
    onFinish: () => { discoverLoading.value = false },
  })
}

const bannerPreview = ref(null)
const logoPreview = ref(null)
const cacheBuster = ref(Date.now())

// Reactive computed properties for current image URLs
const currentBannerUrl = computed(() => {
  return props.vendor?.settings?.banner_url || ''
})

const currentLogoUrl = computed(() => {
  return props.vendor?.settings?.logo_url || ''
})

// Reactive computed property for products
const currentProducts = computed(() => {
  return props.products || []
})

const productSearchValue = ref('')
const productSearchField = ['name']

const productHeaders = [
  { text: 'Image', value: 'image_url', sortable: false },
  { text: 'Name', value: 'name', sortable: true },
  { text: 'Price', value: 'price', sortable: true },
  { text: 'Discount Price', value: 'discount_price', sortable: true },
  { text: 'Second Price', value: 'second_price', sortable: true },
  { text: 'Product URL', value: 'product_url', sortable: false },
  { text: 'Actions', value: 'actions', sortable: false }
]

const editForm = useForm({
  name: props.vendor?.name || '',
  email: props.vendor?.email || '',
  description: props.vendor?.settings?.description || '',
  contact_email: props.vendor?.settings?.contact_email || '',
  phone_number: props.vendor?.settings?.phone_number || '',
  location_id: props.vendor?.settings?.location_id || null,
  location: props.vendor?.location || '',
  shop_url: props.vendor?.settings?.shop_url || '',
  founded_year: props.vendor?.settings?.founded_year || null,
  coupon_code: props.vendor?.settings?.coupon_code || '',
  coupon_discount_percent: props.vendor?.settings?.coupon_discount_percent ?? null,
  shipping_info: props.vendor?.settings?.shipping_info || '',
  return_policy: props.vendor?.settings?.return_policy || '',
  business_hours: props.vendor?.settings?.business_hours || '',
  banner_image_url: props.vendor?.settings?.banner_image_url || '',
  top_vendor: props.vendor?.settings?.top_vendor || false,
  featured: props.vendor?.settings?.featured || false,
  is_partner: props.vendor?.settings?.is_partner || false,
  payment_methods: props.vendor?.settings?.payment_methods || [],
  banner: null,
  logo: null,
  is_active: props.vendor?.is_active ?? false,
  affiliate_url_template: props.vendor?.affiliate_url_template || '',
  affiliate_tag: props.vendor?.affiliate_tag || '',
  api_platform: props.vendor?.settings?.api_platform || null,
  api_key: '', // never pre-filled for security — blank means "don't change"
  _token: usePage().props.csrf_token
})

// Watch for props changes and update form data
watch(() => props.vendor, (newVendor) => {
  console.log('Vendor data changed:', newVendor);
  if (newVendor) {
    editForm.name = newVendor.name || ''
    editForm.email = newVendor.email || ''
    editForm.description = newVendor.settings?.description || ''
    editForm.contact_email = newVendor.settings?.contact_email || ''
    editForm.phone_number = newVendor.settings?.phone_number || ''
    editForm.location_id = newVendor.settings?.location_id || null
    editForm.shop_url = newVendor.settings?.shop_url || ''
    editForm.founded_year = newVendor.settings?.founded_year || null
    editForm.coupon_code = newVendor.settings?.coupon_code || ''
    editForm.coupon_discount_percent = newVendor.settings?.coupon_discount_percent ?? null
    editForm.shipping_info = newVendor.settings?.shipping_info || ''
    editForm.return_policy = newVendor.settings?.return_policy || ''
    editForm.business_hours = newVendor.settings?.business_hours || ''
    editForm.banner_image_url = newVendor.settings?.banner_image_url || ''
    editForm.top_vendor = newVendor.settings?.top_vendor || false
    editForm.featured = newVendor.settings?.featured || false
    editForm.is_partner = newVendor.settings?.is_partner || false
    editForm.payment_methods = newVendor.settings?.payment_methods || []
    editForm.is_active = newVendor.is_active ?? false
    editForm.affiliate_url_template = newVendor.affiliate_url_template || ''
    editForm.affiliate_tag = newVendor.affiliate_tag || ''
    editForm.banner_url = newVendor.settings?.banner_url || ''
    editForm.logo_url = newVendor.settings?.logo_url || ''
  }
}, { deep: true })

function handleFileChange(event, field) {
  const file = event.target.files[0]
  if (file) {
    editForm[field] = file
    const reader = new FileReader()
    reader.onload = e => {
      if (field === 'banner') bannerPreview.value = e.target.result
      if (field === 'logo') logoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function submitEditVendor() {
  if (props.vendor) {
    // Update existing vendor
    editForm._token = usePage().props.csrf_token
    editForm.post(`/admin/vendors/${props.vendor.id}`, {
      forceFormData: true,
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        justSaved.value = true
        setTimeout(() => justSaved.value = false, 3000)
        bannerPreview.value = null
        logoPreview.value = null
        cacheBuster.value = Date.now()
        const fileInputs = document.querySelectorAll('input[type="file"]')
        fileInputs.forEach(input => input.value = '')
      },
      onError: (errors) => {
        if (errors && Object.keys(errors).length > 0) {
          toastError('Please fix the errors and try again.')
        }
      },
    })
  } else {
    // Create new vendor
    editForm._token = usePage().props.csrf_token
    editForm.post('/admin/vendors', {
      forceFormData: true,
      onSuccess: () => {
        router.visit('/admin/vendors')
      },
      onError: (errors) => {
        if (errors && Object.keys(errors).length > 0) {
          toastError('Please fix the errors and try again.')
        }
      }
    })
  }
}

// XML Import logic
const importFileForm = useForm({
  file: null,
  _token: usePage().props.csrf_token
})
const importUrlForm = useForm({
  url: '',
  _token: usePage().props.csrf_token
})

function handleImportFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    importFileForm.file = file
  }
}

function importFromFile() {
  importFileForm.post(`/admin/vendors/${props.vendor.id}/products/import`, {
    forceFormData: true,
    onSuccess: () => {
      importFileForm.file = null
      // Clear file input
      const fileInput = document.querySelector('input[accept=".xml"]')
      if (fileInput) fileInput.value = ''
      router.reload()
    },
    onError: () => {
      console.log('File import failed')
    }
  })
}

function importFromUrl() {
  importUrlForm.post(`/admin/vendors/${props.vendor.id}/products/import-url`, {
    onSuccess: () => {
      importUrlForm.url = ''
      router.reload()
    },
    onError: () => {
      console.log('URL import failed')
    }
  })
}

const { setLoading } = useAdminLoading()
const importShopUrlMessage = ref('')
const importShopUrlSuccess = ref(false)

function importFromShopUrl() {
  setLoading(true, 'Importing products, please wait...')
  importShopUrlMessage.value = ''
  importShopUrlSuccess.value = false
  router.post(
    `/admin/vendors/${props.vendor.id}/import-shop-url`,
    { _token: usePage().props.csrf_token },
    {
      forceFormData: true,
      onSuccess: () => {
        setLoading(false)
        importShopUrlSuccess.value = true
        importShopUrlMessage.value = 'Import Completed'
      },
      onError: (err) => {
        setLoading(false)
        importShopUrlSuccess.value = false
        importShopUrlMessage.value = 'Import failed'
      },
      onFinish: () => {
        setLoading(false)
      }
    }
  )
}

function confirmDeleteVendor() {
  if (!props.vendor) return
  const name = props.vendor.name || 'this vendor'
  const productCount = (props.products || []).length
  const msg = `Delete ${name}?\n\n` +
    `This permanently removes:\n` +
    `  • The brand and storefront\n` +
    `  • ${productCount} product${productCount === 1 ? '' : 's'}\n` +
    `  • Vendor settings, reviews, scraping config, click logs\n` +
    `  • The owning user account (email will be freed)\n\n` +
    `This cannot be undone. Continue?`
  if (!confirm(msg)) return
  const form = useForm({ _token: usePage().props.csrf_token })
  form.delete(`/admin/vendors/${props.vendor.id}`, {
    onError: () => toastError('Failed to delete vendor. Please try again.'),
  })
}

function deleteProduct(productId) {
  if (confirm('Are you sure you want to delete this product?')) {
    const deleteForm = useForm({ _token: usePage().props.csrf_token })
    deleteForm.delete(`/admin/vendors/${props.vendor.id}/products/${productId}`, {
      onSuccess: () => {
        // Toast will be shown automatically from flash message
        router.reload()
      },
      onError: () => {
        toastError('Failed to delete product. Please try again.')
      }
    })
  }
}

function slugify(text) {
  return text
    .toString()
    .toLowerCase()
    .replace(/\s+/g, '-')           // Replace spaces with -
    .replace(/[^\w\-]+/g, '')      // Remove all non-word chars
    .replace(/\-\-+/g, '-')        // Replace multiple - with single -
    .replace(/^-+/, '')              // Trim - from start of text
    .replace(/-+$/, '');             // Trim - from end of text
}
</script> 