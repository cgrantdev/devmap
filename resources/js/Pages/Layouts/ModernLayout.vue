<template>
  <div class="min-h-screen bg-[color:var(--color-bg)] text-[color:var(--color-ink)] antialiased">
    <!-- Sticky glass nav -->
    <header
      :class="[
        'fixed top-0 left-0 right-0 z-50 transition-all duration-[200ms] ease-out',
        scrolled ? 'bg-white/50 backdrop-blur-md border-b border-[color:var(--color-hairline)]/50' : 'bg-transparent',
      ]"
    >
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 h-16 flex items-center gap-8">
        <!-- Logo -->
        <a href="/" class="ui-focus flex items-center gap-2 group">
          <div class="w-8 h-8 rounded-[8px] ui-gradient-bg flex items-center justify-center">
            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2L4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3z"/>
              <path d="M9.5 12.5l2 2 4-4.5"/>
            </svg>
          </div>
          <span class="ui-display text-[17px] font-semibold tracking-tight text-[color:var(--color-ink)]">
            PeptideMap
          </span>
        </a>

        <!-- Primary nav -->
        <nav class="hidden lg:flex items-center gap-1 text-sm font-medium">
          <a
            v-for="link in navLinks"
            :key="link.href"
            :href="link.href"
            class="ui-focus px-3 py-1.5 rounded-[8px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-[color:var(--color-hairline-soft)] transition-colors"
          >
            {{ link.label }}
          </a>
        </nav>

        <!-- Right side: search + country + auth -->
        <div class="ml-auto flex items-center gap-3">
          <div class="hidden md:block">
            <SearchPalette />
          </div>
          <CountrySelector class="hidden sm:block" />
          <Button as="a" href="/login" variant="ghost" size="sm">Sign in</Button>
          <Button as="a" href="/become-a-vendor" variant="secondary" size="sm" class="hidden lg:inline-flex">
            For vendors
          </Button>
        </div>
      </div>
    </header>

    <!-- Page content -->
    <main class="pt-16">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="mt-32 border-t border-[color:var(--color-hairline)] bg-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-16">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-10">
          <!-- Brand column -->
          <div class="col-span-2 lg:col-span-2">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-8 h-8 rounded-[8px] ui-gradient-bg flex items-center justify-center">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2L4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3z"/>
                  <path d="M9.5 12.5l2 2 4-4.5"/>
                </svg>
              </div>
              <span class="ui-display text-base font-semibold">PeptideMap</span>
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
        <div class="mt-16 pt-8 border-t border-[color:var(--color-hairline)] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
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
import { ref, onMounted, onUnmounted } from 'vue'
import Button from '@/components/ui/Button.vue'
import SearchPalette from '@/components/ui/SearchPalette.vue'
import CountrySelector from '@/components/ui/CountrySelector.vue'

const scrolled = ref(false)
const currentYear = new Date().getFullYear()

const navLinks = [
  { href: '/vendors', label: 'Vendors' },
  { href: '/products', label: 'Compounds' },
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

onMounted(() => {
  handleScroll()
  window.addEventListener('scroll', handleScroll, { passive: true })
})
onUnmounted(() => window.removeEventListener('scroll', handleScroll))
</script>
