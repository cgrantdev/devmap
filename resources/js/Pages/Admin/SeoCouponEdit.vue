<template>
  <AdminLayout>
    <PageHeader :title="`Coupon page: ${brand.name}`" :subtitle="brand.url">
      <template #actions>
        <Link href="/admin/seo/coupons" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← Coupon SEO</Link>
      </template>
    </PageHeader>

    <div class="max-w-3xl mx-auto px-6 py-6">
      <p v-if="!ready" class="mb-6 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-[13px] text-amber-900">
        Coupon page editing turns on after the pending database update. The fields are read-only until then.
      </p>
      <p v-else-if="!brand.live" class="mb-6 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-[13px] text-amber-900">
        This vendor has no coupon code right now, so the page is offline. Copy saved here shows once it has one again.
      </p>
      <p class="mb-6 text-[13px] text-[color:var(--color-ink-muted)]">
        Code <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ brand.code || '—' }}</span>. Anything you fill in replaces that part of the page; blank fields keep the default shown in grey.
      </p>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Search snippet</h2>
          <div>
            <label for="coupon-title" class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">Page title <span class="text-[color:var(--color-ink-subtle)]">({{ form.title.length }} / 60 ideal)</span></label>
            <input id="coupon-title" v-model="form.title" maxlength="255" :disabled="!ready" :placeholder="defaults.title" :class="['ui-input', form.errors.title ? 'border-red-500' : '']" />
            <p v-if="form.errors.title" class="mt-1 text-[12px] text-red-600">{{ form.errors.title }}</p>
          </div>
          <div>
            <label for="coupon-meta" class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">Meta description <span class="text-[color:var(--color-ink-subtle)]">({{ form.meta_description.length }} / 160 ideal, 500 max)</span></label>
            <textarea id="coupon-meta" v-model="form.meta_description" rows="3" maxlength="500" :disabled="!ready" :placeholder="defaults.meta_description" :class="['ui-input', form.errors.meta_description ? 'border-red-500' : '']"></textarea>
            <p v-if="form.errors.meta_description" class="mt-1 text-[12px] text-red-600">{{ form.errors.meta_description }}</p>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Heading and intro</h2>
          <div>
            <label for="coupon-h1" class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">H1</label>
            <input id="coupon-h1" v-model="form.h1" maxlength="255" :disabled="!ready" :placeholder="defaults.h1" :class="['ui-input', form.errors.h1 ? 'border-red-500' : '']" />
            <p v-if="form.errors.h1" class="mt-1 text-[12px] text-red-600">{{ form.errors.h1 }}</p>
          </div>
          <RichHtmlField
            v-if="ready"
            v-model="form.intro_html"
            label="Intro"
            :error="form.errors.intro_html"
            :rows="5"
            :min-height="120"
            :max-length="5000"
            hint="One or two sentences under the H1, above the code. There is no default intro."
          />
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Page copy</h2>
          <RichHtmlField
            v-if="ready"
            v-model="form.body_html"
            label="Copy"
            :error="form.errors.body_html"
            :rows="18"
            :min-height="300"
            hint="Shown below the how-to-use steps. Replaces the default &quot;About {vendor}&quot; paragraph. Use H2 and H3 for sections."
          />
          <p v-else class="text-[12px] text-[color:var(--color-ink-subtle)]">Available after the database update.</p>
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-3">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">FAQs</h2>
          <p class="text-[12px] text-[color:var(--color-ink-muted)]">Shown on the page and used for the FAQ schema, so both always match. Up to 12. When empty, the default question blocks show (they have no FAQ schema).</p>
          <FaqListField v-model="form.faqs" :errors="form.errors" :disabled="!ready" empty-text="No FAQs yet. The default question blocks show." />
        </div>

        <div class="flex items-center justify-end gap-4">
          <span v-if="savedAt" class="text-[12px] text-emerald-700">Saved {{ savedAt }}</span>
          <span v-else-if="hasErrors" class="text-[12px] text-red-600">Not saved. Fix the fields marked in red.</span>
          <a :href="brand.url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View live page ↗</a>
          <button type="submit" :disabled="form.processing || !ready" class="bg-[color:var(--color-ink)] text-white rounded-lg px-5 py-2 text-sm font-semibold disabled:opacity-40">{{ form.processing ? 'Saving…' : 'Save' }}</button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import RichHtmlField from '@/components/admin/RichHtmlField.vue'
import FaqListField from '@/components/admin/FaqListField.vue'
import AdminLayout from './Layout.vue'

const props = defineProps({
  brand: { type: Object, required: true },
  defaults: { type: Object, required: true },
  content: { type: Object, required: true },
  ready: { type: Boolean, default: false },
})

const form = useForm({
  title: props.content.title || '',
  meta_description: props.content.meta_description || '',
  h1: props.content.h1 || '',
  intro_html: props.content.intro_html || '',
  body_html: props.content.body_html || '',
  faqs: (props.content.faqs || []).map(f => ({ question: f.question || '', answer: f.answer || '' })),
})

const savedAt = ref('')
const hasErrors = computed(() => Object.keys(form.errors).length > 0)

function submit() {
  savedAt.value = ''
  form.faqs = form.faqs.filter(f => (f.question || '').trim() !== '' || (f.answer || '').trim() !== '')
  form.put(`/admin/seo/coupons/${props.brand.id}`, {
    preserveScroll: true,
    onSuccess: () => { savedAt.value = new Date().toLocaleTimeString() },
  })
}
</script>
