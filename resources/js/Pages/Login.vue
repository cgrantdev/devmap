<template>
  <div class="container mx-auto max-w-md py-8">
    <h1 class="text-2xl font-bold mb-6">Vendor Login</h1>

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
        <div v-if="errors.email" class="text-red-500 text-sm mt-1">{{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}</div>
      </div>
      <div class="mb-4">
        <label class="block mb-1">Password</label>
        <input v-model="form.password" type="password" class="w-full border rounded px-3 py-2" :class="{ 'border-red-500': errors.password }" />
        <div v-if="errors.password" class="text-red-500 text-sm mt-1">{{ Array.isArray(errors.password) ? errors.password[0] : errors.password }}</div>
      </div>
      <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900" :disabled="form.processing">
        {{ form.processing ? 'Logging in...' : 'Login' }}
      </button>
    </form>
    <div class="mt-4">
      <span>Don't have an account?</span>
      <Link href="/register" class="text-blue-600">Register</Link>
    </div>
    <div class="mt-2">
      <span>Are you an admin?</span>
      <Link href="/admin/login" class="text-gray-800">Admin Login</Link>
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

const errors = usePage().props.errors || {}

function submit() {
  form.post('/login', {
    onSuccess: () => {
      window.location.href = '/vendor/dashboard'
    },
    onError: (errors) => {
      console.log('Login failed:', errors)
    }
  })
}
</script> 