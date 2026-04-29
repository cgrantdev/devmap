<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Top bar -->
    <header class="bg-white border-b border-slate-200">
      <div class="max-w-5xl mx-auto px-6 py-4 flex items-center">
        <a href="/" class="flex items-center">
          <img :src="'/images/logo.png'" alt="PeptideMap" class="h-7" />
        </a>
      </div>
    </header>

    <!-- Main -->
    <main class="flex-1 flex items-center justify-center px-6 py-16">
      <div class="max-w-2xl w-full text-center">
        <!-- Success animation -->
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-100 mb-6 animate-pulse-soft">
          <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>

        <h1 class="text-3xl sm:text-4xl font-semibold text-slate-900 mb-4 tracking-tight">
          Thanks{{ company ? `, ${company}` : '' }}!
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed mb-2">
          Your registration has been received.
        </p>
        <p class="text-base text-slate-500 max-w-lg mx-auto leading-relaxed">
          Our team is reviewing your application and we'll be in touch at <strong v-if="email" class="text-slate-700">{{ email }}</strong><span v-else>the email you provided</span> within 1–2 business days.
        </p>

        <!-- What happens next -->
        <div class="mt-12 bg-white border border-slate-200 rounded-lg p-8 text-left">
          <h2 class="text-sm font-semibold uppercase tracking-[0.08em] text-slate-500 mb-5">What happens next</h2>
          <ol class="space-y-5">
            <li class="flex items-start gap-4">
              <span class="flex-shrink-0 w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-semibold">1</span>
              <div>
                <div class="text-sm font-medium text-slate-900">Email confirmation sent</div>
                <div class="text-sm text-slate-500 mt-1">A welcome email with your account details is on its way. Check your inbox (and spam folder) within the next few minutes.</div>
              </div>
            </li>
            <li class="flex items-start gap-4">
              <span class="flex-shrink-0 w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-semibold">2</span>
              <div>
                <div class="text-sm font-medium text-slate-900">Verification & approval</div>
                <div class="text-sm text-slate-500 mt-1">Our team reviews your store, verifies your REST API connection, and tests an initial product sync. This typically takes 1–2 business days.</div>
              </div>
            </li>
            <li class="flex items-start gap-4">
              <span class="flex-shrink-0 w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-semibold">3</span>
              <div>
                <div class="text-sm font-medium text-slate-900">Dashboard access</div>
                <div class="text-sm text-slate-500 mt-1">Once approved, you'll receive an email with a link to your vendor dashboard where you can manage products, view analytics, and respond to reviews.</div>
              </div>
            </li>
          </ol>
        </div>

        <!-- Support -->
        <div class="mt-8 text-sm text-slate-500">
          Questions in the meantime?
          <a href="mailto:vendors@peptidemap.com" class="text-slate-700 hover:text-slate-900 underline underline-offset-2">vendors@peptidemap.com</a>
        </div>

        <!-- Action -->
        <div class="mt-10">
          <a href="/" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-slate-700 hover:text-slate-900 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to PeptideMap
          </a>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-6 px-6">
      <div class="max-w-5xl mx-auto text-center text-xs text-slate-400 space-y-1">
        <p class="uppercase tracking-[0.18em] text-slate-500 font-medium">For Research Use Only</p>
        <p>© {{ new Date().getFullYear() }} PeptideMap. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

defineProps({
  company: { type: String, default: '' },
  email: { type: String, default: null },
})

// Clear any leftover signup draft from localStorage now that submission succeeded
onMounted(() => {
  try {
    localStorage.removeItem('pmap_join_signup_v1')
    localStorage.removeItem('pmap_become_vendor_signup_v1')
  } catch {}
})
</script>

<style scoped>
@keyframes pulse-soft {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}
.animate-pulse-soft {
  animation: pulse-soft 2.5s ease-in-out infinite;
}
</style>
