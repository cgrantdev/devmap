<template>
  <AdminLayout>
    <PageHeader title="Calculator SEO" subtitle="Per-preset title + meta for /calculator/{slug} pages — 55+ compounds">
      <template #actions>
        <Link href="/admin/seo" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← SEO dashboard</Link>
      </template>
    </PageHeader>

    <div class="max-w-7xl mx-auto px-6 py-6 space-y-6">
      <!-- Filter + bulk import -->
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5 flex flex-wrap items-center gap-4">
        <input v-model="searchQ" @input="debouncedSearch" placeholder="Search compound or slug…" class="ui-input flex-1 min-w-[240px]" />
        <label class="inline-flex items-center gap-2 text-sm text-[color:var(--color-ink)]">
          <input type="checkbox" v-model="missingOnly" @change="apply" />
          Missing only ({{ missingCount }})
        </label>
        <form @submit.prevent="uploadCsv" enctype="multipart/form-data" class="flex items-center gap-2">
          <input type="file" @change="e => csvFile = e.target.files[0]" accept=".csv,text/csv" class="text-xs" />
          <button type="submit" :disabled="!csvFile || csvUploading" class="text-[12px] font-medium bg-[color:var(--color-ink)] text-white rounded-lg px-3 py-1.5 disabled:opacity-40">{{ csvUploading ? 'Importing…' : 'Import CSV' }}</button>
        </form>
      </div>
      <p class="text-[11px] text-[color:var(--color-ink-muted)] -mt-3">CSV format: header row with <code class="font-mono bg-[color:var(--color-bg-soft)] px-1">slug,title,description</code>. Rows with unknown slugs are skipped. Leave title and description blank to reset a preset to the compound-generated default.</p>

      <!-- Inline editor table -->
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-[color:var(--color-bg-soft)] text-[11px] uppercase tracking-wide text-[color:var(--color-ink-muted)]">
            <tr>
              <th class="text-left px-4 py-3">Preset</th>
              <th class="text-left px-4 py-3 w-[36%]">Title (max 200)</th>
              <th class="text-left px-4 py-3">Meta description (max 2000)</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[color:var(--color-hairline-soft)]">
            <tr v-for="r in rows" :key="r.slug">
              <td class="px-4 py-3 align-top">
                <div class="font-semibold text-[color:var(--color-ink)] text-[13px]">{{ r.name }}</div>
                <div class="text-[11px] font-mono text-[color:var(--color-ink-muted)]">/{{ r.slug }}</div>
                <div class="text-[10px] uppercase tracking-wide text-[color:var(--color-ink-subtle)] mt-1">{{ r.group }}</div>
              </td>
              <td class="px-4 py-3 align-top">
                <input
                  v-model="r.title"
                  @blur="save(r)"
                  maxlength="200"
                  :placeholder="`Default: ${r.name} Reconstitution Calculator…`"
                  :class="['ui-input text-[12px]', savingState[r.slug] === 'ok' && 'ring-1 ring-emerald-200', savingState[r.slug] === 'err' && 'ring-1 ring-red-300']"
                />
                <div class="text-[10px] font-mono mt-1" :class="r.title.length > 70 ? 'text-amber-600' : 'text-[color:var(--color-ink-subtle)]'">{{ r.title.length }} / 70 ideal</div>
              </td>
              <td class="px-4 py-3 align-top">
                <textarea
                  v-model="r.description"
                  @blur="save(r)"
                  rows="2"
                  maxlength="2000"
                  placeholder="Free calculator preloaded with the…"
                  :class="['ui-input text-[12px]', savingState[r.slug] === 'ok' && 'ring-1 ring-emerald-200', savingState[r.slug] === 'err' && 'ring-1 ring-red-300']"
                ></textarea>
                <div class="text-[10px] font-mono mt-1" :class="r.description.length > 160 ? 'text-amber-600' : 'text-[color:var(--color-ink-subtle)]'">{{ r.description.length }} / 160 ideal</div>
              </td>
              <td class="px-4 py-3 align-top text-right">
                <a :href="r.url" target="_blank" class="text-[11px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View ↗</a>
                <div v-if="savingState[r.slug]" class="text-[10px] mt-1" :class="savingState[r.slug] === 'ok' ? 'text-emerald-700' : savingState[r.slug] === 'err' ? 'text-red-600' : 'text-[color:var(--color-ink-muted)]'">
                  {{ savingState[r.slug] === 'saving' ? 'saving…' : savingState[r.slug] === 'ok' ? 'saved' : 'error' }}
                </div>
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="4" class="px-4 py-10 text-center text-[color:var(--color-ink-muted)]">No presets match these filters.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import AdminLayout from './Layout.vue'

const props = defineProps({ rows: Array, q: String, onlyMissing: Boolean })

const rows = ref((props.rows || []).map(r => ({ ...r })))
const searchQ = ref(props.q || '')
const missingOnly = ref(!!props.onlyMissing)
const savingState = ref({})
const csvFile = ref(null)
const csvUploading = ref(false)
let searchTimeout = null

const missingCount = computed(() => rows.value.filter(r => !r.title && !r.description).length)

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(apply, 300)
}
function apply() {
  router.get('/admin/seo/calculators', {
    q: searchQ.value || undefined,
    missing: missingOnly.value ? 1 : undefined,
  }, { preserveState: true, replace: true })
}

async function save(r) {
  savingState.value[r.slug] = 'saving'
  try {
    const res = await fetch('/admin/seo/calculators/save', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ slug: r.slug, title: r.title, description: r.description }),
    })
    savingState.value[r.slug] = res.ok ? 'ok' : 'err'
  } catch (e) {
    savingState.value[r.slug] = 'err'
  }
  setTimeout(() => { if (savingState.value[r.slug] === 'ok') delete savingState.value[r.slug] }, 1500)
}

function uploadCsv() {
  if (!csvFile.value) return
  csvUploading.value = true
  const data = new FormData()
  data.append('csv', csvFile.value)
  router.post('/admin/seo/calculators/import', data, {
    forceFormData: true,
    onFinish: () => { csvUploading.value = false; csvFile.value = null },
  })
}
</script>
