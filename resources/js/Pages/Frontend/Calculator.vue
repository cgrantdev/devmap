<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- Hero -->
    <section class="border-b border-[color:var(--color-hairline)] bg-gradient-to-b from-[color:var(--color-bg)] to-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-8 pb-8">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-biotech-600)] mb-3">Free · No signup · Instant results</div>
        <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-3">
          The Best Peptide Calculator
        </h1>
        <p class="text-lg text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl mb-5">
          Reconstitution, dosage, and schedule — all in one place. 17+ preset compounds (BPC-157, Semaglutide, Tirzepatide, Retatrutide, TB-500, and more) with instant insulin-syringe unit output.
        </p>
        <!-- Trust chips: quantifies the "best" claim -->
        <div class="flex flex-wrap items-center gap-2 text-[12px]">
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold">⚡ Instant results</span>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-800 font-semibold">🧪 17+ presets</span>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-800 font-semibold">🔗 Shareable results</span>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-900 font-semibold">🆓 Free forever</span>
        </div>
      </div>
    </section>

    <!-- RUO Banner -->
    <div class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-6">
      <div class="flex items-start gap-3 p-4 rounded-[12px] bg-amber-50 border border-amber-200">
        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div>
          <p class="text-[12px] font-semibold text-amber-900">Research Use Only (RUO)</p>
          <p class="text-[11px] text-amber-800 leading-relaxed mt-0.5">This calculator is provided as a reference tool for laboratory research peptide reconstitution. All products and calculations are intended for <strong>in-vitro research and laboratory use only</strong>. Not for human consumption, diagnostic, or therapeutic use. Not medical advice.</p>
        </div>
      </div>
    </div>

    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-8">
      <div class="grid lg:grid-cols-[420px_1fr] gap-8 items-start">

        <!-- LEFT: Inputs -->
        <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-sm)] p-6 lg:sticky lg:top-24">
          <!-- Mode tabs -->
          <div class="flex gap-1 p-1 rounded-[10px] bg-[color:var(--color-bg)] mb-2">
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
          <!-- Inline mode hint — tells first-timers which tab is theirs. -->
          <div class="text-[11px] text-[color:var(--color-ink-subtle)] mb-5 px-1">
            <template v-if="mode === 'dosage'">Figure out how many syringe units to draw for a target dose.</template>
            <template v-else-if="mode === 'reconstitution'">Just mixed a vial with BAC water? See what concentration you got.</template>
            <template v-else-if="mode === 'schedule'">See how long each vial lasts at your dosing frequency.</template>
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

          <!-- Desired aliquot (reconstitution mode doesn't need this) -->
          <CalcField v-if="mode !== 'reconstitution'" label="Desired aliquot amount" unit="mcg" v-model="doseMcg" :step="10" :min="1" />

          <!-- Syringe size -->
          <div class="mb-5">
            <label class="block text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-2">Syringe capacity</label>
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

          <!-- Administration frequency (for schedule mode) -->
          <div v-if="mode === 'schedule'" class="mb-5">
            <label class="block text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-2">Administration frequency</label>
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
              <strong>RUO.</strong> For in-vitro research and laboratory use only. Not for human consumption or therapeutic use. Not medical advice.
            </p>
          </div>
        </div>

        <!-- RIGHT: Results -->
        <div class="space-y-6">

          <!-- HERO RESULT — the actual answer. What the user came for.
               Big syringe-unit number, integrated visual, one-click copy.
               Everything else on the page is supporting detail. -->
          <div v-if="mode !== 'reconstitution'" class="bg-gradient-to-b from-white to-[color:var(--color-bg)] rounded-[20px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-md)] p-6 lg:p-8">
            <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
              <div class="text-[10px] uppercase tracking-[0.14em] font-bold text-[color:var(--color-accent-600)]">Draw · from {{ concentration.toLocaleString('en-US', { maximumFractionDigits: 0 }) }} mcg/mL solution</div>
              <div class="flex items-center gap-2">
                <button @click="copyResult" :class="['inline-flex items-center gap-1.5 h-8 px-3 rounded-[8px] text-[11px] font-semibold transition-all', copiedResult ? 'bg-emerald-600 text-white' : 'bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink)]']">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><template v-if="copiedResult"><path d="M20 6L9 17l-5-5"/></template><template v-else><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></template></svg>
                  {{ copiedResult ? 'Copied' : 'Copy result' }}
                </button>
                <button @click="copyShareLink" :class="['inline-flex items-center gap-1.5 h-8 px-3 rounded-[8px] text-[11px] font-semibold transition-all', copiedShare ? 'bg-emerald-600 text-white' : 'bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink)]']">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><template v-if="copiedShare"><path d="M20 6L9 17l-5-5"/></template><template v-else><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></template></svg>
                  {{ copiedShare ? 'Copied' : 'Copy link' }}
                </button>
              </div>
            </div>

            <div class="flex items-baseline gap-3 mb-4">
              <span class="ui-display text-[64px] lg:text-[88px] leading-none font-bold text-[color:var(--color-ink)] tracking-tight">{{ syringeMarks }}</span>
              <span class="text-[color:var(--color-ink-muted)]">
                <div class="text-lg font-semibold">units</div>
                <div class="text-[13px]">on a {{ syringeUnits }}-unit syringe</div>
              </span>
            </div>

            <!-- Integrated syringe visual — the visual anchor. -->
            <div class="relative w-full h-12 rounded-full border-2 border-[color:var(--color-ink)]/15 bg-white overflow-hidden mb-4">
              <div
                class="absolute left-0 top-0 bottom-0 rounded-full transition-all duration-500 ease-out"
                :class="fillPercent > 80 ? 'bg-[color:var(--color-danger)]/25' : 'bg-[color:var(--color-accent-500)]/30'"
                :style="{ width: Math.min(fillPercent, 100) + '%' }"
              ></div>
              <div v-for="tick in 10" :key="tick" class="absolute top-0 h-3 border-l border-[color:var(--color-ink)]/15" :style="{ left: (tick * 10) + '%' }"></div>
              <div class="absolute top-1/2 -translate-y-1/2 w-0.5 h-8 bg-[color:var(--color-accent-600)] shadow-[0_0_6px_rgba(79,70,229,0.5)] transition-all duration-500 ease-out" :style="{ left: Math.min(fillPercent, 100) + '%' }"></div>
            </div>

            <div class="flex items-center justify-between gap-4 text-[13px] flex-wrap">
              <div class="flex items-center gap-1.5"><span class="text-[color:var(--color-ink-muted)]">Volume</span> <span class="ui-mono font-bold text-[color:var(--color-ink)]">{{ volumeMl }} mL</span></div>
              <div class="flex items-center gap-1.5"><span class="text-[color:var(--color-ink-muted)]">Dose</span> <span class="ui-mono font-bold text-[color:var(--color-ink)]">{{ doseMcg }} mcg</span></div>
              <div class="flex items-center gap-1.5"><span class="text-[color:var(--color-ink-muted)]">Aliquots per vial</span> <span class="ui-mono font-bold text-[color:var(--color-ink)]">{{ dosesPerVial }}</span></div>
            </div>

            <div v-if="fillPercent > 100" class="mt-4 flex items-center gap-2 text-[12px] text-[color:var(--color-danger)] font-semibold">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              Volume exceeds syringe capacity — use a larger syringe or add more reconstitution water.
            </div>
          </div>

          <!-- Reconstitution mode hero -->
          <div v-else class="bg-gradient-to-b from-white to-[color:var(--color-bg)] rounded-[20px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-md)] p-6 lg:p-8">
            <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
              <div class="text-[10px] uppercase tracking-[0.14em] font-bold text-[color:var(--color-accent-600)]">Reconstitution result</div>
              <div class="flex items-center gap-2">
                <button @click="copyResult" :class="['inline-flex items-center gap-1.5 h-8 px-3 rounded-[8px] text-[11px] font-semibold transition-all', copiedResult ? 'bg-emerald-600 text-white' : 'bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink)]']">
                  {{ copiedResult ? 'Copied' : 'Copy result' }}
                </button>
                <button @click="copyShareLink" :class="['inline-flex items-center gap-1.5 h-8 px-3 rounded-[8px] text-[11px] font-semibold transition-all', copiedShare ? 'bg-emerald-600 text-white' : 'bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink)]']">
                  {{ copiedShare ? 'Copied' : 'Copy link' }}
                </button>
              </div>
            </div>
            <div class="flex items-baseline gap-3">
              <span class="ui-display text-[56px] lg:text-[80px] leading-none font-bold text-[color:var(--color-ink)] tracking-tight">{{ concentration.toLocaleString('en-US', { maximumFractionDigits: 0 }) }}</span>
              <span class="text-[color:var(--color-ink-muted)]">
                <div class="text-lg font-semibold">mcg/mL</div>
                <div class="text-[13px]">{{ peptideMg }}mg peptide in {{ waterMl }}mL BAC water</div>
              </span>
            </div>
          </div>

          <!-- Schedule mode: vial duration -->
          <div v-if="mode === 'schedule'" class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h3 class="text-[13px] font-semibold text-[color:var(--color-ink)] mb-4">Vial yield schedule</h3>
            <div class="grid sm:grid-cols-3 gap-4">
              <div class="text-center p-4 rounded-[12px] bg-[color:var(--color-bg)]">
                <div class="ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ dosesPerVial }}</div>
                <div class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">aliquots per vial</div>
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
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-[color:var(--color-hairline)]">
                    <th class="text-left py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Water</th>
                    <th class="text-right py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Concentration</th>
                    <th class="text-right py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">100mcg</th>
                    <th class="text-right py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">250mcg</th>
                    <th class="text-right py-2 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">500mcg</th>
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
                <span>{{ doseMcg }}mcg &divide; {{ concentration.toLocaleString() }} mcg/mL = <strong class="text-[color:var(--color-ink)]">{{ volumeMl }}mL</strong> per aliquot</span>
              </div>
              <div v-if="mode !== 'reconstitution'" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-bold flex items-center justify-center flex-shrink-0">3</span>
                <span>{{ volumeMl }}mL &times; 100 = <strong class="text-[color:var(--color-ink)]">{{ syringeMarks }} units</strong> on a {{ syringeUnits }}-unit syringe</span>
              </div>
              <div v-if="mode !== 'reconstitution'" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-bold flex items-center justify-center flex-shrink-0">4</span>
                <span>{{ waterMl }}mL total &divide; {{ volumeMl }}mL per aliquot = <strong class="text-[color:var(--color-ink)]">{{ dosesPerVial }} aliquots</strong> per vial</span>
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
              <h3 class="text-[13px] font-semibold text-[color:var(--color-ink)] mb-3">Common syringe capacities</h3>
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

          <!-- Standalone share section removed — Copy result + Copy link
               buttons now live in the hero card at the top of results. -->

          <!-- FAQ — visible mirror of the FAQPage schema, required for the
               rich Q&A snippet to earn a SERP block. -->
          <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h2 class="ui-display text-xl font-semibold text-[color:var(--color-ink)] mb-4">Frequently asked</h2>
            <div class="space-y-5">
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">How do I calculate peptide dosage in insulin syringe units?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Divide your desired dose (in mcg) by the peptide concentration (in mcg/mL), then multiply by 100. Example: 250 mcg from a 2,500 mcg/mL solution = 0.10 mL = <strong class="ui-mono">10 units</strong> on a 100-unit insulin syringe. The calculator above does the math automatically.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">How much bacteriostatic water should I use to reconstitute a 5mg peptide?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">2 mL of bacteriostatic water per 5 mg vial is the most common ratio, giving a working concentration of 2,500 mcg/mL. Adjust based on your desired dose granularity — more water gives finer aliquots, less water gives fewer syringe units per dose.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">How long do reconstituted peptides last?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Once reconstituted with bacteriostatic water, peptides are typically stable for <strong>28-30 days</strong> when refrigerated at 2-8 °C. Do not freeze reconstituted peptides — freezing damages the peptide structure and reduces potency.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">Is the Peptidemap Peptide Calculator really free?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Yes — completely free, no signup, no email required, no paywall. Supports 17+ preset compounds and works in reconstitution, dosage, and schedule modes. Shareable results via URL parameters.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">Can I share my calculator settings?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Yes — every change to the calculator updates the URL with your current settings. Copy the URL (or click the "Copy shareable link" button above), and the person opening it sees the exact same inputs and results.</p>
              </div>
            </div>
          </div>

          <!-- SEO content -->
          <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h2 class="ui-display text-xl font-semibold text-[color:var(--color-ink)] mb-3">How to reconstitute research peptides</h2>
            <div class="space-y-3 text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
              <p><strong class="text-[color:var(--color-ink)]">Step 1 &mdash; Gather supplies.</strong> You will need the lyophilized peptide vial, bacteriostatic water (BAC water), alcohol swabs, and an appropriate syringe for measuring volume. A 1mL (100-unit) syringe is the most common choice for laboratory use.</p>
              <p><strong class="text-[color:var(--color-ink)]">Step 2 &mdash; Calculate your concentration.</strong> Use the calculator above. Enter the peptide amount printed on the vial label (e.g. 5mg) and the volume of BAC water you plan to add. A common ratio is 2mL of water per 5mg vial, giving a concentration of 2,500 mcg/mL.</p>
              <p><strong class="text-[color:var(--color-ink)]">Step 3 &mdash; Add water slowly.</strong> Swab both vial tops with alcohol. Draw the desired volume of BAC water and introduce it to the peptide vial. Direct the stream at the glass wall, not directly onto the lyophilized powder. Allow it to dissolve gently &mdash; do not shake or vortex aggressively.</p>
              <p><strong class="text-[color:var(--color-ink)]">Step 4 &mdash; Measure your aliquot.</strong> Once fully dissolved (solution should be clear), use the calculator to determine the appropriate volume. For a 250mcg aliquot from a 2,500 mcg/mL solution, the volume is 10 units (0.10 mL).</p>
              <p><strong class="text-[color:var(--color-ink)]">Step 5 &mdash; Store properly.</strong> Reconstituted peptides should be refrigerated at 2&ndash;8&deg;C and are typically stable for 28&ndash;30 days. Do not freeze reconstituted peptides.</p>
            </div>
          </div>

          <!-- Bottom RUO notice -->
          <div class="flex items-start gap-3 p-4 rounded-[12px] bg-[color:var(--color-bg)] border border-[color:var(--color-hairline)]">
            <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <p class="text-[11px] text-[color:var(--color-ink-subtle)] leading-relaxed">
              <strong class="text-[color:var(--color-ink-muted)]">Research Use Only.</strong> All products listed on PeptideMap are intended for laboratory and research purposes only. They are not intended for human consumption, therapeutic use, or any form of self-administration. Always comply with local regulations. PeptideMap does not provide medical advice.
            </p>
          </div>

        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

defineProps({
  seo: { type: Object, default: () => ({}) },
})

// --- Share-link support -----------------------------------------------
// Every meaningful input syncs to URL query params so users can copy the
// address bar and share their exact configuration. Hydrated on mount from
// whatever's in the URL — Colin Sep 7 revenue push, calculator as an SEO
// funnel + viral share vector.
const copiedShare = ref(false)
const copiedResult = ref(false)
async function copyShareLink() {
  try {
    await navigator.clipboard.writeText(window.location.href)
    copiedShare.value = true
    setTimeout(() => (copiedShare.value = false), 2000)
  } catch { /* clipboard blocked; user can still copy the URL manually */ }
}
// Copies the actual answer text — what people paste into group chats
// and forums. More useful than a URL when someone just wants the number.
async function copyResult() {
  const text = mode.value === 'reconstitution'
    ? `${peptideMg.value}mg peptide + ${waterMl.value}mL BAC water = ${concentration.value.toLocaleString('en-US', { maximumFractionDigits: 0 })} mcg/mL (via Peptidemap)`
    : `Draw ${syringeMarks.value} units on a ${syringeUnits.value}-unit syringe (${volumeMl.value} mL) for a ${doseMcg.value} mcg dose from a ${concentration.value.toLocaleString('en-US', { maximumFractionDigits: 0 })} mcg/mL solution (via Peptidemap)`
  try {
    await navigator.clipboard.writeText(text)
    copiedResult.value = true
    setTimeout(() => (copiedResult.value = false), 2000)
  } catch {}
}

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
const administrationsPerWeek = computed(() => {
  const map = { daily: 7, eod: 3.5, '3x': 3, '2x': 2, weekly: 1, biweekly: 0.5 }
  return map[frequency.value] || 1
})

const vialDurationDays = computed(() => {
  if (dosesPerVial.value <= 0 || administrationsPerWeek.value <= 0) return 0
  return Math.floor(dosesPerVial.value / (administrationsPerWeek.value / 7))
})

const vialsPerMonth = computed(() => {
  if (vialDurationDays.value <= 0) return '—'
  const v = 30 / vialDurationDays.value
  return v < 1 ? '< 1' : v.toFixed(1)
})

// --- URL <-> state sync -----------------------------------------------
// Hydrate from ?mode=&preset=&mg=&water=&dose=&syringe=&freq= on mount,
// then whenever any of those inputs change, replaceState so the URL
// mirrors what the user is looking at. Uses replaceState (not pushState)
// so the browser history doesn't clog with every keystroke.
onMounted(() => {
  if (typeof window === 'undefined') return
  const p = new URL(window.location.href).searchParams
  const get = (k, cast) => (p.has(k) ? (cast ? cast(p.get(k)) : p.get(k)) : null)
  const m = get('mode'); if (m && modes.some(x => x.key === m)) mode.value = m
  const pr = get('preset'); if (pr) { preset.value = pr; applyPreset() }
  const mg = get('mg', parseFloat); if (mg && mg > 0) peptideMg.value = mg
  const w = get('water', parseFloat); if (w && w > 0) waterMl.value = w
  const d = get('dose', parseFloat); if (d && d > 0) doseMcg.value = d
  const s = get('syringe', parseInt); if (s && [30, 50, 100].includes(s)) syringeUnits.value = s
  const f = get('freq'); if (f) frequency.value = f
})

watch([mode, preset, peptideMg, waterMl, doseMcg, syringeUnits, frequency], () => {
  if (typeof window === 'undefined') return
  const url = new URL(window.location.href)
  const set = (k, v, def) => (v !== undefined && v !== null && v !== '' && v !== def
    ? url.searchParams.set(k, String(v))
    : url.searchParams.delete(k))
  set('mode', mode.value, 'dosage')
  set('preset', preset.value, '')
  set('mg', peptideMg.value, 5)
  set('water', waterMl.value, 2)
  set('dose', doseMcg.value, 250)
  set('syringe', syringeUnits.value, 100)
  set('freq', frequency.value, 'daily')
  window.history.replaceState({}, '', url.toString())
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
  props: ['label', 'value', 'unit', 'highlight'],
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
