<template>
  <header class="sticky top-0 z-50 bg-white shadow-sm">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between py-4">
        <!-- Logo -->
        <Link href="/" class="flex items-center">
          <span class="text-2xl font-bold text-gray-800">PeptideSync</span>
        </Link>

        <!-- Navigation Menu -->
        <nav class="hidden md:flex items-center gap-6">
          <Link href="/products" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Products</Link>
          <Link href="/brands" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Brands</Link>
          <Link href="/about" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">About</Link>
          <Link href="/education" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Education</Link>
          <Link href="/calculator" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Calculator</Link>
          <Link href="/contact" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Contact</Link>
        </nav>

        <!-- Search Bar and Auth Buttons -->
        <div class="flex items-center gap-4">
          <!-- Search Bar -->
          <div class="hidden sm:flex items-center relative">
            <input
              type="text"
              placeholder="Type here..."
              class="pl-10 pr-4 py-2 w-64 bg-gray-100 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <svg
              class="absolute left-3 w-4 h-4 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>

          <!-- Auth Buttons -->
          <div class="flex items-center gap-3">
            <Link
              href="/login"
              class="px-4 py-2 text-gray-700 font-medium hover:text-blue-600 transition-colors"
            >
              Login
            </Link>
            <Link
              href="/register"
              class="px-6 py-2 bg-blue-700 text-white font-medium rounded-lg hover:bg-blue-800 transition-colors"
            >
              Signup
            </Link>
          </div>

          <!-- Mobile Menu Button -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="md:hidden p-2 text-gray-700 hover:text-blue-600"
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
        class="md:hidden pb-4 border-t border-gray-200 mt-4"
      >
        <nav class="flex flex-col gap-4 pt-4">
          <Link href="/products" class="text-gray-700 hover:text-blue-600 font-medium">Products</Link>
          <Link href="/brands" class="text-gray-700 hover:text-blue-600 font-medium">Brands</Link>
          <Link href="/about" class="text-gray-700 hover:text-blue-600 font-medium">About</Link>
          <Link href="/education" class="text-gray-700 hover:text-blue-600 font-medium">Education</Link>
          <Link href="/calculator" class="text-gray-700 hover:text-blue-600 font-medium">Calculator</Link>
          <Link href="/contact" class="text-gray-700 hover:text-blue-600 font-medium">Contact</Link>
          <div class="pt-4 border-t border-gray-200">
            <input
              type="text"
              placeholder="Type here..."
              class="w-full pl-10 pr-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </nav>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const mobileMenuOpen = ref(false)
</script>

