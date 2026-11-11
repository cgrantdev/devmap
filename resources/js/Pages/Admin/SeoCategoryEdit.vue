<template>
  <AdminLayout>
    <PageHeader :title="`Compound SEO: ${category.name}`" subtitle="Each tab authors one page. Peptides owns buying/vendor intent; Compare owns price/comparison intent.">
      <template #actions>
        <Link href="/admin/seo/categories" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← All compounds</Link>
      </template>
    </PageHeader>

    <div class="max-w-4xl mx-auto px-6 py-6 space-y-6">
      <!-- Tab switcher -->
      <div class="flex gap-2 border-b border-[color:var(--color-hairline)]">
        <button
          type="button"
          @click="activeTab = 'peptides'"
          :class="['px-4 py-2 text-[13px] font-semibold border-b-2 -mb-px transition-colors',
            activeTab === 'peptides'
              ? 'border-[color:var(--color-ink)] text-[color:var(--color-ink)]'
              : 'border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]']"
        >
          Peptides page
          <span class="ml-2 text-[10px] text-[color:var(--color-ink-subtle)] font-mono">{{ category.peptides_url }}</span>
        </button>
        <button
          type="button"
          @click="activeTab = 'compare'"
          :class="['px-4 py-2 text-[13px] font-semibold border-b-2 -mb-px transition-colors',
            activeTab === 'compare'
              ? 'border-[color:var(--color-ink)] text-[color:var(--color-ink)]'
              : 'border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]']"
        >
          Compare page
          <span class="ml-2 text-[10px] text-[color:var(--color-ink-subtle)] font-mono">{{ category.compare_url }}</span>
        </button>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Block renderer — one shared template, two scopes. -->
        <div v-for="tab in ['peptides', 'compare']" v-show="activeTab === tab" :key="tab" class="space-y-6">
          <!-- Title + meta -->
          <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
            <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Title + meta description</h2>
            <div>
              <label class="text-[11px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)]">Title</label>
              <input
                v-model="form[tab].title"
                maxlength="200"
                class="ui-input"
                :placeholder="`Default: ${defaultTitle(tab)}`"
              />
              <div class="text-[10px] font-mono mt-1" :class="(form[tab].title || '').length > 70 ? 'text-amber-600' : 'text-[color:var(--color-ink-subtle)]'">{{ (form[tab].title || '').length }} / 70 ideal</div>
            </div>
            <div>
              <label class="text-[11px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)]">Meta description</label>
              <textarea
                v-model="form[tab].meta"
                maxlength="2000"
                rows="3"
                class="ui-input text-[13px]"
                placeholder="One-sentence description the search snippet uses."
              ></textarea>
              <div class="text-[10px] font-mono mt-1" :class="(form[tab].meta || '').length > 160 ? 'text-amber-600' : 'text-[color:var(--color-ink-subtle)]'">{{ (form[tab].meta || '').length }} / 160 ideal</div>
            </div>
            <p class="text-[11px] text-[color:var(--color-ink-muted)]">Title and meta are rendered verbatim with no "- Peptidemap" tail. Leave blank to fall back to the auto-generated default.</p>
          </div>

          <!-- H1 -->
          <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-3">
            <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Custom H1</h2>
            <input
              v-model="form[tab].h1"
              maxlength="180"
              class="ui-input"
              :placeholder="`Default: ${category.name.toUpperCase()}`"
            />
            <p class="text-[11px] text-[color:var(--color-ink-muted)]">Overrides the H1 on this page only. Leave blank to use the compound name.</p>
          </div>

          <!-- Intro -->
          <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-3">
            <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Intro paragraph (HTML allowed)</h2>
            <textarea
              v-model="form[tab].intro"
              rows="6"
              class="ui-input font-mono text-[12px]"
              :placeholder="tab === 'peptides' ? '<p>Buy retatrutide from vetted vendors…</p>' : '<p>Compare retatrutide prices across…</p>'"
            ></textarea>
            <p class="text-[11px] text-[color:var(--color-ink-muted)]">Rendered under the H1 on this page. Basic HTML (p, strong, em, a, ul, li) is kept. {{ (form[tab].intro || '').length }} / 50,000 chars.</p>
          </div>

          <!-- FAQs -->
          <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6 space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">FAQs</h2>
              <button
                type="button"
                @click="addFaq(tab)"
                class="text-[11px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]"
                :disabled="form[tab].faqs.length >= 12"
              >+ Add FAQ ({{ form[tab].faqs.length }} / 12)</button>
            </div>
            <div class="space-y-3">
              <div v-for="(faq, i) in form[tab].faqs" :key="i" class="border border-[color:var(--color-hairline-soft)] rounded-lg p-3 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-[10px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)]">Question {{ i + 1 }}</span>
                  <button type="button" @click="form[tab].faqs.splice(i, 1)" class="text-xs text-red-600 hover:text-red-800">Remove</button>
                </div>
                <input v-model="faq.q" maxlength="300" class="ui-input text-[13px]" placeholder="What is retatrutide?" />
                <textarea v-model="faq.a" maxlength="4000" rows="3" class="ui-input text-[13px]" placeholder="Retatrutide is…"></textarea>
              </div>
              <p v-if="!form[tab].faqs.length" class="text-[11px] italic text-[color:var(--color-ink-muted)]">No FAQs yet. Click "+ Add FAQ" to add one.</p>
            </div>
            <p class="text-[11px] text-[color:var(--color-ink-muted)]">Rendered as an accordion on this page AND emitted into FAQPage JSON-LD. Peptides and Compare FAQs should differ (buying-intent vs comparison-intent questions).</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 sticky bottom-4 bg-white/85 backdrop-blur rounded-lg px-4 py-3 border border-[color:var(--color-hairline)] shadow-[var(--shadow-sm)]">
          <a :href="category.peptides_url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View /product ↗</a>
          <a :href="category.compare_url" target="_blank" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View /compare ↗</a>
          <button type="submit" :disabled="form.processing" class="bg-[color:var(--color-ink)] text-white rounded-lg px-5 py-2 text-sm font-semibold">{{ form.processing ? 'Saving…' : 'Save both' }}</button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import AdminLayout from './Layout.vue'

const props = defineProps({ category: Object })

const activeTab = ref('peptides')

const form = useForm({
  peptides: {
    title: props.category.peptides?.title || '',
    meta:  props.category.peptides?.meta || '',
    h1:    props.category.peptides?.h1 || '',
    intro: props.category.peptides?.intro || '',
    faqs:  (props.category.peptides?.faqs || []).map(f => ({ q: f.q || '', a: f.a || '' })),
  },
  compare: {
    title: props.category.compare?.title || '',
    meta:  props.category.compare?.meta || '',
    h1:    props.category.compare?.h1 || '',
    intro: props.category.compare?.intro || '',
    faqs:  (props.category.compare?.faqs || []).map(f => ({ q: f.q || '', a: f.a || '' })),
  },
})

function addFaq(tab) {
  if (form[tab].faqs.length < 12) form[tab].faqs.push({ q: '', a: '' })
}

function defaultTitle(tab) {
  const name = props.category.name
  return tab === 'peptides'
    ? `${name} Peptides — Compare Vendors`
    : `Compare ${name} Prices Across Vendors`
}

function submit() {
  form.put(`/admin/seo/categories/${props.category.id}`)
}
</script>
