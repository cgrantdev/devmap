<template>
  <header class="sticky top-0 z-50 bg-white header-container">
    <div class="max-w-[1440px] mx-auto h-full header-padding">
      <div class="flex items-center justify-between h-full">
        <!-- Logo -->
        <Link href="/" class="flex items-center">
          <span class="font-hv-muse font-normal text-3xl leading-tight tracking-normal text-gray-800 h-[43px]">PeptideSync</span>
        </Link>

        <!-- Navigation Menu -->
        <nav class="hidden md:flex items-center gap-[22px]">
          <Link 
            href="/products" 
            :class="isActive('/products') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
            class="transition-colors hover:opacity-80"
          >
            Products
          </Link>
          <Link 
            href="/brands" 
            :class="isActive('/brands') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
            class="transition-colors hover:opacity-80"
          >
            Brands
          </Link>
          <Link 
            href="/about" 
            :class="isActive('/about') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
            class="transition-colors hover:opacity-80"
          >
            About
          </Link>
          <Link 
            href="/education" 
            :class="isActive('/education') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
            class="transition-colors hover:opacity-80"
          >
            Education
          </Link>
          <Link 
            href="/calculator" 
            :class="isActive('/calculator') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
            class="transition-colors hover:opacity-80"
          >
            Calculator
          </Link>
          <Link 
            href="/contact" 
            :class="isActive('/contact') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
            class="transition-colors hover:opacity-80"
          >
            Contact
          </Link>
        </nav>

        <!-- Search Bar and Auth Buttons -->
        <div class="flex items-center gap-[34px]">
          <!-- Search Bar -->
          <div class="hidden sm:flex items-center relative">
            <svg 
              class="absolute pointer-events-none search-icon"
              width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M13.6666 13.6667L17.4166 17.4167" stroke="#374151" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M15.75 8.25C15.75 4.10786 12.3921 0.75 8.25 0.75C4.10786 0.75 0.75 4.10786 0.75 8.25C0.75 12.3921 4.10786 15.75 8.25 15.75C12.3921 15.75 15.75 12.3921 15.75 8.25Z" stroke="#374151" stroke-width="1.5" stroke-linejoin="round"/>
            </svg>

            <input
              type="text"
              placeholder="Type here..."
              class="w-[320px] h-[52px] rounded-[500px] py-[10px] pl-[42px] pr-4 border-0 border-[rgba(216,207,198,0.1)] font-roboto font-normal text-sm leading-none tracking-normal text-gray-700 focus:outline-none placeholder:text-gray-700 placeholder:opacity-100 bg-[#D8CFC61A]"
            />
          </div>

          <!-- Auth Buttons -->
          <div class="flex items-center gap-6">
            <Link
              href="/login"
              class="font-roboto font-normal text-base leading-none tracking-normal text-gray-500 transition-colors hover:opacity-80"
            >
              Login
            </Link>
            <Link
              href="/register"
              class="h-[52px] rounded-[500px] px-6 py-[10px] bg-gray-800 font-roboto font-medium text-base leading-none tracking-normal text-white transition-colors hover:opacity-90 inline-flex items-center justify-center"
            >
              Signup
            </Link>
          </div>

          <!-- Mobile Menu Button -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="md:hidden p-2 text-gray-700"
          >
            <svg
              v-if="!mobileMenuOpen"
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg
              v-else
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div
        v-if="mobileMenuOpen"
        class="md:hidden pb-4 border-t border-gray-200 pt-4"
      >
        <nav class="flex flex-col gap-4">
          <Link 
            href="/products" 
            :class="isActive('/products') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
          >
            Products
          </Link>
          <Link 
            href="/brands" 
            :class="isActive('/brands') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
          >
            Brands
          </Link>
          <Link 
            href="/about" 
            :class="isActive('/about') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
          >
            About
          </Link>
          <Link 
            href="/education" 
            :class="isActive('/education') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
          >
            Education
          </Link>
          <Link 
            href="/calculator" 
            :class="isActive('/calculator') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
          >
            Calculator
          </Link>
          <Link 
            href="/contact" 
            :class="isActive('/contact') ? 'font-roboto font-medium text-base leading-none tracking-normal text-gray-700' : 'font-roboto font-normal text-base leading-none tracking-normal text-gray-500'"
          >
            Contact
          </Link>
          <div class="pt-4 border-t border-gray-200">
            <div class="relative">
              <svg 
                class="absolute pointer-events-none search-icon"
                width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.6666 13.6667L17.4166 17.4167" stroke="#374151" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15.75 8.25C15.75 4.10786 12.3921 0.75 8.25 0.75C4.10786 0.75 0.75 4.10786 0.75 8.25C0.75 12.3921 4.10786 15.75 8.25 15.75C12.3921 15.75 15.75 12.3921 15.75 8.25Z" stroke="#374151" stroke-width="1.5" stroke-linejoin="round"/>
              </svg>
              <input
                type="text"
                placeholder="Type here..."
                class="w-full h-[52px] rounded-[500px] py-[10px] pl-[42px] pr-4 bg-slate-50 border border-[rgba(216,207,198,0.1)] font-roboto font-normal text-sm leading-none tracking-normal text-gray-700 focus:outline-none placeholder:text-gray-700 placeholder:opacity-100"
              />
            </div>
          </div>
        </nav>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const mobileMenuOpen = ref(false)
const page = usePage()

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
</script>

