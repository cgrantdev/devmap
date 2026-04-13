<template>
  <AdminLayout>
    <PageHeader title="Vendor Discovery" subtitle="Find new vendors and manage pending imports">
      <template #actions>
        <button
          @click="startScan"
          :disabled="scanning || scanLoading"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all disabled:opacity-50 flex items-center gap-2"
        >
          <svg v-if="!scanning && !scanLoading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
          {{ scanning || scanLoading ? 'Scanning...' : 'Scan for New Vendors' }}
        </button>
      </template>
    </PageHeader>

    <!-- Scan progress -->
    <div v-if="scanning" class="mb-4 px-4 py-3 bg-indigo-50 border border-indigo-200 text-indigo-800 text-sm flex items-center gap-3">
      <svg class="w-4 h-4 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
      <span class="font-medium">Scan in progress</span>
      <span v-if="scanProgress" class="text-indigo-600">— {{ scanProgress }}</span>
      <span class="ml-auto text-[12px] text-indigo-500">Auto-refreshing...</span>
    </div>

    <!-- Flash -->
    <div v-if="$page.props.flash?.success && !scanning" class="mb-4 px-4 py-3 bg-[color:var(--color-verified-bg)] border border-[#A7F3D0] text-[#065F46] text-sm">
      {{ $page.props.flash.success }}
    </div>

    <!-- ============================================ -->
    <!-- NEW DISCOVERIES (from scan, not in DB yet)   -->
    <!-- ============================================ -->
    <div v-if="newDiscoveries.length" class="mb-10">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-accent-600)]">
          New Discoveries <span class="ui-mono">{{ newDiscoveries.length }}</span>
        </h2>
        <button
          v-if="selectedNew.length"
          @click="importSelected"
          :disabled="importLoading"
          class="h-8 px-4 text-[12px] font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors flex items-center gap-1.5 disabled:opacity-50"
        >
          Import {{ selectedNew.length }} vendor{{ selectedNew.length > 1 ? 's' : '' }}
        </button>
      </div>

      <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
              <th class="px-3 py-3 w-10"><input type="checkbox" @change="toggleAllNew" :checked="selectedNew.length === newDiscoveries.length" class="w-4 h-4" /></th>
              <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
              <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Platform</th>
              <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Affiliate</th>
              <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Email</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="v in newDiscoveries" :key="v.slug" class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] transition-colors">
              <td class="px-3 py-3"><input type="checkbox" :value="v.slug" v-model="selectedNew" class="w-4 h-4" /></td>
              <td class="px-4 py-3">
                <div class="text-[13px] font-medium text-[color:var(--color-ink)]">{{ v.name }}</div>
                <a :href="v.url" target="_blank" class="text-[11px] text-[color:var(--color-accent-600)] hover:underline">{{ v.domain || v.url?.replace('https://', '') }}</a>
              </td>
              <td class="px-4 py-3">
                <span v-if="v.platform" :class="['inline-flex px-2 py-0.5 text-[10px] font-semibold', v.platform === 'woocommerce' ? 'bg-purple-50 text-purple-700' : v.platform === 'shopify' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600']">{{ v.platform }}</span>
                <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
              </td>
              <td class="px-4 py-3">
                <a v-if="v.affiliate_url" :href="v.affiliate_url" target="_blank" class="text-[11px] text-emerald-600 hover:underline">Sign up</a>
                <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
              </td>
              <td class="px-4 py-3">
                <a v-if="v.email" :href="`mailto:${v.email}`" class="text-[12px] text-[color:var(--color-accent-600)] hover:underline">{{ v.email }}</a>
                <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============================================ -->
    <!-- PENDING VENDORS (in DB, not yet activated)   -->
    <!-- ============================================ -->
    <div>
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">
          Pending Vendors <span class="ui-mono">{{ pending.length }}</span>
        </h2>
        <div class="flex items-center gap-3">
          <button
            v-if="selectedPending.length"
            @click="activateSelected"
            :disabled="activateLoading"
            class="h-8 px-4 text-[12px] font-semibold text-white bg-[color:var(--color-verified)] hover:opacity-90 transition-colors flex items-center gap-1.5 disabled:opacity-50"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Activate {{ selectedPending.length }}
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex items-center gap-1.5 mb-4">
        <button
          v-for="f in pendingFilters"
          :key="f.value"
          @click="activeFilter = f.value"
          :class="['h-7 px-3 text-[12px] font-medium transition-colors whitespace-nowrap', activeFilter === f.value ? 'bg-[color:var(--color-ink)] text-white' : 'text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-[color:var(--color-hairline-soft)]']"
        >{{ f.label }} <span class="ui-mono ml-1">{{ f.count }}</span></button>
      </div>

      <div v-if="filteredPending.length" class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
              <th class="px-3 py-3 w-10"><input type="checkbox" @change="toggleAllPending" :checked="selectedPending.length === filteredPending.length && filteredPending.length > 0" class="w-4 h-4" /></th>
              <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
              <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Platform</th>
              <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Affiliate Signup</th>
              <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Email</th>
              <th class="px-4 py-3 w-20"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="v in filteredPending" :key="v.slug" class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] transition-colors">
              <td class="px-3 py-3"><input type="checkbox" :value="v.brand_id" v-model="selectedPending" class="w-4 h-4" /></td>
              <td class="px-4 py-3">
                <div class="text-[13px] font-medium text-[color:var(--color-ink)]">{{ v.name }}</div>
                <a :href="v.url" target="_blank" class="text-[11px] text-[color:var(--color-accent-600)] hover:underline">{{ v.domain || v.url?.replace('https://', '') }}</a>
                <div v-if="v.description && !v.description.startsWith('Affiliate')" class="text-[11px] text-[color:var(--color-ink-subtle)] truncate max-w-md mt-0.5">{{ v.description }}</div>
              </td>
              <td class="px-4 py-3">
                <span v-if="v.platform" :class="['inline-flex px-2 py-0.5 text-[10px] font-semibold', v.platform === 'woocommerce' ? 'bg-purple-50 text-purple-700' : v.platform === 'shopify' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600']">{{ v.platform }}</span>
                <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
              </td>
              <td class="px-4 py-3">
                <a v-if="v.affiliate_url" :href="v.affiliate_url" target="_blank" class="text-[11px] text-[color:var(--color-accent-600)] hover:underline break-all leading-tight block max-w-[200px]">
                  {{ v.affiliate_url.replace('https://', '').replace('http://', '') }}
                </a>
                <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
              </td>
              <td class="px-4 py-3">
                <a v-if="v.email" :href="`mailto:${v.email}`" class="text-[12px] text-[color:var(--color-accent-600)] hover:underline">{{ v.email }}</a>
                <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
              </td>
              <td class="px-4 py-3">
                <a :href="`/admin/vendors/${v.brand_id}/edit`" class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium text-[color:var(--color-accent-600)] bg-[color:var(--color-accent-50)] hover:bg-[color:var(--color-accent-100)] transition-colors">Edit</a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
        No pending vendors match this filter.
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import PageHeader from '@/components/admin/PageHeader.vue'

