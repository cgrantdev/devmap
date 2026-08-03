<template>
  <Layout>
    <Head><title>CEO — Peptidemap</title></Head>
    <div class="max-w-[1400px] mx-auto px-6 py-8 space-y-8">

      <!-- Header -->
      <div class="flex items-end justify-between border-b border-gray-200 pb-5">
        <div>
          <div class="text-[10px] uppercase tracking-[0.14em] font-semibold text-gray-400 mb-1">Executive dashboard</div>
          <h1 class="ui-display text-3xl font-semibold tracking-[-0.02em] text-gray-900">Peptidemap CEO</h1>
        </div>
        <div class="text-[12px] text-gray-500">
          Restricted to <span class="ui-mono">info@peptidemap.com</span>
        </div>
      </div>

      <!-- Snapshot grid -->
      <section>
        <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500 mb-3">Snapshot</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
          <Metric label="Vendors" :main="snapshot.vendors_active" :sub="`${snapshot.vendors_total} total`" />
          <Metric label="Products" :main="snapshot.products_visible" :sub="`${snapshot.products_total} in db`" />
          <Metric label="Users" :main="snapshot.users" :sub="`${snapshot.users_verified} verified`" />
          <Metric label="Reviews" :main="snapshot.reviews_published" :sub="snapshot.reviews_pending ? `${snapshot.reviews_pending} pending` : 'all published'" :accent="snapshot.reviews_pending > 0 ? 'amber' : ''" />
          <Metric label="Wishlists" :main="snapshot.wishlists" sub="items tracked" />
          <Metric label="Discord" :main="snapshot.discord_members ?? '—'" sub="members" />
        </div>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Agent activity — spans 2 -->
        <section class="lg:col-span-2">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500">Agent activity</h2>
            <button @click="showLogAgentModal = true" class="text-[12px] font-medium text-indigo-600 hover:text-indigo-700">+ Log a run</button>
          </div>
          <div v-if="!agentRuns.length" class="text-[13px] text-gray-500 italic border border-dashed border-gray-200 rounded-lg p-6 text-center">
            No agent runs logged yet. Claude will log each `seo-strategist` / `seo-implementer` run here after completion.
          </div>
          <ul v-else class="space-y-2">
            <li v-for="r in agentRuns" :key="r.id" class="border border-gray-200 rounded-lg bg-white">
              <button @click="expanded[r.id] = !expanded[r.id]" class="w-full p-4 text-left flex items-start gap-3 hover:bg-gray-50 transition-colors">
                <span :class="['ui-mono text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded flex-shrink-0 mt-0.5', agentColor(r.agent_name)]">{{ r.agent_name }}</span>
                <div class="flex-1 min-w-0">
                  <div class="text-[14px] font-medium text-gray-900 leading-snug">{{ r.title }}</div>
                  <div class="text-[11px] text-gray-500 mt-0.5">
                    {{ r.created_at_h }}
                    <span v-if="r.commit_hashes.length" class="ml-2">· {{ r.commit_hashes.length }} commit{{ r.commit_hashes.length === 1 ? '' : 's' }}</span>
                    <span :class="['ml-2 px-1.5 rounded text-[10px] uppercase tracking-wide font-semibold', statusColor(r.status)]">{{ r.status }}</span>
                  </div>
                </div>
                <svg :class="['w-4 h-4 text-gray-400 transition-transform', expanded[r.id] && 'rotate-90']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              </button>
              <div v-if="expanded[r.id]" class="px-4 pb-4 border-t border-gray-100 pt-3 space-y-3">
                <div>
                  <div class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Summary</div>
                  <p class="text-[13px] text-gray-700 whitespace-pre-wrap leading-relaxed">{{ r.summary }}</p>
                </div>
                <div v-if="r.next_steps">
                  <div class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Next steps</div>
                  <p class="text-[13px] text-gray-700 whitespace-pre-wrap leading-relaxed">{{ r.next_steps }}</p>
                </div>
                <div v-if="r.commit_hashes.length">
                  <div class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Commits</div>
                  <div class="flex flex-wrap gap-1.5">
                    <a v-for="c in r.commit_hashes" :key="c" :href="`https://github.com/cgrantdev/devmap/commit/${c}`" target="_blank" rel="noopener" class="ui-mono text-[11px] px-2 py-0.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">{{ c.slice(0, 7) }}</a>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </section>

        <!-- Right column: recent commits -->
        <section>
          <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500 mb-3">Recent commits</h2>
          <ul class="space-y-1.5 text-[12px]">
            <li v-for="c in recentCommits" :key="c.sha" class="flex items-start gap-2">
              <a :href="`https://github.com/cgrantdev/devmap/commit/${c.sha}`" target="_blank" rel="noopener" class="ui-mono text-gray-400 hover:text-indigo-600 flex-shrink-0">{{ c.short }}</a>
              <div class="min-w-0 flex-1">
                <div class="text-gray-800 truncate" :title="c.subject">{{ c.subject }}</div>
                <div class="text-[10px] text-gray-400">{{ formatAgo(c.ts) }} · {{ c.author }}</div>
              </div>
            </li>
          </ul>
        </section>
      </div>

      <!-- Initiatives kanban -->
      <section>
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500">Initiatives</h2>
          <button @click="showAddInitiative = true" class="text-[12px] font-medium text-indigo-600 hover:text-indigo-700">+ Add initiative</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="col in kanbanCols" :key="col.status" class="bg-gray-50 rounded-lg p-3">
            <div class="text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-2 flex items-center justify-between">
              <span>{{ col.label }}</span>
              <span class="ui-mono text-gray-400">{{ initiativesByStatus(col.status).length }}</span>
            </div>
            <ul class="space-y-2">
              <li v-for="i in initiativesByStatus(col.status)" :key="i.id" class="bg-white rounded p-3 border border-gray-200">
                <div class="flex items-start gap-2">
                  <span v-if="i.pinned" class="text-amber-500 text-[11px] flex-shrink-0" title="Pinned">★</span>
                  <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-medium text-gray-900 leading-snug">{{ i.title }}</div>
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 mt-1 flex items-center gap-2 flex-wrap">
                      <span :class="categoryColor(i.category)" class="px-1.5 py-0.5 rounded font-semibold">{{ i.category }}</span>
                      <span v-if="i.owner" class="text-gray-500">{{ i.owner }}</span>
                    </div>
                    <p v-if="i.notes" class="text-[12px] text-gray-600 mt-2 whitespace-pre-wrap">{{ i.notes }}</p>
                  </div>
                </div>
                <div class="mt-2 pt-2 border-t border-gray-100 flex items-center gap-1.5 text-[11px]">
                  <button v-if="i.status !== 'planned'" @click="moveInitiative(i, 'planned')" class="text-gray-500 hover:text-gray-800">← planned</button>
                  <button v-if="i.status === 'planned'" @click="moveInitiative(i, 'in_progress')" class="text-indigo-600 hover:text-indigo-800">start →</button>
                  <button v-if="i.status === 'in_progress'" @click="moveInitiative(i, 'done')" class="text-emerald-600 hover:text-emerald-800">done →</button>
                  <button v-if="i.status === 'done'" @click="moveInitiative(i, 'in_progress')" class="text-gray-500 hover:text-gray-800">← reopen</button>
                  <button @click="destroyInitiative(i)" class="ml-auto text-red-500 hover:text-red-700" title="Delete">×</button>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </section>

      <!-- Notepad -->
      <section>
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500">Notepad</h2>
          <span class="text-[11px] text-gray-400">{{ notepadSaveStatus }}</span>
        </div>
        <textarea
          v-model="notepadDraft"
          @input="scheduleSaveNotepad"
          rows="10"
          placeholder="Reminders, thoughts, calls to make…"
          class="w-full text-[13px] font-mono text-gray-800 border border-gray-200 rounded-lg p-4 focus:outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400/20 resize-none"
        ></textarea>
      </section>

      <!-- Add initiative modal -->
      <div v-if="showAddInitiative" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="showAddInitiative = false">
        <div class="bg-white rounded-lg w-full max-w-md p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">New initiative</h3>
          <form @submit.prevent="submitInitiative" class="space-y-4">
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Title *</label>
              <input v-model="newInit.title" type="text" required class="w-full h-10 px-3 border border-gray-300 rounded focus:outline-none focus:border-indigo-400" placeholder="Bring in 10 new vendors this month" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Category *</label>
                <select v-model="newInit.category" class="w-full h-10 px-3 border border-gray-300 rounded">
                  <option>SEO</option><option>Community</option><option>Product</option><option>Growth</option><option>Ops</option><option>Content</option>
                </select>
              </div>
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Status</label>
                <select v-model="newInit.status" class="w-full h-10 px-3 border border-gray-300 rounded">
                  <option value="planned">planned</option><option value="in_progress">in_progress</option><option value="done">done</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Owner</label>
              <input v-model="newInit.owner" type="text" class="w-full h-10 px-3 border border-gray-300 rounded" placeholder="Colin, Claude, Peptiva…" />
            </div>
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Notes</label>
              <textarea v-model="newInit.notes" rows="3" class="w-full text-[13px] px-3 py-2 border border-gray-300 rounded"></textarea>
            </div>
            <label class="flex items-center gap-2 text-[13px] text-gray-700"><input v-model="newInit.pinned" type="checkbox" /> Pin to top</label>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showAddInitiative = false" class="h-10 px-4 text-[13px] text-gray-600 hover:text-gray-900">Cancel</button>
              <button type="submit" class="h-10 px-5 text-[13px] font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">Add</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Log agent-run modal -->
      <div v-if="showLogAgentModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="showLogAgentModal = false">
        <div class="bg-white rounded-lg w-full max-w-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Log agent run</h3>
          <form @submit.prevent="submitAgentRun" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Agent *</label>
                <select v-model="newRun.agent_name" class="w-full h-10 px-3 border border-gray-300 rounded">
                  <option>seo-strategist</option><option>seo-implementer</option><option>Explore</option><option>Plan</option><option>claude</option><option>general-purpose</option>
                </select>
              </div>
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Status</label>
                <select v-model="newRun.status" class="w-full h-10 px-3 border border-gray-300 rounded">
                  <option>completed</option><option>in_progress</option><option>blocked</option><option>rolled-back</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Title *</label>
              <input v-model="newRun.title" type="text" required class="w-full h-10 px-3 border border-gray-300 rounded" placeholder="Ex: audited /encyclopedia titles, removed hardcoded counts" />
            </div>
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Summary *</label>
              <textarea v-model="newRun.summary" required rows="5" class="w-full text-[13px] px-3 py-2 border border-gray-300 rounded"></textarea>
            </div>
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Next steps</label>
              <textarea v-model="newRun.next_steps" rows="3" class="w-full text-[13px] px-3 py-2 border border-gray-300 rounded"></textarea>
            </div>
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Commit hashes (space-separated)</label>
              <input v-model="commitHashesRaw" type="text" class="w-full h-10 px-3 border border-gray-300 rounded ui-mono" placeholder="abc1234 def5678" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showLogAgentModal = false" class="h-10 px-4 text-[13px] text-gray-600">Cancel</button>
              <button type="submit" class="h-10 px-5 text-[13px] font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">Log run</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import Layout from './Layout.vue'
