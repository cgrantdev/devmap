<template>
  <div class="min-h-screen bg-[color:var(--color-bg)] text-[color:var(--color-ink)] antialiased">
    <!-- Sticky glass nav -->
    <header
      :class="[
        'fixed top-0 left-0 right-0 z-50 transition-all duration-[200ms] ease-out',
        scrolled || mobileOpen ? 'bg-white/70 backdrop-blur-xl border-b border-black/[0.06] shadow-[0_1px_3px_rgba(0,0,0,0.04)]' : 'bg-transparent',
      ]"
    >
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 h-14 lg:h-16 flex items-center gap-8">
        <!-- Logo -->
        <a href="/" class="ui-focus flex items-center">
          <img :src="'/images/logo.png'" alt="PeptideMaps" class="h-8 lg:h-9 brightness-0 translate-y-[1px]" />
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
        <div class="ml-auto flex items-center gap-2 lg:gap-3">
          <!-- Search (desktop) -->
          <div class="hidden md:block">
            <SearchPalette />
          </div>
          <!-- Country (desktop) -->
          <CountrySelector class="hidden lg:block" />
          <!-- Sign in (desktop) -->
          <a
            href="/login"
            class="ui-focus hidden lg:inline-flex px-3 py-1.5 rounded-[8px] text-[13px] font-medium transition-colors duration-200 text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-black/[0.04]"
          >
            Sign in
          </a>
          <!-- For vendors (desktop) -->
          <a
            href="/become-a-vendor"
            class="ui-focus hidden lg:inline-flex items-center h-9 px-4 rounded-[9px] text-[13px] font-semibold transition-all duration-200 bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink)] shadow-[0_1px_2px_rgba(10,11,14,0.04)] hover:border-[color:var(--color-ink-subtle)]"
          >
            For vendors
          </a>

          <!-- Mobile search button -->
          <button
            @click="showMobileSearch = !showMobileSearch"
            class="lg:hidden p-2 -mr-1 rounded-[8px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-black/[0.04] transition-colors md:hidden"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </button>

          <!-- Hamburger -->
          <button
            @click="mobileOpen = !mobileOpen"
            class="lg:hidden p-2 -mr-1 rounded-[8px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-black/[0.04] transition-colors"
          >
            <svg v-if="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- Mobile search bar (slides down) -->
      <div v-if="showMobileSearch" class="md:hidden px-5 pb-3">
        <SearchPalette />
      </div>
    </header>

    <!-- Mobile nav overlay -->
    <Transition name="mobile-nav">
      <div v-if="mobileOpen" class="fixed inset-0 z-40 lg:hidden" @click="mobileOpen = false">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/20 backdrop-blur-sm" />
        <!-- Drawer -->
        <div class="absolute top-14 right-0 w-72 max-h-[calc(100vh-3.5rem)] bg-white border-l border-[color:var(--color-hairline)] shadow-2xl overflow-y-auto" @click.stop>
          <!-- Nav links -->
          <nav class="px-2 py-3">
            <a
              v-for="link in navLinks"
              :key="link.href"
              :href="link.href"
              class="flex items-center gap-3 px-4 py-3 rounded-[8px] text-[15px] font-medium text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-black/[0.04] transition-colors"
              @click="mobileOpen = false"
            >
              {{ link.label }}
            </a>
          </nav>

          <div class="border-t border-[color:var(--color-hairline)] mx-4" />

          <!-- Secondary links -->
          <div class="px-2 py-3">
            <a
              href="/login"
              class="flex items-center gap-3 px-4 py-3 rounded-[8px] text-[15px] font-medium text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-black/[0.04] transition-colors"
              @click="mobileOpen = false"
            >
              <svg class="w-4.5 h-4.5 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Sign in
            </a>
            <a
              href="/become-a-vendor"
              class="flex items-center gap-3 px-4 py-3 rounded-[8px] text-[15px] font-medium text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-black/[0.04] transition-colors"
              @click="mobileOpen = false"
            >
              <svg class="w-4.5 h-4.5 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              For vendors
            </a>
          </div>

          <div class="border-t border-[color:var(--color-hairline)] mx-4" />

          <!-- Country selector -->
          <div class="px-6 py-4">
            <CountrySelector />
          </div>
        </div>
      </div>
    </Transition>

    <!-- Page content -->
    <main class="pt-14 lg:pt-16">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="mt-20 lg:mt-32 border-t border-[color:var(--color-hairline)] bg-white">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-10 lg:py-16">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 lg:gap-10">
          <!-- Brand column -->
          <div class="col-span-2 lg:col-span-2">
            <div class="mb-4">
              <img :src="'/images/logo.png'" alt="PeptideMaps" class="h-8 lg:h-9 brightness-0" />
            </div>
            <p class="text-sm text-[color:var(--color-ink-muted)] leading-relaxed max-w-sm">
              The definitive platform for research peptide discovery. Verified vendors, lab-tested compounds, transparent data.
            </p>
          </div>

          <!-- Link columns -->
          <div v-for="col in footerColumns" :key="col.title">
            <h4 class="ui-display text-xs uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink)] mb-4">
              {{ col.title }}
            </h4>
            <ul class="space-y-2.5">
              <li v-for="link in col.links" :key="link.href">
                <a
                  :href="link.href"
                  class="ui-focus text-sm text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] transition-colors"
                >
                  {{ link.label }}
                </a>
              </li>
            </ul>
          </div>
        </div>

        <!-- Bottom bar -->
        <div class="mt-10 lg:mt-16 pt-6 lg:pt-8 border-t border-[color:var(--color-hairline)] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <p class="text-xs text-[color:var(--color-ink-subtle)] ui-mono">
            © {{ currentYear }} PeptideMap. For research use only.
          </p>
          <div class="flex items-center gap-5 text-xs text-[color:var(--color-ink-muted)]">
            <a href="/privacy" class="hover:text-[color:var(--color-ink)]">Privacy</a>
            <a href="/terms" class="hover:text-[color:var(--color-ink)]">Terms</a>
            <a href="/disclaimer" class="hover:text-[color:var(--color-ink)]">Disclaimer</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import Button from '@/components/ui/Button.vue'
import SearchPalette from '@/components/ui/SearchPalette.vue'
import CountrySelector from '@/components/ui/CountrySelector.vue'

const scrolled = ref(false)
const mobileOpen = ref(false)
const showMobileSearch = ref(false)
const currentYear = new Date().getFullYear()

const navLinks = [
  { href: '/vendors', label: 'Vendors' },
  { href: '/products', label: 'Peptides' },
  { href: '/compare', label: 'Compare' },
  { href: '/encyclopedia', label: 'Encyclopedia' },
  { href: '/news', label: 'Research' },
]

const footerColumns = [
  {
    title: 'Discover',
    links: [
      { href: '/vendors', label: 'All vendors' },
      { href: '/products', label: 'All compounds' },
      { href: '/compare', label: 'Compare' },
      { href: '/deals', label: 'Deals' },
    ],
  },
  {
    title: 'Learn',
    links: [
      { href: '/encyclopedia', label: 'Encyclopedia' },
      { href: '/education', label: 'Education' },
      { href: '/news', label: 'Research' },
      { href: '/blogs', label: 'Blog' },
    ],
  },
  {
    title: 'Company',
    links: [
      { href: '/become-a-vendor', label: 'For vendors' },
      { href: '/vendor/login', label: 'Vendor login' },
      { href: '/about', label: 'About' },
      { href: '/contact', label: 'Contact' },
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
