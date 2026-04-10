<template>
  <AdminLayout>
    <FormPage
      :title="product ? product.name : 'New Product'"
      backLabel="Products"
      backHref="/admin/products"
      :tabs="tabs"
      :activeTab="activeTab"
      @update:activeTab="activeTab = $event"
      :saving="form.processing"
      :saved="justSaved"
      @save="submit"
    />

    <!-- Flash -->
    <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 bg-[color:var(--color-verified-bg)] border border-[#A7F3D0] text-[#065F46] text-sm">
      {{ $page.props.flash.success }}
    </div>

    <!-- Errors -->
    <div v-if="Object.keys(form.errors).length > 0" class="mb-4 px-4 py-3 bg-[color:var(--color-danger-bg)] border border-red-200 text-[#991B1B] text-sm">
      <p class="font-medium mb-1">Please fix the following errors:</p>
      <ul class="list-disc list-inside">
        <li v-for="(error, field) in form.errors" :key="field">{{ Array.isArray(error) ? error[0] : error }}</li>
      </ul>
    </div>

    <!-- General tab -->
    <template v-if="activeTab === 'general'">
      <FormSection title="Product Info" :columns="2">
        <FormField label="Name" :required="true" :error="form.errors.name">
          <input v-model="form.name" type="text" class="ui-input" placeholder="Product name" />
        </FormField>
        <FormField label="Brand" :required="true" :error="form.errors.brand_id">
          <select v-model="form.brand_id" class="ui-input">
            <option :value="null">Select brand...</option>
            <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </FormField>
        <FormField label="Category" :required="true" :error="form.errors.product_category_id">
          <select v-model="form.product_category_id" class="ui-input">
            <option :value="null">Select category...</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </FormField>
        <FormField label="Dosage / Size" :error="form.errors.size_mg" hint="e.g. 5mg, 10mg vial">
          <input v-model="form.size_mg" type="text" class="ui-input" placeholder="5mg" />
        </FormField>
      </FormSection>

      <FormSection title="Description">
        <FormField label="Description" :error="form.errors.description">
          <textarea v-model="form.description" class="ui-input" rows="4" placeholder="Product description..."></textarea>
        </FormField>
      </FormSection>

      <FormSection title="Links" :columns="2">
        <FormField label="Product URL" :error="form.errors.product_url" hint="Vendor's product page URL">
          <input v-model="form.product_url" type="url" class="ui-input" placeholder="https://..." />
        </FormField>
        <FormField label="Image URL" :error="form.errors.image_url">
          <input v-model="form.image_url" type="url" class="ui-input" placeholder="https://..." />
        </FormField>
      </FormSection>
    </template>

    <!-- Pricing tab -->
    <template v-if="activeTab === 'pricing'">
      <FormSection title="Pricing" :columns="2">
        <FormField label="Price" :required="true" :error="form.errors.price">
          <input v-model="form.price" type="number" step="0.01" min="0" class="ui-input" placeholder="0.00" />
        </FormField>
        <FormField label="Discount Price" :error="form.errors.discount_price" hint="Leave empty if not on sale">
          <input v-model="form.discount_price" type="number" step="0.01" min="0" class="ui-input" placeholder="0.00" />
        </FormField>
      </FormSection>

      <FormSection title="Quality" :columns="2">
        <FormField label="Purity (%)" :error="form.errors.purity" hint="e.g. 99.2">
          <input v-model="form.purity" type="number" step="0.1" min="0" max="100" class="ui-input" placeholder="99.0" />
        </FormField>
        <FormField label="Availability">
          <select v-model="form.availability" class="ui-input">
            <option value="in_stock">In Stock</option>
            <option value="out_of_stock">Out of Stock</option>
            <option value="pre_order">Pre-Order</option>
          </select>
        </FormField>
      </FormSection>
    </template>

    <!-- Flags tab -->
    <template v-if="activeTab === 'flags'">
      <FormSection title="Visibility & Features">
        <div class="space-y-3">
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.hidden" type="checkbox" class="w-4 h-4 text-[color:var(--color-accent-600)] border-[color:var(--color-hairline)]" />
            <div>
              <div class="text-sm font-medium text-[color:var(--color-ink)]">Hidden</div>
              <div class="text-[12px] text-[color:var(--color-ink-subtle)]">Product won't appear in frontend listings</div>
            </div>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.featured" type="checkbox" class="w-4 h-4 text-[color:var(--color-accent-600)] border-[color:var(--color-hairline)]" />
            <div>
              <div class="text-sm font-medium text-[color:var(--color-ink)]">Featured</div>
              <div class="text-[12px] text-[color:var(--color-ink-subtle)]">Highlighted on category and vendor pages</div>
            </div>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.lab_tested" type="checkbox" class="w-4 h-4 text-[color:var(--color-accent-600)] border-[color:var(--color-hairline)]" />
            <div>
              <div class="text-sm font-medium text-[color:var(--color-ink)]">Lab Tested</div>
              <div class="text-[12px] text-[color:var(--color-ink-subtle)]">Shows lab-tested badge on product</div>
            </div>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.first_timer_deals" type="checkbox" class="w-4 h-4 text-[color:var(--color-accent-600)] border-[color:var(--color-hairline)]" />
            <div>
              <div class="text-sm font-medium text-[color:var(--color-ink)]">First-Timer Deals</div>
              <div class="text-[12px] text-[color:var(--color-ink-subtle)]">Eligible for first-time buyer promotions</div>
            </div>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.auto_update" type="checkbox" class="w-4 h-4 text-[color:var(--color-accent-600)] border-[color:var(--color-hairline)]" />
            <div>
              <div class="text-sm font-medium text-[color:var(--color-ink)]">Auto Update</div>
              <div class="text-[12px] text-[color:var(--color-ink-subtle)]">Automatically update from scraping source</div>
            </div>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.verified" type="checkbox" class="w-4 h-4 text-[color:var(--color-accent-600)] border-[color:var(--color-hairline)]" />
            <div>
              <div class="text-sm font-medium text-[color:var(--color-ink)]">Verified</div>
              <div class="text-[12px] text-[color:var(--color-ink-subtle)]">Product has been manually verified</div>
            </div>
          </label>
        </div>
      </FormSection>
    </template>

    <!-- SEO tab -->
    <template v-if="activeTab === 'seo'">
      <FormSection title="Search Engine Optimization">
        <FormField label="Page Title" :error="form.errors.seo_page_title" hint="Override the default page title">
          <input v-model="form.seo_page_title" type="text" class="ui-input" placeholder="SEO page title..." />
        </FormField>
        <FormField label="Meta Description" :error="form.errors.seo_description" hint="155 characters recommended">
          <textarea v-model="form.seo_description" class="ui-input" rows="3" placeholder="Meta description..."></textarea>
        </FormField>
      </FormSection>

      <FormSection title="Open Graph" :columns="2">
        <FormField label="OG Title" :error="form.errors.seo_og_title">
          <input v-model="form.seo_og_title" type="text" class="ui-input" placeholder="Open Graph title..." />
        </FormField>
        <FormField label="OG Image URL" :error="form.errors.seo_og_image">
          <input v-model="form.seo_og_image" type="url" class="ui-input" placeholder="https://..." />
        </FormField>
        <div class="md:col-span-2">
          <FormField label="OG Description" :error="form.errors.seo_og_description">
            <textarea v-model="form.seo_og_description" class="ui-input" rows="3" placeholder="Open Graph description..."></textarea>
          </FormField>
        </div>
      </FormSection>
    </template>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import FormPage from '@/components/admin/FormPage.vue'
import FormSection from '@/components/admin/FormSection.vue'
import FormField from '@/components/admin/FormField.vue'

const props = defineProps({
  product: Object,
  categories: { type: Array, default: () => [] },
  brands: { type: Array, default: () => [] },
})

const page = usePage()
const activeTab = ref('general')
const justSaved = ref(false)

const tabs = [
  { id: 'general', label: 'General' },
  { id: 'pricing', label: 'Pricing' },
  { id: 'flags', label: 'Flags' },
  { id: 'seo', label: 'SEO' },
]

const form = useForm({
  name: props.product?.name || '',
  description: props.product?.description || '',
  brand_id: props.product?.brand_id || null,
  product_category_id: props.product?.product_category_id || null,
  price: props.product?.price || '',
  discount_price: props.product?.discount_price || '',
  size_mg: props.product?.size_mg || '',
  purity: props.product?.purity || '',
  availability: props.product?.availability || 'in_stock',
  hidden: !!props.product?.hidden,
  featured: !!props.product?.featured,
  lab_tested: !!props.product?.lab_tested,
  first_timer_deals: !!props.product?.first_timer_deals,
  auto_update: !!props.product?.auto_update,
  verified: !!props.product?.verified,
  image_url: props.product?.image_url || '',
  product_url: props.product?.product_url || '',
  seo_page_title: props.product?.seo_page_title || '',
  seo_description: props.product?.seo_description || '',
  seo_og_title: props.product?.seo_og_title || '',
  seo_og_description: props.product?.seo_og_description || '',
  seo_og_image: props.product?.seo_og_image || '',
  _token: page.props.csrf_token,
})

function submit() {
  form._token = page.props.csrf_token

  if (props.product) {
    form.put(`/admin/products/${props.product.id}`, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        justSaved.value = true
        setTimeout(() => { justSaved.value = false }, 3000)
      },
    })
  } else {
    form.post('/admin/products', {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        justSaved.value = true
        setTimeout(() => { justSaved.value = false }, 3000)
      },
    })
  }
}
</script>
