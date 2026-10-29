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
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Brand description (on-page body)</h2>
          <textarea v-model="form.description" rows="6" class="ui-input"></textarea>
          <p class="text-[11px] text-[color:var(--color-ink-muted)]">Shows on the storefront as the "about this vendor" paragraph.</p>
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
          <p class="text-[11px] text-[color:var(--color-ink-muted)]">Up to 8 reasons shoppers should pick this vendor. Shown on the storefront.</p>
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
import AdminLayout from './Layout.vue'

const props = defineProps({ brand: Object })

const form = useForm({
  _method: 'put',
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
  form.post(`/admin/seo/brands/${props.brand.id}`, { forceFormData: true })
}
</script>
