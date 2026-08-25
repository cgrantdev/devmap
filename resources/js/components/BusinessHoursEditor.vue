<template>
  <!-- Structured 7-day hours editor. Beats freeform text ("M-F 9-5 EST")
       because we can render "OPEN NOW / CLOSED" pills, sort vendors by
       response-time expectations, and emit consistent schema.org
       OpeningHoursSpecification for SEO. Applies-to-all quick preset
       ("Mon-Fri 9am-5pm") for the common case. -->
  <div>
    <div class="flex items-baseline justify-between mb-2 gap-2 flex-wrap">
      <label class="block text-sm text-slate-700">Business hours</label>
      <div class="flex items-center gap-2">
        <select v-model="innerTz" class="h-7 px-2 text-[11px] border border-slate-300 rounded bg-white">
          <option v-for="tz in TIMEZONES" :key="tz.key" :value="tz.key">{{ tz.label }}</option>
        </select>
        <button type="button" @click="applyPreset('mf_9_5')" class="h-7 px-2 text-[11px] font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100">M–F 9–5</button>
        <button type="button" @click="applyPreset('all_24_7')" class="h-7 px-2 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded hover:bg-emerald-100">24 / 7</button>
      </div>
    </div>

    <div class="border border-slate-200 rounded-lg bg-white divide-y divide-slate-100">
      <div v-for="day in DAYS" :key="day.key" class="flex items-center gap-2 sm:gap-3 px-3 py-2">
        <span class="w-10 text-[12px] font-semibold text-slate-700 uppercase tracking-wide">{{ day.short }}</span>
        <label class="flex items-center gap-1.5 text-[12px] text-slate-600 select-none w-16 flex-shrink-0">
          <input
            type="checkbox"
            :checked="isOpen(day.key)"
            @change="toggleDay(day.key, $event.target.checked)"
            class="w-3.5 h-3.5 rounded border-slate-300"
          />
          Open
        </label>
        <template v-if="isOpen(day.key)">
          <input
            type="time"
            :value="hoursFor(day.key).open"
            @input="setTime(day.key, 'open', $event.target.value)"
            class="h-8 px-2 text-[12px] border border-slate-300 rounded"
          />
          <span class="text-[12px] text-slate-400">–</span>
          <input
            type="time"
            :value="hoursFor(day.key).close"
            @input="setTime(day.key, 'close', $event.target.value)"
            class="h-8 px-2 text-[12px] border border-slate-300 rounded"
          />
        </template>
        <span v-else class="text-[12px] text-slate-400 italic">Closed</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

// Common US-facing timezones — extend if we get more international vendors.
const TIMEZONES = [
  { key: 'America/New_York',    label: 'ET (New York)' },
  { key: 'America/Chicago',     label: 'CT (Chicago)' },
  { key: 'America/Denver',      label: 'MT (Denver)' },
  { key: 'America/Los_Angeles', label: 'PT (Los Angeles)' },
  { key: 'Europe/London',       label: 'GMT (London)' },
  { key: 'Europe/Berlin',       label: 'CET (Berlin)' },
  { key: 'UTC',                 label: 'UTC' },
]

const DAYS = [
  { key: 'mon', short: 'Mon' },
  { key: 'tue', short: 'Tue' },
  { key: 'wed', short: 'Wed' },
  { key: 'thu', short: 'Thu' },
  { key: 'fri', short: 'Fri' },
  { key: 'sat', short: 'Sat' },
  { key: 'sun', short: 'Sun' },
]

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:modelValue'])

// Local mirror. `timezone` lives inside the JSON too.
const inner = computed(() => (props.modelValue && typeof props.modelValue === 'object') ? props.modelValue : {})
const innerTz = computed({
  get: () => inner.value.timezone || 'America/New_York',
  set: (v) => emit('update:modelValue', { ...inner.value, timezone: v }),
})

function isOpen(day) {
  const h = inner.value[day]
  return !!(h && h.open && h.close)
}
function hoursFor(day) {
  return inner.value[day] || { open: '09:00', close: '17:00' }
}
function toggleDay(day, willOpen) {
  const next = { ...inner.value }
  if (willOpen) next[day] = { open: '09:00', close: '17:00' }
  else next[day] = null
  emit('update:modelValue', next)
}
function setTime(day, which, value) {
  const cur = inner.value[day] || { open: '09:00', close: '17:00' }
  const next = { ...inner.value, [day]: { ...cur, [which]: value } }
  emit('update:modelValue', next)
}
function applyPreset(preset) {
  const base = { timezone: innerTz.value }
  if (preset === 'mf_9_5') {
    ['mon', 'tue', 'wed', 'thu', 'fri'].forEach(d => (base[d] = { open: '09:00', close: '17:00' }))
    base.sat = null; base.sun = null
  } else if (preset === 'all_24_7') {
    DAYS.forEach(d => (base[d.key] = { open: '00:00', close: '23:59' }))
  }
  emit('update:modelValue', base)
}
</script>
