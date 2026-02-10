<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <!-- <Header /> -->
    
    <main>
      <div class="min-h-screen bg-gray-100 flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
          <div class="p-6 border-b border-gray-800">
            <h1 class="text-xl">Admin Panel</h1>
            <p class="text-sm text-gray-400 mt-1">Peptidemaps</p>
          </div>
          <nav class="p-4">
            <div class="space-y-1">
              <Link href="/admin/dashboard" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url === '/admin/dashboard' }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard w-5 h-5" aria-hidden="true">
                  <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                  <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                  <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                  <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                </svg>
                <span>Dashboard</span>
              </Link>
              <Link href="/admin/vendors" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/vendors') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-store w-5 h-5" aria-hidden="true">
                  <path d="M15 21v-5a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v5"></path>
                  <path d="M17.774 10.31a1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.451 0 1.12 1.12 0 0 0-1.548 0 2.5 2.5 0 0 1-3.452 0 1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.77-3.248l2.889-4.184A2 2 0 0 1 7 2h10a2 2 0 0 1 1.653.873l2.895 4.192a2.5 2.5 0 0 1-3.774 3.244"></path>
                  <path d="M4 10.95V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.05"></path>
                </svg>
                <span>Vendors</span>
              </Link>
              <!-- <Link href="/admin/locations" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/locations') }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span>Locations</span>
              </Link> -->
              <Link href="/admin/products" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/products') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-5 h-5" aria-hidden="true">
                  <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
                  <path d="M12 22V12"></path>
                  <polyline points="3.29 7 12 12 20.71 7"></polyline>
                  <path d="m7.5 4.27 9 5.15"></path>
                </svg>
                <span>Products</span>
              </Link>
              <!-- <Link href="/admin/categories" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/categories') }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                <span>Categories</span>
              </Link> -->
              <Link href="/admin/product-scraping" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/product-scraping') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-refresh-cw w-5 h-5" aria-hidden="true">
                  <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                  <path d="M21 3v5h-5"></path>
                  <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                  <path d="M8 16H3v5"></path>
                </svg>
                <span>Product Scraping</span>
              </Link>
              <Link href="/admin/reviews" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/reviews') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-refresh-cw w-5 h-5" aria-hidden="true">
                  <path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"></path>
                </svg>
                <span>Reviews</span>
              </Link>
              <Link href="/admin/deals" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/deals') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag w-5 h-5" aria-hidden="true">
                  <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path>
                  <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle>
                </svg>
                <span>Deals & Coupons</span>
              </Link>
              <Link href="/admin/banners" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/banners') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image w-5 h-5" aria-hidden="true">
                  <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                  <circle cx="9" cy="9" r="2" fill="currentColor"></circle>
                  <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                </svg>
                <span>Banner Ads</span>
              </Link>
              <Link href="/admin/users" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/users') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-5 h-5" aria-hidden="true">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                  <path d="M16 3.128a4 4 0 0 1 0 7.744"></path>
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                  <circle cx="9" cy="7" r="4" fill="none"></circle>
                </svg>
                <span>Users</span>
              </Link>
              <Link href="/admin/content" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/content') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-5 h-5" aria-hidden="true">
                  <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path>
                  <path d="M14 2v5a1 1 0 0 0 1 1h5"></path>
                  <path d="M10 9H8"></path>
                  <path d="M16 13H8"></path>
                  <path d="M16 17H8"></path>
                </svg>
                <span>Content</span>
              </Link>
              <Link href="/admin/analytics" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/analytics') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-column w-5 h-5" aria-hidden="true">
                  <path d="M3 3v16a2 2 0 0 0 2 2h16"></path>
                  <path d="M18 17V9"></path>
                  <path d="M13 17V5"></path>
                  <path d="M8 17v-3"></path>
                </svg>
                <span>Analytics</span>
              </Link>
              <Link href="/admin/settings" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/admin/settings') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings w-5 h-5" aria-hidden="true">
                  <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"></path>
                  <circle cx="12" cy="12" r="3" fill="none"></circle>
                </svg>
                <span>Settings</span>
              </Link>
            </div>
          </nav>
          <form @submit.prevent="logout" class="absolute bottom-0 w-64 p-4 border-t border-gray-800">
            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-gray-800 rounded-lg transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out w-5 h-5" aria-hidden="true">
                <path d="m16 17 5-5-5-5"></path>
                <path d="M21 12H9"></path>
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              </svg>
              <span>Logout</span>
            </button>
          </form>
        </aside>
        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
          <div class="p-8">
            <slot />
          </div>
        </main>
      </div>
    </main>

    <!-- Footer -->
    <!-- <Footer /> -->

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
import Header from '../Components/Header.vue'
import Footer from '../Components/Footer.vue'

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