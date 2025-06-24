<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
      <h1 class="text-2xl font-bold mb-6 text-center">Vendor Registration</h1>
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
          <label class="block mb-1">Name</label>
          <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2" :class="{ 'border-red-500': errors.name }" />
          <div v-if="errors.name" class="text-red-500 text-sm mt-1">{{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}</div>
        </div>
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
        <div class="mb-4">
          <label class="block mb-1">Confirm Password</label>
          <input v-model="form.password_confirmation" type="password" class="w-full border rounded px-3 py-2" :class="{ 'border-red-500': errors.password_confirmation }" />
          <div v-if="errors.password_confirmation" class="text-red-500 text-sm mt-1">{{ Array.isArray(errors.password_confirmation) ? errors.password_confirmation[0] : errors.password_confirmation }}</div>
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" :disabled="form.processing">
          {{ form.processing ? 'Registering...' : 'Register' }}
        </button>
      </form>
      <div class="mt-4 text-center">
        <span>Already have a vendor account?</span>
        <Link href="/login" class="text-blue-600 ml-1">Login</Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  _token: usePage().props.csrf_token
})

var errors = {}

function submit() {
  form.post('/register', {
    onError: (errs) => {
      console.log('Register failed:', errs)
      errors = errs
    }
  })
}
</script> 