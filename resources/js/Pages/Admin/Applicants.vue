<template>
  <AdminLayout>
    <PageHeader title="Applicants" :subtitle="`${applicants.length} pending vendor application${applicants.length === 1 ? '' : 's'}`">
    </PageHeader>

    <!-- Flash -->
    <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 bg-[color:var(--color-verified-bg)] border border-[#A7F3D0] text-[#065F46] text-sm">
      {{ $page.props.flash.success }}
    </div>
    <div v-if="$page.props.flash?.error" class="mb-4 px-4 py-3 bg-rose-50 border border-rose-200 text-rose-800 text-sm">
      {{ $page.props.flash.error }}
    </div>

    <!-- Empty state -->
    <div v-if="applicants.length === 0" class="bg-white border border-[color:var(--color-hairline)] py-16 text-center">
      <svg class="w-12 h-12 mx-auto mb-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      <h3 class="text-[color:var(--color-ink)] font-medium mb-1">All caught up</h3>
      <p class="text-sm text-[color:var(--color-ink-subtle)]">No pending vendor applications right now. New signups appear here for review.</p>
    </div>

    <!-- Applicant cards -->
    <div v-else class="space-y-4">
      <div
        v-for="applicant in applicants"
        :key="applicant.id"
        class="bg-white border border-[color:var(--color-hairline)] overflow-hidden"
      >
        <!-- Header row -->
        <div class="px-6 py-4 border-b border-[color:var(--color-hairline-soft)] flex items-start gap-4">
          <!-- Logo / initials -->
          <div class="w-12 h-12 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] flex items-center justify-center overflow-hidden">
            <img v-if="applicant.logo_url" :src="applicant.logo_url" :alt="applicant.name" class="w-full h-full object-contain p-0.5" />
            <span v-else class="text-xs font-bold text-[color:var(--color-ink-muted)]">{{ applicant.name.substring(0, 2).toUpperCase() }}</span>
          </div>

          <!-- Title block -->
          <div class="flex-1 min-w-0">
            <div class="flex items-baseline gap-2 flex-wrap">
              <h3 class="text-[15px] font-semibold text-[color:var(--color-ink)]">{{ applicant.name }}</h3>
              <span v-if="applicant.has_api_keys" class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.06em] rounded bg-emerald-50 text-emerald-700">
                <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                API key provided
              </span>
              <span v-else class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.06em] rounded bg-amber-50 text-amber-700">
                No API key
              </span>
            </div>
            <div class="mt-0.5 text-[13px] text-[color:var(--color-ink-subtle)] flex items-center gap-3 flex-wrap">
              <a v-if="applicant.website" :href="applicant.website" target="_blank" rel="noopener" class="hover:text-[color:var(--color-accent-600)] transition-colors flex items-center gap-1">
                {{ (applicant.website || '').replace(/^https?:\/\//, '') }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M7 7h10v10"/></svg>
              </a>
              <span v-if="applicant.country">· {{ applicant.country }}</span>
              <span v-if="applicant.founded_year">· Est. {{ applicant.founded_year }}</span>
              <span class="ui-mono">· {{ applicant.submitted_at }}</span>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="flex items-center gap-2 flex-shrink-0">
            <button
              @click="approveApplicant(applicant)"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 text-[13px] font-semibold rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Approve
            </button>
            <button
              @click="rejectApplicant(applicant)"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 text-[13px] font-semibold rounded border border-rose-200 text-rose-700 hover:bg-rose-50 transition-colors"
            >
              Reject
            </button>
          </div>
        </div>

        <!-- Details grid -->
        <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-[13px]">
          <!-- Contact -->
          <div>
            <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] font-semibold mb-1.5">Contact</div>
            <div class="text-[color:var(--color-ink)]">{{ applicant.contact_name || '—' }}</div>
            <a v-if="applicant.contact_email" :href="`mailto:${applicant.contact_email}`" class="text-[color:var(--color-accent-600)] hover:underline">{{ applicant.contact_email }}</a>
            <div v-if="applicant.phone" class="text-[color:var(--color-ink-muted)] ui-mono">{{ applicant.phone }}</div>
          </div>

          <!-- Payment methods -->
          <div v-if="applicant.payment_methods && applicant.payment_methods.length">
            <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] font-semibold mb-1.5">Accepts</div>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="m in applicant.payment_methods" :key="m" class="px-2 py-0.5 text-[11px] bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)] rounded">{{ m }}</span>
            </div>
          </div>

          <!-- Description -->
          <div v-if="applicant.description" class="md:col-span-2">
            <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] font-semibold mb-1.5">Description</div>
            <p class="text-[color:var(--color-ink-muted)] whitespace-pre-line leading-relaxed">{{ applicant.description }}</p>
          </div>

          <!-- Shipping -->
          <div v-if="applicant.shipping_info">
            <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] font-semibold mb-1.5">Shipping</div>
            <p class="text-[color:var(--color-ink-muted)] whitespace-pre-line leading-relaxed">{{ applicant.shipping_info }}</p>
          </div>

          <!-- Returns -->
          <div v-if="applicant.return_policy">
            <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] font-semibold mb-1.5">Return policy</div>
            <p class="text-[color:var(--color-ink-muted)] whitespace-pre-line leading-relaxed">{{ applicant.return_policy }}</p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import PageHeader from '@/components/admin/PageHeader.vue'

defineProps({
  applicants: { type: Array, default: () => [] },
})

function approveApplicant(applicant) {
  if (!confirm(`Approve "${applicant.name}"?\n\nThis will activate their account, mark their email as verified, and send them a "you're in" email with sign-in instructions.`)) return
  router.post(`/admin/vendors/${applicant.id}/approve`, {
    _token: usePage().props.csrf_token,
  }, {
    preserveScroll: true,
  })
}

function rejectApplicant(applicant) {
  if (!confirm(`Reject and delete "${applicant.name}"?\n\nThis permanently removes their application, user account, and any imported data. The vendor will need to re-apply if they want to be reconsidered.`)) return
  router.post(`/admin/vendors/${applicant.id}/reject`, {
    _token: usePage().props.csrf_token,
  }, {
    preserveScroll: true,
  })
}
</script>
