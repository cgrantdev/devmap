<template>
  <!-- Click-to-edit text/textarea for vendor storefront fields.
       When the logged-in user owns the brand, the wrapped value shows a
       subtle pencil affordance on hover; clicking flips to an input.
       Saves on Enter (text) / Cmd+Enter (textarea) / blur, and posts one
       field at a time to /brand/{slug}/edit-field. Reads-only for non-owners.
  -->
  <div class="inline-edit-wrap" :class="{ 'group is-owner': owner }">
    <template v-if="!editing">
      <slot :value="localValue">
        <span v-if="!multiline" class="whitespace-pre-wrap">{{ localValue || (owner ? placeholder : '') }}</span>
        <p v-else class="whitespace-pre-wrap">{{ localValue || (owner ? placeholder : '') }}</p>
      </slot>
      <button
        v-if="owner"
        type="button"
        @click.stop="beginEdit"
        class="ml-1 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 opacity-0 group-hover:opacity-100 transition-opacity align-middle"
        :title="`Edit ${label}`"
      >
        ✎ Edit
      </button>
    </template>

    <template v-else>
      <div class="inline-edit-editor">
        <textarea
          v-if="multiline"
          ref="inputRef"
          v-model="draft"
          :rows="rows"
          @keydown.meta.enter.prevent="save"
          @keydown.ctrl.enter.prevent="save"
          @keydown.esc="cancel"
          class="w-full px-3 py-2 text-sm border border-indigo-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400 font-inherit"
        />
        <input
          v-else
          ref="inputRef"
          v-model="draft"
          :type="inputType"
          @keydown.enter.prevent="save"
          @keydown.esc="cancel"
          class="w-full px-3 py-2 text-sm border border-indigo-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-400"
        />
        <div class="mt-2 flex items-center gap-2">
          <button
            type="button"
            @click="save"
            :disabled="saving"
            class="px-3 py-1 text-[12px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded disabled:opacity-50"
          >
            {{ saving ? 'Saving…' : 'Save' }}
          </button>
          <button
            type="button"
            @click="cancel"
            :disabled="saving"
            class="px-3 py-1 text-[12px] font-semibold text-slate-700 border border-slate-300 bg-white hover:bg-slate-50 rounded"
          >
            Cancel
          </button>
          <span v-if="errorMsg" class="text-[11px] text-rose-600">{{ errorMsg }}</span>
        </div>
      </div>
    </template>

    <transition
      enter-active-class="transition-opacity duration-150"
      leave-active-class="transition-opacity duration-500"
      enter-from-class="opacity-0" leave-to-class="opacity-0"
    >
      <span v-if="justSaved" class="ml-2 text-[11px] font-semibold text-emerald-600">✓ saved</span>
    </transition>
  </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  // The current stored value. Parent should refresh this on save via v-model
  // (we emit update:modelValue) OR by letting Inertia re-render the page.
  modelValue: { type: [String, Number, null], default: '' },
  // The DB field name (must match the whitelist in StorefrontEditController).
  field: { type: String, required: true },
  // Human label used in tooltips + error messages.
  label: { type: String, default: 'field' },
  // Placeholder shown when the value is empty AND the owner is viewing.
  placeholder: { type: String, default: 'Click to add' },
  // Owner flag drives whether editing UI shows at all.
  owner: { type: Boolean, default: false },
  // Brand slug — used to build the save endpoint URL.
  brandSlug: { type: String, required: true },
  // Textarea vs input.
  multiline: { type: Boolean, default: false },
  rows: { type: Number, default: 4 },
  inputType: { type: String, default: 'text' },
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue)
watch(() => props.modelValue, (v) => { localValue.value = v })

const editing = ref(false)
const draft = ref('')
const inputRef = ref(null)
const saving = ref(false)
const justSaved = ref(false)
const errorMsg = ref(null)

function beginEdit() {
  draft.value = localValue.value ?? ''
  editing.value = true
  nextTick(() => inputRef.value?.focus())
}
function cancel() {
  editing.value = false
  errorMsg.value = null
}
async function save() {
  if (saving.value) return
  saving.value = true
  errorMsg.value = null
  try {
    const resp = await fetch(`/brand/${props.brandSlug}/edit-field`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': usePage().props.csrf_token || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ field: props.field, value: draft.value }),
    })
    const data = await resp.json().catch(() => ({}))
    if (!resp.ok) {
      errorMsg.value = data?.error || data?.errors?.value?.[0] || `Save failed (HTTP ${resp.status})`
      return
    }
    localValue.value = draft.value
    emit('update:modelValue', draft.value)
    editing.value = false
    justSaved.value = true
    setTimeout(() => { justSaved.value = false }, 2000)
  } catch (e) {
    errorMsg.value = 'Network error — try again.'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.inline-edit-wrap { display: inline; }
.inline-edit-editor { display: block; }
</style>
