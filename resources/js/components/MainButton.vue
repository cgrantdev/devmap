<template>
  <button
    :class="[buttonClasses, $attrs.class]"
    @click="handleClick"
  >
    {{ text }}
  </button>
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
  }
})

const buttonClasses = computed(() => {
  const baseClasses = 'font-roboto font-medium leading-none tracking-normal border-none cursor-pointer transition-colors duration-300 rounded-[500px] flex items-center justify-center'
  
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
  if (props.to) {
    router.visit(props.to)
  }
  // Emit the click event so parent can handle stop propagation if needed
  // The @click.stop modifier on the component will work automatically
}
</script>

