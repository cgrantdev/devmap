<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Content Management</h1>
      <p class="text-slate-600">Manage educational content and site pages</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
      <Link href="/admin/education-posts" class="bg-white rounded-lg border-2 border-slate-200 hover:border-blue-500 p-8 text-left transition-colors">
        <svg class="w-12 h-12 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <h3 class="text-lg text-slate-900 mb-2">Encyclopedia Hub</h3>
        <p class="text-sm text-slate-600">Manage peptide information</p>
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

      <Link href="/admin/research" class="bg-white rounded-lg border-2 border-slate-200 hover:border-yellow-500 p-8 text-left transition-colors">
        <svg class="w-12 h-12 text-yellow-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
        </svg>
        <h3 class="text-lg text-slate-900 mb-2">Research Library</h3>
        <p class="text-sm text-slate-600">Edit clinical studies and research papers</p>
      </Link>

      <Link href="/admin/educational-guides" class="bg-white rounded-lg border-2 border-slate-200 hover:border-orange-500 p-8 text-left transition-colors">
        <svg class="w-12 h-12 text-orange-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14v9" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v5" />
        </svg>
        <h3 class="text-lg text-slate-900 mb-2">Educational Guides</h3>
        <p class="text-sm text-slate-600">Edit educational guides</p>
      </Link>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
</script>

