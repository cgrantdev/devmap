<template>
  <Layout>
    <Head><title>CEO / SEO — Peptidemap</title></Head>
    <div class="max-w-[1400px] mx-auto px-6 py-8 space-y-8">

      <!-- Header -->
      <div class="flex items-end justify-between border-b border-gray-200 pb-5">
        <div>
          <div class="text-[10px] uppercase tracking-[0.14em] font-semibold text-gray-400 mb-1">Executive · SEO focus</div>
          <h1 class="ui-display text-3xl font-semibold tracking-[-0.02em] text-gray-900">Strategist · Implementer</h1>
        </div>
        <div class="text-right text-[11px] text-gray-500 leading-relaxed">
          <div>Last strategist run: <span class="font-medium text-gray-700">{{ lastStrategistRun || 'never' }}</span></div>
          <div>Last implementer run: <span class="font-medium text-gray-700">{{ lastImplementerRun || 'never' }}</span></div>
        </div>
      </div>

      <!-- SEO snapshot -->
      <section>
        <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500 mb-3">SEO snapshot</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <Metric label="Indexable URLs" :main="snapshot.indexable_urls" sub="approx sitemap size" />
          <Metric label="Vendors" :main="snapshot.vendors" sub="/brand/ pages" />
          <Metric label="Products" :main="snapshot.products" sub="/product/ pages" />
          <Metric label="Encyclopedia" :main="snapshot.encyclopedia" sub="/encyclopedia/ pages" />
          <Metric label="Blogs" :main="snapshot.blogs" :sub="snapshot.blogs_last_30d ? `+${snapshot.blogs_last_30d} in 30d` : 'nothing in 30d'" :accent="!snapshot.blogs_last_30d && snapshot.blogs > 0 ? 'amber' : ''" />
          <Metric label="Community" :main="snapshot.discord_members ?? '—'" sub="Discord members" />
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
          <Metric label="Open recs" :main="snapshot.recs_open" sub="from strategist" :accent="snapshot.recs_open > 10 ? 'amber' : ''" />
          <Metric label="In progress" :main="snapshot.recs_in_progress" sub="implementer working" :accent="snapshot.recs_in_progress > 0 ? 'indigo' : ''" />
          <Metric label="Shipped (7d)" :main="snapshot.recs_shipped_7d" sub="last week" :accent="snapshot.recs_shipped_7d > 0 ? 'emerald' : ''" />
          <Metric label="Shipped (30d)" :main="snapshot.recs_shipped_30d" sub="rolling month" />
        </div>
      </section>

      <!-- Progress bar — total flow at a glance -->
      <section>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex items-center justify-between text-[11px] text-gray-600 mb-2">
            <div class="font-semibold uppercase tracking-wide text-gray-500">Strategist queue</div>
            <div class="text-gray-500">
              <span class="ui-mono font-semibold text-gray-900">{{ totalRecs }}</span> total ·
              <span class="ui-mono text-emerald-600">{{ shippedRecs.length }}</span> shipped ·
              <span class="ui-mono text-indigo-600">{{ inProgressRecs.length + inProgressAgents }}</span> in progress ·
              <span class="ui-mono text-gray-700">{{ openRecs.length + agentRequests.length }}</span> open
            </div>
          </div>
          <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden flex">
            <div class="bg-emerald-500 transition-all" :style="{ width: pct(shippedRecs.length) + '%' }" :title="`${shippedRecs.length} shipped`"></div>
            <div class="bg-indigo-500 transition-all" :style="{ width: pct(inProgressRecs.length + inProgressAgents) + '%' }" :title="`${inProgressRecs.length + inProgressAgents} in progress`"></div>
            <div class="bg-gray-300 transition-all" :style="{ width: pct(openRecs.length + agentRequests.length) + '%' }" :title="`${openRecs.length + agentRequests.length} open`"></div>
          </div>
          <div class="text-[11px] text-gray-500 mt-2">
            <span class="ui-mono font-semibold text-emerald-600">{{ percentShipped }}%</span> shipped
            <span v-if="shippedRecs.length" class="text-gray-400"> · velocity: {{ snapshot.recs_shipped_7d }} shipped in last 7 days</span>
          </div>
        </div>
      </section>

      <!-- Strategist queue + shipped log side by side -->
      <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- LEFT: current suggestions -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500">
              What the strategist is suggesting <span class="ui-mono text-gray-400">({{ openRecs.length }})</span>
            </h2>
            <button @click="openNewRec()" class="text-[12px] font-medium text-indigo-600 hover:text-indigo-700">+ Add rec</button>
          </div>
          <div v-if="!openRecs.length && !inProgressRecs.length" class="text-[13px] text-gray-500 italic border border-dashed border-gray-200 rounded-lg p-6 text-center">
            No open recommendations. Ask Claude to <span class="ui-mono">run the seo-strategist</span> and the queue will fill up.
          </div>
          <div v-else class="space-y-4">
            <!-- In progress first -->
            <div v-if="inProgressRecs.length">
              <div class="text-[10px] uppercase tracking-wide font-semibold text-indigo-600 mb-1.5">In progress · {{ inProgressRecs.length }}</div>
              <RecCard v-for="r in inProgressRecs" :key="r.id" :rec="r" @update="updateRec" @destroy="destroyRec" @edit="editRec" @implement="implementRec" />
            </div>
            <div v-if="openRecs.length">
              <div class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 mb-1.5">Open · {{ openRecs.length }}</div>
              <RecCard v-for="r in openRecs" :key="r.id" :rec="r" @update="updateRec" @destroy="destroyRec" @edit="editRec" @implement="implementRec" />
            </div>
          </div>
        </div>

        <!-- RIGHT: recently shipped -->
        <div>
          <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500 mb-3">
            Recently shipped <span class="ui-mono text-gray-400">({{ shippedRecs.length }})</span>
          </h2>
          <div v-if="!shippedRecs.length" class="text-[13px] text-gray-500 italic border border-dashed border-gray-200 rounded-lg p-6 text-center">
            Nothing shipped yet. Recs move here when the implementer completes them.
          </div>
          <div v-else class="space-y-2">
            <div v-for="r in shippedRecs" :key="r.id" class="bg-white border border-gray-200 rounded p-3">
              <div class="flex items-start gap-2">
                <span class="text-emerald-600 text-[14px] flex-shrink-0 mt-0.5">✓</span>
                <div class="min-w-0 flex-1">
                  <div class="text-[13px] font-medium text-gray-900 leading-snug">{{ r.title }}</div>
                  <div class="text-[10px] uppercase tracking-wide text-gray-500 mt-1 flex items-center gap-2 flex-wrap">
                    <span :class="categoryColor(r.category)" class="px-1.5 py-0.5 rounded font-semibold">{{ r.category }}</span>
                    <span class="text-gray-500">shipped {{ r.shipped_at_h }}</span>
                    <span v-if="r.shipped_by" class="text-gray-500">by {{ r.shipped_by }}</span>
                  </div>
                  <div v-if="r.commit_hashes.length" class="flex flex-wrap gap-1 mt-1.5">
                    <a v-for="c in r.commit_hashes" :key="c" :href="`https://github.com/cgrantdev/devmap/commit/${c}`" target="_blank" rel="noopener" class="ui-mono text-[10px] px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">{{ c.slice(0, 7) }}</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Agent build requests -->
      <section v-if="agentRequests.length">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500">
            🤖 New agents the strategist wants built <span class="ui-mono text-gray-400">({{ agentRequests.length }})</span>
          </h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="r in agentRequests" :key="r.id" :class="['border rounded-lg p-4 border-l-4', r.status === 'in_progress' ? 'bg-indigo-50 border-indigo-200 border-l-indigo-500' : 'bg-violet-50 border-violet-200 border-l-violet-500']">
            <div class="flex items-start justify-between gap-2 mb-1.5">
              <div class="text-[13px] font-semibold text-violet-900 flex-1 min-w-0">{{ r.title }}</div>
              <span :class="['flex-shrink-0 text-[9px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded', r.status === 'in_progress' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-200 text-gray-700']">
                {{ r.status === 'in_progress' ? 'BUILDING' : 'REQUESTED' }}
              </span>
            </div>
            <p v-if="r.rationale" class="text-[12px] text-violet-800 mt-1 leading-relaxed whitespace-pre-wrap">{{ r.rationale }}</p>
            <p v-if="r.expected_impact" class="text-[11px] text-violet-700 italic mt-2">Expected: {{ r.expected_impact }}</p>
            <div class="flex items-center gap-2 mt-3 pt-2 border-t border-violet-200 text-[11px]">
              <button v-if="r.status === 'open'" @click="updateRec(r, { status: 'in_progress' })" class="text-indigo-600 hover:text-indigo-800 font-semibold">▶ Start building</button>
              <button v-if="r.status === 'in_progress'" @click="updateRec(r, { status: 'shipped' })" class="inline-flex items-center gap-1 text-white bg-emerald-600 hover:bg-emerald-700 px-2 py-1 rounded font-semibold">✓ Mark built</button>
              <button v-if="r.status === 'in_progress'" @click="updateRec(r, { status: 'open' })" class="text-gray-500 hover:text-gray-800">← Back</button>
              <button @click="updateRec(r, { status: 'rejected' })" class="text-gray-500 hover:text-gray-800 ml-auto">Reject</button>
            </div>
          </div>
        </div>
      </section>

      <!-- Agent activity + recent commits -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500">Agent activity log</h2>
            <button @click="showLogAgentModal = true" class="text-[12px] font-medium text-indigo-600 hover:text-indigo-700">+ Log run</button>
          </div>
          <div v-if="!agentRuns.length" class="text-[13px] text-gray-500 italic border border-dashed border-gray-200 rounded-lg p-6 text-center">
            Runs of seo-strategist, seo-implementer, Plan, and Explore land here.
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
                  </div>
                </div>
                <svg :class="['w-4 h-4 text-gray-400 transition-transform', expanded[r.id] && 'rotate-90']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              </button>
              <div v-if="expanded[r.id]" class="px-4 pb-4 border-t border-gray-100 pt-3 space-y-3">
                <div><div class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Summary</div><p class="text-[13px] text-gray-700 whitespace-pre-wrap leading-relaxed">{{ r.summary }}</p></div>
                <div v-if="r.next_steps"><div class="text-[10px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Next steps</div><p class="text-[13px] text-gray-700 whitespace-pre-wrap leading-relaxed">{{ r.next_steps }}</p></div>
                <div v-if="r.commit_hashes.length" class="flex flex-wrap gap-1.5">
                  <a v-for="c in r.commit_hashes" :key="c" :href="`https://github.com/cgrantdev/devmap/commit/${c}`" target="_blank" rel="noopener" class="ui-mono text-[11px] px-2 py-0.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">{{ c.slice(0, 7) }}</a>
                </div>
              </div>
            </li>
          </ul>
        </section>

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

      <!-- Notepad -->
      <section>
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-[11px] uppercase tracking-[0.12em] font-semibold text-gray-500">Notepad</h2>
          <span class="text-[11px] text-gray-400">{{ notepadSaveStatus }}</span>
        </div>
        <textarea v-model="notepadDraft" @input="scheduleSaveNotepad" rows="8" placeholder="Reminders, calls to make, thoughts…" class="w-full text-[13px] font-mono text-gray-800 border border-gray-200 rounded-lg p-4 focus:outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400/20 resize-none"></textarea>
      </section>

      <!-- Recommendation modal (create + edit) -->
      <div v-if="showRecModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="showRecModal = false">
        <div class="bg-white rounded-lg w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ recDraft.id ? 'Edit' : 'Add' }} recommendation</h3>
          <form @submit.prevent="submitRec" class="space-y-3">
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Title *</label>
              <input v-model="recDraft.title" type="text" required maxlength="220" class="w-full h-10 px-3 border border-gray-300 rounded" placeholder="Add FAQPage schema to product pages" />
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Category *</label>
                <select v-model="recDraft.category" class="w-full h-10 px-2 border border-gray-300 rounded text-[13px]">
                  <option>technical</option><option>content</option><option>on-page</option><option>structured-data</option><option>link-building</option><option>internal-linking</option><option>performance</option><option>new-agent</option><option>other</option>
                </select>
              </div>
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Impact</label>
                <select v-model="recDraft.impact" class="w-full h-10 px-2 border border-gray-300 rounded text-[13px]"><option>high</option><option>medium</option><option>low</option></select>
              </div>
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Effort</label>
                <select v-model="recDraft.effort" class="w-full h-10 px-2 border border-gray-300 rounded text-[13px]"><option>small</option><option>medium</option><option>large</option></select>
              </div>
            </div>
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Rationale (why)</label>
              <textarea v-model="recDraft.rationale" rows="3" class="w-full text-[13px] px-3 py-2 border border-gray-300 rounded"></textarea>
            </div>
            <div>
              <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Expected impact</label>
              <textarea v-model="recDraft.expected_impact" rows="2" class="w-full text-[13px] px-3 py-2 border border-gray-300 rounded" placeholder="+X% CTR on SERP, +N indexed URLs, etc."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Status</label>
                <select v-model="recDraft.status" class="w-full h-10 px-2 border border-gray-300 rounded text-[13px]"><option>open</option><option>in_progress</option><option>shipped</option><option>rejected</option><option>deferred</option></select>
              </div>
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Source</label>
                <input v-model="recDraft.source" type="text" class="w-full h-10 px-3 border border-gray-300 rounded text-[13px]" placeholder="seo-strategist" />
              </div>
            </div>
            <label class="flex items-center gap-2 text-[13px] text-gray-700"><input v-model="recDraft.pinned" type="checkbox" /> Pin to top</label>
            <div class="flex justify-between pt-2">
              <button v-if="recDraft.id" type="button" @click="destroyRecFromModal()" class="text-[13px] text-red-600 hover:text-red-800">Delete</button>
              <div class="ml-auto flex gap-2">
                <button type="button" @click="showRecModal = false" class="h-10 px-4 text-[13px] text-gray-600">Cancel</button>
                <button type="submit" class="h-10 px-5 text-[13px] font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">{{ recDraft.id ? 'Save' : 'Add' }}</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Implementer prompt modal -->
      <div v-if="showImplementModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="showImplementModal = false">
        <div class="bg-white rounded-lg w-full max-w-2xl p-6 max-h-[90vh] flex flex-col">
          <div class="flex items-start justify-between mb-3">
            <div>
              <div class="text-[10px] uppercase tracking-[0.12em] font-semibold text-gray-400 mb-1">Implement via Claude</div>
              <h3 class="text-lg font-semibold text-gray-900 leading-tight">{{ implementDraft.rec?.title }}</h3>
            </div>
            <button @click="showImplementModal = false" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
          </div>
          <p class="text-[12px] text-gray-600 mb-3 leading-relaxed">
            Copy this prompt and paste it into a fresh Claude Code session. Claude will branch, edit, commit, and open a PR against <span class="ui-mono">main</span> — you review and merge.
          </p>
          <div class="flex-1 overflow-y-auto border border-gray-200 rounded bg-gray-50 p-3 mb-3">
            <pre class="text-[11px] font-mono text-gray-800 whitespace-pre-wrap break-words">{{ implementDraft.prompt }}</pre>
          </div>
          <div class="flex items-center justify-between gap-2">
            <button @click="copyPrompt" :class="['inline-flex items-center gap-2 h-10 px-4 text-[13px] font-semibold rounded transition-colors', promptCopied ? 'bg-emerald-600 text-white' : 'bg-gray-900 text-white hover:bg-gray-800']">
              <span v-if="promptCopied">✓ Copied to clipboard</span>
              <span v-else>📋 Copy prompt</span>
            </button>
            <button @click="markInProgressFromModal" class="text-[13px] font-medium text-indigo-600 hover:text-indigo-800">
              Mark rec as in progress →
            </button>
          </div>
        </div>
      </div>

      <!-- Agent-run log modal -->
      <div v-if="showLogAgentModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="showLogAgentModal = false">
        <div class="bg-white rounded-lg w-full max-w-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Log agent run</h3>
          <form @submit.prevent="submitAgentRun" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Agent *</label>
                <select v-model="newRun.agent_name" class="w-full h-10 px-3 border border-gray-300 rounded"><option>seo-strategist</option><option>seo-implementer</option><option>claude</option><option>Explore</option><option>Plan</option></select>
              </div>
              <div>
                <label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Status</label>
                <select v-model="newRun.status" class="w-full h-10 px-3 border border-gray-300 rounded"><option>completed</option><option>in_progress</option><option>blocked</option></select>
              </div>
            </div>
            <div><label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Title *</label><input v-model="newRun.title" type="text" required class="w-full h-10 px-3 border border-gray-300 rounded" /></div>
            <div><label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Summary *</label><textarea v-model="newRun.summary" required rows="5" class="w-full text-[13px] px-3 py-2 border border-gray-300 rounded"></textarea></div>
            <div><label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Next steps</label><textarea v-model="newRun.next_steps" rows="3" class="w-full text-[13px] px-3 py-2 border border-gray-300 rounded"></textarea></div>
            <div><label class="block text-[11px] uppercase tracking-wide font-semibold text-gray-500 mb-1">Commit hashes (space-separated)</label><input v-model="commitHashesRaw" type="text" class="w-full h-10 px-3 border border-gray-300 rounded ui-mono" /></div>
            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="showLogAgentModal = false" class="h-10 px-4 text-[13px] text-gray-600">Cancel</button><button type="submit" class="h-10 px-5 text-[13px] font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">Log run</button></div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, h, defineComponent, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import Layout from './Layout.vue'

