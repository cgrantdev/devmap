<template>
  <div class="min-h-screen bg-[color:var(--color-bg)] text-[color:var(--color-ink)] antialiased">
    <!-- Notice bar -->
    <div v-if="showNotice" class="fixed top-0 left-0 right-0 z-[60] bg-[#0A0B0E] text-white">
      <div class="max-w-[1280px] mx-auto px-4 lg:px-10 h-9 flex items-center justify-center gap-1.5 sm:gap-2 text-[11px] sm:text-[13px] relative">
        <template v-if="isDemoHost">
          <span class="text-white/50 hidden sm:inline">👋</span>
          <span class="text-white/70 truncate min-w-0"><span class="hidden sm:inline">You're previewing the PeptideMap demo. </span><span class="sm:hidden">Demo — </span>All vendors and data shown are fictional.</span>
          <button v-if="!isLoggedIn" @click="tryVendorDashboard" class="font-semibold text-[color:var(--color-accent-400)] hover:text-white transition-colors flex-shrink-0"><span class="hidden sm:inline">Try the vendor dashboard </span><span class="sm:hidden">Try dashboard </span>→</button>
        </template>
        <template v-else>
          <span class="text-white/50 hidden sm:inline">🚀</span>
          <span class="text-white/70 truncate min-w-0"><span class="hidden sm:inline">Now in beta — vendors list </span><span class="sm:hidden">Beta — vendors list </span><strong class="text-white font-semibold">free</strong> during launch.</span>
          <a href="/become-a-vendor" class="font-semibold text-[color:var(--color-accent-400)] hover:text-white transition-colors flex-shrink-0">Get Listed →</a>
        </template>
        <button @click="showNotice = false" class="text-white/30 hover:text-white/60 transition-colors p-1 flex-shrink-0 ml-1 sm:ml-2 sm:absolute sm:right-3 lg:right-6">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
    </div>

    <!-- Sticky glass nav.
         Mobile: always a frosted-glass surface so the hamburger + logo
         have contrast against the (often dark) hero image at page load.
         Desktop: transparent until scrolled, so the hero can run full-bleed
         under the nav without the bar feeling heavy. -->
    <header
      :class="[
        'fixed left-0 right-0 z-50 transition-all duration-[200ms] ease-out',
        showNotice ? 'top-9' : 'top-0',
        (scrolled || mobileOpen)
          ? 'bg-white/85 backdrop-blur-xl shadow-[0_1px_2px_rgba(0,0,0,0.04)] border-b border-black/[0.04]'
          : 'bg-white/85 backdrop-blur-xl lg:bg-transparent lg:backdrop-blur-none lg:shadow-none lg:border-b-0',
      ]"
    >
      <div class="max-w-[1280px] mx-auto px-4 lg:px-10 h-14 lg:h-16 flex items-center gap-3 lg:gap-8">
        <!-- Logo -->
        <a href="/" class="ui-focus flex items-center flex-shrink-0 min-w-0">
          <img :src="'/images/logo.png?v=2'" alt="Peptidemap" class="h-7 lg:h-9 brightness-0 translate-y-[1px]" />
        </a>

        <!-- Primary nav (desktop) -->
        <nav class="hidden lg:flex items-center gap-1 text-[13px] font-medium">
          <a
            v-for="link in navLinks"
            :key="link.href"
            :href="link.href"
            class="ui-focus px-3 py-1.5 rounded-[8px] transition-colors duration-200 text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-black/[0.04]"
          >
            {{ link.label }}
          </a>
        </nav>

        <!-- Right side -->
        <div class="ml-auto flex items-center gap-1 sm:gap-2 lg:gap-3 flex-shrink-0">
          <!-- Full search bar (desktop / lg+ only) -->
          <SearchPalette class="hidden lg:block" />

          <!-- Search icon (under lg — opens the SearchPalette modal directly;
               dropped the inline slide-down bar that was overflowing on mobile) -->
          <button
            @click="openSearch"
            class="lg:hidden p-2 rounded-[8px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-black/[0.04] transition-colors"
            aria-label="Search"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </button>

          <!-- View Admin (staff CTA) — replaces Get Listed for admin/admin_viewer -->
          <a
            v-if="isStaff"
            href="/admin/dashboard"
            class="ui-focus hidden sm:inline-flex items-center gap-1.5 h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-slate-800 to-slate-900 shadow-[0_1px_2px_rgba(10,11,14,0.08),0_4px_12px_-4px_rgba(15,23,42,0.3)] hover:-translate-y-[0.5px] hover:shadow-[0_1px_2px_rgba(10,11,14,0.1),0_6px_16px_-4px_rgba(15,23,42,0.4)] transition-all"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            View Admin
          </a>

          <!-- Try Vendor Dashboard (demo subdomain only, when not staff/logged-in) -->
          <button
            v-else-if="isDemoHost && !isLoggedIn"
            @click="tryVendorDashboard"
            class="ui-focus hidden sm:inline-flex items-center gap-1.5 h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-[0_1px_2px_rgba(10,11,14,0.08),0_4px_12px_-4px_rgba(79,70,229,0.3)] hover:-translate-y-[0.5px] hover:shadow-[0_1px_2px_rgba(10,11,14,0.1),0_6px_16px_-4px_rgba(79,70,229,0.4)] transition-all"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Try Vendor Dashboard
          </button>

          <!-- Get Listed (primary CTA) — default for everyone else -->
          <a
            v-else
            href="/become-a-vendor"
            class="ui-focus hidden sm:inline-flex items-center gap-1.5 h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-[0_1px_2px_rgba(10,11,14,0.08),0_4px_12px_-4px_rgba(79,70,229,0.3)] hover:-translate-y-[0.5px] hover:shadow-[0_1px_2px_rgba(10,11,14,0.1),0_6px_16px_-4px_rgba(79,70,229,0.4)] transition-all"
          >
            Get Listed
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>

          <!-- Hamburger (below lg) -->
          <button
            @click="mobileOpen = !mobileOpen"
            class="lg:hidden p-2 rounded-[8px] border border-[color:var(--color-hairline)] text-[color:var(--color-ink)] hover:bg-black/[0.04] transition-colors"
            :aria-expanded="mobileOpen"
            aria-label="Open menu"
          >
            <svg v-if="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- Hidden SearchPalette instance that mobile can open programmatically
           from the header magnifier icon. Kept invisible; only its open state matters. -->
      <SearchPalette ref="mobileSearchRef" class="hidden" />
    </header>

    <!-- Mobile nav overlay -->
    <Transition name="mobile-nav">
      <div v-if="mobileOpen" class="fixed inset-0 z-40 lg:hidden" @click="mobileOpen = false">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" />
        <!-- Drawer — full-height right side, ~85% width capped at 360px -->
        <div
          class="absolute top-0 right-0 bottom-0 w-[85%] max-w-[360px] bg-white shadow-2xl flex flex-col"
          :class="showNotice ? 'pt-9' : 'pt-0'"
          @click.stop
        >
          <!-- Drawer header: logo + close -->
          <div class="flex-shrink-0 flex items-center justify-between px-5 h-14 border-b border-[color:var(--color-hairline)]">
            <img :src="'/images/logo.png?v=2'" alt="PeptideMap" class="h-7 brightness-0" />
            <button
              @click="mobileOpen = false"
              class="p-2 -mr-2 rounded-[8px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-black/[0.04] transition-colors"
              aria-label="Close menu"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Search built into the drawer -->
          <div class="flex-shrink-0 px-5 py-4 border-b border-[color:var(--color-hairline)]">
            <SearchPalette @navigate="mobileOpen = false" />
          </div>

          <!-- Nav links -->
          <nav class="flex-1 overflow-y-auto px-3 py-3">
            <div class="text-[10px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] px-3 py-2">Browse</div>
            <a
              v-for="link in navLinks"
              :key="link.href"
              :href="link.href"
              class="ui-focus flex items-center justify-between px-3 py-3 rounded-[10px] text-[15px] font-medium text-[color:var(--color-ink)] hover:bg-black/[0.04] transition-colors"
              @click="mobileOpen = false"
            >
              <span>{{ link.label }}</span>
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </a>
          </nav>

          <!-- Footer CTA pinned to bottom -->
          <div class="flex-shrink-0 px-5 py-4 border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <a
              v-if="isStaff"
              href="/admin/dashboard"
              class="ui-focus flex items-center justify-center gap-2 h-11 rounded-[10px] text-[14px] font-semibold text-white bg-gradient-to-b from-slate-800 to-slate-900 shadow-sm transition-all hover:-translate-y-[0.5px]"
              @click="mobileOpen = false"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
              View Admin
            </a>
            <button
              v-else-if="isDemoHost && !isLoggedIn"
              @click="() => { mobileOpen = false; tryVendorDashboard() }"
              class="ui-focus w-full flex items-center justify-center gap-2 h-11 rounded-[10px] text-[14px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm transition-all hover:-translate-y-[0.5px]"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
              Try Vendor Dashboard
            </button>
            <a
              v-else
              href="/become-a-vendor"
              class="ui-focus flex items-center justify-center gap-2 h-11 rounded-[10px] text-[14px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm transition-all hover:-translate-y-[0.5px]"
              @click="mobileOpen = false"
            >
              Get Listed
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </a>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Page content -->
    <main :class="showNotice ? 'pt-[86px] lg:pt-[100px]' : 'pt-14 lg:pt-16'">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-[#0A0B0E] text-white">
      <!-- Newsletter + CTA hero area -->
      <div class="relative overflow-hidden">
        <div class="absolute inset-0" :style="{ background: 'radial-gradient(ellipse 800px 400px at 50% 0%, rgba(99,102,241,0.12) 0%, transparent 70%)' }" />
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[120%] h-px bg-gradient-to-r from-transparent via-white/10 to-transparent" />

        <div class="relative max-w-[1280px] mx-auto px-5 lg:px-10 pt-16 lg:pt-20 pb-12 lg:pb-16">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-20">
            <!-- Left: brand + newsletter -->
            <div>
              <img :src="'/images/logo.png?v=2'" alt="PeptideMaps" class="h-10 brightness-0 invert mb-5" />
              <p class="text-[15px] text-white/45 leading-relaxed mb-8 max-w-md">
                The definitive platform for research peptide discovery. Verified vendors, lab-tested compounds, transparent data.
              </p>
              <div>
                <h4 class="text-[13px] font-semibold text-white mb-3">Get research updates</h4>
                <form @submit.prevent="subscribeNewsletter" class="flex gap-0 max-w-sm">
                  <input
                    v-model="newsletterEmail"
                    type="email"
                    placeholder="you@example.com"
                    class="flex-1 h-11 px-4 bg-white/[0.06] border border-white/[0.1] border-r-0 text-sm text-white placeholder:text-white/25 focus:outline-none focus:border-white/20 transition-colors"
                    required
                  />
                  <button type="submit" :disabled="newsletterLoading" class="h-11 px-6 bg-white text-[#0A0B0E] text-[13px] font-semibold hover:bg-white/90 transition-colors flex-shrink-0 disabled:opacity-60">
                    {{ newsletterLoading ? '...' : 'Subscribe' }}
                  </button>
                </form>
                <p v-if="newsletterMsg" :class="['text-[12px] mt-2', newsletterOk ? 'text-emerald-400' : 'text-red-400']">{{ newsletterMsg }}</p>
              </div>
            </div>

            <!-- Right: link columns (2x2 grid) -->
            <div class="grid grid-cols-2 gap-8">
              <div v-for="col in footerColumns" :key="col.title">
                <h4 class="text-[11px] uppercase tracking-[0.1em] font-semibold text-white/30 mb-4">
                  {{ col.title }}
                </h4>
                <ul class="space-y-2.5">
                  <li v-for="link in col.links" :key="link.href">
                    <a :href="link.href" class="text-[13px] text-white/50 hover:text-white transition-colors">
                      {{ link.label }}
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RUO notice -->
      <div class="border-t border-white/[0.06]">
        <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-4">
          <p class="text-[11px] text-white/25 leading-relaxed">
            <strong class="text-white/35">Research Use Only (RUO).</strong> All products listed on PeptideMap are intended for laboratory and research purposes only. They are not intended for human consumption, therapeutic use, or self-administration. PeptideMap is a directory and comparison service — we do not sell, distribute, or endorse peptides for any non-research purpose. Always comply with applicable laws and regulations.
          </p>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="border-t border-white/[0.06]">
        <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-5 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-[12px] text-white/20 ui-mono">
            © {{ currentYear }} PeptideMaps
          </p>
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-3">
              <a href="#" class="text-white/20 hover:text-white/50 transition-colors" aria-label="X"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
              <a href="#" class="text-white/20 hover:text-white/50 transition-colors" aria-label="Discord"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03z"/></svg></a>
              <a href="#" class="text-white/20 hover:text-white/50 transition-colors" aria-label="LinkedIn"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
            </div>
            <span class="w-px h-3 bg-white/[0.06]" />
            <div class="flex items-center gap-4 text-[12px] text-white/20">
              <a href="/privacy" class="hover:text-white/40 transition-colors">Privacy</a>
              <a href="/terms" class="hover:text-white/40 transition-colors">Terms</a>
              <a href="/disclaimer" class="hover:text-white/40 transition-colors">Disclaimer</a>
            </div>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Button from '@/components/ui/Button.vue'
import SearchPalette from '@/components/ui/SearchPalette.vue'
import CountrySelector from '@/components/ui/CountrySelector.vue'

const page = usePage()
const isStaff = computed(() => {
  const role = page.props.auth?.user?.role
  return role === 'admin' || role === 'admin_viewer'
})
const isDemoHost = computed(() => page.props.is_demo_host === true)
const isLoggedIn = computed(() => !!page.props.auth?.user)

function tryVendorDashboard() {
  // Posts to /demo/preview-vendor which auto-logs in as Helix Research and
  // redirects to /vendor/dashboard with a "demo preview" banner.
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = '/demo/preview-vendor'
  const token = document.createElement('input')
  token.type = 'hidden'
  token.name = '_token'
  token.value = page.props.csrf_token
  form.appendChild(token)
  document.body.appendChild(form)
  form.submit()
}

const scrolled = ref(false)
const mobileOpen = ref(false)
const mobileSearchRef = ref(null)
function openSearch() {
  mobileSearchRef.value?.show?.()
}
const showNotice = ref(true)
const currentYear = new Date().getFullYear()

// Newsletter
const newsletterEmail = ref('')
const newsletterMsg = ref('')
const newsletterOk = ref(false)
const newsletterLoading = ref(false)

async function subscribeNewsletter() {
  if (!newsletterEmail.value) return
  newsletterLoading.value = true
  newsletterMsg.value = ''
  try {
    const res = await fetch('/api/subscribe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email: newsletterEmail.value, source: 'footer' }),
    })
    const data = await res.json()
    if (res.ok) {
      newsletterMsg.value = "You're on the list!"
      newsletterOk.value = true
      newsletterEmail.value = ''
    } else {
      newsletterMsg.value = data.errors?.email?.[0] || 'Something went wrong.'
      newsletterOk.value = false
    }
  } catch {
    newsletterMsg.value = 'Network error. Try again.'
    newsletterOk.value = false
  }
  newsletterLoading.value = false
}

