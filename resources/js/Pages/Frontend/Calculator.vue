<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- Hero -->
    <section class="border-b border-[color:var(--color-hairline)] bg-gradient-to-b from-[color:var(--color-bg)] to-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-8 pb-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-biotech-600)] mb-3">Free tool</div>
        <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-3">
          Peptide Calculator
        </h1>
        <p class="text-lg text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl">
          Reconstitution volume, injection dosage, syringe units, and doses per vial &mdash; all in one place. The most accurate peptide calculator available.
        </p>
      </div>
    </section>

    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
      <div class="grid lg:grid-cols-[420px_1fr] gap-8 items-start">

        <!-- LEFT: Inputs -->
        <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-sm)] p-6 lg:sticky lg:top-24">
          <!-- Mode tabs -->
          <div class="flex gap-1 p-1 rounded-[10px] bg-[color:var(--color-bg)] mb-6">
            <button
              v-for="m in modes"
              :key="m.key"
              @click="mode = m.key"
              :class="[
                'flex-1 h-9 rounded-[8px] text-[12px] font-semibold transition-all',
                mode === m.key
                  ? 'bg-white text-[color:var(--color-ink)] shadow-sm'
                  : 'text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]',
              ]"
            >{{ m.label }}</button>
          </div>

          <!-- Peptide preset -->
          <div class="mb-5">
            <label class="block text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-2">Peptide preset</label>
            <select
              v-model="preset"
              @change="applyPreset"
              class="w-full h-10 px-3 text-[13px] border border-[color:var(--color-hairline)] rounded-[8px] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15"
            >
              <option value="">Custom</option>
              <option v-for="p in presets" :key="p.name" :value="p.name">{{ p.name }}</option>
            </select>
          </div>

          <!-- Peptide amount -->
          <CalcField label="Peptide amount in vial" unit="mg" v-model="peptideMg" :step="0.5" :min="0.1" />

          <!-- Water volume -->
          <CalcField label="Bacteriostatic water added" unit="mL" v-model="waterMl" :step="0.5" :min="0.1" />

          <!-- Desired dose (reconstitution mode doesn't need this) -->
          <CalcField v-if="mode !== 'reconstitution'" label="Desired dose per injection" unit="mcg" v-model="doseMcg" :step="10" :min="1" />

          <!-- Syringe size -->
          <div class="mb-5">
            <label class="block text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-2">Syringe size</label>
            <div class="flex gap-2">
              <button
                v-for="s in syringes"
                :key="s"
                @click="syringeUnits = s"
                :class="[
                  'flex-1 h-9 rounded-[8px] text-[12px] font-semibold border transition-all',
                  syringeUnits === s
                    ? 'bg-[color:var(--color-ink)] text-white border-transparent'
                    : 'bg-white border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)]',
                ]"
              >{{ s }}U</button>
            </div>
          </div>

          <!-- Injection frequency (for schedule mode) -->
          <div v-if="mode === 'schedule'" class="mb-5">
            <label class="block text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-2">Injection frequency</label>
            <select
              v-model="frequency"
              class="w-full h-10 px-3 text-[13px] border border-[color:var(--color-hairline)] rounded-[8px] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15"
            >
              <option value="daily">Daily</option>
              <option value="eod">Every other day</option>
              <option value="3x">3x per week</option>
              <option value="2x">2x per week</option>
              <option value="weekly">Weekly</option>
              <option value="biweekly">Every 2 weeks</option>
            </select>
          </div>

          <!-- Disclaimer -->
          <div class="mt-6 pt-5 border-t border-[color:var(--color-hairline)]">
            <p class="text-[11px] text-[color:var(--color-ink-subtle)] leading-relaxed">
              For research and educational purposes only. Not medical advice. Always consult a qualified healthcare professional.
            </p>
          </div>
        </div>

        <!-- RIGHT: Results -->
        <div class="space-y-6">

          <!-- Primary result cards -->
          <div class="grid sm:grid-cols-2 gap-4">
            <ResultCard
              label="Concentration"
              :value="concentration.toLocaleString('en-US', { maximumFractionDigits: 1 })"
              unit="mcg/mL"
              icon="concentration"
              color="accent"
            />
            <ResultCard
              v-if="mode !== 'reconstitution'"
              label="Volume to inject"
              :value="volumeMl"
              unit="mL"
              icon="syringe"
              color="verified"
              highlight
            />
            <ResultCard
              v-if="mode !== 'reconstitution'"
              label="Syringe units"
              :value="String(syringeMarks)"
              :unit="`of ${syringeUnits} units`"
              icon="units"
              color="caution"
            />
            <ResultCard
              label="Doses per vial"
              :value="mode !== 'reconstitution' ? String(dosesPerVial) : '—'"
              unit="injections"
              icon="vial"
              color="ink"
            />
          </div>

          <!-- Syringe visual -->
          <div v-if="mode !== 'reconstitution'" class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h3 class="text-[13px] font-semibold text-[color:var(--color-ink)] mb-4">Syringe visualization</h3>
            <div class="flex items-center gap-6">
              <!-- Syringe barrel -->
              <div class="relative w-full max-w-[360px] h-14 rounded-full border-2 border-[color:var(--color-ink)]/20 bg-[color:var(--color-bg)] overflow-hidden">
                <div
                  class="absolute left-0 top-0 bottom-0 rounded-full transition-all duration-500 ease-out"
                  :class="fillPercent > 80 ? 'bg-[color:var(--color-danger)]/30' : 'bg-[color:var(--color-accent-500)]/25'"
                  :style="{ width: Math.min(fillPercent, 100) + '%' }"
                ></div>
                <!-- Tick marks -->
                <div v-for="tick in 10" :key="tick" class="absolute top-0 h-3 border-l border-[color:var(--color-ink)]/10" :style="{ left: (tick * 10) + '%' }"></div>
                <!-- Needle indicator -->
                <div
                  class="absolute top-1/2 -translate-y-1/2 w-0.5 h-10 bg-[color:var(--color-accent-600)] shadow-[0_0_6px_rgba(79,70,229,0.5)] transition-all duration-500 ease-out"
                  :style="{ left: Math.min(fillPercent, 100) + '%' }"
                ></div>
              </div>
              <div class="text-right flex-shrink-0 min-w-[80px]">
                <div class="ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ syringeMarks }}</div>
                <div class="text-[11px] text-[color:var(--color-ink-subtle)]">units</div>
              </div>
            </div>
            <div v-if="fillPercent > 100" class="mt-3 flex items-center gap-2 text-[12px] text-[color:var(--color-danger)] font-semibold">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              Volume exceeds syringe capacity. Use a larger syringe or add more water to reduce concentration.
            </div>
          </div>

          <!-- Schedule mode: vial duration -->
          <div v-if="mode === 'schedule'" class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h3 class="text-[13px] font-semibold text-[color:var(--color-ink)] mb-4">Vial schedule</h3>
            <div class="grid sm:grid-cols-3 gap-4">
              <div class="text-center p-4 rounded-[12px] bg-[color:var(--color-bg)]">
                <div class="ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ dosesPerVial }}</div>
                <div class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">doses per vial</div>
              </div>
              <div class="text-center p-4 rounded-[12px] bg-[color:var(--color-bg)]">
                <div class="ui-mono text-2xl font-bold text-[color:var(--color-accent-600)]">{{ vialDurationDays }}</div>
                <div class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">days per vial</div>
              </div>
              <div class="text-center p-4 rounded-[12px] bg-[color:var(--color-bg)]">
                <div class="ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ vialsPerMonth }}</div>
                <div class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">vials per month</div>
              </div>
            </div>
          </div>

          <!-- Reconstitution mode: water volume guide -->
          <div v-if="mode === 'reconstitution'" class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h3 class="text-[13px] font-semibold text-[color:var(--color-ink)] mb-4">Concentration by water volume</h3>
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-[color:var(--color-hairline)]">
                  <th class="text-left py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Water added</th>
                  <th class="text-right py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Concentration</th>
                  <th class="text-right py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">100mcg dose</th>
                  <th class="text-right py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">250mcg dose</th>
                  <th class="text-right py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">500mcg dose</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in reconTable"
                  :key="row.water"
                  :class="[
                    'border-b border-[color:var(--color-hairline-soft)]',
                    row.water === waterMl ? 'bg-[color:var(--color-accent-50)] font-semibold' : '',
                  ]"
                >
                  <td class="py-2.5 ui-mono">{{ row.water }}mL</td>
                  <td class="py-2.5 text-right ui-mono">{{ row.conc.toLocaleString() }} mcg/mL</td>
                  <td class="py-2.5 text-right ui-mono">{{ row.d100 }} units</td>
                  <td class="py-2.5 text-right ui-mono">{{ row.d250 }} units</td>
                  <td class="py-2.5 text-right ui-mono">{{ row.d500 }} units</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Calculation breakdown -->
          <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h3 class="text-[13px] font-semibold text-[color:var(--color-ink)] mb-4">Calculation breakdown</h3>
            <div class="space-y-3 text-[13px] text-[color:var(--color-ink-muted)]">
              <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-bold flex items-center justify-center flex-shrink-0">1</span>
                <span>{{ peptideMg }}mg peptide &divide; {{ waterMl }}mL water = <strong class="text-[color:var(--color-ink)]">{{ (peptideMg / waterMl).toFixed(2) }}mg/mL</strong> ({{ concentration.toLocaleString() }} mcg/mL)</span>
              </div>
              <div v-if="mode !== 'reconstitution'" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-bold flex items-center justify-center flex-shrink-0">2</span>
                <span>{{ doseMcg }}mcg &divide; {{ concentration.toLocaleString() }} mcg/mL = <strong class="text-[color:var(--color-ink)]">{{ volumeMl }}mL</strong> per injection</span>
              </div>
              <div v-if="mode !== 'reconstitution'" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-bold flex items-center justify-center flex-shrink-0">3</span>
                <span>{{ volumeMl }}mL &times; 100 = <strong class="text-[color:var(--color-ink)]">{{ syringeMarks }} units</strong> on a {{ syringeUnits }}-unit insulin syringe</span>
              </div>
              <div v-if="mode !== 'reconstitution'" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-bold flex items-center justify-center flex-shrink-0">4</span>
                <span>{{ waterMl }}mL total &divide; {{ volumeMl }}mL per dose = <strong class="text-[color:var(--color-ink)]">{{ dosesPerVial }} doses</strong> per vial</span>
              </div>
            </div>
          </div>

          <!-- Quick reference -->
          <div class="grid sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
              <h3 class="text-[13px] font-semibold text-[color:var(--color-ink)] mb-3">Unit conversions</h3>
              <div class="space-y-2 text-[13px]">
                <div class="flex justify-between"><span class="text-[color:var(--color-ink-muted)]">1 mg</span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">1,000 mcg</span></div>
                <div class="flex justify-between"><span class="text-[color:var(--color-ink-muted)]">1 mL</span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">100 units</span></div>
                <div class="flex justify-between"><span class="text-[color:var(--color-ink-muted)]">0.1 mL</span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">10 units</span></div>
                <div class="flex justify-between"><span class="text-[color:var(--color-ink-muted)]">0.01 mL</span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">1 unit</span></div>
              </div>
            </div>
            <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
              <h3 class="text-[13px] font-semibold text-[color:var(--color-ink)] mb-3">Common syringe sizes</h3>
              <div class="space-y-2 text-[13px]">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[color:var(--color-accent-500)]"></span>
                  <span class="text-[color:var(--color-ink-muted)]">30-unit (0.3 mL) &mdash; low volume</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[color:var(--color-accent-500)]"></span>
                  <span class="text-[color:var(--color-ink-muted)]">50-unit (0.5 mL) &mdash; standard</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[color:var(--color-accent-500)]"></span>
                  <span class="text-[color:var(--color-ink-muted)]">100-unit (1.0 mL) &mdash; most common</span>
                </div>
              </div>
            </div>
          </div>

          <!-- SEO content -->
          <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h2 class="ui-display text-xl font-semibold text-[color:var(--color-ink)] mb-3">How to reconstitute peptides</h2>
            <div class="space-y-3 text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
              <p><strong class="text-[color:var(--color-ink)]">Step 1 &mdash; Gather supplies.</strong> You will need your lyophilized peptide vial, bacteriostatic water (BAC water), an alcohol swab, and an insulin syringe. A 1mL (100-unit) syringe is the most common choice.</p>
              <p><strong class="text-[color:var(--color-ink)]">Step 2 &mdash; Calculate your concentration.</strong> Use the calculator above. Enter the peptide amount printed on your vial label (e.g. 5mg) and the volume of BAC water you plan to add. A common ratio is 2mL of water per 5mg vial, giving a concentration of 2,500 mcg/mL.</p>
              <p><strong class="text-[color:var(--color-ink)]">Step 3 &mdash; Add water slowly.</strong> Swab both vial tops with alcohol. Draw the desired volume of BAC water into your syringe and inject it into the peptide vial. Aim the stream at the glass wall, not directly onto the powder. Let it dissolve gently &mdash; do not shake.</p>
              <p><strong class="text-[color:var(--color-ink)]">Step 4 &mdash; Draw your dose.</strong> Once fully dissolved (solution should be clear), use the calculator to determine how many units to draw. For a 250mcg dose from a 2,500 mcg/mL solution, you would draw 10 units (0.10 mL).</p>
              <p><strong class="text-[color:var(--color-ink)]">Step 5 &mdash; Store properly.</strong> Reconstituted peptides should be refrigerated at 2&ndash;8&deg;C and typically used within 28&ndash;30 days. Never freeze reconstituted peptides.</p>
            </div>
          </div>

        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

