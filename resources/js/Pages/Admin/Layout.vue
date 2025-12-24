<template>
  <div class="flex min-h-screen bg-slate-50">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col h-screen border-r border-gray-800">
      <div class="p-6 border-b border-gray-800">
        <h1 class="text-xl text-white">Admin Panel</h1>
        <p class="text-sm text-gray-400 mt-1">Peptidemaps</p>
      </div>
      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <Link href="/admin/dashboard" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url === '/admin/dashboard' }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            Dashboard
          </span>
        </Link>
        <Link href="/admin/vendors" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/vendors') }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            Vendors
          </span>
        </Link>
        <Link href="/admin/products" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/products') }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
            Products
          </span>
        </Link>
        <Link href="/admin/product-scraping" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/product-scraping') }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            Product Scraping
          </span>
        </Link>
        <Link href="/admin/reviews" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/reviews') }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            Reviews
          </span>
        </Link>
        <Link href="/admin/deals" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/deals') }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
            Deals & Coupons
          </span>
        </Link>
        <Link href="/admin/banners" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/banners') }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            Banner Ads
          </span>
        </Link>
        <Link href="/admin/users" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/users') }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            Users
          </span>
        </Link>
        <Link href="/admin/content" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/content') }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Content
          </span>
        </Link>
        <Link href="/admin/analytics" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/analytics') }">
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            Analytics
          </span>
        </Link>
        <Link href="/admin/categories" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/categories') }">Categories</Link>
        <Link href="/admin/locations" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/locations') }">Locations</Link>
        <Link href="/admin/blogs" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/blogs') }">Blogs</Link>
        <Link href="/admin/education-posts" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/education-posts') }">Education Posts</Link>
        <Link href="/admin/pages" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/pages') }">Pages</Link>
        <Link href="/admin/settings" class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200" :class="{ 'bg-gray-800 text-white font-medium': $page.url.startsWith('/admin/settings') }">Settings</Link>
      </nav>
      <form @submit.prevent="logout" class="p-4 border-t border-gray-800">
        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2.5 rounded-lg hover:bg-red-700 transition-all duration-200 font-medium">Logout</button>
      </form>
    </aside>
    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-y-auto h-screen">
      <slot />
    </main>

    <!-- Global Loading Overlay -->
    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/75">
      <div class="flex flex-col items-center">
        <svg class="animate-spin h-12 w-12 text-white mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span class="text-white text-lg font-semibold">{{ loadingMessage }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'
import { useAdminLoading } from '../../composables/useAdminLoading'
import { useToast } from '../../composables/useToast'

const { isLoading, loadingMessage } = useAdminLoading()
const { toast } = useToast() // Initialize toast to watch for flash messages

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