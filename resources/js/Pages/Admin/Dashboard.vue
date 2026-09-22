<template>
  <AdminLayout>
    <!-- Page header -->
    <div class="mb-8">
      <h1 class="ui-display text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">Dashboard</h1>
      <p class="text-sm text-[color:var(--color-ink-muted)] mt-1">Overview of your marketplace.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div v-for="stat in statCards" :key="stat.label" class="bg-white border border-[color:var(--color-hairline)] p-5">
        <div class="text-[10px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)] mb-2">{{ stat.label }}</div>
        <div class="ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ stat.value }}</div>
      </div>
    </div>

    <!-- Live + scheduled coupon boosts. Colin Sep 14 — Julia's PMAP
         feedback: "add a visual … showing any 'active' promotions
         with start and end dates / times". One place to see what's
         running platform-wide without touching every vendor edit page. -->
    <div v-if="activeBoosts && activeBoosts.length" class="bg-white border border-amber-200 p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="ui-display text-base font-semibold text-[color:var(--color-ink)]">Live promotions</h2>
        <span class="text-[11px] uppercase tracking-[0.12em] font-semibold text-amber-700">{{ activeBoosts.length }} running</span>
      </div>
      <div class="space-y-0">
        <div
          v-for="b in activeBoosts"
          :key="`${b.brand_id}-${b.expires_at}`"
          class="flex items-center gap-3 py-2.5 border-b border-[color:var(--color-hairline-soft)] last:border-0"
        >
          <span
            :class="[
              'inline-flex items-center justify-center text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded-full flex-shrink-0',
              b.status === 'active' ? 'bg-red-600 text-white' : 'bg-amber-600 text-white',
            ]"
          >
            {{ b.status === 'active' ? '● Live' : '◷ Scheduled' }}
          </span>
          <div class="flex-1 min-w-0">
            <Link :href="`/admin/vendors/${b.brand_id}/edit`" class="text-sm font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] truncate block">
              {{ b.brand_name }}
            </Link>
            <p class="text-xs text-[color:var(--color-ink-subtle)] mt-0.5 ui-mono">
              <span class="font-semibold text-amber-800">{{ b.percent }}%</span>
              <span v-if="b.coupon_code"> · code <span class="uppercase">{{ b.coupon_code }}</span></span>
              <span v-if="b.reverts_to_code" class="text-emerald-700"> (was {{ b.reverts_to_code }})</span>
              <span v-if="b.status === 'scheduled' && b.starts_at"> · starts {{ formatTs(b.starts_at) }}</span>
              <span> · ends {{ formatTs(b.expires_at) }}</span>
              <span v-if="b.reverts_to_percent != null"> · reverts to {{ b.reverts_to_percent }}%</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Two columns: activity + quick actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Activity -->
      <div class="bg-white border border-[color:var(--color-hairline)] p-6">
        <h2 class="ui-display text-base font-semibold text-[color:var(--color-ink)] mb-4">Recent Activity</h2>
        <div class="space-y-0">
          <div
            v-for="activity in recentActivity"
            :key="activity.id"
            class="flex items-start gap-3 py-3 border-b border-[color:var(--color-hairline-soft)] last:border-0"
          >
            <div class="w-1.5 h-1.5 bg-[color:var(--color-accent-500)] rounded-full mt-2 flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="text-sm text-[color:var(--color-ink)]">{{ activity.description }}</p>
              <p class="text-xs text-[color:var(--color-ink-subtle)] mt-0.5">{{ activity.vendor }}</p>
            </div>
            <span class="text-[11px] text-[color:var(--color-ink-subtle)] flex-shrink-0 ui-mono">{{ activity.time }}</span>
          </div>
          <div v-if="!recentActivity || recentActivity.length === 0" class="text-sm text-[color:var(--color-ink-subtle)] text-center py-8">
            No recent activity
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white border border-[color:var(--color-hairline)] p-6">
        <h2 class="ui-display text-base font-semibold text-[color:var(--color-ink)] mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-3">
          <Link
            v-for="action in quickActions"
            :key="action.href"
            :href="action.href"
            class="flex items-center gap-3 p-4 border border-[color:var(--color-hairline)] hover:border-[color:var(--color-accent-400)] hover:bg-[color:var(--color-accent-50)] transition-colors"
          >
            <div class="w-8 h-8 bg-[color:var(--color-accent-50)] flex items-center justify-center flex-shrink-0">
              <span class="text-[color:var(--color-accent-600)] text-lg">{{ action.icon }}</span>
            </div>
            <span class="text-sm font-medium text-[color:var(--color-ink)]">{{ action.label }}</span>
          </Link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalVendors: 0,
      activeVendors: 0,
      totalProducts: 0,
      pendingReviews: 0,
      totalUsers: 0,
    })
  },
  recentActivity: {
    type: Array,
    default: () => []
  },
  activeBoosts: {
    type: Array,
    default: () => []
  }
})

function formatTs(iso) {
  if (!iso) return ''
  // Colin/Julia Sep 22 — Live promos rail displays EST/EDT so what
  // Julia typed in the admin datetime picker matches what shows here.
  return new Date(iso).toLocaleString('en-US', {
    timeZone: 'America/New_York',
    month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', timeZoneName: 'short',
  })
}

const statCards = computed(() => [
  { label: 'Total Vendors', value: props.stats.totalVendors || 0 },
  { label: 'Total Products', value: props.stats.totalProducts || 0 },
  { label: 'Pending Reviews', value: props.stats.pendingReviews || 0 },
  { label: 'Users', value: props.stats.totalUsers || 0 },
])

const quickActions = [
  { href: '/admin/vendors/create', label: 'Add Vendor', icon: '+' },
  { href: '/admin/products', label: 'Products', icon: '📦' },
  { href: '/admin/deals', label: 'Create Deal', icon: '🏷️' },
  { href: '/admin/banners', label: 'Banner Ads', icon: '📢' },
  { href: '/admin/staged-products', label: 'Review Staged', icon: '✓' },
  { href: '/admin/blogs/create', label: 'Write Blog', icon: '✍️' },
]
</script>
