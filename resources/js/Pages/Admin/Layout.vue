<template>
  <div class="flex min-h-screen bg-slate-50">
    <!-- Sidebar -->
    <aside class="w-64 bg-white/80 backdrop-blur-sm shadow-sm flex flex-col h-screen border-r border-slate-100">
      <div class="p-6 text-2xl font-normal text-slate-700 border-b border-slate-100">Admin Panel</div>
      <nav class="flex-1 p-4 space-y-1">
        <Link href="/admin/dashboard" class="block px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition-all duration-200" :class="{ 'bg-slate-100 text-slate-800 font-medium': $page.url === '/admin/dashboard' }">Dashboard</Link>
        <Link href="/admin/vendors" class="block px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition-all duration-200" :class="{ 'bg-slate-100 text-slate-800 font-medium': $page.url === '/admin/vendors' }">Vendors</Link>
        <Link href="/admin/products" class="block px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition-all duration-200" :class="{ 'bg-slate-100 text-slate-800 font-medium': $page.url === '/admin/products' }">Products</Link>
        <Link href="/admin/categories" class="block px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition-all duration-200" :class="{ 'bg-slate-100 text-slate-800 font-medium': $page.url.startsWith('/admin/categories') }">Categories</Link>
        <Link href="/admin/blogs" class="block px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition-all duration-200" :class="{ 'bg-slate-100 text-slate-800 font-medium': $page.url.startsWith('/admin/blogs') }">Blogs</Link>
        <Link href="/admin/education-posts" class="block px-4 py-2.5 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition-all duration-200" :class="{ 'bg-slate-100 text-slate-800 font-medium': $page.url.startsWith('/admin/education-posts') }">Education Posts</Link>
      </nav>
      <form @submit.prevent="logout" class="p-4 border-t border-slate-100">
        <button type="submit" class="w-full bg-red-50 text-red-600 px-4 py-2.5 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Logout</button>
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