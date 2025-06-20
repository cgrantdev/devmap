<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
      <h1 class="text-2xl font-bold mb-6 text-center">Admin Login</h1>

    <!-- General Error Message -->
    <div v-if="Object.keys(errors).length > 0" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
      <p class="font-bold">Please fix the following errors:</p>
      <ul class="list-disc list-inside">
        <li v-for="(error, field) in errors" :key="field" class="text-sm">
          {{ Array.isArray(error) ? error[0] : error }}
        </li>
      </ul>
    </div>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block mb-1">Email</label>
          <input v-model="form.email" type="email" class="w-full border rounded px-3 py-2" :class="{ 'border-red-500': errors.email }" />
          <div v-if="errors.email" class="text-red-500 text-sm mt-1">
            <template v-if="Array.isArray(errors.email)">
              <span v-for="(msg, idx) in errors.email" :key="idx">{{ msg }}</span>
            </template>
            <template v-else>
              {{ errors.email }}
            </template>
          </div>
        </div>
        <div class="mb-4">
          <label class="block mb-1">Password</label>
          <input v-model="form.password" type="password" class="w-full border rounded px-3 py-2" :class="{ 'border-red-500': errors.password }" />
          <div v-if="errors.password" class="text-red-500 text-sm mt-1">
            <template v-if="Array.isArray(errors.password)">
              <span v-for="(msg, idx) in errors.password" :key="idx">{{ msg }}</span>
            </template>
            <template v-else>
              {{ errors.password }}
            </template>
          </div>
        </div>
        <button type="submit" class="w-full bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900" :disabled="form.processing">
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