<template>
  <AdminLayout>
    <div class="p-8 max-w-6xl mx-auto">
      <h1 class="text-2xl font-semibold mb-1">SEO Dashboard</h1>
      <p class="text-sm text-[color:var(--color-ink-muted)] mb-6">
        Agency-facing. Overview + Playbook today; page editor and opportunities to follow.
      </p>

      <nav class="flex gap-1 mb-6 border-b border-[color:var(--color-hairline)]">
        <a href="/admin/seo"
          :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px',
            tab === 'overview'
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]']">
          Overview
        </a>
        <a href="/admin/seo/pages"
          :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px',
            tab === 'pages'
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]']">
          Pages
        </a>
        <a href="/admin/seo/playbook"
          :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px',
            tab === 'playbook'
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]']">
          Playbook
        </a>
        <a href="/admin/redirects"
          class="px-4 py-2 text-sm font-medium border-b-2 -mb-px border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">
          Redirects
        </a>
        <a href="/admin/content-scanner"
          class="px-4 py-2 text-sm font-medium border-b-2 -mb-px border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">
          Content scanner
        </a>
      </nav>

      <!-- OVERVIEW -->
      <div v-if="tab === 'overview'" class="space-y-8">
        <!-- Agency welcome / orientation — only shown to seo_agency role. -->
        <section v-if="is_agency && agency_welcome" class="rounded-lg border border-indigo-200 bg-indigo-50/40 p-5">
          <h2 class="text-base font-semibold text-indigo-900 mb-1">Welcome — start here</h2>
          <p class="text-[13px] text-indigo-900/80 mb-4">
            Your home base is this page. Below is where everything lives + how to reach us when you need something we haven't surfaced yet.
          </p>

          <h3 class="text-[12px] uppercase tracking-wider font-semibold text-indigo-700 mb-2">Where to find things</h3>
          <ul class="space-y-1.5 mb-5">
            <li v-for="link in agency_welcome.quick_links" :key="link.url" class="text-[13px]">
              <a :href="link.url" class="text-indigo-700 hover:underline font-semibold">{{ link.label }}</a>
              <span class="text-[color:var(--color-ink-muted)]"> — {{ link.why }}</span>
            </li>
          </ul>

          <h3 class="text-[12px] uppercase tracking-wider font-semibold text-indigo-700 mb-2">How to ask for anything</h3>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] mb-2">
            Direct contact: <a :href="`mailto:${agency_welcome.contact_email}`" class="text-indigo-700 hover:underline font-medium">{{ agency_welcome.contact_email }}</a>
          </p>
          <ul class="space-y-1 mb-1">
            <li v-for="(answer, question) in agency_welcome.how_to_ask" :key="question" class="text-[13px] text-[color:var(--color-ink-muted)]">
              <strong class="text-[color:var(--color-ink)]">{{ question }}:</strong> {{ answer }}
            </li>
          </ul>
        </section>

        <!-- Recovery banner -->
        <section v-if="recovery_context"
          class="rounded-lg border border-amber-200 bg-amber-50 p-5">
          <h2 class="text-sm font-semibold text-amber-900 mb-2">
            Recovery context — Oct 3 cliff
          </h2>
          <p class="text-[13px] text-amber-900 leading-relaxed mb-2">
            <strong>Cause:</strong> {{ recovery_context.drop_cause }}
          </p>
          <p class="text-[13px] text-amber-900 leading-relaxed mb-2">
            <strong>Action taken:</strong> {{ recovery_context.current_action }}
          </p>
          <p class="text-[13px] text-amber-900 leading-relaxed">
            <strong>Leading indicator:</strong> {{ recovery_context.leading_indicator }}
          </p>
        </section>

        <!-- GSC snapshot -->
        <section v-if="snapshot_28d && snapshot_28d.configured">
          <h2 class="text-lg font-semibold mb-3">Search performance</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <StatTile label="Clicks (28d)"
              :value="snapshot_28d.totals.clicks"
              :prev="snapshot_28d.totals_prev.clicks" />
            <StatTile label="Impressions (28d)"
              :value="snapshot_28d.totals.impressions"
              :prev="snapshot_28d.totals_prev.impressions" />
            <StatTile label="CTR (28d)"
              :value="(snapshot_28d.totals.ctr * 100).toFixed(2) + '%'" />
            <StatTile label="Avg Position (28d)"
              :value="snapshot_28d.totals.position"
              :prev="snapshot_28d.totals_prev.position"
              inverse />
          </div>

          <div class="grid md:grid-cols-2 gap-5 mt-5">
            <div class="border border-[color:var(--color-hairline)] rounded-lg p-4">
              <h3 class="text-sm font-semibold mb-2">Top queries (28d)</h3>
              <div class="space-y-1.5">
                <div v-for="row in (snapshot_28d.top_queries || []).slice(0, 10)" :key="row.query"
                  class="flex items-start justify-between gap-3 text-[13px]">
                  <span class="ui-mono text-[12px] truncate flex-1">{{ row.query }}</span>
                  <span class="text-[color:var(--color-ink-muted)] whitespace-nowrap">
                    {{ row.clicks }} clk · #{{ row.position?.toFixed(1) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="border border-[color:var(--color-hairline)] rounded-lg p-4">
              <h3 class="text-sm font-semibold mb-2">Opportunity queries (pos 8-20)</h3>
              <div class="space-y-1.5">
                <div v-for="row in (snapshot_28d.opportunities || []).slice(0, 10)" :key="row.query"
                  class="flex items-start justify-between gap-3 text-[13px]">
                  <span class="ui-mono text-[12px] truncate flex-1">{{ row.query }}</span>
                  <span class="text-[color:var(--color-ink-muted)] whitespace-nowrap">
                    {{ row.impressions }} imp · #{{ row.position?.toFixed(1) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="border border-[color:var(--color-hairline)] rounded-lg p-4 mt-5">
            <h3 class="text-sm font-semibold mb-2">Top pages (28d)</h3>
            <div class="space-y-1.5">
              <div v-for="row in (snapshot_28d.top_pages || []).slice(0, 10)" :key="row.page"
                class="flex items-start justify-between gap-3 text-[13px]">
                <span class="ui-mono text-[12px] truncate flex-1">{{ row.page.replace('https://peptidemap.com', '') }}</span>
                <span class="text-[color:var(--color-ink-muted)] whitespace-nowrap">
                  {{ row.clicks }} clk · {{ row.impressions }} imp · #{{ row.position?.toFixed(1) }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <section v-else class="rounded-lg border border-slate-200 bg-slate-50 p-5">
          <p class="text-sm text-slate-700">GSC isn't configured yet. Overview will populate automatically once connected.</p>
        </section>

        <!-- Index coverage -->
        <section v-if="index_coverage">
          <h2 class="text-lg font-semibold mb-3">Index coverage</h2>
          <p class="text-sm text-[color:var(--color-ink-muted)] mb-3">
            {{ index_coverage.sitemap_total.toLocaleString() }} URLs submitted via sitemap. Pattern-level counts below show what we're asking Google to rank.
          </p>
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[color:var(--color-hairline)] text-[12px] text-[color:var(--color-ink-muted)] uppercase tracking-wider">
                <th class="text-left py-2 font-medium">URL pattern</th>
                <th class="text-right py-2 font-medium">Count</th>
                <th class="text-right py-2 font-medium">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in index_coverage.by_pattern" :key="row.pattern"
                class="border-b border-[color:var(--color-hairline-soft)]">
                <td class="py-2.5 ui-mono text-[12px]">{{ row.pattern }}</td>
                <td class="py-2.5 text-right ui-mono">{{ row.count.toLocaleString() }}</td>
                <td class="py-2.5 text-right">
                  <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-emerald-100 text-emerald-700">
                    {{ row.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </section>
      </div>

      <!-- PAGES -->
      <div v-else-if="tab === 'pages'" class="space-y-6">
        <section>
          <div class="flex items-center justify-between mb-3">
            <div>
              <h2 class="text-lg font-semibold">SEO page overrides</h2>
              <p class="text-sm text-[color:var(--color-ink-muted)]">
                Per-URL title + description + OG. Admin override wins over the auto-generated default.
                Keyed by page slug ({{ "{" }}home{{ "}" }}, {{ "{" }}brands{{ "}" }}, etc).
              </p>
            </div>
            <button @click="startNew" class="h-9 px-4 text-[13px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded">
              + New override
            </button>
          </div>
          <div v-if="(seo_pages || []).length === 0" class="rounded-lg border border-dashed border-[color:var(--color-hairline)] p-8 text-center">
            <p class="text-sm text-[color:var(--color-ink-muted)]">No SEO overrides yet. Click "+ New override" to add one.</p>
          </div>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="border-b border-[color:var(--color-hairline)] text-[12px] text-[color:var(--color-ink-muted)] uppercase tracking-wider">
                <th class="text-left py-2 font-medium">Key</th>
                <th class="text-left py-2 font-medium">Title</th>
                <th class="text-left py-2 font-medium">Description</th>
                <th class="text-right py-2 font-medium">Updated</th>
                <th class="text-right py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in seo_pages" :key="row.id" class="border-b border-[color:var(--color-hairline-soft)]">
                <td class="py-3 ui-mono text-[12px]">{{ row.key }}</td>
                <td class="py-3 text-[13px] truncate max-w-[260px]">{{ row.title || '—' }}</td>
                <td class="py-3 text-[12px] text-[color:var(--color-ink-muted)] truncate max-w-[320px]">{{ row.description || '—' }}</td>
                <td class="py-3 text-right text-[11px] text-[color:var(--color-ink-subtle)]">{{ formatTs(row.updated_at) }}</td>
                <td class="py-3 text-right">
                  <button @click="startEdit(row)" class="text-[12px] text-indigo-600 hover:underline mr-3">Edit</button>
                  <button @click="deletePage(row)" class="text-[12px] text-red-600 hover:underline">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </section>

        <section v-if="suggested_keys">
          <h3 class="text-sm font-semibold mb-2">Suggested keys to override</h3>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="s in suggested_keys" :key="s.key"
              @click="startNewWith(s.key)"
              :disabled="s.exists"
              :class="['text-[12px] px-3 py-1 rounded-full border',
                s.exists
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200 cursor-default'
                  : 'bg-white text-[color:var(--color-ink)] border-[color:var(--color-hairline)] hover:border-indigo-400 hover:text-indigo-700']">
              {{ s.exists ? '✓ ' : '+ ' }}{{ s.key }}
              <span class="text-[10px] text-[color:var(--color-ink-muted)] ml-1">— {{ s.label }}</span>
            </button>
          </div>
        </section>

        <!-- Edit modal (inline) -->
        <div v-if="editing" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="editing = null">
          <div class="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">{{ editing.id ? 'Edit' : 'New' }} SEO override</h3>
            <div class="space-y-3">
              <label class="block">
                <span class="block text-[12px] font-semibold mb-1">Key</span>
                <input v-model="editing.key" type="text" :disabled="editing.id" class="w-full h-9 px-3 text-sm border border-[color:var(--color-hairline)] rounded ui-mono" placeholder="home" />
                <span class="text-[11px] text-[color:var(--color-ink-muted)]">Lowercase, no spaces. Matches the page slug.</span>
              </label>
              <label class="block">
                <span class="block text-[12px] font-semibold mb-1">Title <span class="text-[color:var(--color-ink-subtle)]">(max 70)</span></span>
                <input v-model="editing.title" type="text" maxlength="70" class="w-full h-9 px-3 text-sm border border-[color:var(--color-hairline)] rounded" />
                <span class="text-[11px] text-[color:var(--color-ink-muted)]">{{ (editing.title || '').length }}/70</span>
              </label>
              <label class="block">
                <span class="block text-[12px] font-semibold mb-1">Meta description <span class="text-[color:var(--color-ink-subtle)]">(max 200)</span></span>
                <textarea v-model="editing.description" maxlength="200" rows="3" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] rounded"></textarea>
                <span class="text-[11px] text-[color:var(--color-ink-muted)]">{{ (editing.description || '').length }}/200</span>
              </label>
              <label class="block">
                <span class="block text-[12px] font-semibold mb-1">OG title <span class="text-[color:var(--color-ink-subtle)]">(optional)</span></span>
                <input v-model="editing.og_title" type="text" maxlength="120" class="w-full h-9 px-3 text-sm border border-[color:var(--color-hairline)] rounded" />
              </label>
              <label class="block">
                <span class="block text-[12px] font-semibold mb-1">OG description <span class="text-[color:var(--color-ink-subtle)]">(optional)</span></span>
                <textarea v-model="editing.og_description" maxlength="300" rows="2" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] rounded"></textarea>
              </label>
              <label class="block">
                <span class="block text-[12px] font-semibold mb-1">OG image URL <span class="text-[color:var(--color-ink-subtle)]">(optional)</span></span>
                <input v-model="editing.og_image" type="url" class="w-full h-9 px-3 text-sm border border-[color:var(--color-hairline)] rounded" placeholder="https://..." />
              </label>
            </div>
            <div class="flex justify-end gap-2 mt-5">
              <button @click="editing = null" class="h-9 px-4 text-[13px] font-medium bg-slate-100 hover:bg-slate-200 rounded">Cancel</button>
              <button @click="savePage" :disabled="saving" class="h-9 px-4 text-[13px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- PLAYBOOK -->
      <div v-else-if="tab === 'playbook'" class="prose prose-sm max-w-none">
        <div v-html="rendered_playbook" class="seo-playbook" />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref, h } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

