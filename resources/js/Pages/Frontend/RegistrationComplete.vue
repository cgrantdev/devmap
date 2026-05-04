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

        <!-- Optional: WordPress plugin (only when API not refused, since the
             plugin's whole job is sending API keys to PeptideMap) -->
        <div v-if="!refusedApi && connectionToken" class="mt-8 bg-white border border-slate-200 rounded-lg p-8 text-left">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-lg bg-slate-900 flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="21 12 16.5 14.6 16.5 19.79"/></svg>
            </div>
            <div>
              <h2 class="text-base font-semibold text-slate-900">One-click plugin (optional)</h2>
              <p class="text-xs text-slate-500 mt-0.5">For WooCommerce stores — installs the API connection automatically.</p>
            </div>
          </div>

          <p class="text-sm text-slate-600 leading-relaxed mb-4">
            We've made a small WordPress plugin that auto-generates a read-only WooCommerce API key and securely sends it to PeptideMap. Skip the manual key copying.
          </p>

          <!-- Connection token (the plugin needs this) -->
          <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-4">
            <div class="text-[10px] uppercase tracking-[0.08em] text-slate-500 font-semibold mb-1.5">Your connection token</div>
            <div class="flex items-center gap-2">
              <code class="flex-1 text-[12px] font-mono text-slate-800 bg-white px-3 py-2 rounded border border-slate-200 select-all break-all">{{ connectionToken }}</code>
              <button
                @click="copyToken"
                type="button"
                class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-700 bg-white hover:bg-slate-100 border border-slate-200 rounded transition-colors"
              >
                <svg v-if="!tokenCopied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                <svg v-else class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                {{ tokenCopied ? 'Copied' : 'Copy' }}
              </button>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">Keep this safe — you'll paste it into the plugin's settings page.</p>
          </div>

          <!-- Quick install steps -->
          <ol class="text-sm text-slate-700 space-y-2.5 mb-5">
            <li class="flex gap-2.5">
              <span class="text-slate-400 font-mono text-xs mt-0.5">1.</span>
              <span><a :href="pluginDownloadUrl" class="text-indigo-600 hover:text-indigo-800 font-medium underline underline-offset-2">Download peptidemap-connect.zip</a></span>
            </li>
            <li class="flex gap-2.5">
              <span class="text-slate-400 font-mono text-xs mt-0.5">2.</span>
              <span>In WordPress admin: <strong>Plugins → Add New → Upload Plugin</strong>, select the .zip, click Install Now.</span>
            </li>
            <li class="flex gap-2.5">
              <span class="text-slate-400 font-mono text-xs mt-0.5">3.</span>
              <span>Activate, then go to <strong>WooCommerce → PeptideMap</strong>.</span>
            </li>
            <li class="flex gap-2.5">
              <span class="text-slate-400 font-mono text-xs mt-0.5">4.</span>
              <span>Paste the token above and click <strong>Connect to PeptideMap</strong>.</span>
            </li>
          </ol>

          <a
            :href="pluginDownloadUrl"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
            Download plugin
          </a>
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
import { onMounted, ref } from 'vue'

const props = defineProps({
  company: { type: String, default: '' },
  email: { type: String, default: null },
  connectionToken: { type: String, default: null },
  refusedApi: { type: Boolean, default: false },
  pluginDownloadUrl: { type: String, default: '/downloads/peptidemap-connect.zip' },
})

const tokenCopied = ref(false)
async function copyToken() {
  try {
    await navigator.clipboard.writeText(props.connectionToken || '')
  } catch {
    const ta = document.createElement('textarea')
    ta.value = props.connectionToken || ''
    document.body.appendChild(ta)
    ta.select()
    try { document.execCommand('copy') } catch {}
    document.body.removeChild(ta)
  }
  tokenCopied.value = true
  setTimeout(() => { tokenCopied.value = false }, 1800)
}

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
