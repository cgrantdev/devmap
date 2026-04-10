<template>
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-60 bg-[#0F172A] text-white flex-shrink-0 flex flex-col sticky top-0 h-screen overflow-y-auto">
      <!-- Logo -->
      <div class="px-5 py-4 border-b border-white/[0.06]">
        <a href="/admin/dashboard" class="flex items-center gap-2.5">
          <img src="/images/logo.png" alt="PeptideMaps" class="h-6 brightness-0 invert" />
          <span class="text-[10px] text-white/40 uppercase tracking-[0.1em] border-l border-white/10 pl-2.5 ml-0.5">Admin</span>
        </a>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-6">
        <!-- Overview -->
        <div>
          <NavItem href="/admin/dashboard" icon="dashboard" label="Dashboard" />
          <NavItem href="/admin/analytics" icon="chart" label="Analytics" />
        </div>

        <!-- Catalog -->
        <div>
          <div class="px-3 mb-2 text-[10px] uppercase tracking-[0.12em] font-semibold text-white/30">Catalog</div>
          <NavItem href="/admin/vendors" icon="store" label="Vendors" :badge="$page.props.pending_vendors_count" badge-color="red" />
          <NavItem href="/admin/products" icon="package" label="Products" />
          <NavItem href="/admin/categories" icon="folder" label="Categories" />
          <NavItem href="/admin/deals" icon="tag" label="Deals & Coupons" />
        </div>

        <!-- Ingestion -->
        <div>
          <div class="px-3 mb-2 text-[10px] uppercase tracking-[0.12em] font-semibold text-white/30">Ingestion</div>
          <NavItem href="/admin/product-scraping" icon="refresh" label="Scraping" />
          <NavItem href="/admin/staged-products" icon="clipboard" label="Staged Products" />
        </div>

        <!-- Content -->
        <div>
          <div class="px-3 mb-2 text-[10px] uppercase tracking-[0.12em] font-semibold text-white/30">Content</div>
          <NavItem href="/admin/blogs" icon="file" label="Blog Posts" />
          <NavItem href="/admin/education-posts" icon="file" label="Education Posts" />
          <NavItem href="/admin/encyclopedia-entries" icon="book" label="Encyclopedia" />
          <NavItem href="/admin/research" icon="file" label="Research" />
          <NavItem href="/admin/educational-guides" icon="file" label="Guides" />
        </div>

        <!-- Marketing -->
        <div>
          <div class="px-3 mb-2 text-[10px] uppercase tracking-[0.12em] font-semibold text-white/30">Marketing</div>
          <NavItem href="/admin/banners" icon="image" label="Banner Ads" />
          <NavItem href="/admin/reviews" icon="message" label="Reviews" :badge="$page.props.pending_reviews_count" badge-color="yellow" />
        </div>

        <!-- System -->
        <div>
          <div class="px-3 mb-2 text-[10px] uppercase tracking-[0.12em] font-semibold text-white/30">System</div>
          <NavItem href="/admin/users" icon="users" label="Users" />
          <NavItem href="/admin/settings" icon="settings" label="Settings" />
        </div>
      </nav>

      <!-- Logout -->
      <div class="px-3 py-4 border-t border-white/[0.06]">
        <form @submit.prevent="logout">
          <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-[13px] text-white/50 hover:text-white hover:bg-white/[0.06] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
            </svg>
            Logout
          </button>
        </form>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 min-h-screen bg-[color:var(--color-bg)]">
      <div class="p-6 lg:p-8">
        <slot />
      </div>
    </main>

    <!-- Loading overlay -->
    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
      <div class="flex flex-col items-center">
        <svg class="animate-spin h-10 w-10 text-white mb-3" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
        </svg>
        <span class="text-white text-sm font-medium">{{ loadingMessage }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'
import { useAdminLoading } from '../../composables/useAdminLoading'
import { useToast } from '../../composables/useToast'
import { h, defineComponent } from 'vue'

const { isLoading, loadingMessage } = useAdminLoading()
const { toast } = useToast()

const page = usePage()

const form = useForm({
  _token: page.props.csrf_token
})

function logout() {
  form.post('/admin/logout', {
    onSuccess: () => { window.location.href = '/admin/login' }
  })
}

// Reusable nav item component
const NavItem = defineComponent({
  props: {
    href: String,
    icon: String,
    label: String,
    badge: { type: Number, default: 0 },
    badgeColor: { type: String, default: 'red' },
  },
  setup(props) {
    const icons = {
      dashboard: 'M3 3h7v9H3V3zm11 0h7v5h-7V3zm0 9h7v9h-7v-9zM3 16h7v5H3v-5z',
      chart: 'M3 3v18h18M18 9l-5 5-3-3-5 5',
      store: 'M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z',
      package: 'M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3zm0 9l8-4.5M12 12v9M4 7.5l8 4.5',
      folder: 'M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z',
      tag: 'M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82zM7 7h.01',
      refresh: 'M1 4v6h6M23 20v-6h-6M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15',
      clipboard: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
      file: 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8',
      book: 'M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z',
      image: 'M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2zm16 12l-3.09-3.09a2 2 0 00-2.82 0L6 21M9 9a2 2 0 11-4 0 2 2 0 014 0z',
      message: 'M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z',
      users: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zm14 14v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
      settings: 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z',
    }

    return () => {
      const isActive = page.url.startsWith(props.href)
      const badgeColors = { red: 'bg-red-500', yellow: 'bg-amber-500' }

      return h(Link, {
        href: props.href,
        class: [
          'w-full flex items-center gap-3 px-3 py-2 text-[13px] transition-colors',
          isActive
            ? 'bg-white/[0.08] text-white font-medium'
            : 'text-white/55 hover:text-white hover:bg-white/[0.04]',
        ].join(' '),
      }, () => [
        h('svg', {
          class: 'w-4 h-4 flex-shrink-0',
          viewBox: '0 0 24 24',
          fill: 'none',
          stroke: 'currentColor',
          'stroke-width': '2',
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          innerHTML: `<path d="${icons[props.icon] || icons.file}"/>`,
        }),
        h('span', { class: 'flex-1 truncate' }, props.label),
        props.badge > 0
          ? h('span', {
              class: `ml-auto text-[10px] font-bold px-1.5 py-0.5 text-white ${badgeColors[props.badgeColor] || 'bg-red-500'}`,
            }, String(props.badge))
          : null,
      ])
    }
  },
})
</script>
