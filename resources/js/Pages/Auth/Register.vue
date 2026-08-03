<template>
  <div class="min-h-screen flex">
    <!-- Left: branding panel (matches Login.vue) -->
    <div class="hidden lg:flex lg:w-1/2 bg-[#0F172A] relative overflow-hidden items-center justify-center">
      <div class="absolute inset-0" :style="{ background: 'radial-gradient(ellipse 800px 500px at 30% 40%, rgba(99,102,241,0.2) 0%, transparent 60%)' }" />
      <div class="absolute inset-0 opacity-[0.03]" :style="{ backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)', backgroundSize: '40px 40px' }" />
      <div class="relative z-10 max-w-md px-12">
        <a href="/" class="inline-block mb-8">
          <img :src="'/images/logo.png?v=2'" alt="Peptidemap" class="h-12 brightness-0 invert" />
        </a>
        <h2 class="text-white text-3xl font-semibold tracking-tight leading-tight mb-4" style="font-family: 'Inter Tight', sans-serif;">
          Compare verified peptide vendors — on your side.
        </h2>
        <p class="text-white/50 text-[15px] leading-relaxed mb-8">
          Free account to leave verified reviews, save vendors, and unlock PMAP coupons across the directory.
        </p>
        <ul class="space-y-3 text-white/70 text-[14px]">
          <li class="flex items-start gap-3">
            <svg class="w-4 h-4 mt-0.5 text-[#34d399] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Save products + get <strong class="text-white/90">price drop alerts</strong> across all vendors</span>
          </li>
          <li class="flex items-start gap-3">
            <svg class="w-4 h-4 mt-0.5 text-[#34d399] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Leave <strong class="text-white/90">verified reviews</strong> after purchasing from any vendor</span>
          </li>
          <li class="flex items-start gap-3">
            <svg class="w-4 h-4 mt-0.5 text-[#34d399] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Track <strong class="text-white/90">exclusive PMAP coupons</strong> across 40+ vendors</span>
          </li>
          <li class="flex items-start gap-3">
            <svg class="w-4 h-4 mt-0.5 text-[#34d399] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span><strong class="text-white/90">Free forever.</strong> No ads. Research use only.</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Right: register form -->
    <div class="flex-1 flex items-center justify-center px-6 py-12 bg-white">
      <div class="w-full max-w-sm">
        <!-- Mobile logo -->
        <a href="/" class="inline-block mb-8 lg:hidden">
          <img :src="'/images/logo.png?v=2'" alt="Peptidemap" class="h-10 brightness-0" />
        </a>

        <h1 class="text-2xl font-semibold tracking-tight text-[#0A0B0E] mb-1" style="font-family: 'Inter Tight', sans-serif;">Create your account</h1>
        <p class="text-sm text-[#52525B] mb-8">Free — takes 30 seconds. We'll email a confirmation link.</p>

        <!-- Error -->
        <div v-if="hasErrors" class="mb-6 px-4 py-3 bg-[#FEF2F2] border border-[#FECACA] text-[#991B1B] text-sm">
          <div v-for="(error, field) in form.errors" :key="field">
            {{ Array.isArray(error) ? error[0] : error }}
          </div>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-[12px] font-medium text-[#52525B] mb-1.5">Name</label>
            <input
              v-model="form.name"
              type="text"
              required
              autofocus
              class="w-full h-11 px-4 text-[15px] border border-[#E4E4E7] bg-white focus:border-[#6366F1] focus:outline-none focus:ring-2 focus:ring-[#6366F1]/15 transition-colors"
              placeholder="Your name"
            />
          </div>

          <div>
            <label class="block text-[12px] font-medium text-[#52525B] mb-1.5">Email</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full h-11 px-4 text-[15px] border border-[#E4E4E7] bg-white focus:border-[#6366F1] focus:outline-none focus:ring-2 focus:ring-[#6366F1]/15 transition-colors"
              placeholder="you@example.com"
            />
          </div>

          <div>
            <label class="block text-[12px] font-medium text-[#52525B] mb-1.5">Password</label>
            <input
              v-model="form.password"
              type="password"
              required
              class="w-full h-11 px-4 text-[15px] border border-[#E4E4E7] bg-white focus:border-[#6366F1] focus:outline-none focus:ring-2 focus:ring-[#6366F1]/15 transition-colors"
              placeholder="At least 8 characters"
            />
          </div>

          <div>
            <label class="block text-[12px] font-medium text-[#52525B] mb-1.5">Confirm password</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              required
              class="w-full h-11 px-4 text-[15px] border border-[#E4E4E7] bg-white focus:border-[#6366F1] focus:outline-none focus:ring-2 focus:ring-[#6366F1]/15 transition-colors"
              placeholder="Re-enter password"
            />
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full h-11 text-[15px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_1px_2px_rgba(10,11,14,0.08),0_8px_20px_-8px_rgba(79,70,229,0.4)] hover:-translate-y-[0.5px] active:translate-y-0 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? 'Creating account…' : 'Create free account' }}
          </button>

          <p class="text-[11px] text-[#A1A1AA] leading-relaxed">
            By creating an account you agree to our
            <a href="/terms" class="underline hover:text-[#52525B]">Terms</a>
            and
            <a href="/privacy" class="underline hover:text-[#52525B]">Privacy Policy</a>.
            Peptidemap is a research directory — all listings are for research use only.
          </p>
        </form>

        <div class="mt-8 pt-6 border-t border-[#E4E4E7] flex items-center justify-between text-sm">
          <span class="text-[#A1A1AA]">
            Already have an account?
            <Link :href="loginHref" class="text-[#6366F1] font-medium hover:text-[#4F46E5] ml-1">Sign in</Link>
          </span>
          <a href="/become-a-vendor" class="text-[#A1A1AA] hover:text-[#52525B]">
            List a vendor →
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'

const redirectTarget = usePage().props.redirect || null

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'customer',
  redirect: redirectTarget,
  _token: usePage().props.csrf_token,
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)

const loginHref = computed(() =>
  redirectTarget ? `/login?redirect=${encodeURIComponent(redirectTarget)}` : '/login'
)

function submit() {
  form.post('/register', {
    onError: () => {
      form.password = ''
      form.password_confirmation = ''
    },
  })
}
</script>
