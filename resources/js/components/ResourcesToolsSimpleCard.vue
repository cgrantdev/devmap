<template>
  <div
    class="bg-white border border-gray-200 rounded-lg p-6"
  >
    <!-- Icon & Title -->
    <div class="flex items-center gap-4 mb-4">
      <component :is="iconComponent" class="w-8 h-8" />
      <h3 class="text-xl text-gray-900">
        {{ title }}
      </h3>
    </div>


    <!-- Description -->
    <p class="text-gray-600">
      {{ description }}
    </p>
  </div>
</template>

<script setup>
import { computed, h } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  icon: {
    type: String,
    required: true,
    validator: (value) => ['graph', 'ribbon', 'book'].includes(value)
  }
})

// Icon components - dark blue/purple style
const iconComponents = {
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
  ]),
  book: () => h('svg', {
    xmlns: 'http://www.w3.org/2000/svg',
    width: '32',
    height: '32',
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
  ])
}

const iconComponent = computed(() => {
  return iconComponents[props.icon] || iconComponents.book
})
</script>
