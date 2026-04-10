<template>
  <div>
    <label v-if="label" class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1.5">
      {{ label }}
      <span v-if="required" class="text-[color:var(--color-danger)]">*</span>
    </label>
    <slot />
    <p v-if="hint" class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">{{ hint }}</p>
    <p v-if="error" class="text-[11px] text-[color:var(--color-danger)] mt-1">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, default: '' },
  required: { type: Boolean, default: false },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
})
</script>
