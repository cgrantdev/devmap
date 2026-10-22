<template>
  <div
    v-if="bar"
    class="fixed top-0 left-0 right-0 z-[9999] bg-[#1a1a1a] text-white text-[13px] h-8 flex items-center px-4 gap-4 shadow-[0_1px_3px_rgba(0,0,0,0.2)]"
    style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;"
  >
    <a href="/admin/dashboard" class="flex items-center gap-1.5 font-semibold hover:text-amber-300">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Peptidemap Admin
    </a>

    <span class="text-white/30">·</span>

    <a v-if="bar.edit_url" :href="bar.edit_url" class="flex items-center gap-1.5 text-white/90 hover:text-amber-300 font-medium">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit {{ bar.page_type || 'page' }}
    </a>
    <span v-else class="text-white/50 italic">No editable page</span>

    <span class="text-white/30">·</span>

    <a :href="analyticsHref" class="text-white/90 hover:text-amber-300 font-medium">Analytics</a>
    <a href="/admin/seo" class="text-white/90 hover:text-amber-300 font-medium">SEO</a>
    <a href="/admin/dev/console" class="text-white/90 hover:text-amber-300 font-medium hidden sm:inline">Dev Console</a>

    <div class="flex-1" />

    <span class="text-white/60 text-[12px] hidden md:inline">{{ bar.user_label }}</span>
    <form method="POST" action="/logout" class="inline">
      <input type="hidden" name="_token" :value="csrfToken" />
      <button type="submit" class="text-white/60 hover:text-white text-[12px]">Logout</button>
    </form>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const bar = computed(() => page.props.admin_bar || null)
const csrfToken = computed(() => page.props.csrf_token || '')
// Analytics link always deep-links to the current URL path so the
// admin lands on the per-page view, not the generic dashboard.
const analyticsHref = computed(() => {
  const path = typeof window !== 'undefined' ? window.location.pathname : '/'
  return '/admin/analytics/page?url=' + encodeURIComponent(path)
})
</script>
