<template>
  <a
    :href="to"
    :class="[buttonClasses, $attrs.class]"
    @click="handleClick"
  >
    <span>{{ text }}</span>
    <span v-if="svg" class="inline-flex items-center">
      <component :is="svg" class="w-5 h-5" />
    </span>
  </a>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  text: {
    type: String,
    required: true
  },
  to: {
    type: String,
    required: true
  },
  bgColor: {
    type: String,
    default: 'gray-800',
    validator: (value) => {
      return ['gray-800', 'white', 'slate-50', 'gray-200', 'gray-700'].includes(value)
    }
  },
  size: {
    type: String,
    default: 'lg',
    validator: (value) => {
      return ['sm', 'md', 'lg'].includes(value)
    }
  },
  fullWidth: {
    type: Boolean,
    default: false
  },
  svg: {
    type: [Object, Function, String],
    default: null
  }
})

const buttonClasses = computed(() => {
  const baseClasses = 'font-roboto font-medium leading-none tracking-normal border-none cursor-pointer transition-colors duration-300 rounded-[500px] flex items-center justify-center gap-2'
  
  const sizeClasses = {
    sm: 'py-3 px-11 text-sm',
    md: 'py-[10px] px-20 text-xl',
    lg: 'py-[10px] px-20 text-xl h-[68px]'
  }
  
  const bgClasses = {
    'gray-800': 'bg-gray-800 text-white hover:bg-gray-700 shadow-[0_2px_4px_rgba(0,0,0,0.1)]',
    'white': 'bg-white text-gray-800 hover:bg-gray-100 shadow-[0_2px_4px_rgba(0,0,0,0.1)]',
    'slate-50': 'bg-slate-50 text-gray-700 hover:bg-gray-100',
    'gray-200': 'bg-gray-200 text-gray-800 hover:bg-gray-300',
    'gray-700': 'bg-gray-700 text-white hover:bg-gray-600'
  }
  
  const widthClass = props.fullWidth ? 'w-full' : 'w-fit'
  
  return [
    baseClasses,
    sizeClasses[props.size],
    bgClasses[props.bgColor],
    widthClass
  ].join(' ')
})

const handleClick = (event) => {
  // Allow default behavior for new-tab / new-window / middle-click
  if (
    event.metaKey ||
    event.ctrlKey ||
    event.shiftKey ||
    event.altKey ||
    event.button !== 0
  ) {
    return
  }

  // In-page navigation via Inertia
  event.preventDefault()
  if (props.to) {
    router.visit(props.to)
  }
}
</script>

