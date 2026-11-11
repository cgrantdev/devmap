<template>
  <AdminLayout>
    <PageHeader title="Compound SEO" subtitle="Custom H1, intro, and FAQs — applied to BOTH /product/{slug} and /compare/{slug}">
      <template #actions>
        <Link href="/admin/seo" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← SEO dashboard</Link>
      </template>
    </PageHeader>

    <div class="max-w-6xl mx-auto px-6 py-6 space-y-6">
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5 grid gap-3 md:grid-cols-[1fr_auto]">
        <input v-model="searchQ" @input="debouncedSearch" placeholder="Search compound name or slug…" class="ui-input" />
        <label class="inline-flex items-center gap-2 text-sm text-[color:var(--color-ink)]">
          <input type="checkbox" v-model="missingOnly" @change="apply" />
          Missing content only
        </label>
      </div>

      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-[color:var(--color-bg-soft)] text-[11px] uppercase tracking-wide text-[color:var(--color-ink-muted)]">
            <tr>
              <th class="text-left px-4 py-3">Compound</th>
              <th class="text-right px-4 py-3">Products</th>
              <th class="text-center px-4 py-3" title="Custom H1 set">H1</th>
              <th class="text-center px-4 py-3" title="Intro paragraph set">Intro</th>
              <th class="text-center px-4 py-3" title="Number of FAQs">FAQs</th>
              <th class="text-right px-4 py-3">Live pages</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[color:var(--color-hairline-soft)]">
            <tr v-for="c in categories.data" :key="c.id">
              <td class="px-4 py-3">
                <div class="font-semibold text-[color:var(--color-ink)]">{{ c.name }}</div>
                <div class="text-[11px] font-mono text-[color:var(--color-ink-muted)]">/{{ c.slug }}</div>
              </td>
              <td class="px-4 py-3 text-right font-mono text-[color:var(--color-ink-muted)]">{{ c.products_count }}</td>
              <td class="px-4 py-3 text-center">
                <span v-if="c.has_h1" class="text-emerald-700 text-base">●</span>
                <span v-else class="text-amber-600 text-base">○</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span v-if="c.has_intro" class="text-emerald-700 text-base">●</span>
                <span v-else class="text-amber-600 text-base">○</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span v-if="c.has_faqs" class="text-emerald-700 text-xs font-mono">{{ c.faq_count }}</span>
                <span v-else class="text-amber-600 text-base">○</span>
              </td>
              <td class="px-4 py-3 text-right text-[11px]">
                <a :href="c.peptides_url" target="_blank" class="text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] mr-2">Peptides ↗</a>
                <a :href="c.compare_url" target="_blank" class="text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">Compare ↗</a>
              </td>
              <td class="px-4 py-3 text-right">
                <Link :href="`/admin/seo/categories/${c.id}/edit`" class="text-xs font-medium bg-[color:var(--color-ink)] text-white rounded-lg px-3 py-1.5 hover:opacity-90">Edit →</Link>
              </td>
            </tr>
            <tr v-if="!categories.data.length">
              <td colspan="7" class="px-4 py-10 text-center text-[color:var(--color-ink-muted)]">No compounds match these filters.</td>
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
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import AdminLayout from './Layout.vue'

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
