<template>
  <footer class="bg-white border-t border-gray-200">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <!-- Logo and Sign Up -->
        <div class="md:col-span-1">
          <a href="/" class="inline-block mb-4">
            <span class="text-2xl font-bold text-gray-800">PeptideSync</span>
          </a>
          <Link
            href="/register"
            class="inline-block px-6 py-2 bg-blue-700 text-white font-medium rounded-lg hover:bg-blue-800 transition-colors"
          >
            Sign Up
          </Link>
        </div>

        <!-- Legal Links -->
        <div>
          <h3 class="text-gray-800 font-semibold mb-4">Legal</h3>
          <ul class="space-y-2">
            <li>
              <a href="/disclaimer" class="text-gray-600 hover:text-blue-600 transition-colors">Disclaimer</a>
            </li>
            <li>
              <a href="/privacy" class="text-gray-600 hover:text-blue-600 transition-colors">Privacy Policy</a>
            </li>
            <li>
              <a href="/brands" class="text-gray-600 hover:text-blue-600 transition-colors">Brands</a>
            </li>
            <li>
              <a href="/calculator" class="text-gray-600 hover:text-blue-600 transition-colors">Calculator</a>
            </li>
          </ul>
        </div>

        <!-- Resources -->
        <div>
          <h3 class="text-gray-800 font-semibold mb-4">Resources</h3>
          <ul class="space-y-2">
            <li>
              <a href="/contact" class="text-gray-600 hover:text-blue-600 transition-colors">Contact</a>
            </li>
            <li>
              <a href="/about" class="text-gray-600 hover:text-blue-600 transition-colors">About</a>
            </li>
            <li>
              <a href="/brands" class="text-gray-600 hover:text-blue-600 transition-colors">Brands</a>
            </li>
            <li>
              <a href="/products" class="text-gray-600 hover:text-blue-600 transition-colors">Products</a>
            </li>
          </ul>
        </div>

        <!-- Additional Resources -->
        <div>
          <h3 class="text-gray-800 font-semibold mb-4">Resources</h3>
          <ul class="space-y-2">
            <li>
              <a href="/calculator" class="text-gray-600 hover:text-blue-600 transition-colors">Calculator</a>
            </li>
            <li>
              <a href="/education" class="text-gray-600 hover:text-blue-600 transition-colors">Education</a>
            </li>
            <li>
              <a href="/disclaimer" class="text-gray-600 hover:text-blue-600 transition-colors">Disclaimer</a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Copyright -->
      <div class="flex flex-col md:flex-row justify-between items-center pt-8 border-t border-gray-200 relative">
        <p class="text-gray-600 text-sm">© 2024 PeptideSync. All Rights Reserved.</p>
        
        <!-- Decorative Elements -->
        <div class="hidden md:block absolute right-0 top-0 bottom-0 w-48 overflow-hidden pointer-events-none">
          <div class="absolute right-0 top-0 w-32 h-32 bg-blue-100 rounded-full opacity-20 blur-2xl"></div>
          <div class="absolute right-8 top-8 w-24 h-24 bg-blue-200 rounded-full opacity-30 blur-xl"></div>
          <div class="absolute right-16 top-16 w-16 h-16 bg-blue-300 rounded-full opacity-40 blur-lg"></div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>

