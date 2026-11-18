<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
      <label :for="htmlId" class="block text-sm font-semibold text-[color:var(--color-ink)]">{{ label }}</label>
      <div class="inline-flex rounded-md border border-[color:var(--color-hairline)] overflow-hidden text-[12px]" role="tablist" :aria-label="`${label} editor mode`">
        <button
          v-for="m in MODES"
          :key="m.key"
          type="button"
          role="tab"
          :aria-selected="mode === m.key"
          @click="setMode(m.key)"
          :class="['px-2.5 py-1 font-medium transition-colors', mode === m.key ? 'bg-[color:var(--color-ink)] text-white' : 'bg-white text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]']"
        >{{ m.label }}</button>
      </div>
    </div>

    <div v-if="mode === 'visual'" :class="['rich-html-field bg-white', error ? 'ring-1 ring-red-500' : '']">
      <QuillEditor
        :content="modelValue || ''"
        contentType="html"
        theme="snow"
        :toolbar="TOOLBAR"
        :style="{ minHeight: `${minHeight}px` }"
        @update:content="v => emit('update:modelValue', v)"
      />
    </div>
    <textarea
      v-else-if="mode === 'html'"
      :id="htmlId"
      :value="modelValue || ''"
      @input="emit('update:modelValue', $event.target.value)"
      :rows="rows"
      spellcheck="false"
      :class="['ui-input font-mono text-[12px] leading-relaxed', error ? 'border-red-500' : '']"
    ></textarea>
    <div v-else class="border border-[color:var(--color-hairline)] rounded-md bg-white p-4 max-h-[640px] overflow-auto">
      <div v-if="modelValue" class="edu-article" v-html="modelValue"></div>
      <p v-else class="text-[13px] text-[color:var(--color-ink-subtle)]">Nothing written yet.</p>
    </div>

    <p v-if="error" class="mt-1 text-[12px] text-red-600">{{ error }}</p>
    <p v-if="hasUnsupported" class="mt-1 text-[11px] text-amber-700">This copy has tables or other blocks the visual editor can't keep, so it opens in HTML mode.</p>
    <p v-if="hint" class="mt-1 text-[11px] text-[color:var(--color-ink-muted)]">{{ hint }} <span class="ui-mono">{{ (modelValue || '').length.toLocaleString() }} / {{ maxLength.toLocaleString() }}</span></p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, required: true },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  rows: { type: Number, default: 14 },
  minHeight: { type: Number, default: 260 },
  maxLength: { type: Number, default: 200000 },
})
const emit = defineEmits(['update:modelValue'])

const MODES = [
  { key: 'visual', label: 'Visual' },
  { key: 'html', label: 'HTML' },
  { key: 'preview', label: 'Preview' },
]
const TOOLBAR = [
  [{ header: [2, 3, 4, false] }],
  ['bold', 'italic', 'underline'],
  [{ list: 'ordered' }, { list: 'bullet' }],
  ['blockquote', 'link'],
  ['clean'],
]

// Blocks the visual editor drops when it loads HTML. Copy that has them
// opens in HTML mode, and switching to Visual asks first.
const UNSUPPORTED = /<(table|thead|tbody|tfoot|tr|td|th|caption|div|hr|figure|figcaption|dl|dt|dd|img)\b/i
const hasUnsupported = computed(() => UNSUPPORTED.test(props.modelValue || ''))

const htmlId = `rich-html-${Math.random().toString(36).slice(2, 9)}`
const mode = ref(hasUnsupported.value ? 'html' : 'visual')

function setMode(next) {
  if (next === 'visual' && mode.value !== 'visual' && hasUnsupported.value) {
    const ok = window.confirm('The visual editor removes tables, images and some other blocks from this copy. Switch anyway?')
    if (!ok) return
  }
  mode.value = next
}
</script>

<style>
.rich-html-field .ql-editor { min-height: inherit; font-size: 14px; line-height: 1.7; }
.rich-html-field .ql-container { min-height: inherit; }
</style>
