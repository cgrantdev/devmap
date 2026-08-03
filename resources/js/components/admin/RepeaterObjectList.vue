<template>
  <div class="space-y-2">
    <draggable
      v-model="items"
      :item-key="itemKey"
      handle=".drag-handle"
      ghost-class="opacity-40"
      animation="150"
      class="space-y-2"
    >
      <template #item="{ element, index }">
        <div class="border border-[color:var(--color-hairline)] bg-white">
          <!-- Header row -->
          <div class="flex items-center gap-2 pl-1 pr-2 py-1.5 bg-[color:var(--color-hairline-soft)]/40">
            <button
              type="button"
              class="drag-handle flex-shrink-0 flex items-center px-1 py-1 text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink-muted)] cursor-grab active:cursor-grabbing"
              title="Drag to reorder"
            >
              <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="currentColor"><circle cx="6" cy="3" r="1"/><circle cx="10" cy="3" r="1"/><circle cx="6" cy="8" r="1"/><circle cx="10" cy="8" r="1"/><circle cx="6" cy="13" r="1"/><circle cx="10" cy="13" r="1"/></svg>
            </button>
            <button
              type="button"
              @click="toggle(index)"
              class="flex-1 min-w-0 flex items-center gap-2 text-left"
            >
              <svg
                class="w-3 h-3 flex-shrink-0 text-[color:var(--color-ink-subtle)] transition-transform"
                :class="{ 'rotate-90': isOpen(index) }"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
              >
                <polyline points="9 18 15 12 9 6"/>
              </svg>
              <span class="text-[13px] font-medium text-[color:var(--color-ink)] truncate">
                {{ headerText(element, index) }}
              </span>
            </button>
            <button
              type="button"
              @click="remove(index)"
              class="flex-shrink-0 p-1 text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-danger)]"
              :title="`Remove ${itemLabel}`"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>
              </svg>
            </button>
          </div>
          <!-- Body -->
          <div v-show="isOpen(index)" class="p-4 border-t border-[color:var(--color-hairline)]">
            <slot name="item" :item="element" :index="index" />
          </div>
        </div>
      </template>
    </draggable>
    <button
      type="button"
      @click="add"
      class="w-full py-2 text-[12px] font-medium text-[color:var(--color-ink-muted)] border border-dashed border-[color:var(--color-hairline)] hover:border-[color:var(--color-accent-500)] hover:text-[color:var(--color-accent-600)] hover:bg-[color:var(--color-accent-50)]/30 transition-colors"
    >
      + Add {{ itemLabel }}
    </button>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import draggable from 'vuedraggable'

const props = defineProps({
  modelValue: { type: Array, required: true },
  itemLabel: { type: String, default: 'item' },
  titleField: { type: String, default: 'title' },
  newItem: { type: Function, required: true },
  defaultOpen: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const items = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// Track open indexes as a Set; new items default open
const openSet = ref(new Set(props.defaultOpen ? props.modelValue.map((_, i) => i) : []))

const isOpen = (i) => openSet.value.has(i)
const toggle = (i) => {
  const s = new Set(openSet.value)
  if (s.has(i)) s.delete(i)
  else s.add(i)
  openSet.value = s
}

const headerText = (element, index) => {
  const t = element?.[props.titleField]
  if (typeof t === 'string' && t.trim()) return t
  return `${props.itemLabel} ${index + 1}`
}

const itemKey = (el) => {
  const idx = items.value.indexOf(el)
  const t = el?.[props.titleField] || ''
  return `${idx}-${String(t).slice(0, 12)}`
}

const add = () => {
  const next = [...items.value, props.newItem()]
  emit('update:modelValue', next)
  // open the new item
  const s = new Set(openSet.value)
  s.add(next.length - 1)
  openSet.value = s
}
const remove = (i) => {
  const next = [...items.value]
  next.splice(i, 1)
  emit('update:modelValue', next)
  const s = new Set()
  openSet.value.forEach((idx) => {
    if (idx < i) s.add(idx)
    else if (idx > i) s.add(idx - 1)
  })
  openSet.value = s
}

defineExpose({ openAll: () => { openSet.value = new Set(items.value.map((_, i) => i)) } })
</script>