// StatTile component — colocated here to avoid Vite's rejection of
// `export const` inside <script setup>. Vue 3 auto-exposes top-level
// consts to the template.
const StatTile = {
  props: { label: String, value: [String, Number], prev: [String, Number, null], inverse: Boolean },
  setup(props) {
    return () => {
      const v = Number(props.value) || null
      const p = props.prev != null ? Number(props.prev) : null
      let delta = null, cls = 'text-slate-500'
      if (v != null && p != null && p > 0) {
        const pct = Math.round(((v - p) / p) * 100)
        delta = (pct >= 0 ? '+' : '') + pct + '%'
        const good = props.inverse ? pct < 0 : pct > 0
        cls = good ? 'text-emerald-600' : (pct === 0 ? 'text-slate-500' : 'text-red-600')
      }
      return h('div', { class: 'border border-[color:var(--color-hairline)] rounded-lg p-3' }, [
        h('div', { class: 'text-[11px] uppercase tracking-wider text-slate-500 mb-1' }, props.label),
        h('div', { class: 'text-xl font-semibold ui-mono' }, props.value),
        delta ? h('div', { class: 'text-[11px] mt-0.5 ' + cls }, delta + ' vs prev') : null,
      ])
    }
  }
}

const props = defineProps({
  tab: { type: String, required: true },
  snapshot_28d: { type: Object, default: null },
  snapshot_7d: { type: Object, default: null },
  index_coverage: { type: Object, default: null },
  recovery_context: { type: Object, default: null },
  playbook: { type: String, default: '' },
  seo_pages: { type: Array, default: () => [] },
  suggested_keys: { type: Array, default: () => [] },
  is_agency: { type: Boolean, default: false },
  agency_welcome: { type: Object, default: null },
})

