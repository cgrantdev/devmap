<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-purple-100 flex flex-col">
    <Header />
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-16">
      <div class="max-w-3xl text-center">
        <h1 class="text-5xl md:text-6xl font-extrabold text-gray-800 mb-6 drop-shadow">Discover Research Peptides & Trusted Vendors</h1>
        <p class="text-lg md:text-xl text-gray-600 mb-8">Welcome to the leading marketplace for research peptides. Explore a curated selection of high-quality peptides from verified vendors. Our platform connects researchers and professionals with the best suppliers in the industry, ensuring transparency, quality, and trust.</p>
        <a href="/vendors" class="inline-block px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-lg font-semibold rounded-full shadow hover:from-blue-700 hover:to-purple-700 transition-colors">Browse Vendors</a>
      </div>
      <div class="mt-16 flex flex-col md:flex-row items-center justify-center gap-10">
        <div class="bg-white rounded-2xl shadow-lg p-8 max-w-sm flex-1">
          <h2 class="text-2xl font-bold text-blue-700 mb-2">What are Peptides?</h2>
          <p class="text-gray-600">Peptides are short chains of amino acids that play crucial roles in biological processes. They are widely used in scientific research, medicine, and wellness for their diverse functions and therapeutic potential.</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-8 max-w-sm flex-1">
          <h2 class="text-2xl font-bold text-purple-700 mb-2">Why Shop with Us?</h2>
          <ul class="text-gray-600 list-disc list-inside space-y-2 text-left">
            <li>Verified, trusted vendors</li>
            <li>Transparent product sourcing</li>
            <li>Modern, secure platform</li>
            <li>Easy vendor comparison</li>
          </ul>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import Header from './Header.vue'
</script>