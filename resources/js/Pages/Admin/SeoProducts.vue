<template>
  <AdminLayout>
    <PageHeader title="Product SEO" subtitle="Edit title, meta description, and OG tags for every product">
      <template #actions>
        <Link href="/admin/seo" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← SEO dashboard</Link>
      </template>
    </PageHeader>

    <div class="max-w-6xl mx-auto px-6 py-6 space-y-6">
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5 grid gap-3 md:grid-cols-[1fr_200px_auto]">
        <input v-model="searchQ" @input="debouncedSearch" placeholder="Search product name or slug…" class="ui-input" />
        <select v-model="brandId" @change="apply" class="ui-input">
          <option :value="0">All brands</option>
          <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
        <label class="inline-flex items-center gap-2 text-sm text-[color:var(--color-ink)]">
          <input type="checkbox" v-model="missingOnly" @change="apply" />
          Missing SEO only
        </label>
      </div>

      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-[color:var(--color-bg-soft)] text-[11px] uppercase tracking-wide text-[color:var(--color-ink-muted)]">
            <tr>
              <th class="text-left px-4 py-3">Product</th>
              <th class="text-left px-4 py-3">Brand</th>
              <th class="text-center px-4 py-3">Title</th>
              <th class="text-center px-4 py-3">Meta</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[color:var(--color-hairline-soft)]">
            <tr v-for="p in products.data" :key="p.id">
              <td class="px-4 py-3">
                <div class="font-semibold text-[color:var(--color-ink)]">{{ p.name }}</div>
                <div class="text-[11px] font-mono text-[color:var(--color-ink-muted)]">/{{ p.slug }}</div>
              </td>
              <td class="px-4 py-3 text-[color:var(--color-ink-muted)]">{{ p.brand?.name || '—' }}</td>
              <td class="px-4 py-3 text-center">
                <span v-if="p.has_title" class="text-emerald-700 text-base">●</span>
                <span v-else class="text-amber-600 text-base" title="Missing">○</span>
              </td>
              <td class="px-4 py-3 text-center">
                <span v-if="p.has_meta" class="text-emerald-700 text-base">●</span>
                <span v-else class="text-amber-600 text-base" title="Missing">○</span>
              </td>
              <td class="px-4 py-3 text-right">
                <Link :href="`/admin/seo/products/${p.id}/edit`" class="text-xs font-medium bg-[color:var(--color-ink)] text-white rounded-lg px-3 py-1.5 hover:opacity-90">Edit SEO →</Link>
              </td>
            </tr>
            <tr v-if="!products.data.length">
              <td colspan="5" class="px-4 py-10 text-center text-[color:var(--color-ink-muted)]">No products match these filters.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="products.last_page > 1" class="flex gap-2 justify-center flex-wrap">
        <Link v-for="link in products.links" :key="link.label"
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

const props = defineProps({ products: Object, brands: Array, filters: Object })
const searchQ = ref(props.filters?.q || '')
const brandId = ref(props.filters?.brand_id || 0)
const missingOnly = ref(!!props.filters?.missing)
let searchTimeout = null

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(apply, 300)
}
function apply() {
  router.get('/admin/seo/products', {
    q: searchQ.value || undefined,
    brand_id: brandId.value || undefined,
    missing: missingOnly.value ? 1 : undefined,
  }, { preserveState: true, replace: true })
}
</script>
