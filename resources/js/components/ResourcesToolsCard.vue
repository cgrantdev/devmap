<template>
  <Link
    :href="href"
    class="bg-white border border-gray-200 rounded-lg p-8 hover:shadow-lg transition-all cursor-pointer group block"
  >
    <!-- Icon Area -->
    <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center mb-4">
      <component :is="iconComponent" class="w-6 h-6" />
    </div>

    <!-- Title -->
    <h3 class="text-xl text-gray-900 mb-2">
      {{ title }}
    </h3>

    <!-- Description -->
    <p class="text-gray-600 mb-4">
      {{ description }}
    </p>

    <!-- Call-to-Action Link -->
    <div class="text-slate-700 text-sm group-hover:underline flex items-center gap-1">
      <span>{{ ctaText }}</span>
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform group-hover:translate-x-1">
        <path d="M5 12h14"></path>
        <path d="M12 5l7 7-7 7"></path>
      </svg>
    </div>
  </Link>
</template>

<script setup>
import { computed, h } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  href: {
    type: String,
    required: true
  },
  ctaText: {
    type: String,
    default: 'Explore'
  },
  icon: {
    type: String,
    required: true,
    validator: (value) => ['book', 'graph', 'ribbon'].includes(value)
  }
})

// Icon components
const iconComponents = {
  book: () => h('svg', {
    xmlns: 'http://www.w3.org/2000/svg',
    width: '24',
    height: '24',
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: '2',
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    class: 'lucide lucide-book-open w-6 h-6 text-slate-700',
    'aria-hidden': 'true'
  }, [
    h('path', { d: 'M12 7v14' }),
    h('path', { d: 'M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z' })
  ]),
  graph: () => h('svg', {
    xmlns: 'http://www.w3.org/2000/svg',
    width: '24',
    height: '24',
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: '2',
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    class: 'lucide lucide-trending-up w-6 h-6 text-slate-700',
    'aria-hidden': 'true'
  }, [
    h('path', { d: 'M16 7h6v6' }),
    h('path', { d: 'm22 7-8.5 8.5-5-5L2 17' })
  ]),
  ribbon: () => h('svg', {
    xmlns: 'http://www.w3.org/2000/svg',
    width: '24',
    height: '24',
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: '2',
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    class: 'lucide lucide-award w-6 h-6 text-slate-700',
    'aria-hidden': 'true'
  }, [
    h('path', { d: 'm15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526' }),
    h('circle', { cx: '12', cy: '8', r: '6' })
  ])
}

const iconComponent = computed(() => {
  return iconComponents[props.icon] || iconComponents.book
})
</script>