import { h, defineComponent } from 'vue'

const props = defineProps({
  snapshot: Object,
  agentRuns: Array,
  initiatives: Array,
  notepad: String,
  recentCommits: Array,
})

const expanded = reactive({})
const showAddInitiative = ref(false)
const showLogAgentModal = ref(false)

const newInit = reactive({ title: '', category: 'SEO', status: 'planned', owner: '', notes: '', pinned: false })
const newRun = reactive({ agent_name: 'seo-strategist', status: 'completed', title: '', summary: '', next_steps: '' })
const commitHashesRaw = ref('')

const kanbanCols = [
  { status: 'planned', label: 'Planned' },
  { status: 'in_progress', label: 'In progress' },
  { status: 'done', label: 'Done' },
]

function initiativesByStatus(s) {
  return props.initiatives.filter(i => i.status === s)
}

function agentColor(name) {
  if (name?.includes('seo-strategist')) return 'bg-violet-100 text-violet-700'
  if (name?.includes('seo-implementer')) return 'bg-emerald-100 text-emerald-700'
  if (name?.includes('Explore')) return 'bg-blue-100 text-blue-700'
  if (name?.includes('Plan')) return 'bg-amber-100 text-amber-700'
  return 'bg-gray-100 text-gray-700'
}

function statusColor(s) {
  return {
    completed: 'bg-emerald-100 text-emerald-700',
    in_progress: 'bg-indigo-100 text-indigo-700',
    blocked: 'bg-red-100 text-red-700',
    'rolled-back': 'bg-gray-200 text-gray-700',
  }[s] || 'bg-gray-100 text-gray-700'
}

