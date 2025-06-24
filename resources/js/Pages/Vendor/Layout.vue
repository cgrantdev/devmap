<template>
  <div class="flex min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow flex flex-col h-screen">
      <div class="p-6 text-2xl font-bold border-b">Vendor Panel</div>
      <nav class="flex-1 p-4 space-y-2">
        <Link href="/vendor/dashboard" class="block px-4 py-2 rounded hover:bg-gray-200" :class="{ 'bg-gray-200 font-semibold': $page.url === '/vendor/dashboard' }">Dashboard</Link>
        <Link href="/vendor/import" class="block px-4 py-2 rounded hover:bg-gray-200" :class="{ 'bg-gray-200 font-semibold': $page.url === '/vendor/import' }">Import</Link>
        <Link href="/vendor/settings" class="block px-4 py-2 rounded hover:bg-gray-200" :class="{ 'bg-gray-200 font-semibold': $page.url === '/vendor/settings' }">Settings</Link>
        <Link href="/vendor/profile" class="block px-4 py-2 rounded hover:bg-gray-200" :class="{ 'bg-gray-200 font-semibold': $page.url === '/vendor/profile' }">Profile</Link>
      </nav>
      <form @submit.prevent="logout" class="p-4 border-t">
        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Logout</button>
      </form>
    </aside>
    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-y-auto h-screen">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'

const form = useForm({
  _token: usePage().props.csrf_token
})

// Mock data - replace with actual data from your backend
const stats = {
  totalOrders: 0,
  revenue: 0
}

const recentOrders = []

function logout() {
  form.post('/logout', {
    onSuccess: () => {
      window.location.href = '/login'
    }
  })
}
</script> 