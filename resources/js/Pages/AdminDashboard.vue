<template>
  <div class="container mx-auto max-w-2xl py-8">
    <h1 class="text-3xl font-bold mb-6">Admin Dashboard</h1>
    <p class="mb-4">Welcome to the admin dashboard!</p>
    <form @submit.prevent="logout">
      <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Logout</button>
    </form>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm()
function logout() {
  form.post('/logout')
}
</script> 