function categoryColor(c) {
  return {
    SEO: 'bg-violet-100 text-violet-700',
    Community: 'bg-pink-100 text-pink-700',
    Product: 'bg-blue-100 text-blue-700',
    Growth: 'bg-emerald-100 text-emerald-700',
    Ops: 'bg-gray-200 text-gray-700',
    Content: 'bg-amber-100 text-amber-700',
  }[c] || 'bg-gray-100 text-gray-700'
}

function formatAgo(ts) {
  const now = Date.now() / 1000
  const d = now - ts
  if (d < 60) return 'just now'
  if (d < 3600) return `${Math.floor(d / 60)}m ago`
  if (d < 86400) return `${Math.floor(d / 3600)}h ago`
  return `${Math.floor(d / 86400)}d ago`
}

function submitInitiative() {
  router.post('/admin/ceo/initiatives', newInit, {
    preserveScroll: true,
    onSuccess: () => {
      Object.assign(newInit, { title: '', category: 'SEO', status: 'planned', owner: '', notes: '', pinned: false })
      showAddInitiative.value = false
    },
  })
}

function submitAgentRun() {
  const commit_hashes = commitHashesRaw.value.trim().split(/\s+/).filter(Boolean)
  router.post('/admin/ceo/agent-runs', { ...newRun, commit_hashes }, {
    preserveScroll: true,
    onSuccess: () => {
      Object.assign(newRun, { agent_name: 'seo-strategist', status: 'completed', title: '', summary: '', next_steps: '' })
      commitHashesRaw.value = ''
      showLogAgentModal.value = false
    },
  })
}

