<template>
  <AdminLayout>
    <PageHeader :title="`Compound SEO: ${category.name}`" subtitle="Each tab edits one page. Peptides is buying and vendor intent; Compare is price and comparison intent.">
      <template #actions>
        <Link href="/admin/seo/categories" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">&larr; All compounds</Link>
      </template>
    </PageHeader>

    <div class="max-w-4xl mx-auto px-6 py-6 space-y-6">
      <div role="tablist" aria-label="Page to edit" class="flex gap-2 border-b border-[color:var(--color-hairline)]">
        <button
          v-for="tab in TABS"
          :id="`seo-tab-${tab.key}`"
          :key="tab.key"
          type="button"
          role="tab"
          :aria-selected="activeTab === tab.key ? 'true' : 'false'"
          :aria-controls="`seo-panel-${tab.key}`"
          :class="['px-4 py-2 text-[13px] font-semibold border-b-2 -mb-px transition-colors',
            activeTab === tab.key
              ? 'border-[color:var(--color-ink)] text-[color:var(--color-ink)]'
              : 'border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]']"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
          <span class="ml-2 text-[10px] text-[color:var(--color-ink-subtle)] font-mono">{{ pageUrl(tab.key) }}</span>
          <span
            v-if="tabErrorCount(tab.key)"
            class="ml-2 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-semibold"
          >{{ tabErrorCount(tab.key) }}</span>
        </button>
      </div>

      <form class="space-y-6" @submit.prevent="submit">
        <!-- One panel per tab. v-if on an inner element: v-show on the
             v-for element itself was never re-applied after mount, so the
             Peptides fields stayed on screen when Compare was selected. -->
        <template v-for="tab in TABS" :key="tab.key">
          <div
            v-if="activeTab === tab.key"
            :id="`seo-panel-${tab.key}`"
            role="tabpanel"
            :aria-labelledby="`seo-tab-${tab.key}`"
            class="space-y-6"
          >
            <p class="text-[13px] text-[color:var(--color-ink-muted)]">
              Editing the <strong class="text-[color:var(--color-ink)]">{{ tab.label }}</strong>:
              <a :href="pageUrl(tab.key)" target="_blank" rel="noopener" class="font-mono text-[12px] text-[color:var(--color-accent-700)] underline underline-offset-2">{{ pageUrl(tab.key) }} &#8599;</a>
            </p>

            <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
              <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">{{ tab.label }} title + meta description</h2>
              <div>
                <label :for="`${tab.key}-title`" class="text-[11px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)]">Title</label>
                <input
                  :id="`${tab.key}-title`"
                  v-model="form[tab.key].title"
                  maxlength="200"
                  :class="['ui-input', fieldError(tab.key, 'title') ? 'border-red-500' : '']"
                  :placeholder="`Default: ${defaultTitle(tab.key)}`"
                />
                <div class="text-[10px] font-mono mt-1" :class="(form[tab.key].title || '').length > 70 ? 'text-amber-600' : 'text-[color:var(--color-ink-subtle)]'">{{ (form[tab.key].title || '').length }} / 70 ideal</div>
                <p v-if="fieldError(tab.key, 'title')" class="mt-1 text-[12px] text-red-600">{{ fieldError(tab.key, 'title') }}</p>
              </div>
              <div>
                <label :for="`${tab.key}-meta`" class="text-[11px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)]">Meta description</label>
                <textarea
                  :id="`${tab.key}-meta`"
                  v-model="form[tab.key].meta"
                  maxlength="2000"
                  rows="3"
                  :class="['ui-input text-[13px]', fieldError(tab.key, 'meta') ? 'border-red-500' : '']"
                  placeholder="One-sentence description the search snippet uses."
                ></textarea>
                <div class="text-[10px] font-mono mt-1" :class="(form[tab.key].meta || '').length > 160 ? 'text-amber-600' : 'text-[color:var(--color-ink-subtle)]'">{{ (form[tab.key].meta || '').length }} / 160 ideal</div>
                <p v-if="fieldError(tab.key, 'meta')" class="mt-1 text-[12px] text-red-600">{{ fieldError(tab.key, 'meta') }}</p>
              </div>
              <p class="text-[11px] text-[color:var(--color-ink-muted)]">Title and meta render verbatim with no site-name tail appended. Leave blank to fall back to the auto-generated default.</p>
            </div>

            <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-3">
              <label :for="`${tab.key}-h1`" class="block text-sm font-semibold text-[color:var(--color-ink)]">{{ tab.label }} H1</label>
              <input
                :id="`${tab.key}-h1`"
                v-model="form[tab.key].h1"
                maxlength="180"
                :class="['ui-input', fieldError(tab.key, 'h1') ? 'border-red-500' : '']"
                :placeholder="`Default: ${defaultH1(tab.key)}`"
              />
              <p v-if="fieldError(tab.key, 'h1')" class="text-[12px] text-red-600">{{ fieldError(tab.key, 'h1') }}</p>
              <p class="text-[11px] text-[color:var(--color-ink-muted)]">Overrides the H1 on this page only. Leave blank to use the compound name.</p>
            </div>

            <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-3">
              <label :for="`${tab.key}-intro`" class="block text-sm font-semibold text-[color:var(--color-ink)]">{{ tab.label }} intro (HTML allowed)</label>
              <textarea
                :id="`${tab.key}-intro`"
                v-model="form[tab.key].intro"
                rows="6"
                :class="['ui-input font-mono text-[12px]', fieldError(tab.key, 'intro') ? 'border-red-500' : '']"
                :placeholder="tab.key === 'peptides' ? '&lt;p&gt;Buy retatrutide from vetted vendors...&lt;/p&gt;' : '&lt;p&gt;Compare retatrutide prices across...&lt;/p&gt;'"
              ></textarea>
              <p v-if="fieldError(tab.key, 'intro')" class="text-[12px] text-red-600">{{ fieldError(tab.key, 'intro') }}</p>
              <p class="text-[11px] text-[color:var(--color-ink-muted)]">Rendered under the H1 on this page and replaces the default description. Basic HTML (p, strong, em, a, ul, li) is kept. {{ (form[tab.key].intro || '').length }} / 50,000 chars.</p>
            </div>

            <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
              <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">{{ tab.label }} FAQs</h2>
                <button
                  type="button"
                  class="text-[11px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] disabled:opacity-50"
                  :disabled="form[tab.key].faqs.length >= 12"
                  @click="addFaq(tab.key)"
                >+ Add FAQ ({{ form[tab.key].faqs.length }} / 12)</button>
              </div>
              <p v-if="fieldError(tab.key, 'faqs')" class="text-[12px] text-red-600">{{ fieldError(tab.key, 'faqs') }}</p>
              <div class="space-y-3">
                <div v-for="(faq, i) in form[tab.key].faqs" :key="i" class="border border-[color:var(--color-hairline-soft)] rounded-lg p-3 space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)]">Question {{ i + 1 }}</span>
                    <button type="button" class="text-xs text-red-600 hover:text-red-800" @click="form[tab.key].faqs.splice(i, 1)">Remove</button>
                  </div>
                  <input v-model="faq.q" maxlength="300" :class="['ui-input text-[13px]', fieldError(tab.key, `faqs.${i}.q`) ? 'border-red-500' : '']" placeholder="What is retatrutide?" />
                  <p v-if="fieldError(tab.key, `faqs.${i}.q`)" class="text-[12px] text-red-600">{{ fieldError(tab.key, `faqs.${i}.q`) }}</p>
                  <textarea v-model="faq.a" maxlength="4000" rows="3" :class="['ui-input text-[13px]', fieldError(tab.key, `faqs.${i}.a`) ? 'border-red-500' : '']" placeholder="Retatrutide is..."></textarea>
                  <p v-if="fieldError(tab.key, `faqs.${i}.a`)" class="text-[12px] text-red-600">{{ fieldError(tab.key, `faqs.${i}.a`) }}</p>
                </div>
                <p v-if="!form[tab.key].faqs.length" class="text-[11px] italic text-[color:var(--color-ink-muted)]">No FAQs yet. Click "+ Add FAQ" to add one.</p>
              </div>
              <p class="text-[11px] text-[color:var(--color-ink-muted)]">Rendered as an accordion on this page AND emitted into FAQPage JSON-LD. When this list has FAQs, the auto-generated FAQs are dropped from the page and the JSON-LD. Peptides and Compare FAQs should differ (buying-intent vs comparison-intent questions).</p>
            </div>
          </div>
        </template>

        <div class="flex flex-wrap items-center justify-end gap-3 sticky bottom-4 bg-white/85 backdrop-blur rounded-lg px-4 py-3 border border-[color:var(--color-hairline)] shadow-[var(--shadow-sm)]">
          <span v-if="form.recentlySuccessful" class="mr-auto text-[13px] font-semibold text-emerald-700" role="status">Saved. Both pages are updated.</span>
          <span v-else-if="errorCount" class="mr-auto text-[13px] font-semibold text-red-600" role="alert">Not saved. Fix {{ errorCount }} {{ errorCount === 1 ? 'field' : 'fields' }} marked in red.</span>
          <a :href="category.peptides_url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View /product &#8599;</a>
          <a :href="category.compare_url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View /compare &#8599;</a>
          <button type="submit" :disabled="form.processing" class="bg-[color:var(--color-ink)] text-white rounded-lg px-5 py-2 text-sm font-semibold disabled:opacity-60">{{ form.processing ? 'Saving...' : 'Save both pages' }}</button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import AdminLayout from './Layout.vue'