const props = defineProps({
  snapshot: Object,
  openRecs: Array,
  inProgressRecs: Array,
  shippedRecs: Array,
  agentRequests: Array,
  agentRuns: Array,
  notepad: String,
  recentCommits: Array,
  lastStrategistRun: String,
  lastImplementerRun: String,
})

// Aggregate counters used by the progress bar.
// inProgressAgents comes from the API's separate agent-requests set — we need
// their in_progress subset for the total but the endpoint only returns open.
// Approximated as 0 for now (agents that were 'started' but not yet built
// live in openRecs when the DB flips them out of new-agent category, or in
// inProgressRecs after status flip). Kept as a helper for future use.
const inProgressAgents = computed(() => 0)
const totalRecs = computed(() =>
  props.openRecs.length + props.inProgressRecs.length + props.shippedRecs.length + props.agentRequests.length
)
const percentShipped = computed(() =>
  totalRecs.value === 0 ? 0 : Math.round((props.shippedRecs.length / totalRecs.value) * 100)
)
function pct(n) {
  return totalRecs.value === 0 ? 0 : (n / totalRecs.value) * 100
}

const expanded = reactive({})
const showRecModal = ref(false)
const showLogAgentModal = ref(false)
const recDraft = reactive({ id: null, title: '', category: 'technical', impact: 'medium', effort: 'medium', status: 'open', rationale: '', expected_impact: '', source: 'seo-strategist', pinned: false })
const newRun = reactive({ agent_name: 'seo-strategist', status: 'completed', title: '', summary: '', next_steps: '' })
const commitHashesRaw = ref('')

