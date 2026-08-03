<template>
  <Layout>
    <Head><title>Edit product — Peptidemap</title></Head>
    <div class="max-w-3xl mx-auto">
      <div class="mb-8">
        <Link href="/vendor/products" class="text-[12px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] mb-2 inline-flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          Back to products
        </Link>
        <h1 class="ui-display text-3xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)]">Edit product</h1>
        <p v-if="product.category" class="text-[13px] text-[color:var(--color-ink-subtle)] mt-1">
          Category: <span class="font-medium text-[color:var(--color-ink-muted)]">{{ product.category }}</span>
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basics -->
        <div class="bg-white rounded-[12px] border border-[color:var(--color-hairline)] p-6 space-y-5">
          <h2 class="text-[14px] font-semibold text-[color:var(--color-ink)]">Basics</h2>

          <div>
            <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1.5">Name *</label>
            <input v-model="form.name" type="text" required class="ui-input" placeholder="BPC-157 (10mg)" />
            <p v-if="form.errors.name" class="text-[11px] text-red-600 mt-1">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1.5">Description</label>
            <textarea v-model="form.description" rows="4" class="ui-input" placeholder="Short product description shown on the product page."></textarea>
            <p class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">Under 5,000 characters. Plain text — HTML is stripped.</p>
          </div>

          <div>
            <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1.5">Size (e.g. "10mg", "5mg")</label>
            <input v-model="form.size_mg" type="text" class="ui-input" placeholder="10mg" />
          </div>
        </div>

        <!-- Pricing -->
        <div class="bg-white rounded-[12px] border border-[color:var(--color-hairline)] p-6 space-y-5">
          <h2 class="text-[14px] font-semibold text-[color:var(--color-ink)]">Pricing</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1.5">Retail price *</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[color:var(--color-ink-subtle)] text-sm">$</span>
                <input v-model.number="form.price" type="number" step="0.01" min="0" required class="ui-input pl-7" placeholder="39.99" />
              </div>
              <p v-if="form.errors.price" class="text-[11px] text-red-600 mt-1">{{ form.errors.price }}</p>
            </div>
            <div>
              <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1.5">Sale price (optional)</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[color:var(--color-ink-subtle)] text-sm">$</span>
                <input v-model.number="form.discount_price" type="number" step="0.01" min="0" class="ui-input pl-7" placeholder="32.99" />
              </div>
              <p class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">Ignored if ≥ retail.</p>
            </div>
          </div>
        </div>

        <!-- Links + Image -->
        <div class="bg-white rounded-[12px] border border-[color:var(--color-hairline)] p-6 space-y-5">
          <h2 class="text-[14px] font-semibold text-[color:var(--color-ink)]">Links &amp; Image</h2>

          <div>
            <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1.5">Product URL on your site</label>
            <input v-model="form.product_url" type="url" class="ui-input" placeholder="https://yoursite.com/products/bpc-157-10mg" />
            <p class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">Where the "Buy" button sends customers.</p>
            <p v-if="form.errors.product_url" class="text-[11px] text-red-600 mt-1">{{ form.errors.product_url }}</p>
          </div>

          <div>
            <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1.5">Image URL</label>
            <input v-model="form.image_url" type="url" class="ui-input" placeholder="https://yoursite.com/images/bpc-157.png" />
            <p class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">Hosted on your own site. Transparent PNGs work best.</p>
            <p v-if="form.errors.image_url" class="text-[11px] text-red-600 mt-1">{{ form.errors.image_url }}</p>
            <div v-if="form.image_url" class="mt-3 w-32 h-32 border border-[color:var(--color-hairline)] rounded-[8px] p-2 bg-[color:var(--color-hairline-soft)] flex items-center justify-center">
              <img :src="form.image_url" alt="Preview" class="max-w-full max-h-full object-contain" @error="onImageError" />
            </div>
          </div>
        </div>

        <!-- Flags -->
        <div class="bg-white rounded-[12px] border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-[14px] font-semibold text-[color:var(--color-ink)]">Status</h2>

          <label v-if="product.auto_scraped" class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="form.auto_update" class="mt-1" />
            <div>
              <div class="text-[13px] font-medium text-[color:var(--color-ink)]">Auto-sync from your site</div>
              <div class="text-[12px] text-[color:var(--color-ink-subtle)]">
                We re-scrape your storefront daily and refresh this product's price, image, description, and stock. Uncheck to lock the edits below and stop overwrites.
                <span v-if="product.last_scraped_at" class="block mt-0.5 text-[color:var(--color-ink-subtle)]">Last synced {{ formatDate(product.last_scraped_at) }}.</span>
              </div>
            </div>
          </label>

          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="form.hidden" class="mt-1" />
            <div>
              <div class="text-[13px] font-medium text-[color:var(--color-ink)]">Hide from Peptidemap</div>
              <div class="text-[12px] text-[color:var(--color-ink-subtle)]">Product stays in your catalog but doesn't appear in public listings.</div>
            </div>
          </label>

          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="form.featured" class="mt-1" />
            <div>
              <div class="text-[13px] font-medium text-[color:var(--color-ink)]">Featured product</div>
              <div class="text-[12px] text-[color:var(--color-ink-subtle)]">Show at the top of your storefront + eligible for compound-comparison hero slot.</div>
            </div>
          </label>

          <div>
            <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1.5">Stock availability</label>
            <select v-model="form.availability" class="ui-input">
              <option value="in_stock">In stock</option>
              <option value="out_of_stock">Out of stock</option>
            </select>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
          <Link href="/vendor/products" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">Cancel</Link>
          <button type="submit" :disabled="form.processing" class="h-11 px-6 text-[14px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] rounded-[10px] shadow-sm hover:-translate-y-[0.5px] transition-all disabled:opacity-50">
            {{ form.processing ? 'Saving…' : 'Save changes' }}
          </button>
        </div>
      </form>
    </div>
  </Layout>
</template>

<script setup>
import { Link, useForm, Head } from '@inertiajs/vue3'
import Layout from './Layout.vue'

const props = defineProps({
  product: { type: Object, required: true },
})

const form = useForm({
  name: props.product.name || '',
  description: props.product.description || '',
  price: props.product.price || 0,
  discount_price: props.product.discount_price || null,
  image_url: props.product.image_url || '',
  product_url: props.product.product_url || '',
  size_mg: props.product.size_mg || '',
  featured: !!props.product.featured,
  hidden: !!props.product.hidden,
  availability: props.product.availability || 'in_stock',
  auto_update: props.product.auto_update ?? true,
})

function formatDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

function submit() {
  form.put(`/vendor/products/${props.product.id}`, {
    preserveScroll: true,
  })
}

function onImageError(e) {
  e.target.style.display = 'none'
}
</script>