// Pages tab state
const editing = ref(null)
const saving = ref(false)
function startNew() {
  editing.value = { id: null, key: '', title: '', description: '', og_title: '', og_description: '', og_image: '' }
}
function startNewWith(key) {
  editing.value = { id: null, key, title: '', description: '', og_title: '', og_description: '', og_image: '' }
}
function startEdit(row) { editing.value = { ...row } }
function savePage() {
  saving.value = true
  router.post('/admin/seo/pages', editing.value, {
    preserveScroll: true,
    onFinish: () => { saving.value = false; editing.value = null },
  })
}
function deletePage(row) {
  if (!confirm(`Remove SEO override for "${row.key}"? Default auto-generated meta takes over.`)) return
  router.delete(`/admin/seo/pages/${row.id}`, { preserveScroll: true })
}
function formatTs(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

// Minimal markdown -> HTML. Not using a library since the playbook
// is hand-authored by us; this covers the elements we actually use.
const rendered_playbook = computed(() => {
  let html = props.playbook
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
  // Code tables need to render BEFORE paragraphs so we handle them first
  const lines = html.split('\n')
  const out = []
  let inTable = false
  let tableRows = []
  let inList = false
  for (const raw of lines) {
    const line = raw
    if (/^\|.*\|$/.test(line)) {
      inTable = true
      tableRows.push(line)
      continue
    }
    if (inTable && !/^\|/.test(line)) {
      out.push(renderTable(tableRows))
      inTable = false
      tableRows = []
    }
    if (line.startsWith('# '))      out.push('<h1>' + inline(line.slice(2)) + '</h1>')
    else if (line.startsWith('## ')) out.push('<h2>' + inline(line.slice(3)) + '</h2>')
    else if (line.startsWith('### ')) out.push('<h3>' + inline(line.slice(4)) + '</h3>')
    else if (line.startsWith('> '))  out.push('<blockquote>' + inline(line.slice(2)) + '</blockquote>')
    else if (/^\d+\.\s/.test(line))  out.push('<li>' + inline(line.replace(/^\d+\.\s/, '')) + '</li>')
    else if (/^-\s/.test(line))       out.push('<li>' + inline(line.slice(2)) + '</li>')
    else if (line.trim() === '')      out.push('')
    else                              out.push('<p>' + inline(line) + '</p>')
  }
  if (inTable) out.push(renderTable(tableRows))

  // wrap consecutive <li> in <ul>
  let final = out.join('\n')
    .replace(/(<li>[^]+?<\/li>\n?)+/g, m => '<ul>' + m + '</ul>')
  return final
})

function inline(s) {
  return s
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/\*([^*]+)\*/g, '<em>$1</em>')
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>')
}

