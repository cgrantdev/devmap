<template>
  <AdminLayout>
    <PageHeader :title="`Page copy: ${page.label}`" :subtitle="page.url">
      <template #actions>
        <Link href="/admin/seo/calculators" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← Calculator SEO</Link>
      </template>
    </PageHeader>

    <div class="max-w-3xl mx-auto px-6 py-6">
      <p v-if="!ready" class="mb-6 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-[13px] text-amber-900">
        Page copy turns on after the pending database update. The fields are read-only until then.
      </p>
      <p class="mb-6 text-[13px] text-[color:var(--color-ink-muted)]">
        Title and meta description stay on the Calculator SEO list. Anything you fill in here replaces that part of the page; blank fields keep the default.
      </p>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Heading and intro</h2>
          <div>
            <label for="calc-h1" class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">H1 <span class="text-[color:var(--color-ink-subtle)]">(blank = "{{ page.default_h1 }}")</span></label>
            <input id="calc-h1" v-model="form.h1" maxlength="255" :disabled="!ready" :placeholder="page.default_h1" :class="['ui-input', form.errors.h1 ? 'border-red-500' : '']" />
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
            hint="One or two sentences under the H1, above the calculator. Replaces the default line."
          />
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Page copy below the calculator</h2>
          <RichHtmlField
            v-if="ready"
            v-model="form.body_html"
            label="Copy"
            :error="form.errors.body_html"
            :rows="20"
            :min-height="360"
            hint="Replaces the three default guide sections (How to reconstitute, Storage and handling, Troubleshooting) and the HowTo schema that mirrors them. Use H2 and H3 for sections."
          />
          <p v-else class="text-[12px] text-[color:var(--color-ink-subtle)]">Available after the database update.</p>
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-3">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">FAQs</h2>
          <p class="text-[12px] text-[color:var(--color-ink-muted)]">Shown on the page and used for the FAQ schema, so both always match. Up to 12. When empty, the 12 default calculator FAQs show.</p>
          <FaqListField v-model="form.faqs" :errors="form.errors" :disabled="!ready" empty-text="No FAQs yet. The default ones show." />
        </div>

        <div class="flex items-center justify-end gap-4">
          <span v-if="savedAt" class="text-[12px] text-emerald-700">Saved {{ savedAt }}</span>
          <span v-else-if="hasErrors" class="text-[12px] text-red-600">Not saved. Fix the fields marked in red.</span>
          <a :href="page.url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View live page ↗</a>
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
  page: { type: Object, required: true },
  content: { type: Object, required: true },
  ready: { type: Boolean, default: false },
})

const form = useForm({
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
  form.put(`/admin/seo/calculators/${props.page.slug}/content`, {
    preserveScroll: true,
    onSuccess: () => { savedAt.value = new Date().toLocaleTimeString() },
  })
}
</script>