defineProps({
  seo: { type: Object, default: () => ({}) },
})

// --- Modes ---
const modes = [
  { key: 'dosage', label: 'Dosage' },
  { key: 'reconstitution', label: 'Reconstitution' },
  { key: 'schedule', label: 'Schedule' },
]
const mode = ref('dosage')

// --- Presets ---
const presets = [
  { name: 'BPC-157 (5mg)', mg: 5, water: 2, dose: 250 },
  { name: 'BPC-157 (10mg)', mg: 10, water: 3, dose: 500 },
  { name: 'TB-500 (5mg)', mg: 5, water: 2, dose: 2500 },
  { name: 'Semaglutide (3mg)', mg: 3, water: 1.5, dose: 250 },
  { name: 'Semaglutide (5mg)', mg: 5, water: 2, dose: 500 },
  { name: 'Tirzepatide (5mg)', mg: 5, water: 2, dose: 2500 },
  { name: 'Tirzepatide (10mg)', mg: 10, water: 2, dose: 5000 },
  { name: 'Retatrutide (5mg)', mg: 5, water: 2, dose: 1000 },
  { name: 'CJC-1295 (5mg)', mg: 5, water: 2.5, dose: 100 },
  { name: 'Ipamorelin (5mg)', mg: 5, water: 2.5, dose: 200 },
  { name: 'Tesamorelin (2mg)', mg: 2, water: 2, dose: 2000 },
  { name: 'Sermorelin (2mg)', mg: 2, water: 2, dose: 200 },
  { name: 'GHK-Cu (50mg)', mg: 50, water: 2, dose: 500 },
  { name: 'PT-141 (10mg)', mg: 10, water: 2, dose: 1750 },
  { name: 'AOD-9604 (5mg)', mg: 5, water: 2, dose: 300 },
  { name: 'MOTS-c (5mg)', mg: 5, water: 2.5, dose: 5000 },
  { name: 'NAD+ (100mg)', mg: 100, water: 2, dose: 5000 },
]