const props = defineProps({ category: Object })

// Keys match the request blocks SeoCategoriesController validates.
const TABS = [
  { key: 'peptides', label: 'Peptides page' },
  { key: 'compare', label: 'Compare page' },
]

const activeTab = ref('peptides')

function block(source) {
  return {
    title: source?.title || '',
    meta: source?.meta || '',
    h1: source?.h1 || '',
    intro: source?.intro || '',
    faqs: (source?.faqs || []).map(function (f) { return { q: f.q || '', a: f.a || '' } }),
  }
}

const form = useForm({
  peptides: block(props.category.peptides),
  compare: block(props.category.compare),
})

const errorCount = computed(() => Object.keys(form.errors || {}).length)

function pageUrl(key) {
  return key === 'peptides' ? props.category.peptides_url : props.category.compare_url
}

function fieldError(tab, path) {
  return (form.errors || {})[`${tab}.${path}`] || null
}

function tabErrorCount(tab) {
  return Object.keys(form.errors || {}).filter((k) => k.startsWith(`${tab}.`)).length
}

function addFaq(tab) {
  if (form[tab].faqs.length < 12) form[tab].faqs.push({ q: '', a: '' })
}

function defaultTitle(tab) {
  const name = props.category.name
  return tab === 'peptides'
    ? `${name} Peptides - Compare Vendors`
    : `Compare ${name} Prices Across Vendors`
}

function defaultH1(tab) {
  return tab === 'peptides' ? props.category.name.toUpperCase() : props.category.name
}

function submit() {
  form.put(`/admin/seo/categories/${props.category.id}`, {
    preserveScroll: true,
    onError: (errors) => {
      const first = TABS.find((t) => Object.keys(errors || {}).some((k) => k.startsWith(`${t.key}.`)))
      if (first) activeTab.value = first.key
    },
  })
}
</script>
