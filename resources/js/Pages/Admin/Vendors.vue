<template>
  <AdminLayout>
    <PageHeader title="Vendors" :subtitle="`${vendors.length} vendors total`">
      <template #actions>
        <Link
          href="/admin/vendors/create"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          Add Vendor
        </Link>
      </template>
    </PageHeader>

    <!-- Flash -->
    <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 bg-[color:var(--color-verified-bg)] border border-[#A7F3D0] text-[#065F46] text-sm">
      {{ $page.props.flash.success }}
    </div>

    <!-- Filters -->
    <FilterBar
      v-model:search="searchTerm"
      placeholder="Search vendors..."
      v-model:status="statusFilter"
      :tabs="[
        { value: 'all', label: 'All', count: vendors.length },
        { value: 'active', label: 'Active', count: vendors.filter(v => v.is_active).length },
        { value: 'pending', label: 'Pending', count: vendors.filter(v => v.approval_status === 'pending' || v.settings?.approval_status === 'pending').length },
        { value: 'inactive', label: 'Inactive', count: vendors.filter(v => !v.is_active).length },
      ]"
    />

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Location</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Products</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Rating</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Status</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Dashboard</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="vendor in filteredVendors"
            :key="vendor.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            @click="router.visit(`/admin/vendors/${vendor.id}/edit`)"
          >
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] flex items-center justify-center overflow-hidden">
                  <img v-if="vendor.settings?.logo_url || vendor.settings?.logo" :src="vendor.settings?.logo_url || (vendor.settings?.logo ? '/storage/' + vendor.settings.logo : '')" :alt="vendor.name" class="w-full h-full object-contain p-0.5" loading="lazy" />
                  <span v-else class="text-[10px] font-bold text-[color:var(--color-ink-muted)]">{{ vendor.name.substring(0, 2).toUpperCase() }}</span>
                </div>
                <div class="min-w-0">
                  <div class="text-[13px] font-medium text-[color:var(--color-ink)] truncate">{{ vendor.name }}</div>
                  <div v-if="vendor.settings?.shop_url" class="text-[11px] text-[color:var(--color-ink-subtle)] truncate">
                    {{ (vendor.settings.shop_url || '').replace('https://', '').replace('http://', '') }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ vendor.location || '—' }}
            </td>
            <td class="px-5 py-3.5 ui-mono text-[13px] text-[color:var(--color-ink)]">
              {{ vendor.products_count || 0 }}
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                <span class="ui-mono text-[13px] text-[color:var(--color-ink)]">{{ vendor.rating_average || vendor.rating || '0.0' }}</span>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <StatusBadge
                v-if="vendor.approval_status === 'pending' || vendor.settings?.approval_status === 'pending'"
                status="pending"
                label="Pending"
              />
              <StatusBadge
                v-else-if="vendor.is_active"
                status="active"
                label="Active"
              />
              <StatusBadge
                v-else
                status="inactive"
                label="Inactive"
              />
            </td>
            <td class="px-5 py-3.5" @click.stop>
              <a
                v-if="vendor.user_id"
                :href="`/admin/impersonate/${vendor.user_id}`"
                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium text-[color:var(--color-accent-600)] bg-[color:var(--color-accent-50)] hover:bg-[color:var(--color-accent-100)] transition-colors"
                title="View vendor dashboard as this vendor"
              >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                Dashboard
              </a>
              <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">No user</span>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="filteredVendors.length === 0" class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
        No vendors match your filters.
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import PageHeader from '@/components/admin/PageHeader.vue'
import FilterBar from '@/components/admin/FilterBar.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'

const props = defineProps({
  vendors: { type: Array, default: () => [] },
})

const searchTerm = ref('')
const statusFilter = ref('all')

const filteredVendors = computed(() => {
  let list = props.vendors

  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(v =>
      v.name.toLowerCase().includes(q) ||
      (v.location || '').toLowerCase().includes(q)
    )
  }

  if (statusFilter.value === 'active') {
    list = list.filter(v => v.is_active)
  } else if (statusFilter.value === 'pending') {
    list = list.filter(v => v.approval_status === 'pending' || v.settings?.approval_status === 'pending')
  } else if (statusFilter.value === 'inactive') {
    list = list.filter(v => !v.is_active)
  }

  return list
})
</script>
