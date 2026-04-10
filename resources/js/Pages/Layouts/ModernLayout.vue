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
    <footer class="mt-16 lg:mt-24 bg-[#0A0B0E] text-white">
      <!-- Top section: brand + newsletter -->
      <div class="border-b border-white/[0.06]">
        <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-12 lg:py-16">
          <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
            <div class="max-w-md">
              <img :src="'/images/logo.png'" alt="PeptideMaps" class="h-10 lg:h-12 brightness-0 invert mb-4" />
              <p class="text-[15px] text-white/50 leading-relaxed">
                The definitive platform for research peptide discovery. Verified vendors, lab-tested compounds, transparent data.
              </p>
            </div>
            <!-- Newsletter -->
            <div class="w-full lg:w-auto lg:min-w-[380px]">
              <h4 class="text-[13px] font-semibold text-white mb-2">Stay in the loop</h4>
              <p class="text-[13px] text-white/40 mb-3">Get weekly research updates and vendor news.</p>
              <form @submit.prevent="subscribeNewsletter" class="flex gap-2">
                <input
                  v-model="newsletterEmail"
                  type="email"
                  placeholder="you@example.com"
                  class="flex-1 h-10 px-4 bg-white/[0.06] border border-white/[0.08] text-sm text-white placeholder:text-white/30 focus:outline-none focus:border-white/20 focus:ring-1 focus:ring-white/10 transition-colors"
                  required
                />
                <button type="submit" :disabled="newsletterLoading" class="h-10 px-5 bg-white text-[#0A0B0E] text-[13px] font-semibold hover:bg-white/90 transition-colors flex-shrink-0 disabled:opacity-60">
                  {{ newsletterLoading ? '...' : 'Subscribe' }}
                </button>
              </form>
              <p v-if="newsletterMsg" :class="['text-[12px] mt-2', newsletterOk ? 'text-emerald-400' : 'text-red-400']">{{ newsletterMsg }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Middle section: link columns -->
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-10 lg:py-14">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 lg:gap-12">
          <div v-for="col in footerColumns" :key="col.title">
            <h4 class="text-[11px] uppercase tracking-[0.1em] font-semibold text-white/30 mb-4">
              {{ col.title }}
            </h4>
            <ul class="space-y-2.5">
              <li v-for="link in col.links" :key="link.href">
                <a
                  :href="link.href"
                  class="text-[13px] text-white/50 hover:text-white transition-colors"
                >
                  {{ link.label }}
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="border-t border-white/[0.06]">
        <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-5 lg:py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-[12px] text-white/25 ui-mono">
            © {{ currentYear }} PeptideMaps. For research use only.
          </p>
          <div class="flex items-center gap-5">
            <!-- Social icons -->
            <div class="flex items-center gap-3">
              <a href="#" class="text-white/25 hover:text-white/60 transition-colors" aria-label="Twitter / X">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
              </a>
              <a href="#" class="text-white/25 hover:text-white/60 transition-colors" aria-label="Discord">
                <svg class="w-4.5 h-4.5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03z"/></svg>
              </a>
              <a href="#" class="text-white/25 hover:text-white/60 transition-colors" aria-label="LinkedIn">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
              </a>
              <a href="#" class="text-white/25 hover:text-white/60 transition-colors" aria-label="GitHub">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
              </a>
            </div>
            <span class="w-px h-3 bg-white/10" />
            <div class="flex items-center gap-4 text-[12px] text-white/25">
              <a href="/privacy" class="hover:text-white/50 transition-colors">Privacy</a>
              <a href="/terms" class="hover:text-white/50 transition-colors">Terms</a>
              <a href="/disclaimer" class="hover:text-white/50 transition-colors">Disclaimer</a>
            </div>
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
      { href: '/education', label: 'Peptide guides' },
      { href: '/news', label: 'Research papers' },
      { href: '/blogs', label: 'Blog' },
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
