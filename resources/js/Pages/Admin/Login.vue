<template>
  <div class="container mx-auto max-w-md py-8">
    <h1 class="text-2xl font-bold mb-6">Admin Login</h1>
    <form @submit.prevent="submit">
      <div class="mb-4">
        <label class="block mb-1">Email</label>
        <input v-model="form.email" type="email" class="w-full border rounded px-3 py-2" />
        <div v-if="errors.email" class="text-red-500 text-sm mt-1">{{ errors.email }}</div>
      </div>
      <div class="mb-4">
        <label class="block mb-1">Password</label>
        <input v-model="form.password" type="password" class="w-full border rounded px-3 py-2" />
        <div v-if="errors.password" class="text-red-500 text-sm mt-1">{{ errors.password }}</div>
      </div>
      <div v-if="errors.email && !form.email" class="text-red-500 text-sm mb-2">{{ errors.email }}</div>
      <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded">Login</button>
    </form>
    <div class="mt-4">
      <span>Are you a vendor?</span>
      <Link href="/login" class="text-blue-600">Vendor Login</Link>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: ''
})
const errors = usePage().props.errors || {}

function submit() {
  form.post('/admin/login', {
    onSuccess: () => {
      window.location.href = '/admin/dashboard'
    }
  })
}
</script> 