function moveInitiative(i, status) {
  router.patch(`/admin/ceo/initiatives/${i.id}`, { status }, { preserveScroll: true })
}

function destroyInitiative(i) {
  if (!confirm(`Delete "${i.title}"?`)) return
  router.delete(`/admin/ceo/initiatives/${i.id}`, { preserveScroll: true })
}

// Notepad autosave — 1.5s debounce, single-flight guard
const notepadDraft = ref(props.notepad || '')
const notepadSaveStatus = ref('')
let saveTimer = null
function scheduleSaveNotepad() {
  notepadSaveStatus.value = 'unsaved…'
  clearTimeout(saveTimer)
  saveTimer = setTimeout(() => {
    notepadSaveStatus.value = 'saving…'
    router.post('/admin/ceo/notepad', { body: notepadDraft.value }, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => { notepadSaveStatus.value = 'saved' },
      onError: () => { notepadSaveStatus.value = 'save failed' },
    })
  }, 1500)
}

const Metric = defineComponent({
  props: { label: String, main: [String, Number], sub: String, accent: { type: String, default: '' } },
  setup(props) {
    return () => h('div', { class: 'bg-white border border-gray-200 rounded-lg p-4' }, [
      h('div', { class: 'text-[10px] uppercase tracking-[0.1em] font-semibold text-gray-500 mb-1.5' }, props.label),
      h('div', { class: ['ui-mono text-2xl font-bold leading-none', props.accent === 'amber' ? 'text-amber-600' : 'text-gray-900'].join(' ') }, String(props.main)),
      h('div', { class: 'text-[11px] text-gray-500 mt-1' }, props.sub),
    ])
  },
})
</script>
