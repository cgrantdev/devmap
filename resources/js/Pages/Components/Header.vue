<template>
  <header class="sticky top-0 z-50 bg-white shadow-sm header-container">
    <div class="max-w-[1440px] mx-auto h-full header-padding">
      <div class="flex items-center justify-between h-full">
        <!-- Logo -->
        <Link href="/" class="flex items-center">
          <span class="logo">PeptideSync</span>
        </Link>

        <!-- Navigation Menu -->
        <nav class="hidden md:flex items-center gap-[22px]">
          <Link 
            href="/products" 
            :class="isActive('/products') ? 'menu-link' : 'menu-link-inactive'"
            class="transition-colors hover:opacity-80"
          >
            Products
          </Link>
          <Link 
            href="/brands" 
            :class="isActive('/brands') ? 'menu-link' : 'menu-link-inactive'"
            class="transition-colors hover:opacity-80"
          >
            Brands
          </Link>
          <Link 
            href="/about" 
            :class="isActive('/about') ? 'menu-link' : 'menu-link-inactive'"
            class="transition-colors hover:opacity-80"
          >
            About
          </Link>
          <Link 
            href="/education" 
            :class="isActive('/education') ? 'menu-link' : 'menu-link-inactive'"
            class="transition-colors hover:opacity-80"
          >
            Education
          </Link>
          <Link 
            href="/calculator" 
            :class="isActive('/calculator') ? 'menu-link' : 'menu-link-inactive'"
            class="transition-colors hover:opacity-80"
          >
            Calculator
          </Link>
          <Link 
            href="/contact" 
            :class="isActive('/contact') ? 'menu-link' : 'menu-link-inactive'"
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
              class="search-input focus:outline-none"
            />
          </div>

          <!-- Auth Buttons -->
          <div class="flex items-center gap-6">
            <Link
              href="/login"
              class="menu-link-inactive transition-colors hover:opacity-80"
            >
              Login
            </Link>
            <Link
              href="/register"
              class="signup-button text-white transition-colors hover:opacity-90 inline-flex items-center justify-center"
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
            :class="isActive('/products') ? 'menu-link' : 'menu-link-inactive'"
          >
            Products
          </Link>
          <Link 
            href="/brands" 
            :class="isActive('/brands') ? 'menu-link' : 'menu-link-inactive'"
          >
            Brands
          </Link>
          <Link 
            href="/about" 
            :class="isActive('/about') ? 'menu-link' : 'menu-link-inactive'"
          >
            About
          </Link>
          <Link 
            href="/education" 
            :class="isActive('/education') ? 'menu-link' : 'menu-link-inactive'"
          >
            Education
          </Link>
          <Link 
            href="/calculator" 
            :class="isActive('/calculator') ? 'menu-link' : 'menu-link-inactive'"
          >
            Calculator
          </Link>
          <Link 
            href="/contact" 
            :class="isActive('/contact') ? 'menu-link' : 'menu-link-inactive'"
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
                class="w-full search-input focus:outline-none"
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