function openNewRec() {
  Object.assign(recDraft, { id: null, title: '', category: 'technical', impact: 'medium', effort: 'medium', status: 'open', rationale: '', expected_impact: '', source: 'seo-strategist', pinned: false })
  showRecModal.value = true
}
function editRec(r) {
  Object.assign(recDraft, {
    id: r.id, title: r.title, category: r.category, impact: r.impact, effort: r.effort,
    status: r.status, rationale: r.rationale || '', expected_impact: r.expected_impact || '',
    source: r.source, pinned: r.pinned,
  })
  showRecModal.value = true
}
function submitRec() {
  const url = recDraft.id ? `/admin/ceo/recommendations/${recDraft.id}` : '/admin/ceo/recommendations'
  const method = recDraft.id ? 'patch' : 'post'
  router[method](url, { ...recDraft }, { preserveScroll: true, onSuccess: () => { showRecModal.value = false } })
}
function updateRec(r, patch) {
  router.patch(`/admin/ceo/recommendations/${r.id}`, patch, { preserveScroll: true })
}

const showImplementModal = ref(false)
const implementDraft = ref({ rec: null, prompt: '' })
const promptCopied = ref(false)

function buildPrompt(r) {
  const branch = `implementer/rec-${r.id}-${r.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '').slice(0, 60)}`
  const lines = [
    `# SEO Recommendation to implement`,
    ``,
    `**Title:** ${r.title}`,
    `**Category:** ${r.category}    **Impact:** ${r.impact}    **Effort:** ${r.effort}`,
    `**Rec ID:** ${r.id}`,
    ``,
    `## Why (strategist's rationale)`,
    r.rationale || '_(no rationale provided)_',
    ``,
    `## Expected impact`,
    r.expected_impact || '_(no expected impact given)_',
    ``,
    `## How to work this`,
    ``,
    `1. Create a branch: \`git checkout -b ${branch}\``,
    `2. Implement the change end-to-end (edit files, run any needed migrations, verify).`,
    `3. Keep the diff scoped to this one recommendation — no drive-by cleanup.`,
    `4. Commit with a message referencing rec #${r.id}.`,
    `5. Push the branch and open a PR against \`main\` titled: \`SEO: ${r.title}\``,
    `6. PR body should include: the strategist's rationale (above), expected impact, and what you changed.`,
    `7. Once the PR is open, come back to the CEO dashboard (/admin/ceo) and mark rec #${r.id} as shipped, adding the PR URL and commit SHA(s).`,
    ``,
    `## Peptidemap context (relevant paths)`,
    ``,
    `- Frontend controllers: \`app/Http/Controllers/Frontend/\``,
    `- Blade template with sitewide meta/JSON-LD: \`resources/views/app.blade.php\``,
    `- Vue pages: \`resources/js/Pages/Frontend/\``,
    `- Sitemap: \`app/Http/Controllers/Frontend/SitemapController.php\``,
    `- Robots: served via Cloudflare (be aware of the CF override on /robots.txt)`,
    `- Deploy: git push → Forge auto-pulls → \`npm run build\` on server if frontend changed`,
    ``,
    `## Skip if`,
    ``,
    `- The change requires infrastructure the user needs to touch (Cloudflare, DNS, third-party dashboards) — stop and describe what's needed instead of half-doing it.`,
    `- The rec description is too vague to implement without decisions — ask a clarifying question first.`,
  ]
  return lines.join('\n')
}

