<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
      <h1 class="text-2xl font-normal mb-6 text-center text-slate-700">Admin Login</h1>

    <!-- General Error Message -->
    <div v-if="Object.keys(errors).length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
      <p class="font-medium">Please fix the following errors:</p>
      <ul class="list-disc list-inside mt-2">
        <li v-for="(error, field) in errors" :key="field" class="text-sm">
          {{ Array.isArray(error) ? error[0] : error }}
        </li>
      </ul>
    </div>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block mb-1.5 text-slate-700 font-medium">Email</label>
          <input v-model="form.email" type="email" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" :class="{ 'border-red-300': errors.email }" />
          <div v-if="errors.email" class="text-red-600 text-sm mt-1">
            <template v-if="Array.isArray(errors.email)">
              <span v-for="(msg, idx) in errors.email" :key="idx">{{ msg }}</span>
            </template>
            <template v-else>
              {{ errors.email }}
            </template>
          </div>
        </div>
        <div class="mb-4">
          <label class="block mb-1.5 text-slate-700 font-medium">Password</label>
          <input v-model="form.password" type="password" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" :class="{ 'border-red-300': errors.password }" />
          <div v-if="errors.password" class="text-red-600 text-sm mt-1">
            <template v-if="Array.isArray(errors.password)">
              <span v-for="(msg, idx) in errors.password" :key="idx">{{ msg }}</span>
            </template>
            <template v-else>
              {{ errors.password }}
            </template>
          </div>
        </div>
        <button type="submit" class="w-full bg-slate-700 text-white px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all duration-200 font-medium" :disabled="form.processing">
          {{ form.processing ? 'Logging in...' : 'Login' }}
        </button>
      </form>
      <div class="mt-4 text-center">
        <span>Are you a vendor?</span>
        <Link href="/login" class="text-blue-600 ml-1">Vendor Login</Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  _token: usePage().props.csrf_token
})

var errors = {}

function submit() {
  form.post('/admin/login', {
    onSuccess: () => {
      window.location.href = '/admin/dashboard'
    },
    onError: (errs) => {
      console.log('Login failed:', errs)
      errors = errs
    }
  })
}
</script> 