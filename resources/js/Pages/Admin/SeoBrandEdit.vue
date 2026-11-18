<template>
  <AdminLayout>
    <PageHeader :title="`SEO: ${brand.name}`" :subtitle="brand.storefront_url">
      <template #actions>
        <Link href="/admin/seo/brands" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← All brands</Link>
      </template>
    </PageHeader>

    <div class="max-w-3xl mx-auto px-6 py-6 space-y-6">
      <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Logo</h2>
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 rounded-lg border border-[color:var(--color-hairline-soft)] bg-[color:var(--color-bg-soft)] flex items-center justify-center overflow-hidden">
              <img v-if="brand.logo" :src="brand.logo" class="max-w-full max-h-full object-contain" />
              <span v-else class="text-[10px] text-[color:var(--color-ink-muted)]">No logo</span>
            </div>
            <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="e => form.logo = e.target.files[0]" class="text-xs" />
          </div>
          <p class="text-[11px] text-[color:var(--color-ink-muted)]">PNG, JPG, WebP or SVG. Max 2MB. Shown on the storefront + OG share image.</p>
        </div>

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
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Page heading</h2>
          <div>
            <label for="brand-h1" class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">H1 <span class="text-[color:var(--color-ink-subtle)]">(blank = "{{ brand.name }}")</span></label>
            <input id="brand-h1" v-model="form.h1" maxlength="255" :disabled="!copyReady" :placeholder="brand.name" :class="['ui-input', form.errors.h1 ? 'border-red-500' : '']" />
            <p v-if="form.errors.h1" class="mt-1 text-[12px] text-red-600">{{ form.errors.h1 }}</p>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">About this vendor</h2>
          <textarea v-model="form.description" rows="6" class="ui-input"></textarea>
          <p class="text-[11px] text-[color:var(--color-ink-muted)]">The short paragraph under the vendor name at the top of the page.</p>
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Main page copy</h2>
          <p v-if="!copyReady" class="text-[12px] text-amber-700">The H1, main copy and FAQ fields turn on after the pending database update.</p>
          <RichHtmlField
            v-if="copyReady"
            v-model="form.body_html"
            label="Copy"
            :error="form.errors.body_html"
            :rows="18"
            :min-height="320"
            hint="Long-form copy shown below the products and reviews. Use H2 and H3 for sections."
          />
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-3">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">FAQs</h2>
          <p class="text-[12px] text-[color:var(--color-ink-muted)]">Shown on the page and used for the FAQ schema, so both always match. Up to 12. When empty, the automatic FAQs (coupon code, where they ship, product count, rating) show instead.</p>
          <FaqListField v-model="form.faqs" :errors="form.errors" :disabled="!copyReady" empty-text="No FAQs yet. The automatic ones show." />
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Why Choose bullets</h2>
            <button type="button" @click="addBullet" class="text-[11px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]" :disabled="form.why_choose_bullets.length >= 8">+ Add bullet</button>
          </div>
          <div class="space-y-2">
            <div v-for="(b, i) in form.why_choose_bullets" :key="i" class="flex items-start gap-2">
              <input v-model="form.why_choose_bullets[i]" maxlength="200" class="ui-input flex-1" :placeholder="`Bullet ${i + 1}`" />
              <button type="button" @click="form.why_choose_bullets.splice(i, 1)" class="text-xs text-red-600 hover:text-red-800 mt-2">✕</button>
            </div>
            <p v-if="!form.why_choose_bullets.length" class="text-[11px] text-[color:var(--color-ink-muted)] italic">No bullets yet. Click "+ Add bullet" to add one.</p>
          </div>
          <p class="text-[11px] text-[color:var(--color-ink-muted)]">Up to 8 reasons shoppers should pick this vendor. Shown in the "Why choose" panel beside the products.</p>
        </div>

        <div class="flex justify-end gap-3">
          <a :href="brand.storefront_url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View live page ↗</a>
          <button type="submit" :disabled="form.processing" class="bg-[color:var(--color-ink)] text-white rounded-lg px-5 py-2 text-sm font-semibold">{{ form.processing ? 'Saving…' : 'Save' }}</button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import RichHtmlField from '@/components/admin/RichHtmlField.vue'
import FaqListField from '@/components/admin/FaqListField.vue'
import AdminLayout from './Layout.vue'

const props = defineProps({
  brand: Object,
  // False until the database update that adds page_contents has run.
  copyReady: { type: Boolean, default: false },
})

const form = useForm({
  _method: 'put',
  h1: props.brand.h1 || '',
  body_html: props.brand.body_html || '',
  faqs: (props.brand.faqs || []).map(f => ({ question: f.question || '', answer: f.answer || '' })),
  // Tells the server this form carries the H1 / copy / FAQ fields (FormData
  // drops an empty FAQ list, so the flag is how an emptied list clears).
  copy_sent: props.copyReady,
  description: props.brand.description || '',
  why_choose_bullets: props.brand.why_choose_bullets?.slice() || [],
  seo_page_title: props.brand.seo_page_title || '',
  seo_description: props.brand.seo_description || '',
  seo_og_title: props.brand.seo_og_title || '',
  seo_og_description: props.brand.seo_og_description || '',
  logo: null,
})

function addBullet() {
  if (form.why_choose_bullets.length < 8) form.why_choose_bullets.push('')
}

function submit() {
  form.faqs = form.faqs.filter(f => (f.question || '').trim() !== '' || (f.answer || '').trim() !== '')
  form.post(`/admin/seo/brands/${props.brand.id}`, { forceFormData: true, preserveScroll: true })
}
</script>