function implementRec(r) {
  implementDraft.value = { rec: r, prompt: buildPrompt(r) }
  promptCopied.value = false
  showImplementModal.value = true
}

async function copyPrompt() {
  try {
    await navigator.clipboard.writeText(implementDraft.value.prompt)
    promptCopied.value = true
    setTimeout(() => { promptCopied.value = false }, 2500)
  } catch {
    // Fallback for non-secure contexts
    const ta = document.createElement('textarea')
    ta.value = implementDraft.value.prompt
    ta.style.cssText = 'position:fixed;left:-999px;top:-999px'
    document.body.appendChild(ta); ta.select()
    try { document.execCommand('copy'); promptCopied.value = true; setTimeout(() => { promptCopied.value = false }, 2500) } catch {}
    document.body.removeChild(ta)
  }
}

function markInProgressFromModal() {
  const r = implementDraft.value.rec
  if (!r) return
  router.patch(`/admin/ceo/recommendations/${r.id}`, { status: 'in_progress' }, {
    preserveScroll: true,
    onSuccess: () => { showImplementModal.value = false },
  })
}
function destroyRec(r) {
  if (!confirm(`Delete "${r.title}"?`)) return
  router.delete(`/admin/ceo/recommendations/${r.id}`, { preserveScroll: true })
}
function destroyRecFromModal() {
  if (!recDraft.id || !confirm(`Delete "${recDraft.title}"?`)) return
  router.delete(`/admin/ceo/recommendations/${recDraft.id}`, { preserveScroll: true, onSuccess: () => { showRecModal.value = false } })
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

// Notepad autosave
const notepadDraft = ref(props.notepad || '')
const notepadSaveStatus = ref('')
let saveTimer = null
function scheduleSaveNotepad() {
  notepadSaveStatus.value = 'unsaved…'
  clearTimeout(saveTimer)
  saveTimer = setTimeout(() => {
    notepadSaveStatus.value = 'saving…'
    router.post('/admin/ceo/notepad', { body: notepadDraft.value }, {
      preserveScroll: true, preserveState: true,
      onSuccess: () => { notepadSaveStatus.value = 'saved' },
      onError: () => { notepadSaveStatus.value = 'save failed' },
    })
  }, 1500)
}

/* -------- helpers -------- */
function agentColor(name) {
  if (name === 'seo-strategist') return 'bg-violet-100 text-violet-700'
  if (name === 'seo-implementer') return 'bg-emerald-100 text-emerald-700'
  if (name === 'Explore') return 'bg-blue-100 text-blue-700'
  if (name === 'Plan') return 'bg-amber-100 text-amber-700'
  return 'bg-gray-100 text-gray-700'
}
function categoryColor(c) {
  return {
    technical: 'bg-slate-100 text-slate-700',
    content: 'bg-amber-100 text-amber-700',
    'on-page': 'bg-sky-100 text-sky-700',
    'structured-data': 'bg-violet-100 text-violet-700',
    'link-building': 'bg-pink-100 text-pink-700',
    'internal-linking': 'bg-cyan-100 text-cyan-700',
    performance: 'bg-orange-100 text-orange-700',
    'new-agent': 'bg-violet-100 text-violet-700',
    other: 'bg-gray-100 text-gray-700',
  }[c] || 'bg-gray-100 text-gray-700'
}
function formatAgo(ts) {
  const d = Date.now() / 1000 - ts
  if (d < 60) return 'just now'
  if (d < 3600) return `${Math.floor(d / 60)}m ago`
  if (d < 86400) return `${Math.floor(d / 3600)}h ago`
  return `${Math.floor(d / 86400)}d ago`
}

/* -------- child components -------- */
const RecCard = defineComponent({
  props: { rec: Object },
  emits: ['update', 'destroy', 'edit', 'implement'],
  setup(props, { emit }) {
    const impactBadge = { high: 'bg-red-100 text-red-700', medium: 'bg-amber-100 text-amber-700', low: 'bg-gray-100 text-gray-700' }
    const statusPill = {
      open:        { label: 'OPEN',        cls: 'bg-gray-200 text-gray-700' },
      in_progress: { label: 'IN PROGRESS', cls: 'bg-indigo-100 text-indigo-700' },
      shipped:     { label: 'SHIPPED',     cls: 'bg-emerald-100 text-emerald-700' },
      deferred:    { label: 'DEFERRED',    cls: 'bg-amber-100 text-amber-700' },
      rejected:    { label: 'REJECTED',    cls: 'bg-red-100 text-red-700' },
    }
    const borderColor = {
      open: 'border-l-gray-300',
      in_progress: 'border-l-indigo-500',
      shipped: 'border-l-emerald-500',
      deferred: 'border-l-amber-400',
      rejected: 'border-l-red-400',
    }[props.rec.status] || 'border-l-gray-300'

    return () => h('div', { class: `bg-white border border-gray-200 border-l-4 ${borderColor} rounded p-3 mb-2` }, [
      h('div', { class: 'flex items-start gap-2' }, [
        props.rec.pinned ? h('span', { class: 'text-amber-500 text-[11px] mt-0.5', title: 'Pinned' }, '★') : null,
        h('div', { class: 'flex-1 min-w-0' }, [
          h('div', { class: 'flex items-start justify-between gap-2 mb-1.5' }, [
            h('button', { onClick: () => emit('edit', props.rec), class: 'text-[13px] font-medium text-gray-900 leading-snug text-left hover:text-indigo-600 flex-1 min-w-0' }, props.rec.title),
            h('span', { class: `flex-shrink-0 text-[9px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded ${statusPill[props.rec.status]?.cls || 'bg-gray-100 text-gray-700'}` }, statusPill[props.rec.status]?.label || props.rec.status),
          ]),
          h('div', { class: 'text-[10px] uppercase tracking-wide flex items-center gap-1.5 flex-wrap' }, [
            h('span', { class: `${categoryColor(props.rec.category)} px-1.5 py-0.5 rounded font-semibold` }, props.rec.category),
            h('span', { class: `${impactBadge[props.rec.impact]} px-1.5 py-0.5 rounded font-semibold` }, `impact:${props.rec.impact}`),
            h('span', { class: 'text-gray-500' }, `effort:${props.rec.effort}`),
          ]),
          props.rec.rationale ? h('p', { class: 'text-[12px] text-gray-600 mt-2 whitespace-pre-wrap leading-snug' }, props.rec.rationale) : null,
          props.rec.expected_impact ? h('p', { class: 'text-[11px] text-emerald-700 italic mt-1' }, `→ ${props.rec.expected_impact}`) : null,
        ]),
      ]),
      h('div', { class: 'mt-2 pt-2 border-t border-gray-100 flex items-center gap-2 text-[11px] flex-wrap' }, [
        // Copy-prompt-for-Claude button — the primary action on open recs
        props.rec.status === 'open'
          ? h('button', {
              onClick: () => emit('implement', props.rec),
              class: 'inline-flex items-center gap-1 text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] hover:from-indigo-600 hover:to-indigo-700 px-2 py-1 rounded font-semibold',
              title: 'Copy a ready-to-paste prompt for Claude',
            }, '📋 Prompt for Claude')
          : null,
        props.rec.status === 'open' ? h('button', { onClick: () => emit('update', props.rec, { status: 'in_progress' }), class: 'text-indigo-600 hover:text-indigo-800 font-medium' }, 'Manual start') : null,
        props.rec.status === 'in_progress' ? h('button', { onClick: () => emit('update', props.rec, { status: 'shipped' }), class: 'inline-flex items-center gap-1 text-white bg-emerald-600 hover:bg-emerald-700 px-2 py-1 rounded font-semibold' }, '✓ Mark shipped') : null,
        props.rec.status === 'in_progress' ? h('button', { onClick: () => emit('update', props.rec, { status: 'open' }), class: 'text-gray-500 hover:text-gray-800' }, '← Back to open') : null,
        props.rec.status === 'shipped' ? h('button', { onClick: () => emit('update', props.rec, { status: 'in_progress' }), class: 'text-gray-500 hover:text-gray-800' }, '← Reopen') : null,
        props.rec.status !== 'deferred' && props.rec.status !== 'shipped' ? h('button', { onClick: () => emit('update', props.rec, { status: 'deferred' }), class: 'text-gray-500 hover:text-gray-800' }, 'Defer') : null,
        h('button', { onClick: () => emit('destroy', props.rec), class: 'ml-auto text-red-500 hover:text-red-700', title: 'Delete' }, '×'),
      ]),
    ])
  },
})

const Metric = defineComponent({
  props: { label: String, main: [String, Number], sub: String, accent: { type: String, default: '' } },
  setup(props) {
    const accentColor = {
      amber: 'text-amber-600', emerald: 'text-emerald-600', indigo: 'text-indigo-600', red: 'text-red-600',
    }[props.accent] || 'text-gray-900'
    return () => h('div', { class: 'bg-white border border-gray-200 rounded-lg p-4' }, [
      h('div', { class: 'text-[10px] uppercase tracking-[0.1em] font-semibold text-gray-500 mb-1.5' }, props.label),
      h('div', { class: `ui-mono text-2xl font-bold leading-none ${accentColor}` }, String(props.main)),
      h('div', { class: 'text-[11px] text-gray-500 mt-1' }, props.sub),
    ])
  },
})
</script>
