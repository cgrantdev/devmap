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
        <div class="group flex items-start gap-2 border border-[color:var(--color-hairline)] bg-white pl-1 pr-2 py-1.5 hover:border-[color:var(--color-hairline)] hover:bg-[color:var(--color-hairline-soft)]/40 transition-colors">
          <button
            type="button"
            class="drag-handle flex-shrink-0 self-stretch flex items-center px-1 text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink-muted)] cursor-grab active:cursor-grabbing"
            title="Drag to reorder"
          >
            <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="currentColor"><circle cx="6" cy="3" r="1"/><circle cx="10" cy="3" r="1"/><circle cx="6" cy="8" r="1"/><circle cx="10" cy="8" r="1"/><circle cx="6" cy="13" r="1"/><circle cx="10" cy="13" r="1"/></svg>
          </button>
          <template v-if="multiline">
            <textarea
              v-model="items[index]"
              :rows="rows"
              :placeholder="placeholder"
              class="ui-input flex-1 !border-transparent !bg-transparent focus:!border-[color:var(--color-accent-500)] focus:!bg-white text-sm"
            />
          </template>
          <template v-else>
            <input
              v-model="items[index]"
              type="text"
              :placeholder="placeholder"
              class="ui-input flex-1 !border-transparent !bg-transparent focus:!border-[color:var(--color-accent-500)] focus:!bg-white text-sm h-8"
            />
          </template>
          <button
            type="button"
            @click="remove(index)"
            class="flex-shrink-0 self-start mt-1 p-1 text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-danger)] opacity-0 group-hover:opacity-100 transition-opacity"
            :title="`Remove ${itemLabel}`"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>
            </svg>
          </button>
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
import { computed } from 'vue'
import draggable from 'vuedraggable'

const props = defineProps({
  modelValue: { type: Array, required: true },
  itemLabel: { type: String, default: 'item' },
  placeholder: { type: String, default: '' },
  multiline: { type: Boolean, default: false },
  rows: { type: Number, default: 2 },
})
const emit = defineEmits(['update:modelValue'])

const items = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const itemKey = (el) => `${items.value.indexOf(el)}-${(el || '').slice(0, 8)}`

const add = () => emit('update:modelValue', [...items.value, ''])
const remove = (i) => {
  const next = [...items.value]
  next.splice(i, 1)
  emit('update:modelValue', next)
}
</script>
