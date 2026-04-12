<template>
  <div class="min-h-screen flex flex-col">
    <!-- Impersonation banner -->
    <div v-if="$page.props.impersonating" class="bg-amber-500 text-amber-950 text-center py-2 px-4 text-[13px] font-semibold flex items-center justify-center gap-3 z-50">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      Viewing as vendor — {{ $page.props.auth?.user?.name || 'vendor' }}
      <a href="/admin/stop-impersonating" class="underline hover:no-underline ml-2">Return to admin</a>
    </div>
    <main>
      <div class="min-h-screen bg-gray-100 flex">
    <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
          <div class="p-6 border-b border-gray-800">
            <h1 class="text-xl">Vendor Panel</h1>
            <p class="text-sm text-gray-400 mt-1">Peptidemap</p>
          </div>
          <nav class="p-4">
            <div class="space-y-1">
              <Link href="/vendor/dashboard" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url === '/vendor/dashboard' }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard w-5 h-5" aria-hidden="true">
                  <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                  <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                  <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                  <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                </svg>
                <span>Dashboard</span>
              </Link>
              <Link href="/vendor/profile" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/vendor/profile') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-5 h-5" aria-hidden="true">
                  <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Profile</span>
              </Link>
              <Link href="/vendor/storefront-analytics" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/vendor/storefront-analytics') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-store w-5 h-5" aria-hidden="true">
                  <path d="M15 21v-5a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v5"></path>
                  <path d="M17.774 10.31a1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.451 0 1.12 1.12 0 0 0-1.548 0 2.5 2.5 0 0 1-3.452 0 1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.77-3.248l2.889-4.184A2 2 0 0 1 7 2h10a2 2 0 0 1 1.653.873l2.895 4.192a2.5 2.5 0 0 1-3.774 3.244"></path>
                  <path d="M4 10.95V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.05"></path>
                </svg>
                <span>Storefront Analytics</span>
              </Link>
              <Link href="/vendor/advertisement-analytics" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/vendor/advertisement-analytics') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone w-5 h-5" aria-hidden="true">
                  <path d="m3 11 18-5v12L3 14v-3z"></path>
                  <path d="m11.6 16.8 3.5 2.7 3.5-2.7"></path>
                  <path d="m11.6 16.8 3.5 2.7v-6.2l-3.5-2.7"></path>
                  <path d="m8.1 10.7 3.5 2.7 3.5-2.7"></path>
                </svg>
                <span>Advertisement Analytics</span>
              </Link>
              <Link href="/vendor/products" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800 relative" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/vendor/products') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                  <path d="M6 2l3 6"></path>
                  <path d="M18 2l-3 6"></path>
                  <path d="M3 7h18"></path>
                  <path d="M4 7l1 13a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2l1-13"></path>
                </svg>
                <span>Products</span>
              </Link>
              <Link href="/vendor/reviews" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800 relative" :class="{ 'bg-blue-600 text-white': $page.url.startsWith('/vendor/reviews') }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square w-5 h-5" aria-hidden="true">
                  <path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"></path>
                </svg>
                <span>Reviews</span>
                <div v-if="$page.props.approved_reviews_count > 0" class="ml-auto relative flex-shrink-0">
                  <span class="absolute -top-1 -right-1 flex items-center justify-center min-w-[18px] h-[18px] bg-yellow-600 text-white text-[10px] font-bold rounded-full px-1">
                    {{ $page.props.approved_reviews_count }}
                  </span>
                </div>
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
  </div>
</template>

<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'

const form = useForm({
  _token: usePage().props.csrf_token
})

function logout() {
  form.post('/vendor/logout', {
    onSuccess: () => {
      window.location.href = '/vendor/login'
    }
  })
}
</script> 
