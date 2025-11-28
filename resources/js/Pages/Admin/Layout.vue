<template>
  <div class="flex min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow flex flex-col h-screen">
      <div class="p-6 text-2xl font-bold border-b">Admin Panel</div>
      <nav class="flex-1 p-4 space-y-2">
        <Link href="/admin/dashboard" class="block px-4 py-2 rounded hover:bg-gray-200" :class="{ 'bg-gray-200 font-semibold': $page.url === '/admin/dashboard' }">Dashboard</Link>
        <Link href="/admin/vendors" class="block px-4 py-2 rounded hover:bg-gray-200" :class="{ 'bg-gray-200 font-semibold': $page.url === '/admin/vendors' }">Vendors</Link>
        <Link href="/admin/products" class="block px-4 py-2 rounded hover:bg-gray-200" :class="{ 'bg-gray-200 font-semibold': $page.url === '/admin/products' }">Products</Link>
        <Link href="/admin/blogs" class="block px-4 py-2 rounded hover:bg-gray-200" :class="{ 'bg-gray-200 font-semibold': $page.url.startsWith('/admin/blogs') }">Blogs</Link>
        <Link href="/admin/education-posts" class="block px-4 py-2 rounded hover:bg-gray-200" :class="{ 'bg-gray-200 font-semibold': $page.url.startsWith('/admin/education-posts') }">Education Posts</Link>
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

function logout() {
  form.post('/admin/logout', {
    onSuccess: () => {
      window.location.href = '/admin/login'
    }
  })
}
</script> 