<template>
  <div class="min-h-screen bg-[color:var(--color-canvas)]">
    <PageHeader title="SEO changelog" subtitle="Every SEO-relevant change, newest first">
      <template #actions>
        <Link href="/admin/seo" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← SEO dashboard</Link>
      </template>
    </PageHeader>

    <div class="max-w-4xl mx-auto px-6 py-6 space-y-6">
      <!-- Summary -->
      <div class="grid grid-cols-2 gap-4">
        <StatTile :n="summary.total" label="Total entries" />
        <StatTile :n="summary.last_30d" label="Last 30 days" />
      </div>

      <!-- Add-new form -->
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6">
        <h2 class="text-sm font-semibold text-[color:var(--color-ink)] mb-4">Log a change</h2>
        <form @submit.prevent="submit" class="grid grid-cols-12 gap-3">
          <input v-model="form.happened_at" type="date" required class="ui-input col-span-3" />
          <select v-model="form.category" class="ui-input col-span-2">
            <option value="">Category…</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
          <input v-model="form.actor" placeholder="Actor (Colin, Advantage Collective)" class="ui-input col-span-4" />
          <button type="submit" class="col-span-3 bg-[color:var(--color-ink)] text-white rounded-lg px-3 py-2 text-sm font-medium">Add entry</button>
          <input v-model="form.title" placeholder="Title — short, descriptive" required class="ui-input col-span-12" />
          <textarea v-model="form.summary" rows="2" placeholder="One or two sentences on what shipped and why (optional)" class="ui-input col-span-12"></textarea>
        </form>
      </div>

      <!-- Category filter -->
      <div class="flex flex-wrap gap-2 items-center text-[12px]">
        <span class="text-[color:var(--color-ink-muted)]">Filter:</span>
        <Link href="/admin/seo/changelog" :class="['px-2 py-1 rounded-full border', !category_filter ? 'bg-[color:var(--color-ink)] text-white border-transparent' : 'border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)]']">All</Link>
        <Link v-for="c in categories" :key="c" :href="`/admin/seo/changelog?category=${c}`" :class="['px-2 py-1 rounded-full border', category_filter === c ? 'bg-[color:var(--color-ink)] text-white border-transparent' : 'border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)]']">{{ c }}</Link>
      </div>

      <!-- Timeline -->
      <div class="space-y-3">
        <div v-for="change in changes.data" :key="change.id" class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5">
          <div class="flex justify-between items-start gap-4 mb-2">
            <div class="flex-1">
              <div class="flex items-center gap-2 flex-wrap mb-2">
                <span class="text-[11px] font-mono text-[color:var(--color-ink-muted)]">{{ formatDate(change.happened_at) }}</span>
                <span v-if="change.category" class="text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[color:var(--color-biotech-50)] text-[color:var(--color-biotech-700)]">{{ change.category }}</span>
                <span v-if="change.actor" class="text-[10px] text-[color:var(--color-ink-muted)]">by {{ change.actor }}</span>
              </div>
              <div class="text-sm font-semibold text-[color:var(--color-ink)]">{{ change.title }}</div>
              <p v-if="change.summary" class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed mt-2">{{ change.summary }}</p>
            </div>
            <button @click="destroy(change)" class="shrink-0 text-xs text-red-600 hover:underline">Remove</button>
          </div>
        </div>
        <div v-if="changes.data.length === 0" class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-10 text-center text-sm text-[color:var(--color-ink-muted)]">
          No entries yet. Add one above.
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="changes.last_page > 1" class="flex gap-2 justify-center">
        <Link v-for="link in changes.links" :key="link.label"
          v-html="link.label"
          :href="link.url || '#'"
          :class="['px-3 py-1.5 text-xs rounded border', link.active ? 'bg-[color:var(--color-ink)] text-white border-transparent' : 'border-[color:var(--color-hairline)]']"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'

const props = defineProps({
  changes: Object,
  categories: Array,
  category_filter: String,
  summary: Object,
})

const form = useForm({
  happened_at: new Date().toISOString().slice(0, 10),
  title: '',
  summary: '',
  category: '',
  actor: 'Colin',
})

function submit() {
  form.post('/admin/seo/changelog', {
    onSuccess: () => form.reset('title', 'summary'),
  })
}

function destroy(c) {
  if (!confirm(`Remove entry: ${c.title}?`)) return
  router.delete(`/admin/seo/changelog/${c.id}`, { preserveScroll: true })
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const StatTile = {
  props: ['n', 'label'],
  template: `
    <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5">
      <div class="text-2xl font-semibold text-[color:var(--color-ink)]">{{ n }}</div>
      <div class="text-[11px] text-[color:var(--color-ink-muted)] uppercase tracking-wider mt-1">{{ label }}</div>
    </div>
  `,
}
</script>
