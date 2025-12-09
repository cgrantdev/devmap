<template>
  <button
    :class="buttonClasses"
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
    default: 'Read Details'
  },
  to: {
    type: String,
    required: true
  },
  bgColor: {
    type: String,
    default: 'gray-200',
    validator: (value) => {
      return ['gray-200', 'gray-800'].includes(value)
    }
  }
})

const buttonClasses = computed(() => {
  const bgClasses = {
    'gray-200': 'bg-gray-200 text-gray-800 hover:bg-gray-300',
    'gray-800': 'bg-gray-800 text-white hover:bg-gray-700'
  }
  
  return [
    // 'w-[178px]',
    'h-[46px]',
    'py-3',
    'px-11',
    'rounded-[500px]',
    'font-roboto',
    'font-medium',
    'text-sm',
    'leading-none',
    'tracking-normal',
    bgClasses[props.bgColor],
    'border-none',
    'cursor-pointer',
    'transition-colors',
    'duration-300',
    'opacity-100',
    'flex',
    'items-center',
    'justify-center',
    'mx-auto'
  ].join(' ')
})

const handleClick = () => {
  if (props.to) {
    router.visit(props.to)
  }
}
</script>

