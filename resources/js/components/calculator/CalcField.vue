<template>
  <div class="mb-5">
    <div class="flex items-center justify-between gap-2 mb-2">
      <label :for="inputId" class="block text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">{{ label }}</label>
      <slot name="aside" />
    </div>
    <div class="relative">
      <input
        :id="inputId"
        type="text"
        inputmode="decimal"
        autocomplete="off"
        spellcheck="false"
        :value="text"
        :placeholder="placeholder"
        @input="onInput"
        @blur="onBlur"
        class="w-full h-10 pl-3 pr-14 text-[14px] ui-mono border border-[color:var(--color-hairline)] rounded-[8px] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15"
      />
      <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[12px] text-[color:var(--color-ink-subtle)] font-semibold pointer-events-none">{{ unit }}</span>
    </div>
  </div>
</template>

<script setup>
// Numeric input that never fights the user while they type. It keeps its
// own text and only emits a number once the text is a complete positive
// value; partial entries like "0." or "." leave the last good value in
// place, and a cleared field emits null so results can show a dash.
import { computed, ref, watch } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  unit: { type: String, default: '' },
  modelValue: { type: Number, default: null },
  placeholder: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const inputId = computed(() => 'calc-' + props.label.toLowerCase().replace(/[^a-z0-9]+/g, '-'))

function format(n) {
  if (typeof n !== 'number' || !Number.isFinite(n)) return ''
  // Trim float noise (0.30000000000000004) without losing real precision.
  return String(Math.round(n * 1e6) / 1e6)
}

function normalize(raw) {
  let s = String(raw).trim().replace(/\s+/g, '')
  if (/^[1-9]\d{0,2}(,\d{3})+(\.\d*)?$/.test(s)) {
    // "2,500" or "2,500.5": comma is a thousands separator.
    s = s.replace(/,/g, '')
  } else if (/^\d*,\d*$/.test(s)) {
    // "0,5": decimal comma.
    s = s.replace(',', '.')
  }
  return s
}

function parse(raw) {
  const s = normalize(raw)
  if (s === '') return { kind: 'empty' }
  if (!/^\d*\.?\d*$/.test(s) || s === '.') return { kind: 'partial' }
  const n = Number(s)
  if (!Number.isFinite(n) || n <= 0) return { kind: 'partial' }
  return { kind: 'value', value: n }
}

const text = ref(format(props.modelValue))

// Only rewrite the text when the value changed from outside (a preset,
// the mcg/mg toggle, URL hydration), never when it echoes the user's typing.
watch(() => props.modelValue, (next) => {
  const current = parse(text.value)
  if (typeof next !== 'number' || !Number.isFinite(next)) {
    if (current.kind === 'value') text.value = ''
    return
  }
  if (current.kind !== 'value' || Math.abs(current.value - next) > 1e-9) {
    text.value = format(next)
  }
})

function onInput(event) {
  text.value = event.target.value
  const parsed = parse(text.value)
  if (parsed.kind === 'value') emit('update:modelValue', parsed.value)
  else if (parsed.kind === 'empty') emit('update:modelValue', null)
}

function onBlur() {
  const parsed = parse(text.value)
  if (parsed.kind === 'partial') text.value = format(props.modelValue)
  else if (parsed.kind === 'value') text.value = format(parsed.value)
}
</script>
