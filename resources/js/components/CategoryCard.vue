<template>
  <div
    class="bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-blue-500 hover:shadow-lg transition-all text-left group cursor-pointer"
    @click="handleClick"
  >
    <div class="aspect-square bg-gradient-to-br from-blue-50 to-indigo-50 p-6 flex items-center justify-center mb-6 rounded-md">
      <img
        v-if="image && !hasError"
        :src="image"
        :alt="name"
        class="w-full h-full object-contain select-none"
        loading="lazy"
        @error="onError"
      />
      <!-- Themed SVG fallback so every category card always renders something
           when the image url is empty or 404s (most often: external vendor
           CDN blocks hotlinking). Uses the category name as the label so the
           card still communicates which compound it represents. -->
      <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 select-none">
        <svg viewBox="0 0 80 100" class="w-20 h-24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="26" y="6" width="28" height="6" rx="1" fill="#475569"/>
          <rect x="22" y="12" width="36" height="76" rx="3" fill="white" stroke="#94A3B8" stroke-width="1.5"/>
          <rect x="22" y="50" width="36" height="38" rx="2" fill="#93C5FD" opacity="0.65"/>
          <line x1="60" y1="22" x2="64" y2="22" stroke="#64748B" stroke-width="1"/>
          <line x1="60" y1="34" x2="64" y2="34" stroke="#64748B" stroke-width="1"/>
          <line x1="60" y1="46" x2="64" y2="46" stroke="#64748B" stroke-width="1"/>
          <line x1="60" y1="58" x2="64" y2="58" stroke="#64748B" stroke-width="1"/>
          <line x1="60" y1="70" x2="64" y2="70" stroke="#64748B" stroke-width="1"/>
          <rect x="28" y="60" width="24" height="14" rx="1" fill="white" opacity="0.85"/>
          <line x1="30" y1="64" x2="50" y2="64" stroke="#94A3B8" stroke-width="0.6"/>
          <line x1="30" y1="68" x2="50" y2="68" stroke="#94A3B8" stroke-width="0.6"/>
          <line x1="30" y1="71" x2="46" y2="71" stroke="#94A3B8" stroke-width="0.6"/>
        </svg>
        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mt-2 line-clamp-1 max-w-full px-2 text-center">{{ name }}</span>
      </div>
    </div>
    <div class="h-14 flex items-end mb-1">      
      <h3
        class="pt-6 border-t border-slate-200 text-xl text-gray-900 group-hover:text-blue-600 transition-colors leading-tight"
        :title="name"
      >
        {{ name }}
      </h3>
    </div>
    <!-- Research Area -->
    <div v-if="researchArea" class="text-slate-600 text-xs italic justify-between mb-6">      
      {{ researchArea }}
    </div>
    
    <!-- Learn More Link (Fixed position at bottom) -->
    <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
      <span class="text-gray-900 text-xs italic underline">View All</span>
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-4 h-4 text-slate-500 group-hover:text-slate-700 transition-colors" aria-hidden="true">
        <path d="m9 18 6-6-6-6"></path>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
// import MainButton from '@/components/MainButton.vue'

const props = defineProps({
  name: { type: String, required: true },
  image: { type: String, default: null },
  totalItems: { type: [String, Number], default: 0 },
  to: { type: String, required: true },
  researchArea: { type: String, default: null },
})

const hasError = ref(false)

const handleClick = () => {
  router.visit(props.to)
}

const onError = () => {
  hasError.value = true
}
</script>
