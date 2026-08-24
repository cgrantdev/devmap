<template>
  <AdminLayout>
    <Head>
      <title>SEO WIP · Peptidemap Admin</title>
      <meta name="robots" content="noindex,nofollow" />
    </Head>

    <div class="max-w-[1200px] mx-auto px-6 lg:px-10 py-8 space-y-8">
      <!-- Header -->
      <div>
        <div class="flex items-center gap-2 mb-1">
          <div class="text-[11px] uppercase tracking-wider font-semibold text-slate-500">Internal · noindex</div>
        </div>
        <h1 class="text-2xl font-semibold text-slate-900">SEO Work in Progress</h1>
        <p class="text-[13px] text-slate-600 mt-1">
          Live inventory of every SEO surface on the site + the strategist queue + your ideas scratchpad. All counts pulled from the DB — refresh to see updates. Cached 5 min server-side.
        </p>
      </div>

      <!-- Rec queue summary -->
      <section class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white border border-slate-200 rounded-lg p-4">
          <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Open recs</div>
          <div class="ui-mono text-2xl font-bold text-amber-600">{{ recs.open }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
          <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">In progress</div>
          <div class="ui-mono text-2xl font-bold text-indigo-600">{{ recs.in_progress }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
          <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Shipped · 7d</div>
          <div class="ui-mono text-2xl font-bold text-emerald-600">{{ recs.shipped_7d }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
          <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-1">Shipped · 30d</div>
          <div class="ui-mono text-2xl font-bold text-slate-900">{{ recs.shipped_30d }}</div>
        </div>
      </section>

      <!-- Live inventory -->
      <section
        v-for="group in inventory"
        :key="group.group"
        class="bg-white border border-slate-200 rounded-lg overflow-hidden"
      >
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50">
          <h2 class="text-[13px] uppercase tracking-wider font-semibold text-slate-700">{{ group.group }}</h2>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-for="item in group.items" :key="item.url_pattern" class="px-5 py-3 grid grid-cols-1 md:grid-cols-[1fr_60px_260px_auto] gap-3 md:gap-4 items-start">
            <div>
              <div class="text-[14px] font-semibold text-slate-900">{{ item.title }}</div>
              <div class="text-[11px] text-slate-500 ui-mono">{{ item.url_pattern }}</div>
            </div>
            <div class="ui-mono text-[15px] font-bold text-slate-900 md:text-right">{{ item.count.toLocaleString() }}</div>
            <div class="text-[12px] text-slate-600 leading-snug">{{ item.notes }}</div>
            <div>
              <a
                :href="item.sample"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-[12px] font-semibold text-indigo-600 hover:text-indigo-800"
              >
                View sample ↗
              </a>
            </div>
          </li>
        </ul>
      </section>

      <!-- Recently shipped recs -->
      <section v-if="recs.shipped_recent?.length" class="bg-white border border-slate-200 rounded-lg">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
          <h2 class="text-[13px] uppercase tracking-wider font-semibold text-slate-700">Recently shipped recs</h2>
          <a href="/admin/ceo" class="text-[12px] font-semibold text-indigo-600 hover:text-indigo-800">Full CEO dashboard →</a>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-for="r in recs.shipped_recent" :key="r.id" class="px-5 py-3 flex items-center gap-3">
            <span
              class="text-[9px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded"
              :class="{
                'bg-red-100 text-red-700': r.impact === 'high',
                'bg-amber-100 text-amber-700': r.impact === 'medium',
                'bg-slate-100 text-slate-700': r.impact === 'low',
              }"
            >{{ r.impact }}</span>
            <span class="text-[13px] text-slate-800 flex-1 min-w-0 truncate">{{ r.title }}</span>
            <span class="text-[11px] text-slate-500 flex-shrink-0">{{ r.shipped_at }}</span>
          </li>
        </ul>
      </section>

      <!-- Top open recs -->
      <section v-if="recs.open_top?.length" class="bg-white border border-slate-200 rounded-lg">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50">
          <h2 class="text-[13px] uppercase tracking-wider font-semibold text-slate-700">Top open recs · by impact</h2>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-for="r in recs.open_top" :key="r.id" class="px-5 py-3 flex items-center gap-3">
            <span
              class="text-[9px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded"
              :class="{
                'bg-red-100 text-red-700': r.impact === 'high',
                'bg-amber-100 text-amber-700': r.impact === 'medium',
                'bg-slate-100 text-slate-700': r.impact === 'low',
              }"
            >{{ r.impact }}</span>
            <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 flex-shrink-0">{{ r.effort }}</span>
            <span class="text-[13px] text-slate-800 flex-1 min-w-0 truncate">{{ r.title }}</span>
            <span class="text-[11px] text-slate-500 flex-shrink-0">{{ r.category }}</span>
          </li>
        </ul>
      </section>

      <!-- Ideas scratchpad -->
      <section class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
          <h2 class="text-[13px] uppercase tracking-wider font-semibold text-slate-700">Ideas & notes</h2>
          <div class="flex items-center gap-3">
            <span v-if="savedMoment" class="text-[11px] text-emerald-600">✓ saved {{ savedMoment }}</span>
            <button
              @click="save"
              type="button"
              :disabled="saving"
              class="h-8 px-3 rounded-md text-[12px] font-semibold text-white bg-slate-900 hover:bg-slate-700 disabled:opacity-50 transition-colors"
            >
              {{ saving ? 'Saving…' : 'Save notes' }}
            </button>
          </div>
        </div>
        <textarea
          v-model="notesBody"
          rows="12"
          placeholder="Ideas to sort. Compounds to write about. Vendors to email. Pages that need work. Anything."
          class="w-full px-5 py-4 text-[13px] font-mono leading-relaxed border-0 focus:outline-none resize-y"
        ></textarea>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  inventory: { type: Array, default: () => [] },
  recs: { type: Object, default: () => ({}) },
  notes: { type: String, default: '' },
})

const notesBody = ref(props.notes)
const saving = ref(false)
const savedMoment = ref(null)

function save() {
  saving.value = true
  router.post('/admin/seo-wip/notes', {
    body: notesBody.value,
    _token: usePage().props.csrf_token,
  }, {
    preserveScroll: true,
    onFinish: () => {
      saving.value = false
      savedMoment.value = 'just now'
      setTimeout(() => { savedMoment.value = null }, 3000)
    },
  })
}
</script>
