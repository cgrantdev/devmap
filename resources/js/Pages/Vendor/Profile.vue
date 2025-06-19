<template>
  <div>
    <h1 class="text-2xl font-bold mb-6">Profile</h1>
    <div class="max-w-md space-y-4">
      <div>
        <label class="block mb-1 font-semibold">Name</label>
        <div class="p-2 bg-gray-100 rounded">{{ profile.name }}</div>
      </div>
      <div>
        <label class="block mb-1 font-semibold">Email</label>
        <div class="p-2 bg-gray-100 rounded">{{ profile.email }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
const user = usePage().props.auth.user || {}
const profile = {
  name: user.name || '',
  email: user.email || ''
}
</script> 