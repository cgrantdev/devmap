<template>
  <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16 gap-6">
        <Link href="/" class="flex items-center gap-2 cursor-pointer">
          <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center shadow-sm">
            <svg viewBox="0 0 24 24" fill="none" class="w-3/5 h-3/5">
              <path d="M12 2L4 6v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V6l-8-4z" fill="white" opacity="0.9"></path>
              <circle cx="12" cy="11" r="2.5" fill="currentColor" class="text-blue-600"></circle>
              <circle cx="8" cy="14" r="1.5" fill="currentColor" class="text-blue-600"></circle>
              <circle cx="16" cy="14" r="1.5" fill="currentColor" class="text-blue-600"></circle>
            </svg>
          </div>
          <div class="text-xl text-gray-900 select-none">
            <span class="font-semibold">Peptide</span>
            <span class="text-blue-600 font-semibold">maps</span>
          </div>
        </Link>
        <form @submit.prevent="handleSearch" class="hidden lg:flex items-center flex-1 max-w-2xl">
          <div class="relative flex-1">
            <input v-model="searchQuery" type="text" placeholder="Search brands, products, peptides..." class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-sm border-r-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" aria-hidden="true">
              <path d="m21 21-4.34-4.34"></path>
              <circle cx="11" cy="11" r="8"></circle>
            </svg>
          </div>
          <div class="relative border-l border-slate-300">
            <select class="pl-4 pr-8 py-2.5 border border-slate-300 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-sm appearance-none bg-white text-slate-700 border-l-0 h-full">
              <option value="all">All</option>
              <option value="us">United States</option>
              <option value="uk">United Kingdom</option>
              <option value="ca">Canada</option>
              <option value="au">Australia</option>
              <option value="de">Germany</option>
              <option value="ch">Switzerland</option>
            </select>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none hidden" aria-hidden="true">
              <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
              <circle cx="12" су="10" r="3"></circle>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" aria-hidden="true">
              <path d="m6 9 6 6 6-6"></path>
            </svg>
          </div>
        </form>
        <div class="hidden lg:flex items-center gap-3">
          <Link href="/become-a-vendor" class="bg-slate-700 hover:bg-slate-600 text-white px-5 py-2 rounded-lg text-sm transition-colors whitespace-nowrap">Become a Vendor</Link>
        </div>
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2">
          <svg v-if="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu w-6 h-6 text-slate-600" aria-hidden="true">
            <path d="M4 5h16"></path>
            <path d="M4 12h16"></path>
            <path d="M4 19h16"></path>
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x w-6 h-6 text-slate-600" aria-hidden="true">
            <path d="M18 6L6 18"></path>
            <path d="M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Mobile Search and Location (Always Visible) -->
      <div class="lg:hidden space-y-3 py-3 border-t border-slate-200">
        <!-- Search Bar -->
        <form @submit.prevent="handleSearch">
        <div class="relative">
          <input
              v-model="searchQuery"
            type="text"
            placeholder="Search brands, products, peptides..."
            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
          />
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" aria-hidden="true">
            <path d="m21 21-4.34-4.34"></path>
            <circle cx="11" cy="11" r="8"></circle>
          </svg>
        </div>
        </form>

        <!-- Location Filter -->
        <div class="relative">
          <select class="w-full pl-10 pr-8 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm appearance-none bg-white text-gray-700">
            <option value="all">All Locations</option>
            <option value="us">United States</option>
            <option value="uk">United Kingdom</option>
            <option value="ca">Canada</option>
            <option value="au">Australia</option>
            <option value="de">Germany</option>
            <option value="ch">Switzerland</option>
          </select>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" aria-hidden="true">
            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" aria-hidden="true">
            <path d="m6 9 6 6 6-6"></path>
          </svg>
        </div>
      </div>

      <nav class="hidden lg:flex items-center justify-between border-t border-slate-200 py-3">
        <div class="flex items-center gap-6">
          <Link 
            href="/products" 
            class="flex items-center gap-2 text-gray-500 hover:text-gray-700 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
              <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
              <path d="M12 22V12"></path>
              <polyline points="3.29 7 12 12 20.71 7"></polyline>
              <path d="m7.5 4.27 9 5.15"></path>
            </svg>
            <span>Peptides</span>
          </Link>
          <Link 
            href="/brands" 
            class="flex items-center gap-2 text-gray-500 hover:text-gray-700 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 lucide-building-2 w-4 h-4" aria-hidden="true">
              <path d="M10 12h4"></path>
              <path d="M10 8h4"></path>
              <path d="M14 21v-3a2 2 0 0 0-4 0v3"></path>
              <path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2"></path>
              <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"></path>
            </svg>
            <span>Vendors</span>
          </Link>
          <Link 
            href="/encyclopedia" 
            class="flex items-center gap-2 text-gray-500 hover:text-gray-700 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-4 h-4" aria-hidden="true">
              <path d="M12 7v4"></path>
              <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
            </svg>
            <span>Encyclopedia</span>
          </Link>
          <Link 
            href="/calculator" 
            class="flex items-center gap-2 text-gray-500 hover:text-gray-700 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
              <rect width="16" height="20" x="4" y="2" rx="2"></rect>
              <line x1="8" x2="16" y1="6" y2="6"></line>
              <line x1="8" x2="16" y1="10" y2="10"></line>
              <line x1="8" x2="16" y1="14" y2="14"></line>
              <line x1="8" x2="16" y1="18" y2="18"></line>
            </svg>
            <span>Calculator</span>
          </Link>
          <!-- <Link 
            href="/compare" 
            class="flex items-center gap-2 text-gray-500 hover:text-gray-700 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-git-compare w-4 h-4" aria-hidden="true">
              <circle cx="18" cy="18" r="3"></circle>
              <circle cx="6" cy="6" r="3"></circle>
              <path d="M13 6h3a2 2 0 0 1 2 2v7"></path>
              <path d="M11 18H8a2 2 0 0 1-2-2V9"></path>
            </svg>
            <span>Compare</span>
          </Link> -->
          <Link 
            href="/news" 
            class="flex items-center gap-2 text-gray-500 hover:text-gray-700 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
              <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
              <path d="M18 14h-8"></path>
              <path d="M15 18h-5"></path>
              <path d="M10 6h8v4h-8V6Z"></path>
            </svg>
            <span>News</span>
          </Link>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center">
            <Link
              href="/deals"
              :class="[
                'flex items-center gap-2 px-4 py-2 border rounded-lg transition-colors',
                isActive('/deals')
                  ? 'border-slate-700 bg-slate-700 text-white hover:bg-slate-600'
                  : 'border-gray-300 bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                <line x1="7" x2="7.01" y1="7" y2="7"></line>
              </svg>
              <span>Deals</span>
            </Link>
          </div>

          <!-- Customer auth controls -->
          <div class="flex items-center">
            <template v-if="!authUser">
              <Link
                href="/login"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                Log In
              </Link>
            </template>

            <template v-else-if="isCustomer">
              <button
                type="button"
                @click="logout"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                Log Out
              </button>
            </template>
          </div>
        </div>
      </nav>
      

      <!-- Mobile Menu -->
      <div
        v-if="mobileMenuOpen"
        class="lg:hidden fixed inset-0 bg-white z-50 overflow-y-auto"
      >
        <!-- Mobile Menu Header -->
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200">
          <Link href="/" class="flex items-center gap-2" @click="mobileMenuOpen = false">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center shadow-sm">
              <svg viewBox="0 0 24 24" fill="none" class="w-3/5 h-3/5">
                <path d="M12 2L4 6v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V6l-8-4z" fill="white" opacity="0.9"></path>
                <circle cx="12" cy="11" r="2.5" fill="currentColor" class="text-blue-600"></circle>
                <circle cx="8" cy="14" r="1.5" fill="currentColor" class="text-blue-600"></circle>
                <circle cx="16" cy="14" r="1.5" fill="currentColor" class="text-blue-600"></circle>
              </svg>
            </div>
            <div class="text-xl text-gray-900 select-none">
              <span class="font-semibold">Peptide</span>
              <span class="text-blue-600 font-semibold">maps</span>
            </div>
          </Link>
          <button @click="mobileMenuOpen = false" class="p-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-slate-600" aria-hidden="true">
              <path d="M18 6L6 18"></path>
              <path d="M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Search and Location Filters -->
        <div class="px-4 py-4 space-y-3 border-b border-gray-200">
          <!-- Search Bar -->
          <form @submit.prevent="handleSearch">
          <div class="relative">
            <input
                v-model="searchQuery"
              type="text"
              placeholder="Search brands, products, peptides..."
              class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
            />
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" aria-hidden="true">
              <path d="m21 21-4.34-4.34"></path>
              <circle cx="11" cy="11" r="8"></circle>
            </svg>
          </div>
          </form>

          <!-- Location Filter -->
          <div class="relative">
            <select class="w-full pl-10 pr-8 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm appearance-none bg-white text-gray-700">
              <option value="all">All Locations</option>
              <option value="us">United States</option>
              <option value="uk">United Kingdom</option>
              <option value="ca">Canada</option>
              <option value="au">Australia</option>
              <option value="de">Germany</option>
              <option value="ch">Switzerland</option>
            </select>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" aria-hidden="true">
              <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" aria-hidden="true">
              <path d="m6 9 6 6 6-6"></path>
            </svg>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="px-4 py-4 space-y-1">
          <Link 
            href="/products" 
            @click="mobileMenuOpen = false"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" aria-hidden="true">
              <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
              <path d="M12 22V12"></path>
              <polyline points="3.29 7 12 12 20.71 7"></polyline>
              <path d="m7.5 4.27 9 5.15"></path>
            </svg>
            <span>Peptides</span>
          </Link>
          <Link 
            href="/brands" 
            @click="mobileMenuOpen = false"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 w-5 h-5" aria-hidden="true">
              <path d="M10 12h4"></path>
              <path d="M10 8h4"></path>
              <path d="M14 21v-3a2 2 0 0 0-4 0v3"></path>
              <path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2"></path>
              <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"></path>
            </svg>
            <span>Vendors</span>
          </Link>
          <Link 
            href="/encyclopedia" 
            @click="mobileMenuOpen = false"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-5 h-5" aria-hidden="true">
              <path d="M12 7v4"></path>
              <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
            </svg>
            <span>Encyclopedia</span>
          </Link>
          <Link 
            href="/calculator" 
            @click="mobileMenuOpen = false"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" aria-hidden="true">
              <rect width="16" height="20" x="4" y="2" rx="2"></rect>
              <line x1="8" x2="16" y1="6" y2="6"></line>
              <line x1="8" x2="16" y1="10" y2="10"></line>
              <line x1="8" x2="16" y1="14" y2="14"></line>
              <line x1="8" x2="16" y1="18" y2="18"></line>
            </svg>
            <span>Calculator</span>
          </Link>
          <Link 
            href="/compare" 
            @click="mobileMenuOpen = false"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-git-compare w-5 h-5" aria-hidden="true">
              <circle cx="18" cy="18" r="3"></circle>
              <circle cx="6" cy="6" r="3"></circle>
              <path d="M13 6h3a2 2 0 0 1 2 2v7"></path>
              <path d="M11 18H8a2 2 0 0 1-2-2V9"></path>
            </svg>
            <span>Compare</span>
          </Link>
          <Link 
            href="/news" 
            @click="mobileMenuOpen = false"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" aria-hidden="true">
              <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
              <path d="M18 14h-8"></path>
              <path d="M15 18h-5"></path>
              <path d="M10 6h8v4h-8V6Z"></path>
            </svg>
            <span>News</span>
          </Link>
          <Link 
            href="/deals" 
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors',
              isActive('/deals') 
                ? 'bg-slate-700 border-slate-700 text-white hover:bg-slate-600' 
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" aria-hidden="true">
              <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
              <line x1="7" x2="7.01" y1="7" y2="7"></line>
            </svg>
            <span>Deals</span>
          </Link>
        </nav>

        <!-- Become a Vendor Button -->
        <div class="px-4 py-4 border-t border-gray-200">
          <Link href="/become-a-vendor" @click="mobileMenuOpen = false" class="w-full bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-lg text-sm font-medium transition-colors block text-center">
            Become a Vendor
          </Link>
        </div>

        <!-- Customer auth actions -->
        <div class="px-4 py-4 border-t border-gray-200">
          <template v-if="!authUser">
            <Link
              href="/login"
              @click="mobileMenuOpen = false"
              class="w-full bg-white hover:bg-gray-50 text-slate-700 px-6 py-3 rounded-lg text-sm font-medium transition-colors block text-center border border-gray-200"
            >
              Log In
            </Link>
          </template>
          <template v-else-if="isCustomer">
            <button
              type="button"
              @click="() => { logout(); mobileMenuOpen = false }"
              class="w-full bg-white hover:bg-gray-50 text-slate-700 px-6 py-3 rounded-lg text-sm font-medium transition-colors block text-center border border-gray-200"
            >
              Log Out
            </button>
          </template>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const mobileMenuOpen = ref(false)
const page = usePage()
const searchQuery = ref('')

const authUser = computed(() => page.props.auth?.user ?? null)
const isCustomer = computed(() => authUser.value?.role === 'customer')
const csrfToken = computed(() => page.props.csrf_token ?? '')

const logout = () => {
  // Include CSRF token explicitly to avoid 419 errors.
  router.post('/logout', { _token: csrfToken.value }, { preserveScroll: true })
}

const isActive = (path) => {
  const currentPath = page.url
  
  // Handle root path - only active if exactly "/"
  if (path === '/') {
    return currentPath === '/'
  }
  
  // For other paths, check if current path starts with the link path
  // Use strict matching to avoid partial matches (e.g., "/about" matching "/about-us")
  return currentPath === path || currentPath.startsWith(path + '/')
}

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    router.visit(`/search?q=${encodeURIComponent(searchQuery.value.trim())}`)
  }
}
</script>

