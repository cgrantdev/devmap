<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">User Management</h1>
      <p class="text-slate-600">Manage users, vendors, and administrators</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
      <div class="grid grid-cols-2 gap-4">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search users..."
            v-model="searchTerm"
            class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <select
          v-model="filterRole"
          class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All Roles</option>
          <option value="customer">Customers</option>
          <option value="vendor">Vendors</option>
          <option value="admin">Admins</option>
        </select>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">User</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Joined</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <span class="text-sm text-slate-900">{{ user.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  {{ user.email }}
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-3 py-1 rounded-full text-xs flex items-center gap-1 w-fit',
                  user.role === 'admin' ? 'bg-purple-100 text-purple-700' :
                  user.role === 'vendor' ? 'bg-blue-100 text-blue-700' :
                  'bg-slate-100 text-slate-700'
                ]">
                  <svg v-if="user.role === 'admin'" class="lucide lucide-shield w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                  </svg>
                  {{ user.role }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-3 py-1 rounded-full text-xs',
                  user.email_verified_at ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                ]">
                  {{ user.email_verified_at ? 'active' : 'inactive' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-600">{{ formatDate(user.created_at) }}</span>
              </td>
              <td class="px-6 py-4">
                <button class="text-blue-600 hover:text-blue-700 text-sm">
                  Manage
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="filteredUsers.length === 0" class="bg-white rounded-lg border border-slate-200 p-6 text-center text-slate-500">
      No users found.
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminLayout from './Layout.vue'

const props = defineProps({
  users: {
    type: Array,
    default: () => []
  }
})

const searchTerm = ref('')
const filterRole = ref('all')

const filteredUsers = computed(() => {
  return props.users.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
                         user.email.toLowerCase().includes(searchTerm.value.toLowerCase())
    const matchesRole = filterRole.value === 'all' || user.role === filterRole.value
    return matchesSearch && matchesRole
  })
})

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString()
}
</script>

