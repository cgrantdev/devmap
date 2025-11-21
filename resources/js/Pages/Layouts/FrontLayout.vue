<template>
  <div class="min-h-screen bg-white flex flex-col">
    <Header />
    <main class="flex-1">
      <slot />
    </main>
    <Footer />
  </div>
</template>

<script setup>
import Header from '../Components/Header.vue'
import Footer from '../Components/Footer.vue'
</script>

