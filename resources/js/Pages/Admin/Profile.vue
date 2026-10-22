<template>
  <AdminLayout>
    <div class="p-8 max-w-2xl mx-auto space-y-8">
      <div>
        <h1 class="text-2xl font-semibold mb-1">Account</h1>
        <p class="text-sm text-[color:var(--color-ink-muted)]">Signed in as <strong>{{ account.email }}</strong> · Role: <code class="ui-mono text-[12px] bg-slate-100 px-1.5 py-0.5 rounded">{{ account.role }}</code></p>
      </div>

      <!-- Profile -->
      <section class="border border-[color:var(--color-hairline)] rounded-lg p-5 bg-white">
        <h2 class="text-base font-semibold mb-3">Profile</h2>
        <form @submit.prevent="saveProfile" class="space-y-3">
          <label class="block">
            <span class="block text-[12px] font-semibold mb-1">Name</span>
            <input v-model="profileForm.name" type="text" required class="w-full h-9 px-3 text-sm border border-[color:var(--color-hairline)] rounded" />
          </label>
          <label class="block">
            <span class="block text-[12px] font-semibold mb-1">Email</span>
            <input v-model="profileForm.email" type="email" required class="w-full h-9 px-3 text-sm border border-[color:var(--color-hairline)] rounded" />
          </label>
          <div class="flex justify-end">
            <button type="submit" :disabled="profileForm.processing" class="h-9 px-4 text-[13px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded disabled:opacity-50">
              {{ profileForm.processing ? 'Saving…' : 'Save profile' }}
            </button>
          </div>
          <p v-if="profileSaved" class="text-[12px] text-emerald-700">Profile saved.</p>
        </form>
      </section>

      <!-- Password -->
      <section class="border border-[color:var(--color-hairline)] rounded-lg p-5 bg-white">
        <h2 class="text-base font-semibold mb-3">Change password</h2>
        <form @submit.prevent="savePassword" class="space-y-3">
          <label class="block">
            <span class="block text-[12px] font-semibold mb-1">Current password</span>
            <input v-model="pwForm.current_password" type="password" required autocomplete="current-password"
              class="w-full h-9 px-3 text-sm border border-[color:var(--color-hairline)] rounded ui-mono" />
            <span v-if="pwForm.errors.current_password" class="text-[11px] text-red-600">{{ pwForm.errors.current_password }}</span>
          </label>
          <label class="block">
            <span class="block text-[12px] font-semibold mb-1">New password <span class="text-[color:var(--color-ink-subtle)]">(min 8 chars)</span></span>
            <input v-model="pwForm.password" type="password" minlength="8" required autocomplete="new-password"
              class="w-full h-9 px-3 text-sm border border-[color:var(--color-hairline)] rounded ui-mono" />
            <span v-if="pwForm.errors.password" class="text-[11px] text-red-600">{{ pwForm.errors.password }}</span>
          </label>
          <label class="block">
            <span class="block text-[12px] font-semibold mb-1">Confirm new password</span>
            <input v-model="pwForm.password_confirmation" type="password" minlength="8" required autocomplete="new-password"
              class="w-full h-9 px-3 text-sm border border-[color:var(--color-hairline)] rounded ui-mono" />
          </label>
          <div class="flex justify-end">
            <button type="submit" :disabled="pwForm.processing" class="h-9 px-4 text-[13px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded disabled:opacity-50">
              {{ pwForm.processing ? 'Saving…' : 'Change password' }}
            </button>
          </div>
          <p v-if="pwSaved" class="text-[12px] text-emerald-700">Password updated. You'll stay signed in on this device.</p>
        </form>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({ account: Object })

const profileSaved = ref(false)
const pwSaved = ref(false)

const profileForm = useForm({
  name: props.account.name || '',
  email: props.account.email || '',
})
const pwForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

function saveProfile() {
  profileSaved.value = false
  profileForm.post('/admin/profile', {
    preserveScroll: true,
    onSuccess: () => { profileSaved.value = true },
  })
}

function savePassword() {
  pwSaved.value = false
  pwForm.post('/admin/profile/password', {
    preserveScroll: true,
    onSuccess: () => {
      pwSaved.value = true
      pwForm.reset('current_password', 'password', 'password_confirmation')
    },
  })
}
</script>
