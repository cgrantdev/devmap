<template>
  <AdminLayout>
    <div class="p-8 max-w-5xl mx-auto">
      <div class="flex items-start justify-between gap-4 mb-5">
        <div>
          <h1 class="text-2xl font-semibold">Page analytics</h1>
          <p class="text-sm text-[color:var(--color-ink-muted)] mt-0.5">
            <a :href="full_url" target="_blank" class="ui-mono text-[12px] text-indigo-600 hover:underline">{{ path }}</a>
          </p>
        </div>
        <a :href="full_url" target="_blank" class="h-9 px-4 inline-flex items-center text-[13px] font-medium border border-[color:var(--color-hairline)] hover:bg-slate-50 rounded">
          View page →
        </a>
      </div>

      <!-- GSC windows -->
      <section class="mb-8">
        <h2 class="text-lg font-semibold mb-3">Search Console</h2>
        <div v-if="!gsc.configured" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          GSC isn't configured. Site-wide dashboard at <a href="/admin/ceo" class="underline font-medium">/admin/ceo</a> explains setup.
        </div>
        <div v-else class="space-y-3">
          <div v-for="(w, days) in gsc.windows" :key="days"
            class="border border-[color:var(--color-hairline)] rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold">Last {{ days }} days</h3>
              <span class="text-[11px] text-[color:var(--color-ink-muted)] ui-mono">{{ w.start }} → {{ w.end }}</span>
            </div>
            <div class="grid grid-cols-4 gap-3">
              <Stat label="Clicks" :cur="w.current.clicks" :prev="w.previous.clicks" />
              <Stat label="Impressions" :cur="w.current.impressions" :prev="w.previous.impressions" />
              <Stat label="CTR" :cur="w.current.ctr + '%'" :raw-cur="w.current.ctr" :raw-prev="w.previous.ctr" :fmt="v => v + '%'" />
              <Stat label="Position" :cur="w.current.position" :prev="w.previous.position" inverse />
            </div>
          </div>
        </div>
      </section>

      <!-- Internal tracking -->
      <section class="mb-8">
        <h2 class="text-lg font-semibold mb-3">Internal</h2>
        <div class="grid md:grid-cols-2 gap-4">
          <div class="border border-[color:var(--color-hairline)] rounded-lg p-4">
            <h3 class="text-sm font-semibold mb-3">Page views</h3>
            <div v-if="internal.views" class="grid grid-cols-3 gap-3">
              <SimpleStat label="7d" :value="internal.views.d7" />
              <SimpleStat label="28d" :value="internal.views.d28" />
              <SimpleStat label="90d" :value="internal.views.d90" />
            </div>
            <div v-else class="text-sm text-[color:var(--color-ink-muted)]">Tracking table not available.</div>
          </div>
          <div class="border border-[color:var(--color-hairline)] rounded-lg p-4">
            <h3 class="text-sm font-semibold mb-3">Outbound affiliate clicks (from this page)</h3>
            <div v-if="internal.outbound" class="grid grid-cols-3 gap-3">
              <SimpleStat label="7d" :value="internal.outbound.d7" />
              <SimpleStat label="28d" :value="internal.outbound.d28" />
              <SimpleStat label="90d" :value="internal.outbound.d90" />
            </div>
            <div v-else class="text-sm text-[color:var(--color-ink-muted)]">Tracking table not available.</div>
          </div>
        </div>
      </section>

      <!-- Top queries driving traffic to this URL -->
      <section v-if="recent_queries && recent_queries.length" class="mb-8">
        <h2 class="text-lg font-semibold mb-3">Top queries → this page (28d)</h2>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-[color:var(--color-hairline)] text-[11px] uppercase tracking-wider text-[color:var(--color-ink-muted)]">
              <th class="text-left py-2 font-medium">Query</th>
              <th class="text-right py-2 font-medium">Clicks</th>
              <th class="text-right py-2 font-medium">Impressions</th>
              <th class="text-right py-2 font-medium">CTR</th>
              <th class="text-right py-2 font-medium">Position</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(q, idx) in recent_queries" :key="idx"
              class="border-b border-[color:var(--color-hairline-soft)]">
              <td class="py-2 ui-mono text-[12px]">{{ q.query }}</td>
              <td class="py-2 text-right ui-mono">{{ q.clicks }}</td>
              <td class="py-2 text-right ui-mono">{{ q.impressions }}</td>
              <td class="py-2 text-right ui-mono">{{ q.ctr }}%</td>
              <td class="py-2 text-right ui-mono">{{ q.position }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { h } from 'vue'
import AdminLayout from './Layout.vue'

defineProps({
  path: String,
  full_url: String,
  gsc: { type: Object, default: () => ({ configured: false, windows: {} }) },
  internal: { type: Object, default: () => ({}) },
  recent_queries: { type: Array, default: () => [] },
})

export const Stat = {
  props: { label: String, cur: [String, Number], prev: [Number, null], inverse: Boolean, rawCur: Number, rawPrev: Number, fmt: Function },
  setup(p) {
    return () => {
      const c = p.rawCur != null ? p.rawCur : Number(p.cur)
      const pr = p.rawPrev != null ? p.rawPrev : Number(p.prev)
      let delta = null, cls = 'text-slate-500'
      if (!isNaN(c) && !isNaN(pr) && pr > 0) {
        const pct = Math.round(((c - pr) / pr) * 100)
        delta = (pct >= 0 ? '+' : '') + pct + '%'
        const good = p.inverse ? pct < 0 : pct > 0
        cls = good ? 'text-emerald-600' : (pct === 0 ? 'text-slate-500' : 'text-red-600')
      }
      return h('div', { class: 'border border-[color:var(--color-hairline-soft)] rounded p-2' }, [
        h('div', { class: 'text-[10px] uppercase tracking-wider text-slate-500' }, p.label),
        h('div', { class: 'text-lg font-semibold ui-mono' }, p.cur),
        delta ? h('div', { class: 'text-[10px] ' + cls }, delta) : null,
      ])
    }
  }
}

export const SimpleStat = {
  props: { label: String, value: [String, Number] },
  setup(p) {
    return () => h('div', [
      h('div', { class: 'text-[10px] uppercase tracking-wider text-slate-500 mb-0.5' }, p.label),
      h('div', { class: 'text-lg font-semibold ui-mono' }, p.value),
    ])
  }
}
</script>