const props = defineProps({
  pending: { type: Array, default: () => [] },
  newDiscoveries: { type: Array, default: () => [] },
  scanning: { type: Boolean, default: false },
  scanProgress: { type: String, default: '' },
  lastScanAt: { type: String, default: null },
})

const selectedNew = ref([])
const selectedPending = ref([])
const activeFilter = ref('all')
const scanLoading = ref(false)
const importLoading = ref(false)
const activateLoading = ref(false)

const pendingFilters = computed(() => [
  { label: 'All', value: 'all', count: props.pending.length },
  { label: 'Has Affiliate', value: 'affiliate', count: props.pending.filter(r => r.has_affiliate).length },
  { label: 'WooCommerce', value: 'woocommerce', count: props.pending.filter(r => r.platform === 'woocommerce').length },
  { label: 'Has Email', value: 'email', count: props.pending.filter(r => r.email).length },
])

const filteredPending = computed(() => {
  if (activeFilter.value === 'affiliate') return props.pending.filter(r => r.has_affiliate)
  if (activeFilter.value === 'woocommerce') return props.pending.filter(r => r.platform === 'woocommerce')
  if (activeFilter.value === 'email') return props.pending.filter(r => r.email)
  return props.pending
})

function toggleAllNew(e) {
  selectedNew.value = e.target.checked ? props.newDiscoveries.map(r => r.slug) : []
}

function toggleAllPending(e) {
  selectedPending.value = e.target.checked ? filteredPending.value.map(r => r.brand_id) : []
}

// Auto-refresh while scanning
let pollInterval = null
function startPolling() { if (!pollInterval) pollInterval = setInterval(() => router.reload({ only: ['newDiscoveries', 'pending', 'scanning', 'scanProgress'], preserveScroll: true }), 5000) }
function stopPolling() { if (pollInterval) { clearInterval(pollInterval); pollInterval = null } }
watch(() => props.scanning, (val) => val ? startPolling() : stopPolling(), { immediate: true })
onUnmounted(() => stopPolling())

function startScan() {
  scanLoading.value = true
  router.post('/admin/discover/scan', { _token: usePage().props.csrf_token }, {
    preserveScroll: true,
    onFinish: () => { scanLoading.value = false },
  })
}

function importSelected() {
  const vendors = props.newDiscoveries.filter(r => selectedNew.value.includes(r.slug))
  if (!vendors.length) return
  importLoading.value = true
  router.post('/admin/discover/import', {
    _token: usePage().props.csrf_token,
    vendors: vendors.map(v => ({ name: v.name, url: v.url, slug: v.slug, email: v.email, platform: v.platform, description: v.description })),
  }, {
    preserveScroll: true,
    onFinish: () => { importLoading.value = false; selectedNew.value = [] },
  })
}

function activateSelected() {
  if (!selectedPending.value.length) return
  activateLoading.value = true
  router.post('/admin/discover/activate', {
    _token: usePage().props.csrf_token,
    brand_ids: selectedPending.value,
  }, {
    preserveScroll: true,
    onFinish: () => { activateLoading.value = false; selectedPending.value = [] },
  })
}
</script>
