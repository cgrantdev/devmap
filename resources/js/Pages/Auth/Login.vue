<template>
  <div class="min-h-screen flex">
    <!-- Left: branding panel -->
    <div class="hidden lg:flex lg:w-1/2 bg-[#0F172A] relative overflow-hidden items-center justify-center">
      <div class="absolute inset-0" :style="{ background: 'radial-gradient(ellipse 800px 500px at 30% 40%, rgba(99,102,241,0.2) 0%, transparent 60%)' }" />
      <div class="absolute inset-0 opacity-[0.03]" :style="{ backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)', backgroundSize: '40px 40px' }" />
      <div class="relative z-10 max-w-md px-12">
        <div class="flex items-center gap-3 mb-8">
          <div class="w-10 h-10 bg-gradient-to-br from-[#5B5FE8] to-[#4338CA] flex items-center justify-center">
            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2L4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3z"/><path d="M9.5 12.5l2 2 4-4.5"/>
            </svg>
          </div>
          <span class="text-white text-xl font-semibold tracking-tight" style="font-family: 'Inter Tight', sans-serif;">PeptideMap</span>
        </div>
        <h2 class="text-white text-3xl font-semibold tracking-tight leading-tight mb-4" style="font-family: 'Inter Tight', sans-serif;">
          The definitive platform for research peptide vendors.
        </h2>
        <p class="text-white/50 text-[15px] leading-relaxed">
          Compare verified suppliers, inspect lab testing, and discover new compounds — all in one place.
        </p>
      </div>
    </div>

    <!-- Right: login form -->
    <div class="flex-1 flex items-center justify-center px-6 py-12 bg-white">
      <div class="w-full max-w-sm">
        <!-- Mobile logo -->
        <div class="flex items-center gap-2.5 mb-8 lg:hidden">
          <div class="w-8 h-8 bg-gradient-to-br from-[#5B5FE8] to-[#4338CA] flex items-center justify-center">
            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2L4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3z"/><path d="M9.5 12.5l2 2 4-4.5"/>
            </svg>
          </div>
          <span class="text-lg font-semibold tracking-tight" style="font-family: 'Inter Tight', sans-serif;">PeptideMap</span>
        </div>

        <h1 class="text-2xl font-semibold tracking-tight text-[#0A0B0E] mb-1" style="font-family: 'Inter Tight', sans-serif;">Sign in</h1>
        <p class="text-sm text-[#52525B] mb-8">Enter your credentials to access your dashboard.</p>

        <!-- Error -->
        <div v-if="Object.keys(errors).length > 0" class="mb-6 px-4 py-3 bg-[#FEF2F2] border border-[#FECACA] text-[#991B1B] text-sm">
          <span v-for="(error, field) in errors" :key="field">{{ Array.isArray(error) ? error[0] : error }}</span>
        </div>

        <!-- Success -->
        <div v-if="$page.props.flash?.success" class="mb-6 px-4 py-3 bg-[#ECFDF5] border border-[#A7F3D0] text-[#065F46] text-sm">
          {{ $page.props.flash.success }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-[12px] font-medium text-[#52525B] mb-1.5">Email</label>
            <input
              v-model="form.email"
              type="email"
              required
              autofocus
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
              placeholder="••••••••"
            />
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full h-11 text-[15px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_1px_2px_rgba(10,11,14,0.08),0_8px_20px_-8px_rgba(79,70,229,0.4)] hover:-translate-y-[0.5px] active:translate-y-0 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? 'Signing in...' : 'Sign in' }}
          </button>
        </form>

        <div class="mt-8 pt-6 border-t border-[#E4E4E7] text-center">
          <p class="text-sm text-[#A1A1AA]">
            Want to list your peptides?
            <a href="/become-a-vendor" class="text-[#6366F1] font-medium hover:text-[#4F46E5]">Become a vendor</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'

const errors = usePage().props.errors || {}

const form = useForm({
  email: '',
  password: '',
})

function submit() {
  form.post('/login', {
    preserveScroll: true,
  })
}
</script>
