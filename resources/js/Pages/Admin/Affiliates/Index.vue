<template>
  <Layout>
    <Head><title>Affiliates · Admin — Peptidemap</title></Head>
    <div class="max-w-[1400px] mx-auto px-6 py-8 space-y-6">
      <div class="flex items-baseline justify-between border-b border-gray-200 pb-4">
        <div>
          <div class="text-[10px] uppercase tracking-[0.14em] font-semibold text-gray-400 mb-1">Affiliate management</div>
          <h1 class="text-2xl font-semibold text-gray-900">Vendor affiliates</h1>
          <p class="text-[12px] text-gray-500 mt-1">One pane for every vendor's affiliate program stats. Add tokens, sync, negotiate.</p>
        </div>
        <div class="flex items-center gap-3">
          <select v-model="days" @change="reloadWindow" class="text-[12px] border border-gray-200 rounded px-2 py-1">
            <option v-for="d in [7, 14, 30, 60, 90]" :key="d" :value="d">last {{ d }} days</option>
          </select>
        </div>
      </div>

      <!-- Portfolio totals -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="text-[10px] uppercase tracking-wider font-semibold text-gray-500 mb-1">Our clicks · {{ window_days }}d</div>
          <div class="text-2xl font-semibold text-gray-900 ui-mono">{{ totals.clicks.toLocaleString() }}</div>
          <div class="text-[11px] text-gray-500 mt-0.5">est ${{ Number(totals.est_commission).toFixed(2) }} potential</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="text-[10px] uppercase tracking-wider font-semibold text-gray-500 mb-1">Vendor-reported orders</div>
          <div class="text-2xl font-semibold text-gray-900 ui-mono">{{ Number(totals.vendor_orders || 0).toLocaleString() }}</div>
          <div class="text-[11px] text-gray-500 mt-0.5">from vendors that have synced</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="text-[10px] uppercase tracking-wider font-semibold text-gray-500 mb-1">Commission earned (all-time)</div>
          <div class="text-2xl font-semibold text-emerald-700 ui-mono">${{ Number(totals.commission_earned || 0).toFixed(2) }}</div>
          <div class="text-[11px] text-gray-500 mt-0.5">${{ Number(totals.commission_pending || 0).toFixed(2) }} pending</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="text-[10px] uppercase tracking-wider font-semibold text-gray-500 mb-1">Vendors wired</div>
          <div class="text-2xl font-semibold text-gray-900 ui-mono">{{ totals.vendors_wired }} / {{ totals.vendors_total }}</div>
          <div class="text-[11px] text-gray-500 mt-0.5">have affiliate credentials saved</div>
        </div>
      </div>

      <!-- Per-vendor table -->
      <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-[13px]">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr class="text-left text-[10px] uppercase tracking-wider text-gray-500 font-semibold">
              <th class="px-3 py-2.5">Vendor</th>
              <th class="px-3 py-2.5">Platform</th>
              <th class="px-3 py-2.5 text-right">Our clicks</th>
              <th class="px-3 py-2.5 text-right">Est. $</th>
              <th class="px-3 py-2.5 text-right">Vendor sales</th>
              <th class="px-3 py-2.5 text-right">Earned</th>
              <th class="px-3 py-2.5 text-right">Pending</th>
              <th class="px-3 py-2.5">Last sync</th>
              <th class="px-3 py-2.5"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.id" class="border-b border-gray-100 hover:bg-gray-50">
              <td class="px-3 py-2">
                <a :href="`/brand/${r.slug}`" target="_blank" class="font-semibold text-gray-900 hover:text-indigo-700 hover:underline">{{ r.name }}</a>
              </td>
              <td class="px-3 py-2">
                <span :class="platformPill(r.platform).cls">{{ r.platform }}</span>
                <span v-if="r.commission_rate_pct" class="ml-2 text-[10px] text-gray-500">{{ r.commission_rate_pct }}%</span>
              </td>
              <td class="px-3 py-2 text-right ui-mono">{{ r.our_clicks.toLocaleString() }}</td>
              <td class="px-3 py-2 text-right ui-mono text-gray-500">${{ Number(r.our_est_commission).toFixed(2) }}</td>
              <td class="px-3 py-2 text-right ui-mono">{{ r.vendor_orders !== null ? Number(r.vendor_orders).toLocaleString() : '—' }}</td>
              <td class="px-3 py-2 text-right ui-mono text-emerald-700">{{ r.commission_earned !== null ? '$' + Number(r.commission_earned).toFixed(2) : '—' }}</td>
              <td class="px-3 py-2 text-right ui-mono text-amber-700">{{ r.commission_pending !== null ? '$' + Number(r.commission_pending).toFixed(2) : '—' }}</td>
              <td class="px-3 py-2 text-[11px]">
                <span :class="r.stats_stale ? 'text-amber-700' : 'text-gray-600'">{{ r.stats_updated_at || 'never' }}</span>
              </td>
              <td class="px-3 py-2 text-right">
                <button @click="openEdit(r)" class="text-[11px] font-semibold text-indigo-700 hover:text-indigo-900">Config</button>
                <button v-if="r.has_credentials" @click="sync(r)" class="ml-2 text-[11px] font-semibold text-emerald-700 hover:text-emerald-900">Sync</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit modal -->
    <div v-if="editRow" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" @click.self="editRow = null">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-5 space-y-4">
        <div>
          <div class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Affiliate config</div>
          <h2 class="text-lg font-semibold text-gray-900">{{ editRow.name }}</h2>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-700 mb-1">Platform</label>
          <select v-model="editForm.affiliate_platform" class="w-full h-9 text-[13px] border border-gray-200 rounded px-2">
            <option v-for="p in platforms" :key="p" :value="p">{{ p }}</option>
          </select>
        </div>
        <div v-if="['goaffpro', 'refersion', 'impact', 'sharesale'].includes(editForm.affiliate_platform)">
          <label class="block text-[11px] font-semibold text-gray-700 mb-1">
            Access token
            <span v-if="editForm.affiliate_platform === 'goaffpro'" class="text-gray-500 font-normal">— from Account → Integrations → API</span>
          </label>
          <input v-model="editForm.affiliate_token" type="text" class="w-full h-9 text-[13px] border border-gray-200 rounded px-2 ui-mono" placeholder="Paste token" />
          <p class="text-[10px] text-gray-500 mt-1">Encrypted at rest.</p>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-700 mb-1">Commission rate %</label>
          <input v-model.number="editForm.commission_rate_pct" type="number" min="0" max="100" step="0.5" class="w-full h-9 text-[13px] border border-gray-200 rounded px-2 ui-mono" placeholder="e.g. 15" />
          <p class="text-[10px] text-gray-500 mt-1">Used to estimate revenue per outbound click before real sales sync in.</p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button @click="editRow = null" class="text-[12px] px-3 py-1.5 text-gray-600 hover:text-gray-900">Cancel</button>
          <button @click="save" class="text-[12px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-1.5 rounded">Save</button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import Layout from '../Layout.vue'

