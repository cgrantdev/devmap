<template>
  <div class="flex items-center justify-between mt-8">
    <!-- Items Count -->
    <div class="font-roboto font-normal text-sm leading-relaxed text-gray-800">
      Items {{ itemsFrom }} to {{ itemsTo }} of {{ pagination.total || 0 }}
    </div>

    <!-- Pagination Controls -->
    <div class="flex items-center gap-2 bg-gray-200 rounded-[100px] px-4 py-2">
      <!-- Previous Button -->
      <Link
        v-if="pagination.prev_page_url"
        :href="pagination.prev_page_url"
        class="flex items-center justify-center w-8 h-8 rounded-[500px] bg-gray-200 hover:bg-gray-300 transition-colors"
      >
        <svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M6.84853 11.6485C6.3799 12.1172 5.6201 12.1172 5.15147 11.6485L0.351472 6.84853C-0.117157 6.3799 -0.117157 5.6201 0.351472 5.15147L5.15147 0.351472C5.6201 -0.117157 6.3799 -0.117157 6.84853 0.351472C7.31716 0.820101 7.31716 1.5799 6.84853 2.04853L4.09706 4.8L13.2 4.8C13.8627 4.8 14.4 5.33726 14.4 6C14.4 6.66274 13.8627 7.2 13.2 7.2H4.09706L6.84853 9.95147C7.31716 10.4201 7.31716 11.1799 6.84853 11.6485Z" fill="#475569"/>
        </svg>
      </Link>
      <span
        v-else
        class="flex items-center justify-center w-8 h-8 rounded-[500px] bg-gray-200 cursor-not-allowed"
      >
        <svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M6.84853 11.6485C6.3799 12.1172 5.6201 12.1172 5.15147 11.6485L0.351472 6.84853C-0.117157 6.3799 -0.117157 5.6201 0.351472 5.15147L5.15147 0.351472C5.6201 -0.117157 6.3799 -0.117157 6.84853 0.351472C7.31716 0.820101 7.31716 1.5799 6.84853 2.04853L4.09706 4.8L13.2 4.8C13.8627 4.8 14.4 5.33726 14.4 6C14.4 6.66274 13.8627 7.2 13.2 7.2H4.09706L6.84853 9.95147C7.31716 10.4201 7.31716 11.1799 6.84853 11.6485Z" fill="#94A3B8"/>
        </svg>
      </span>

      <!-- Page Numbers -->
      <template v-for="page in visiblePages" :key="page">
        <Link
          v-if="page !== '...'"
          :href="getPageUrl(page)"
          :class="[
            'flex items-center justify-center min-w-[32px] h-8 px-2 rounded-[500px] font-roboto font-medium text-sm leading-none tracking-normal transition-colors',
            page === pagination.current_page
              ? 'bg-white text-gray-800'
              : 'text-gray-800 hover:text-gray-600'
          ]"
        >
          {{ page }}
        </Link>
        <span v-else class="flex items-center justify-center min-w-[32px] h-8 px-2 text-gray-800">...</span>
      </template>

      <!-- Next Button -->
      <Link
        v-if="pagination.next_page_url"
        :href="pagination.next_page_url"
        class="flex items-center justify-center w-8 h-8 rounded-[500px] bg-gray-200 hover:bg-gray-300 transition-colors"
      >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M12.3513 6.35147C12.8199 5.88284 13.5797 5.88284 14.0483 6.35147L18.8483 11.1515C19.317 11.6201 19.317 12.3799 18.8483 12.8485L14.0483 17.6485C13.5797 18.1172 12.8199 18.1172 12.3513 17.6485C11.8826 17.1799 11.8826 16.4201 12.3513 15.9515L15.1028 13.2L5.99981 13.2C5.33706 13.2 4.7998 12.6627 4.7998 12C4.7998 11.3373 5.33706 10.8 5.99981 10.8H15.1028L12.3513 8.04853C11.8826 7.5799 11.8826 6.8201 12.3513 6.35147Z" fill="#475569"/>
        </svg>
      </Link>
      <span
        v-else
        class="flex items-center justify-center w-8 h-8 rounded-[500px] bg-gray-200 cursor-not-allowed"
      >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M12.3513 6.35147C12.8199 5.88284 13.5797 5.88284 14.0483 6.35147L18.8483 11.1515C19.317 11.6201 19.317 12.3799 18.8483 12.8485L14.0483 17.6485C13.5797 18.1172 12.8199 18.1172 12.3513 17.6485C11.8826 17.1799 11.8826 16.4201 12.3513 15.9515L15.1028 13.2L5.99981 13.2C5.33706 13.2 4.7998 12.6627 4.7998 12C4.7998 11.3373 5.33706 10.8 5.99981 10.8H15.1028L12.3513 8.04853C11.8826 7.5799 11.8826 6.8201 12.3513 6.35147Z" fill="#94A3B8"/>
        </svg>
      </span>
    </div>

    <!-- Items Per Page Selector -->
    <div class="flex items-center gap-2">
      <span class="font-roboto font-normal text-sm leading-relaxed text-gray-800">Show</span>
      <div class="relative">
        <select
          :value="currentPerPage"
          @change="handlePerPageChange"
          class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-1.5 pr-8 font-roboto font-normal text-sm leading-relaxed text-gray-800 cursor-pointer focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-transparent"
        >
          <option
            v-for="option in perPageOptions"
            :key="option"
            :value="option"
          >
            {{ option }}
          </option>
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
          <svg
            class="w-4 h-4 text-gray-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 9l-7 7-7-7"
            />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  pagination: {
    type: Object,
    required: true,
    validator: (value) => {
      return value.current_page !== undefined && value.last_page !== undefined
    }
  },
  getPageUrl: {
    type: Function,
    required: true
  },
  perPageOptions: {
    type: Array,
    default: () => [10, 20, 50, 100]
  },
  onPerPageChange: {
    type: Function,
    default: null
  }
})

const itemsFrom = computed(() => {
  return props.pagination.from || 0
})

const itemsTo = computed(() => {
  return props.pagination.to || 0
})

const currentPerPage = computed(() => {
  return props.pagination.per_page || props.perPageOptions[0]
})

const handlePerPageChange = (event) => {
  const newPerPage = parseInt(event.target.value)
  if (props.onPerPageChange) {
    props.onPerPageChange(newPerPage)
  }
}

const visiblePages = computed(() => {
  const current = props.pagination.current_page
  const last = props.pagination.last_page
  const pages = []
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 3) {
      for (let i = 1; i <= 4; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 2) {
      pages.push(1)
      pages.push('...')
      for (let i = last - 3; i <= last; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    }
  }
  
  return pages
})
</script>
