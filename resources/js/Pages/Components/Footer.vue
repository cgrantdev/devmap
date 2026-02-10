<template>
  <footer class="bg-gray-900 text-gray-300">
    <!-- Top Section - Promotional Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
          <!-- Left Content -->
          <div class="flex-1 text-center lg:text-left">
            <h2 class="text-3xl mb-3">Are you a peptide vendor?</h2>
            <p class="text-blue-100 text-lg">
              Join hundreds of brands reaching thousands of customers. Get verified, listed, and start growing your business today.
            </p>
          </div>
          <!-- Right Buttons -->
          <div class="flex flex-col sm:flex-row gap-4">
            <Link href="/become-a-vendor" class="bg-white text-blue-600 px-8 py-4 rounded-lg hover:bg-gray-100 transition-colors whitespace-nowrap">
              List Your Brand
            </Link>
            <Link href="/become-a-vendor" class="bg-white/10 backdrop-blur-sm border-2 border-white text-white px-8 py-4 rounded-lg hover:bg-white/20 transition-colors whitespace-nowrap">
              Learn More
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Section - Informational Footer -->
    <div class="bg-slate-900 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-8">
          <!-- Left Column - Branding and Description -->
          <div class="lg:col-span-2">
            <Link href="/" class="flex items-center gap-2 mb-4">
              <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center shadow-sm">
                <svg viewBox="0 0 24 24" fill="none" class="w-3/5 h-3/5">
                  <path d="M12 2L4 6v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V6l-8-4z" fill="white" opacity="0.9"></path>
                  <circle cx="12" cy="11" r="2.5" fill="currentColor" class="text-blue-600"></circle>
                  <circle cx="8" cy="14" r="1.5" fill="currentColor" class="text-blue-600"></circle>
                  <circle cx="16" cy="14" r="1.5" fill="currentColor" class="text-blue-600"></circle>
                </svg>
              </div>
              <div class="text-xl text-white select-none">
                <span class="font-semibold">Peptide</span>
                <span class="text-blue-600 font-semibold">maps</span>
              </div>
            </Link>
            <p class="text-sm text-gray-400 mb-6">
              Your trusted marketplace for comparing peptide brands, prices, and reviews. Make informed decisions with verified vendors and transparent information.
            </p>
            <!-- Social Media Icons -->
            <div class="flex gap-3">
              <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-slate-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="lucide lucide-facebook w-5 h-5">
                  <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                </svg>
              </a>
              <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-slate-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-twitter w-5 h-5">
                  <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path>
                </svg>
              </a>
              <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-slate-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-instagram w-5 h-5">
                  <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                  <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                  <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                </svg>
              </a>
              <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-slate-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="lucide lucide-linkedin w-5 h-5">
                  <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                  <rect x="2" y="9" width="4" height="12"></rect>
                  <circle cx="4" cy="4" r="2"></circle>
                </svg>
              </a>
            </div>
          </div>

          <!-- Marketplace Column -->
          <div>
            <h3 class="text-white mb-4">Marketplace</h3>
            <ul class="space-y-3 text-sm">
              <li>
                <Link href="/brands" class="hover:text-white transition-colors">Browse Brands</Link>
              </li>
              <li>
                <Link href="/products" class="hover:text-white transition-colors">All Products</Link>
              </li>
              <li>
                <Link href="/deals" class="hover:text-white transition-colors">Current Deals</Link>
              </li>
              <li>
                <Link href="/compare" class="hover:text-white transition-colors">Price Compare</Link>
              </li>
            </ul>
          </div>

          <!-- Resources Column -->
          <div>
            <h3 class="text-white mb-4">Resources</h3>
            <ul class="space-y-3">
              <li>
                <Link href="/education" class="hover:text-white transition-colors">Education Hub</Link>
              </li>
              <!-- <li>
                <Link href="/developers" class="hover:text-white transition-colors">Developers Guide</Link>
              </li> -->
              <li>
                <Link href="/about" class="hover:text-white transition-colors">About Us</Link>
              </li>
              <li>
                <Link href="/contact" class="hover:text-white transition-colors">Contact</Link>
              </li>
            </ul>
          </div>

          <!-- Legal Column -->
          <div>
            <h3 class="text-white mb-4">Legal</h3>
            <ul class="space-y-3">
              <li>
                <Link href="/privacy" class="hover:text-white transition-colors">Privacy Policy</Link>
              </li>
              <li>
                <Link href="/terms" class="hover:text-white transition-colors">Terms of Service</Link>
              </li>
              <li>
                <Link href="/advertise" class="hover:text-white transition-colors">Advertise</Link>
              </li>
              <!-- <li>
                <Link href="/admin/login" class="hover:text-white transition-colors">Admin Login</Link>
              </li> -->
            </ul>
          </div>
        </div>

        <!-- Bottom Section - Copyright and Disclaimer -->
        <div class="border-t border-gray-700 pt-6 mt-8">
          <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-sm text-gray-500">© 2024 Peptidemaps. All rights reserved.</p>
            <p class="text-xs text-gray-500 text-center">
              For research purposes only. Not for human consumption. Consult with a healthcare professional.
            </p>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

</script>