const preset = ref('')

function applyPreset() {
  const p = presets.find(x => x.name === preset.value)
  if (p) {
    peptideMg.value = p.mg
    waterMl.value = p.water
    doseMcg.value = p.dose
  }
}

// --- Inputs ---
const peptideMg = ref(5)
const waterMl = ref(2)
const doseMcg = ref(250)
const syringeUnits = ref(100)
const syringes = [30, 50, 100]
const frequency = ref('daily')

// --- Computed results ---
const concentration = computed(() => (peptideMg.value * 1000) / waterMl.value)

const volumeMlRaw = computed(() => doseMcg.value / concentration.value)
const volumeMl = computed(() => volumeMlRaw.value.toFixed(3))

const syringeMarks = computed(() => Math.round(volumeMlRaw.value * 100))

const fillPercent = computed(() => (syringeMarks.value / syringeUnits.value) * 100)

const dosesPerVial = computed(() => {
  if (volumeMlRaw.value <= 0) return 0
  return Math.floor(waterMl.value / volumeMlRaw.value)
})

// Schedule
const injectionsPerWeek = computed(() => {
  const map = { daily: 7, eod: 3.5, '3x': 3, '2x': 2, weekly: 1, biweekly: 0.5 }
  return map[frequency.value] || 1
})

const vialDurationDays = computed(() => {
  if (dosesPerVial.value <= 0 || injectionsPerWeek.value <= 0) return 0
  return Math.floor(dosesPerVial.value / (injectionsPerWeek.value / 7))
})

