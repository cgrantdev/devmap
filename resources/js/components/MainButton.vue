<template>
  <component
    :is="to ? 'a' : 'button'"
    :href="to || undefined"
    :class="[buttonClasses, $attrs.class]"
    @click="handleClick"
  >
    <span>{{ text }}</span>
    <span v-if="badge" class="ml-1 bg-orange-500 text-white px-2 py-0.5 text-xs rounded">
      {{ badge }}
    </span>
    <span v-if="svg" class="inline-flex items-center">
      <component :is="svg" class="w-5 h-5" />
    </span>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  text: {
    type: String,
    default: 'Read Details'
  },
  to: {
    type: String,
    default: null
  },
  bgColor: {
    type: String,
    default: 'gray-800',
    validator: (value) => {
      return ['gray-800', 'white', 'slate-50', 'gray-200', 'gray-700', 'blue-600'].includes(value)
    }
  },
  size: {
    type: String,
    default: 'lg',
    validator: (value) => {
      return ['sm', 'md', 'lg', 'custom', 'custom-small', 'second'].includes(value)
    }
  },
  fullWidth: {
    type: Boolean,
    default: false
  },
  svg: {
    type: [Object, Function, String],
    default: null
  },
  badge: {
    type: [String, Number],
    default: null
  }
})

const buttonClasses = computed(() => {
  const baseClasses = 'font-roboto font-medium leading-none tracking-normal border-none cursor-pointer transition-colors duration-300 rounded-[500px] flex items-center justify-center'
  
  const gapClasses = {
    sm: 'gap-2',
    md: 'gap-2',
    lg: 'gap-2',
    custom: 'gap-[10px]',
    'custom-small': 'gap-[10px]',
    second: 'gap-2'
  }
  
  const sizeClasses = {
    sm: 'py-3 px-11 text-sm',
    md: 'py-[10px] px-20 text-xl',
    lg: 'py-[10px] px-20 text-xl h-[68px]',
    custom: 'py-[15px] px-[45px] text-[20px] h-[53px]',
    'custom-small': 'py-[10px] px-[28px] pb-[9px] text-sm h-[52px]',
    second: 'py-3 px-11 text-sm h-[46px]'
  }
  
  const bgClasses = {
    'gray-800': 'bg-gray-800 text-white hover:bg-gray-700 shadow-[0_2px_4px_rgba(0,0,0,0.1)]',
    'white': 'bg-white text-gray-800 hover:bg-gray-100 shadow-[0_2px_4px_rgba(0,0,0,0.1)]',
    'slate-50': 'bg-slate-50 text-gray-700 hover:bg-gray-100',
    'gray-200': 'bg-gray-200 text-gray-800 hover:bg-gray-300',
    'gray-700': 'bg-gray-700 text-white hover:bg-gray-600',
    'blue-600': 'bg-blue-600 text-white hover:bg-blue-700'
  }
  
  const widthClass = props.fullWidth ? 'w-full' : 'w-fit' 
  
  // For second size, add mx-auto for centering (matching SecondButton behavior)
  const centerClass = props.size === 'second' ? 'mx-auto' : ''
  
  return [
    baseClasses,
    gapClasses[props.size],
    sizeClasses[props.size],
    bgClasses[props.bgColor],
    widthClass,
    centerClass
  ].filter(Boolean).join(' ')
})

const emit = defineEmits(['click'])

const handleClick = (event) => {
  // If no 'to' prop, emit click event for parent to handle
  if (!props.to) {
    emit('click', event)
    return
  }

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
  router.visit(props.to)
}
</script>

