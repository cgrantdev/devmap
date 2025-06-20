<template>
  <div class="min-h-screen flex flex-col bg-gray-50">
    <!-- Header -->
    <header class="w-full flex justify-end items-center p-4 bg-white shadow">
      <Link href="/login" class="ml-2 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Vendor Login</Link>
      <Link href="/register" class="ml-2 px-4 py-2 rounded bg-gray-800 text-white hover:bg-gray-900">Vendor Register</Link>
    </header>
    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center">
      <h1 class="text-4xl font-bold text-center text-gray-800">Welcome to Peptide</h1>
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>