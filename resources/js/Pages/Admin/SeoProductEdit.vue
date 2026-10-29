<template>
  <AdminLayout>
    <PageHeader :title="`SEO: ${product.name}`" :subtitle="product.brand?.name || ''">
      <template #actions>
        <Link href="/admin/seo/products" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← All products</Link>
      </template>
    </PageHeader>

    <div class="max-w-3xl mx-auto px-6 py-6 space-y-6">
      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Search snippet</h2>
          <div>
            <label class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">Page title <span class="text-[color:var(--color-ink-subtle)]">({{ form.seo_page_title.length }} / 70)</span></label>
            <input v-model="form.seo_page_title" maxlength="70" class="ui-input" />
          </div>
          <div>
            <label class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">Meta description <span class="text-[color:var(--color-ink-subtle)]">({{ form.seo_description.length }} / 160 ideal, 300 max)</span></label>
            <textarea v-model="form.seo_description" rows="3" maxlength="300" class="ui-input"></textarea>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Social share (OG)</h2>
          <div>
            <label class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">OG title</label>
            <input v-model="form.seo_og_title" maxlength="120" class="ui-input" />
          </div>
          <div>
            <label class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">OG description</label>
            <textarea v-model="form.seo_og_description" rows="2" maxlength="300" class="ui-input"></textarea>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Product description (on-page body)</h2>
          <textarea v-model="form.description" rows="8" class="ui-input"></textarea>
          <p class="text-[11px] text-[color:var(--color-ink-muted)]">Shows on the product page. Keep it unique per product — Google collapses near-duplicates.</p>
        </div>

        <div class="flex justify-end gap-3">
          <a v-if="product.public_url" :href="product.public_url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View live page ↗</a>
          <button type="submit" :disabled="form.processing" class="bg-[color:var(--color-ink)] text-white rounded-lg px-5 py-2 text-sm font-semibold">{{ form.processing ? 'Saving…' : 'Save' }}</button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import AdminLayout from './Layout.vue'

const props = defineProps({ product: Object })

const form = useForm({
  description: props.product.description || '',
  seo_page_title: props.product.seo_page_title || '',
  seo_description: props.product.seo_description || '',
  seo_og_title: props.product.seo_og_title || '',
  seo_og_description: props.product.seo_og_description || '',
})

function submit() {
  form.put(`/admin/seo/products/${props.product.id}`)
}
</script>
