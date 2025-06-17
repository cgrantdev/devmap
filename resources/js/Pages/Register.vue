<template>
  <div class="container mx-auto max-w-md py-8">
    <h1 class="text-2xl font-bold mb-6">Register</h1>
    <form @submit.prevent="submit">
      <div class="mb-4">
        <label class="block mb-1">Name</label>
        <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2" />
        <div v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</div>
      </div>
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
      <div class="mb-4">
        <label class="block mb-1">Confirm Password</label>
        <input v-model="form.password_confirmation" type="password" class="w-full border rounded px-3 py-2" />
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Register</button>
    </form>
    <div class="mt-4">
      <span>Already have an account?</span>
      <inertia-link href="/login" class="text-blue-600">Login</inertia-link>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})
const errors = usePage().props.errors || {}

function submit() {
  form.post('/register')
}
</script> 