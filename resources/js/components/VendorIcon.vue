<template>
  <div class="w-full h-full flex items-center justify-center">
    <!-- Candy Icon -->
    <svg v-if="type === 'candy'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-purple-600">
      <circle cx="12" cy="12" r="10"></circle>
      <path d="M12 2v4"></path>
      <path d="M12 18v4"></path>
      <path d="M4.93 4.93l2.83 2.83"></path>
      <path d="M16.24 16.24l2.83 2.83"></path>
      <path d="M2 12h4"></path>
      <path d="M18 12h4"></path>
      <path d="M4.93 19.07l2.83-2.83"></path>
      <path d="M16.24 7.76l2.83-2.83"></path>
    </svg>
    
    <!-- Beaker Icon -->
    <svg v-else-if="type === 'beaker'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-blue-600">
      <path d="M9 2v6"></path>
      <path d="M15 2v6"></path>
      <path d="M3 8h18"></path>
      <path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"></path>
      <line x1="8" y1="12" x2="16" y2="12"></line>
    </svg>
    
    <!-- Flask Icon (default) -->
    <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-green-600">
      <path d="M9 2v6"></path>
      <path d="M15 2v6"></path>
      <path d="M3 8h18"></path>
      <path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"></path>
      <line x1="8" y1="12" x2="16" y2="12"></line>
      <line x1="8" y1="16" x2="16" y2="16"></line>
    </svg>
  </div>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'flask',
    validator: (value) => ['candy', 'beaker', 'flask'].includes(value)
  }
})
</script>
