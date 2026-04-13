<template>
  <AdminLayout>
    <PageHeader title="Vendor Discovery" subtitle="Find and import peptide vendors from across the web">
      <template #actions>
        <button
          @click="startScan"
          :disabled="scanning || scanLoading"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all disabled:opacity-50 flex items-center gap-2"
        >
          <svg v-if="!scanning && !scanLoading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
          {{ scanning || scanLoading ? 'Scanning...' : 'Scan for Vendors' }}
        </button>
      </template>
    </PageHeader>

    <!-- Scan progress banner -->
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

    <!-- Stats -->
    <div v-if="results.length" class="flex items-center gap-6 mb-4 text-[13px] text-[color:var(--color-ink-muted)]">
      <span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ results.length }}</span> vendors found</span>
      <span><span class="ui-mono font-semibold text-[color:var(--color-verified)]">{{ results.filter(r => !r.already_exists).length }}</span> new</span>
      <span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ results.filter(r => r.has_affiliate).length }}</span> with affiliate programs</span>
      <span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ results.filter(r => r.platform === 'woocommerce').length }}</span> WooCommerce</span>
      <span v-if="lastScanAt" class="ml-auto text-[11px]">Last scan: {{ lastScanAt }}</span>
    </div>

    <!-- Filters -->
    <div v-if="results.length" class="flex items-center gap-1.5 mb-4">
      <button
        v-for="f in filters"
        :key="f.value"
        @click="activeFilter = f.value"
        :class="[
          'h-8 px-3 text-[12px] font-medium transition-colors whitespace-nowrap',
          activeFilter === f.value
            ? 'bg-[color:var(--color-ink)] text-white'
            : 'text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-[color:var(--color-hairline-soft)]',
        ]"
      >{{ f.label }} <span class="ui-mono ml-1">{{ f.count }}</span></button>
    </div>

    <!-- Import selected button -->
    <div v-if="selected.length" class="mb-4 flex items-center gap-3">
      <button
        @click="importSelected"
        :disabled="importLoading"
        class="h-9 px-4 text-[13px] font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors flex items-center gap-2 disabled:opacity-50"
      >
        <svg v-if="!importLoading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        {{ importLoading ? 'Importing...' : `Import ${selected.length} Vendor${selected.length > 1 ? 's' : ''}` }}
      </button>
      <button @click="selected = []" class="text-[12px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">Clear selection</button>
    </div>

    <!-- Results table -->
    <div v-if="results.length" class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-3 py-3 w-10">
              <input type="checkbox" @change="toggleAll" :checked="selected.length === filteredResults.length && filteredResults.length > 0" class="w-4 h-4" />
            </th>
            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Platform</th>
            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Affiliate</th>
            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Email</th>
            <th class="px-4 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="vendor in filteredResults"
            :key="vendor.slug"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] transition-colors"
          >
            <td class="px-3 py-3">
              <input
                v-if="!vendor.already_exists"
                type="checkbox"
                :value="vendor.slug"
                v-model="selected"
                class="w-4 h-4"
              />
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-col gap-0.5">
                <div class="text-[13px] font-medium text-[color:var(--color-ink)]">{{ vendor.name }}</div>
                <a :href="vendor.url" target="_blank" class="text-[11px] text-[color:var(--color-accent-600)] hover:underline truncate max-w-xs">
                  {{ vendor.domain || vendor.url?.replace('https://', '').replace('http://', '') }}
                </a>
                <div v-if="vendor.description" class="text-[11px] text-[color:var(--color-ink-subtle)] truncate max-w-md mt-0.5">{{ vendor.description }}</div>
              </div>
            </td>
            <td class="px-4 py-3">
              <span v-if="vendor.platform" :class="[
                'inline-flex px-2 py-0.5 text-[10px] font-semibold',
                vendor.platform === 'woocommerce' ? 'bg-purple-50 text-purple-700' :
                vendor.platform === 'shopify' ? 'bg-green-50 text-green-700' :
                vendor.platform === 'medusa' ? 'bg-blue-50 text-blue-700' :
                'bg-gray-100 text-gray-600'
              ]">{{ vendor.platform }}</span>
              <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
            </td>
            <td class="px-4 py-3">
              <a v-if="vendor.affiliate_url" :href="vendor.affiliate_url" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 hover:underline">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Sign up
              </a>
              <span v-else-if="vendor.has_affiliate" class="text-[11px] font-semibold text-emerald-600">Yes</span>
              <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
            </td>
            <td class="px-4 py-3">
              <a v-if="vendor.email" :href="`mailto:${vendor.email}`" class="text-[12px] text-[color:var(--color-accent-600)] hover:underline">{{ vendor.email }}</a>
              <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
            </td>
            <td class="px-4 py-3">
              <span v-if="vendor.already_exists" class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)]">Already listed</span>
              <span v-else class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold bg-[color:var(--color-verified-bg)] text-[#065F46]">New</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty state -->
    <div v-else class="py-20 text-center">
      <svg class="w-12 h-12 mx-auto text-[color:var(--color-ink-subtle)] mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <p class="text-[color:var(--color-ink-muted)] mb-2">No scan results yet</p>
      <p class="text-[13px] text-[color:var(--color-ink-subtle)]">Click "Scan for Vendors" to discover peptide vendors across the web.</p>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import PageHeader from '@/components/admin/PageHeader.vue'

const props = defineProps({
  results: { type: Array, default: () => [] },
  scanning: { type: Boolean, default: false },
  scanProgress: { type: String, default: '' },
  lastScanAt: { type: String, default: null },
})

const selected = ref([])

// Auto-refresh while scanning
let pollInterval = null

function startPolling() {
  if (pollInterval) return
  pollInterval = setInterval(() => {
    router.reload({ only: ['results', 'scanning', 'scanProgress', 'lastScanAt'], preserveScroll: true })
  }, 5000)
}

function stopPolling() {
  if (pollInterval) { clearInterval(pollInterval); pollInterval = null }
}

watch(() => props.scanning, (val) => {
  if (val) startPolling()
  else stopPolling()
}, { immediate: true })

onUnmounted(() => stopPolling())
const activeFilter = ref('all')
const scanLoading = ref(false)
const importLoading = ref(false)

const filters = computed(() => [
  { label: 'All', value: 'all', count: props.results.length },
  { label: 'New', value: 'new', count: props.results.filter(r => !r.already_exists).length },
  { label: 'Affiliate', value: 'affiliate', count: props.results.filter(r => r.has_affiliate).length },
  { label: 'WooCommerce', value: 'woocommerce', count: props.results.filter(r => r.platform === 'woocommerce').length },
  { label: 'Shopify', value: 'shopify', count: props.results.filter(r => r.platform === 'shopify').length },
])

const filteredResults = computed(() => {
  if (activeFilter.value === 'all') return props.results
  if (activeFilter.value === 'new') return props.results.filter(r => !r.already_exists)
  if (activeFilter.value === 'affiliate') return props.results.filter(r => r.has_affiliate)
  return props.results.filter(r => r.platform === activeFilter.value)
})

function toggleAll(e) {
  if (e.target.checked) {
    selected.value = filteredResults.value.filter(r => !r.already_exists).map(r => r.slug)
  } else {
    selected.value = []
  }
}

function startScan() {
  scanLoading.value = true
  router.post('/admin/discover/scan', { _token: usePage().props.csrf_token }, {
    preserveScroll: true,
    onFinish: () => { scanLoading.value = false },
  })
}

function importSelected() {
  const vendors = props.results.filter(r => selected.value.includes(r.slug))
  if (!vendors.length) return

  importLoading.value = true
  router.post('/admin/discover/import', {
    vendors: vendors.map(v => ({
      name: v.name,
      url: v.url,
      slug: v.slug,
      email: v.email,
      platform: v.platform,
      description: v.description,
    })),
  }, {
    preserveScroll: true,
    onFinish: () => {
      importLoading.value = false
      selected.value = []
    },
  })
}
</script>