const navLinks = [
  { href: '/vendors', label: 'Vendors' },
  { href: '/products', label: 'Peptides' },
  { href: '/compare', label: 'Compare' },
  { href: '/encyclopedia', label: 'Encyclopedia' },
  { href: '/calculator', label: 'Calculator' },
  { href: '/news', label: 'Research' },
]

const footerColumns = [
  {
    title: 'Discover',
    links: [
      { href: '/vendors', label: 'All vendors' },
      { href: '/products', label: 'All compounds' },
      { href: '/compare', label: 'Price comparison' },
      { href: '/deals', label: 'Deals & offers' },
    ],
  },
  {
    title: 'Learn',
    links: [
      { href: '/encyclopedia', label: 'Encyclopedia' },
      { href: '/news', label: 'Research' },
      { href: '/blogs', label: 'Blog' },
      { href: '/calculator', label: 'Peptide calculator' },
    ],
  },
  {
    title: 'For vendors',
    links: [
      { href: '/become-a-vendor', label: 'Get listed' },
      { href: '/vendor/login', label: 'Vendor dashboard' },
    ],
  },
  {
    title: 'Company',
    links: [
      { href: '/about', label: 'About' },
      { href: '/contact', label: 'Contact' },
      { href: '/privacy', label: 'Privacy policy' },
      { href: '/terms', label: 'Terms of service' },
    ],
  },
]

function handleScroll() {
  scrolled.value = window.scrollY > 12
}

// Lock body scroll when mobile nav is open
watch(mobileOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
  // Close search when nav opens
  if (open) showMobileSearch.value = false
})

onMounted(() => {
  handleScroll()
  window.addEventListener('scroll', handleScroll, { passive: true })
})
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.mobile-nav-enter-active,
.mobile-nav-leave-active {
  transition: opacity 0.2s ease;
}
.mobile-nav-enter-active > div:last-child,
.mobile-nav-leave-active > div:last-child {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.mobile-nav-enter-from,
.mobile-nav-leave-to {
  opacity: 0;
}
.mobile-nav-enter-from > div:last-child {
  transform: translateX(100%);
}
.mobile-nav-leave-to > div:last-child {
  transform: translateX(100%);
}
</style>
