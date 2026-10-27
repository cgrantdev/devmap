<template>
  <AdminLayout>
    <PageHeader title="Brand SEO" subtitle="Edit page titles, meta descriptions, and OG for every vendor's storefront">
      <template #actions>
        <Link href="/admin/seo" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← SEO dashboard</Link>
      </template>
    </PageHeader>

    <div class="max-w-5xl mx-auto px-6 py-6 space-y-6">
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5">
        <input v-model="searchQ" @input="debouncedSearch" placeholder="Search brands…" class="ui-input" />
      </div>

      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] divide-y divide-[color:var(--color-hairline-soft)]">
        <div v-for="b in brands.data" :key="b.id" class="p-4 flex items-center justify-between gap-4">
          <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-[color:var(--color-ink)]">{{ b.name }}</div>
            <div class="text-[11px] text-[color:var(--color-ink-muted)] font-mono">/vendor/{{ b.slug }}</div>
            <div v-if="b.vendor_setting?.seo_page_title" class="text-[12px] text-[color:var(--color-ink-muted)] mt-1 truncate">Title: {{ b.vendor_setting.seo_page_title }}</div>
            <div v-else class="text-[12px] text-amber-700 mt-1">⚠ No SEO title set</div>
          </div>
          <Link :href="`/admin/seo/brands/${b.id}/edit`" class="shrink-0 text-xs font-medium bg-[color:var(--color-ink)] text-white rounded-lg px-3 py-1.5 hover:opacity-90">Edit SEO →</Link>
        </div>
      </div>

      <div v-if="brands.last_page > 1" class="flex gap-2 justify-center">
        <Link v-for="link in brands.links" :key="link.label"
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

const props = defineProps({ brands: Object, q: String })
const searchQ = ref(props.q || '')
let searchTimeout = null
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get('/admin/seo/brands', { q: searchQ.value }, { preserveState: true, replace: true })
  }, 300)
}
</script>
