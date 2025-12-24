<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Content Management</h1>
      <p class="text-slate-600">Manage educational content and site pages</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <Link href="/admin/education-posts" class="bg-white rounded-lg border-2 border-slate-200 hover:border-blue-500 p-8 text-left transition-colors">
        <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <h3 class="text-lg text-slate-900 mb-2">Education Hub</h3>
        <p class="text-sm text-slate-600">Manage peptide information and guides</p>
      </Link>

      <Link href="/admin/blogs" class="bg-white rounded-lg border-2 border-slate-200 hover:border-green-500 p-8 text-left transition-colors">
        <svg class="w-12 h-12 text-green-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-lg text-slate-900 mb-2">Blog Posts</h3>
        <p class="text-sm text-slate-600">Create and edit blog articles</p>
      </Link>

      <Link href="/admin/pages" class="bg-white rounded-lg border-2 border-slate-200 hover:border-purple-500 p-8 text-left transition-colors">
        <svg class="w-12 h-12 text-purple-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="text-lg text-slate-900 mb-2">Static Pages</h3>
        <p class="text-sm text-slate-600">Edit About, Contact, Legal pages</p>
      </Link>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
</script>

