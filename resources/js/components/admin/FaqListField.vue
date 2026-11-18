<template>
  <div class="space-y-3">
    <div
      v-for="(faq, i) in modelValue"
      :key="i"
      class="rounded-lg border border-[color:var(--color-hairline)] bg-[color:var(--color-bg-soft)] p-3 space-y-2"
    >
      <div class="flex items-center justify-between gap-2">
        <span class="text-[11px] font-semibold uppercase tracking-wide text-[color:var(--color-ink-muted)]">FAQ {{ i + 1 }}</span>
        <div class="flex items-center gap-1 text-[12px]">
          <button type="button" :disabled="disabled || i === 0" @click="move(i, -1)" class="px-1.5 py-0.5 rounded hover:bg-white disabled:opacity-30" :aria-label="`Move FAQ ${i + 1} up`">▲</button>
          <button type="button" :disabled="disabled || i === modelValue.length - 1" @click="move(i, 1)" class="px-1.5 py-0.5 rounded hover:bg-white disabled:opacity-30" :aria-label="`Move FAQ ${i + 1} down`">▼</button>
          <button type="button" :disabled="disabled" @click="remove(i)" class="ml-1 px-1.5 py-0.5 rounded text-red-600 hover:bg-red-50 disabled:opacity-30">Remove</button>
        </div>
      </div>
      <input
        :value="faq.question"
        @input="set(i, 'question', $event.target.value)"
        :disabled="disabled"
        maxlength="300"
        :aria-label="`FAQ ${i + 1} question`"
        placeholder="Question"
        :class="['ui-input', errorFor(i, 'question') ? 'border-red-500' : '']"
      />
      <p v-if="errorFor(i, 'question')" class="text-[12px] text-red-600">{{ errorFor(i, 'question') }}</p>
      <textarea
        :value="faq.answer"
        @input="set(i, 'answer', $event.target.value)"
        :disabled="disabled"
        rows="3"
        maxlength="4000"
        :aria-label="`FAQ ${i + 1} answer`"
        placeholder="Answer (plain text)"
        :class="['ui-input', errorFor(i, 'answer') ? 'border-red-500' : '']"
      ></textarea>
      <p v-if="errorFor(i, 'answer')" class="text-[12px] text-red-600">{{ errorFor(i, 'answer') }}</p>
    </div>

    <p v-if="!modelValue.length" class="text-[12px] text-[color:var(--color-ink-subtle)] italic">{{ emptyText }}</p>

    <button
      type="button"
      @click="add"
      :disabled="disabled || modelValue.length >= max"
      class="h-8 px-3 text-[12px] font-medium rounded-md text-[color:var(--color-ink)] bg-white border border-[color:var(--color-hairline)] hover:bg-[color:var(--color-bg-soft)] disabled:opacity-40"
    >+ Add FAQ ({{ modelValue.length }} / {{ max }})</button>
    <p v-if="errors?.[errorKey]" class="text-[12px] text-red-600">{{ errors[errorKey] }}</p>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  max: { type: Number, default: 12 },
  // Inertia form errors; row errors arrive as "faqs.0.question".
  errors: { type: Object, default: () => ({}) },
  errorKey: { type: String, default: 'faqs' },
  disabled: { type: Boolean, default: false },
  emptyText: { type: String, default: 'No FAQs yet.' },
})
const emit = defineEmits(['update:modelValue'])

function commit(next) {
  emit('update:modelValue', next)
}
function set(i, field, value) {
  commit(props.modelValue.map((f, j) => (j === i ? { ...f, [field]: value } : f)))
}
function add() {
  if (props.modelValue.length < props.max) commit([...props.modelValue, { question: '', answer: '' }])
}
function remove(i) {
  commit(props.modelValue.filter((_, j) => j !== i))
}
function move(i, delta) {
  const j = i + delta
  if (j < 0 || j >= props.modelValue.length) return
  const next = props.modelValue.slice()
  ;[next[i], next[j]] = [next[j], next[i]]
  commit(next)
}
function errorFor(i, field) {
  return props.errors?.[`${props.errorKey}.${i}.${field}`] || null
}
</script>