const vialsPerMonth = computed(() => {
  if (vialDurationDays.value <= 0) return '—'
  const v = 30 / vialDurationDays.value
  return v < 1 ? '< 1' : v.toFixed(1)
})

// Reconstitution table
const reconTable = computed(() => {
  const waters = [0.5, 1, 1.5, 2, 2.5, 3, 4, 5]
  return waters.map(w => {
    const conc = (peptideMg.value * 1000) / w
    return {
      water: w,
      conc,
      d100: Math.round((100 / conc) * 100),
      d250: Math.round((250 / conc) * 100),
      d500: Math.round((500 / conc) * 100),
    }
  })
})

// --- Subcomponents ---
const CalcField = {
  props: ['label', 'unit', 'modelValue', 'step', 'min'],
  emits: ['update:modelValue'],
  template: `
    <div class="mb-5">
      <label class="block text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-2">{{ label }}</label>
      <div class="relative">
        <input
          type="number"
          :value="modelValue"
          @input="$emit('update:modelValue', parseFloat($event.target.value) || 0)"
          :step="step"
          :min="min"
          class="w-full h-10 pl-3 pr-14 text-[14px] ui-mono border border-[color:var(--color-hairline)] rounded-[8px] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15"
        />
        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[12px] text-[color:var(--color-ink-subtle)] font-semibold">{{ unit }}</span>
      </div>
    </div>
  `,
}

const ResultCard = {
  props: ['label', 'value', 'unit', 'icon', 'color', 'highlight'],
  template: `
    <div :class="[
      'rounded-[14px] border p-5 transition-all',
      highlight
        ? 'bg-[color:var(--color-verified-bg)] border-[#A7F3D0] shadow-[var(--shadow-sm)]'
        : 'bg-white border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)]',
    ]">
      <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-2">{{ label }}</div>
      <div class="flex items-baseline gap-1.5">
        <span class="ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ value }}</span>
        <span class="text-[12px] text-[color:var(--color-ink-muted)]">{{ unit }}</span>
      </div>
    </div>
  `,
}
</script>
