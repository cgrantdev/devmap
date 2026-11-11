<template>
  <AdminLayout>
    <PageHeader :title="`Compound SEO: ${category.name}`" :subtitle="`Lives on ${category.peptides_url} and ${category.compare_url}`">
      <template #actions>
        <Link href="/admin/seo/categories" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← All compounds</Link>
      </template>
    </PageHeader>

    <div class="max-w-3xl mx-auto px-6 py-6 space-y-6">
      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Custom H1</h2>
          <input v-model="form.custom_h1" maxlength="180" class="ui-input" :placeholder="`Default: ${category.name.toUpperCase()}`" />
          <p class="text-[11px] text-[color:var(--color-ink-muted)]">Overrides the H1 on both /product/{{ category.slug }} and /compare/{{ category.slug }}. Leave blank to use the compound name.</p>
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Intro paragraph (HTML allowed)</h2>
          <textarea v-model="form.custom_intro_html" rows="6" class="ui-input font-mono text-[12px]" placeholder="<p>Retatrutide is a triple-agonist peptide...</p>"></textarea>
          <p class="text-[11px] text-[color:var(--color-ink-muted)]">Rendered under the H1 on both pages. Basic HTML (p, strong, em, a, ul, li) is kept. {{ form.custom_intro_html.length }} / 50,000 chars.</p>
        </div>

        <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">FAQs</h2>
            <button type="button" @click="addFaq" class="text-[11px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]" :disabled="form.custom_faqs.length >= 12">+ Add FAQ ({{ form.custom_faqs.length }} / 12)</button>
          </div>
          <div class="space-y-3">
            <div v-for="(faq, i) in form.custom_faqs" :key="i" class="border border-[color:var(--color-hairline-soft)] rounded-lg p-3 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)]">Question {{ i + 1 }}</span>
                <button type="button" @click="form.custom_faqs.splice(i, 1)" class="text-xs text-red-600 hover:text-red-800">Remove</button>
              </div>
              <input v-model="faq.q" maxlength="300" class="ui-input text-[13px]" placeholder="What is retatrutide?" />
              <textarea v-model="faq.a" maxlength="4000" rows="3" class="ui-input text-[13px]" placeholder="Retatrutide is..."></textarea>
            </div>
            <p v-if="!form.custom_faqs.length" class="text-[11px] italic text-[color:var(--color-ink-muted)]">No FAQs yet. Click "+ Add FAQ" to add one.</p>
          </div>
          <p class="text-[11px] text-[color:var(--color-ink-muted)]">Rendered as an accordion on both pages AND emitted into FAQPage JSON-LD so Google can surface them as rich results. 6-8 is the sweet spot.</p>
        </div>

        <div class="flex justify-end gap-3">
          <a :href="category.peptides_url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View /product ↗</a>
          <a :href="category.compare_url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View /compare ↗</a>
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

const props = defineProps({ category: Object })

const form = useForm({
  custom_h1: props.category.custom_h1 || '',
  custom_intro_html: props.category.custom_intro_html || '',
  custom_faqs: (props.category.custom_faqs || []).map(f => ({ q: f.q || '', a: f.a || '' })),
})

function addFaq() {
  if (form.custom_faqs.length < 12) form.custom_faqs.push({ q: '', a: '' })
}

function submit() {
  form.put(`/admin/seo/categories/${props.category.id}`)
}
</script>
