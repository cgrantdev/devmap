<template>
  <AdminLayout>
    <PageHeader title="Compound SEO" subtitle="Title, meta, H1, intro and FAQs — authored separately for /product/{slug} (Peptides) and /compare/{slug} (Compare)">
      <template #actions>
        <Link href="/admin/seo" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← SEO dashboard</Link>
      </template>
    </PageHeader>

    <div class="max-w-7xl mx-auto px-6 py-6 space-y-6">
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5 grid gap-3 md:grid-cols-[1fr_auto]">
        <input v-model="searchQ" @input="debouncedSearch" placeholder="Search compound name or slug…" class="ui-input" />
        <label class="inline-flex items-center gap-2 text-sm text-[color:var(--color-ink)]">
          <input type="checkbox" v-model="missingOnly" @change="apply" />
          Missing on either page
        </label>
      </div>

      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-[color:var(--color-bg-soft)] text-[11px] uppercase tracking-wide text-[color:var(--color-ink-muted)]">
            <tr>
              <th rowspan="2" class="text-left px-4 py-3 align-bottom">Compound</th>
              <th rowspan="2" class="text-right px-4 py-3 align-bottom">Products</th>
              <th colspan="5" class="text-center px-4 py-2 border-l border-[color:var(--color-hairline-soft)]">Peptides page</th>
              <th colspan="5" class="text-center px-4 py-2 border-l border-[color:var(--color-hairline-soft)]">Compare page</th>
              <th rowspan="2" class="px-4 py-3"></th>
            </tr>
            <tr>
              <th class="text-center px-2 py-2 border-l border-[color:var(--color-hairline-soft)]" title="Custom title">T</th>
              <th class="text-center px-2 py-2" title="Meta description">M</th>
              <th class="text-center px-2 py-2" title="Custom H1">H</th>
              <th class="text-center px-2 py-2" title="Intro paragraph">I</th>
              <th class="text-center px-2 py-2" title="Number of FAQs">F</th>
              <th class="text-center px-2 py-2 border-l border-[color:var(--color-hairline-soft)]" title="Custom title">T</th>
              <th class="text-center px-2 py-2" title="Meta description">M</th>
              <th class="text-center px-2 py-2" title="Custom H1">H</th>
              <th class="text-center px-2 py-2" title="Intro paragraph">I</th>
              <th class="text-center px-2 py-2" title="Number of FAQs">F</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[color:var(--color-hairline-soft)]">
            <tr v-for="c in categories.data" :key="c.id">
              <td class="px-4 py-3">
                <div class="font-semibold text-[color:var(--color-ink)]">{{ c.name }}</div>
                <div class="text-[11px] font-mono text-[color:var(--color-ink-muted)]">/{{ c.slug }}</div>
                <div class="text-[10px] mt-1">
                  <a :href="c.peptides_url" target="_blank" class="text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] mr-2">Peptides ↗</a>
                  <a :href="c.compare_url" target="_blank" class="text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">Compare ↗</a>
                </div>
              </td>
              <td class="px-4 py-3 text-right font-mono text-[color:var(--color-ink-muted)]">{{ c.products_count }}</td>

              <!-- Peptides coverage -->
              <td class="px-2 py-3 text-center border-l border-[color:var(--color-hairline-soft)]"><Dot :on="c.peptides.has_title" /></td>
              <td class="px-2 py-3 text-center"><Dot :on="c.peptides.has_meta" /></td>
              <td class="px-2 py-3 text-center"><Dot :on="c.peptides.has_h1" /></td>
              <td class="px-2 py-3 text-center"><Dot :on="c.peptides.has_intro" /></td>
              <td class="px-2 py-3 text-center">
                <span v-if="c.peptides.faq_count" class="text-emerald-700 text-xs font-mono">{{ c.peptides.faq_count }}</span>
                <span v-else class="text-amber-600 text-base">○</span>
              </td>

              <!-- Compare coverage -->
              <td class="px-2 py-3 text-center border-l border-[color:var(--color-hairline-soft)]"><Dot :on="c.compare.has_title" /></td>
              <td class="px-2 py-3 text-center"><Dot :on="c.compare.has_meta" /></td>
              <td class="px-2 py-3 text-center"><Dot :on="c.compare.has_h1" /></td>
              <td class="px-2 py-3 text-center"><Dot :on="c.compare.has_intro" /></td>
              <td class="px-2 py-3 text-center">
                <span v-if="c.compare.faq_count" class="text-emerald-700 text-xs font-mono">{{ c.compare.faq_count }}</span>
                <span v-else class="text-amber-600 text-base">○</span>
              </td>

              <td class="px-4 py-3 text-right">
                <Link :href="`/admin/seo/categories/${c.id}/edit`" class="text-xs font-medium bg-[color:var(--color-ink)] text-white rounded-lg px-3 py-1.5 hover:opacity-90">Edit →</Link>
              </td>
            </tr>
            <tr v-if="!categories.data.length">
              <td colspan="13" class="px-4 py-10 text-center text-[color:var(--color-ink-muted)]">No compounds match these filters.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="categories.last_page > 1" class="flex gap-2 justify-center flex-wrap">
        <Link v-for="link in categories.links" :key="link.label"
          v-html="link.label"
          :href="link.url || '#'"
          :class="['px-3 py-1.5 text-xs rounded border', link.active ? 'bg-[color:var(--color-ink)] text-white border-transparent' : 'border-[color:var(--color-hairline)]']"
        />
      </div>

      <p class="text-[11px] text-[color:var(--color-ink-muted)]">Legend: T=title, M=meta description, H=H1, I=intro HTML, F=FAQ count. Green dot = set, amber ring = empty.</p>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, h } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import AdminLayout from './Layout.vue'

const Dot = {
  props: ['on'],
  render() {
    return this.on
      ? h('span', { class: 'text-emerald-700 text-base' }, '●')
      : h('span', { class: 'text-amber-600 text-base' }, '○')
  },
}

const props = defineProps({ categories: Object, q: String, onlyMissing: Boolean })
const searchQ = ref(props.q || '')
const missingOnly = ref(!!props.onlyMissing)
let searchTimeout = null

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(apply, 300)
}
function apply() {
  router.get('/admin/seo/categories', {
    q: searchQ.value || undefined,
    missing: missingOnly.value ? 1 : undefined,
  }, { preserveState: true, replace: true })
}
</script>
