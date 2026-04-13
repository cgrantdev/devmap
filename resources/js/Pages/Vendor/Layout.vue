<template>
  <div class="min-h-screen flex flex-col bg-[color:var(--color-bg)]">
    <!-- Impersonation banner -->
    <div v-if="$page.props.impersonating" class="bg-gradient-to-r from-[#F59E0B] to-[#D97706] text-white text-center py-2 px-4 text-[12px] font-semibold flex items-center justify-center gap-3 z-50">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      Viewing as vendor — {{ $page.props.auth?.user?.name || 'vendor' }}
      <a href="/admin/stop-impersonating" class="ml-2 inline-flex items-center gap-1 px-3 py-1 rounded-md bg-white/20 hover:bg-white/30 transition-colors text-white text-[11px] font-bold uppercase tracking-wider">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        Return to admin
      </a>
    </div>

    <div class="flex flex-1">
      <!-- Sidebar -->
      <aside class="w-[240px] flex-shrink-0 bg-[color:var(--color-ink)] flex flex-col">
        <!-- Brand -->
        <div class="px-5 py-5 border-b border-white/10">
          <div class="text-[15px] font-semibold text-white">Vendor Panel</div>
          <div class="text-[11px] text-white/40 mt-0.5">PeptideMap</div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-4 space-y-0.5">
          <NavItem href="/vendor/dashboard" icon="dashboard" label="Dashboard" />
          <NavItem href="/vendor/profile" icon="user" label="Profile & Branding" />
          <NavItem href="/vendor/products" icon="products" label="Products" />
          <NavItem href="/vendor/reviews" icon="reviews" label="Reviews" :badge="$page.props.approved_reviews_count" />
          <NavItem href="/vendor/storefront-analytics" icon="analytics" label="Storefront Analytics" />
          <NavItem href="/vendor/advertisement-analytics" icon="ads" label="Ad Analytics" />
          <NavItem href="/vendor/integrations" icon="integrations" label="Integrations" />
        </nav>

        <!-- Bottom -->
        <div class="px-3 py-4 border-t border-white/10">
          <form @submit.prevent="logout">
            <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-[8px] text-[13px] text-white/50 hover:text-white/80 hover:bg-white/5 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/></svg>
              Logout
            </button>
          </form>
        </div>
      </aside>

      <!-- Main content -->
      <main class="flex-1 overflow-auto">
        <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-8">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'
import { h } from 'vue'

const form = useForm({
  _token: usePage().props.csrf_token
})

function logout() {
  form.post('/vendor/logout', {
    onSuccess: () => {
      window.location.href = '/login'
    }
  })
}

// Inline NavItem component
const NavItem = {
  props: ['href', 'icon', 'label', 'badge'],
  setup(props) {
    const page = usePage()
    const isActive = () => {
      if (props.href === '/vendor/dashboard') return page.url === '/vendor/dashboard'
      return page.url.startsWith(props.href)
    }

    const icons = {
      dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1',
      user: 'M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2M12 3a4 4 0 100 8 4 4 0 000-8',
      products: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
      reviews: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
      analytics: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
      ads: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
      integrations: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
    }

    return () => h(Link, {
      href: props.href,
      class: [
        'w-full flex items-center gap-2.5 px-3 py-2.5 rounded-[8px] text-[13px] font-medium transition-all',
        isActive()
          ? 'bg-white/10 text-white'
          : 'text-white/50 hover:text-white/80 hover:bg-white/5',
      ].join(' '),
    }, {
      default: () => [
        h('svg', {
          class: 'w-4 h-4 flex-shrink-0',
          fill: 'none',
          stroke: 'currentColor',
          'stroke-width': '1.5',
          viewBox: '0 0 24 24',
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round',
          innerHTML: `<path d="${icons[props.icon] || icons.dashboard}"/>`,
        }),
        h('span', { class: 'truncate' }, props.label),
        props.badge && props.badge > 0
          ? h('span', {
              class: 'ml-auto flex-shrink-0 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-[color:var(--color-caution)] text-[color:var(--color-ink)] text-[10px] font-bold px-1',
            }, String(props.badge))
          : null,
      ],
    })
  },
}
</script>