const props = defineProps({ rows: Array, totals: Object, window_days: Number, platforms: Array })

const days = ref(props.window_days)
function reloadWindow() {
  router.visit(`/admin/affiliates?days=${days.value}`, { preserveScroll: true })
}

function platformPill(p) {
  const map = {
    goaffpro: 'text-[10px] uppercase font-bold bg-purple-100 text-purple-800 px-2 py-0.5 rounded',
    refersion: 'text-[10px] uppercase font-bold bg-blue-100 text-blue-800 px-2 py-0.5 rounded',
    impact: 'text-[10px] uppercase font-bold bg-pink-100 text-pink-800 px-2 py-0.5 rounded',
    sharesale: 'text-[10px] uppercase font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded',
    manual: 'text-[10px] uppercase font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded',
    none: 'text-[10px] uppercase font-bold bg-gray-100 text-gray-500 px-2 py-0.5 rounded',
  }
  return { cls: map[p] || map.none }
}

const editRow = ref(null)
const editForm = reactive({ affiliate_platform: 'none', affiliate_token: '', commission_rate_pct: null })

function openEdit(r) {
  editRow.value = r
  editForm.affiliate_platform = r.platform || 'none'
  editForm.affiliate_token = ''
  editForm.commission_rate_pct = r.commission_rate_pct || null
}
function save() {
  if (!editRow.value) return
  const form = useForm({ ...editForm, _token: usePage().props.csrf_token })
  form.post(`/admin/affiliates/${editRow.value.id}/credentials`, {
    preserveScroll: true,
    onSuccess: () => { editRow.value = null },
  })
}
function sync(r) {
  const form = useForm({ _token: usePage().props.csrf_token })
  form.post(`/admin/affiliates/${r.id}/sync`, { preserveScroll: true })
}
</script>