function renderTable(rows) {
  if (!rows.length) return ''
  const parse = r => r.replace(/^\|/, '').replace(/\|$/, '').split('|').map(c => c.trim())
  const header = parse(rows[0])
  const body = rows.slice(2).map(parse) // skip separator row
  const th = header.map(h => '<th>' + inline(h) + '</th>').join('')
  const tr = body.map(cells => '<tr>' + cells.map(c => '<td>' + inline(c) + '</td>').join('') + '</tr>').join('')
  return '<table><thead><tr>' + th + '</tr></thead><tbody>' + tr + '</tbody></table>'
}
</script>


<style scoped>
.seo-playbook :deep(h1) { font-size: 1.5rem; font-weight: 600; margin: 1.5rem 0 0.5rem; }
.seo-playbook :deep(h2) { font-size: 1.15rem; font-weight: 600; margin: 1.75rem 0 0.5rem; padding-bottom: 0.3rem; border-bottom: 1px solid var(--color-hairline); }
.seo-playbook :deep(h3) { font-size: 1rem; font-weight: 600; margin: 1.25rem 0 0.4rem; }
.seo-playbook :deep(p) { font-size: 13px; line-height: 1.6; margin: 0.5rem 0; color: var(--color-ink-muted); }
.seo-playbook :deep(ul) { padding-left: 1.25rem; margin: 0.5rem 0; }
.seo-playbook :deep(li) { font-size: 13px; line-height: 1.6; margin: 0.25rem 0; color: var(--color-ink-muted); }
.seo-playbook :deep(blockquote) { border-left: 3px solid var(--color-accent-500); padding-left: 1rem; color: var(--color-ink-muted); font-style: italic; margin: 0.75rem 0; }
.seo-playbook :deep(table) { width: 100%; border-collapse: collapse; margin: 0.75rem 0; font-size: 13px; }
.seo-playbook :deep(th), .seo-playbook :deep(td) { padding: 0.5rem 0.75rem; border-bottom: 1px solid var(--color-hairline-soft); text-align: left; vertical-align: top; }
.seo-playbook :deep(th) { font-weight: 600; background: #fafafa; }
.seo-playbook :deep(code) { background: #f1f1f2; padding: 0.1rem 0.3rem; border-radius: 3px; font-size: 12px; }
.seo-playbook :deep(a) { color: var(--color-accent-600); text-decoration: underline; }
.seo-playbook :deep(strong) { color: var(--color-ink); }
</style>
