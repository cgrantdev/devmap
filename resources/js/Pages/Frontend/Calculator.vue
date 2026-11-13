<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- Hero -->
    <section class="border-b border-[color:var(--color-hairline)] bg-gradient-to-b from-[color:var(--color-bg)] to-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-6 pb-6 md:pt-8 md:pb-8">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-biotech-600)] mb-3">Free · No signup · Instant results</div>
        <h1 class="ui-display text-3xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-3">
          {{ pageHeading }}
        </h1>
        <p class="text-base md:text-lg text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl mb-5">
          Reconstitution, dosage, and schedule — all in one place. 55+ preset compounds across GLP-1s, growth-hormone secretagogues, healing peptides, longevity, and cognitive stacks — with instant insulin-syringe unit output.
        </p>
        <!-- Trust chips: quantifies the "best" claim -->
        <div class="flex flex-wrap items-center gap-2 text-[12px]">
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold">⚡ Instant results</span>
          <span v-if="presets.length" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-800 font-semibold">🧪 {{ presets.length }} presets</span>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-800 font-semibold">🔗 Shareable results</span>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-900 font-semibold">🆓 Free forever</span>
        </div>
      </div>
    </section>

    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-6 md:py-8">
      <div class="grid lg:grid-cols-[420px_1fr] gap-8 items-start">

        <!-- LEFT: Inputs -->
        <div ref="inputsRef" class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-sm)] p-6 lg:sticky lg:top-24">
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
          <div class="text-[12px] text-[color:var(--color-ink-muted)] leading-relaxed mb-5 px-1">
            <template v-if="mode === 'dosage'">
              <strong class="text-[color:var(--color-ink)]">Pick your peptide, enter your dose, see the exact syringe units.</strong>
              If you don't know your peptide's vial size or how much BAC water you added, pick a preset below and we'll fill in the standard values.
            </template>
            <template v-else-if="mode === 'reconstitution'">
              <strong class="text-[color:var(--color-ink)]">Just mixed a vial with BAC water?</strong> Enter what's on the vial and how much water you added — we'll show you the strength and a quick dose-to-units reference.
            </template>
            <template v-else-if="mode === 'schedule'">
              <strong class="text-[color:var(--color-ink)]">How long does one vial last?</strong> Enter your dose + how often you take it — we'll show how many days per vial and how many vials you'll go through in a month.
            </template>
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
              <optgroup v-for="g in presetGroups" :key="g.label" :label="g.label">
                <option v-for="p in g.items" :key="p.slug" :value="p.slug">{{ p.name }}</option>
              </optgroup>
            </select>
          </div>

          <!-- Peptide amount -->
          <CalcField label="Peptide in vial" unit="mg" v-model="peptideMg" placeholder="e.g. 5" />

          <!-- Water volume — called out as BAC water for clarity -->
          <CalcField label="BAC water added" unit="mL" v-model="waterMl" placeholder="e.g. 2" />

          <!-- Desired dose (reconstitution mode doesn't need this). Stored in
               mcg; the toggle only changes how it's entered and shown. -->
          <CalcField
            v-if="mode !== 'reconstitution'"
            label="Dose per injection"
            :unit="doseUnit"
            v-model="doseDisplay"
            :placeholder="doseUnit === 'mg' ? 'e.g. 2.5' : 'e.g. 250'"
          >
            <template #aside>
              <div class="inline-flex rounded-[7px] border border-[color:var(--color-hairline)] bg-[color:var(--color-bg)] p-0.5" role="group" aria-label="Dose unit">
                <button
                  v-for="u in doseUnits"
                  :key="u"
                  type="button"
                  @click="doseUnit = u"
                  :aria-pressed="doseUnit === u ? 'true' : 'false'"
                  :class="[
                    'h-6 px-2 rounded-[5px] text-[11px] font-semibold transition-all',
                    doseUnit === u
                      ? 'bg-white text-[color:var(--color-ink)] shadow-sm'
                      : 'text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]',
                  ]"
                >{{ u }}</button>
              </div>
            </template>
          </CalcField>

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
              <option v-for="o in frequencyOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
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

          <div ref="resultRef" class="scroll-mt-24">
          <!-- HERO RESULT — the actual answer. What the user came for.
               Big syringe-unit number, integrated visual, one-click copy.
               Everything else on the page is supporting detail. -->
          <div v-if="mode !== 'reconstitution'" class="bg-gradient-to-b from-white to-[color:var(--color-bg)] rounded-[20px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-md)] p-6 lg:p-8">
            <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
              <div class="text-[10px] uppercase tracking-[0.14em] font-bold text-[color:var(--color-accent-600)]">Draw · from {{ concentrationText }} mcg/mL solution</div>
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

            <div class="flex items-baseline gap-3 mb-2">
              <span class="ui-display text-[64px] lg:text-[88px] leading-none font-bold text-[color:var(--color-ink)] tracking-tight">{{ unitsText }}</span>
              <span class="text-[color:var(--color-ink-muted)]">
                <div class="text-lg font-semibold">units</div>
                <div class="text-[13px]">on a {{ syringeUnits }}-unit syringe</div>
              </span>
            </div>

            <!-- Plain-English restatement — reads the answer back to the
                 user so they don't have to decode the number. -->
            <p v-if="hasDraw" class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed mb-4">
              Draw the plunger to the <strong class="text-[color:var(--color-ink)]">{{ unitsText }}-unit mark</strong> on your {{ syringeUnits }}-unit insulin syringe for a {{ doseLabel }} dose.
            </p>
            <p v-else class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed mb-4">
              Enter the vial size, the BAC water you added and your dose to see the draw.
            </p>

            <!-- Syringe visual — now actually looks like a syringe + has
                 unit labels so the user can map the "pull to X units"
                 instruction to a physical mark on their own syringe. -->
            <div class="mb-5">
              <!-- Caption above tells the user what the picture is. -->
              <div class="flex items-center justify-between text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-2 px-1">
                <span>Your {{ syringeUnits }}-unit insulin syringe</span>
                <span class="text-[color:var(--color-accent-700)]">{{ plungerText }}</span>
              </div>
              <div class="relative pl-1 pr-10">
                <!-- Barrel -->
                <div class="relative h-9 bg-white border border-[color:var(--color-ink)]/25 rounded-[3px] overflow-hidden">
                  <!-- Fill (liquid inside the barrel) -->
                  <div
                    class="absolute left-0 top-0 bottom-0 transition-all duration-500 ease-out"
                    :class="fillPercent > 100 ? 'bg-[color:var(--color-danger)]/25' : 'bg-[color:var(--color-accent-500)]/35'"
                    :style="{ width: Math.min(fillPercent, 100) + '%' }"
                  ></div>
                  <!-- Minor tick marks every 10 units -->
                  <div v-for="tick in 9" :key="`minor-${tick}`" class="absolute top-0 h-2 border-l border-[color:var(--color-ink)]/25" :style="{ left: (tick * 10) + '%' }"></div>
                  <!-- Major tick marks every 20 units with labels -->
                  <div v-for="tick in 4" :key="`major-${tick}`" class="absolute top-0 h-full border-l border-[color:var(--color-ink)]/35" :style="{ left: (tick * 20) + '%' }"></div>
                  <!-- Plunger line (where the user pulls to) -->
                  <div class="absolute top-0 bottom-0 w-[3px] bg-[color:var(--color-accent-700)] shadow-[0_0_8px_rgba(79,70,229,0.5)] transition-all duration-500 ease-out z-10" :style="{ left: `calc(${Math.min(fillPercent, 100)}% - 1.5px)` }"></div>
                </div>
                <!-- Needle hint on the right -->
                <div class="absolute right-0 top-1/2 -translate-y-1/2 flex items-center">
                  <div class="w-2 h-[6px] bg-[color:var(--color-ink)]/50 rounded-r-[2px]"></div>
                  <div class="w-6 h-[2px] bg-[color:var(--color-ink)]/40"></div>
                </div>
                <!-- Unit scale under the barrel -->
                <div class="relative mt-1 h-4 text-[10px] ui-mono text-[color:var(--color-ink-subtle)]">
                  <span v-for="(lbl, i) in [0, 0.2, 0.4, 0.6, 0.8, 1]" :key="i"
                        class="absolute -translate-x-1/2"
                        :style="{ left: (lbl * 100) + '%' }">{{ Math.round(lbl * syringeUnits) }}</span>
                </div>
              </div>
            </div>

            <!-- Result stats underneath — plain English labels, no "aliquot". -->
            <div class="flex items-center justify-between gap-4 text-[13px] flex-wrap pt-4 border-t border-[color:var(--color-hairline-soft)]">
              <div class="flex items-center gap-1.5"><span class="text-[color:var(--color-ink-muted)]">Liquid volume</span> <span class="ui-mono font-bold text-[color:var(--color-ink)]">{{ volumeText }} mL</span></div>
              <div class="flex items-center gap-1.5"><span class="text-[color:var(--color-ink-muted)]">Dose</span> <span class="ui-mono font-bold text-[color:var(--color-ink)]">{{ doseLabel }}</span></div>
              <div class="flex items-center gap-1.5"><span class="text-[color:var(--color-ink-muted)]">Doses per vial</span> <span class="ui-mono font-bold text-[color:var(--color-ink)]">{{ dosesPerVialText }}</span></div>
            </div>

            <div v-if="doseExceedsVial" class="mt-4 flex items-start gap-2 text-[12px] text-[color:var(--color-danger)] font-semibold">
              <svg class="w-4 h-4 flex-shrink-0 mt-px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              <span>This dose is more than the whole vial holds ({{ vialMcgText }} mcg). Check the vial size and dose units.</span>
            </div>
            <div v-else-if="overCapacity" class="mt-4 flex items-start gap-2 text-[12px] text-[color:var(--color-danger)] font-semibold">
              <svg class="w-4 h-4 flex-shrink-0 mt-px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              <span>That's more than one {{ syringeUnits }}-unit syringe holds. Use a larger syringe, split the dose into two draws, or reconstitute with less BAC water.</span>
            </div>
            <div v-else-if="tinyDraw" class="mt-4 flex items-start gap-2 text-[12px] text-amber-700 font-semibold">
              <svg class="w-4 h-4 flex-shrink-0 mt-px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              <span>Under 5 units is hard to measure accurately. A 30-unit syringe has finer marks, or add more BAC water so each dose is a larger volume.</span>
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
              <span class="ui-display text-[56px] lg:text-[80px] leading-none font-bold text-[color:var(--color-ink)] tracking-tight">{{ concentrationText }}</span>
              <span class="text-[color:var(--color-ink-muted)]">
                <div class="text-lg font-semibold">mcg/mL</div>
                <div class="text-[13px]">{{ mgText }}mg peptide in {{ waterText }}mL BAC water</div>
              </span>
            </div>
          </div>
          </div>

          <!-- Price this vial: live cheapest listing for the page's preset. -->
          <PriceThisVial
            v-if="pricingForCurrent"
            :pricing="pricingForCurrent"
            :compound-name="currentCompoundName"
            :vial-mg="peptideMg"
            :doses-per-vial="dosesPerVial"
            :per-week="administrationsPerWeek"
            :stability-days="STABILITY_DAYS"
            :frequency-options="frequencyOptions"
            v-model:frequency="frequency"
          />
          <div v-else-if="compareHref" class="flex flex-wrap items-center gap-x-5 gap-y-2 rounded-[14px] border border-[color:var(--color-hairline)] bg-white px-5 py-4 text-[13px] font-semibold">
            <Link :href="compareHref" class="text-[color:var(--color-accent-700)] hover:underline">Compare {{ currentCompoundName }} prices across vendors &rarr;</Link>
            <Link href="/bacteriostatic-water" class="text-[color:var(--color-accent-700)] hover:underline">Bacteriostatic water prices &rarr;</Link>
          </div>

          <!-- Schedule mode: vial duration -->
          <div v-if="mode === 'schedule'" class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h3 class="text-[13px] font-semibold text-[color:var(--color-ink)] mb-4">Vial yield schedule</h3>
            <div class="grid sm:grid-cols-3 gap-4">
              <div class="text-center p-4 rounded-[12px] bg-[color:var(--color-bg)]">
                <div class="ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ dosesPerVialText }}</div>
                <div class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">doses per vial</div>
              </div>
              <div class="text-center p-4 rounded-[12px] bg-[color:var(--color-bg)]">
                <div :class="['ui-mono text-2xl font-bold', stabilityExceeded ? 'text-amber-700' : 'text-[color:var(--color-accent-600)]']">{{ vialDurationText }}</div>
                <div class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">days per vial</div>
              </div>
              <div class="text-center p-4 rounded-[12px] bg-[color:var(--color-bg)]">
                <div class="ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ vialsPerMonth }}</div>
                <div class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">vials per month</div>
              </div>
            </div>
            <div v-if="stabilityExceeded" class="mt-4 flex items-start gap-2 rounded-[10px] bg-amber-50 border border-amber-200 p-3 text-[12px] text-amber-900">
              <svg class="w-4 h-4 flex-shrink-0 mt-px text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
              <span>At this schedule one vial lasts {{ vialDurationText }} days, longer than the ~{{ STABILITY_DAYS }} days reconstituted peptide keeps in the fridge. Plan on a fresh vial about every {{ STABILITY_DAYS }} days (the vials-per-month figure assumes this), or choose a smaller vial.</span>
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
                    <td class="py-2.5 text-right ui-mono">{{ fmtNum(row.conc, 0) }} mcg/mL</td>
                    <td class="py-2.5 text-right ui-mono">{{ fmtNum(row.d100, 1) }} units</td>
                    <td class="py-2.5 text-right ui-mono">{{ fmtNum(row.d250, 1) }} units</td>
                    <td class="py-2.5 text-right ui-mono">{{ fmtNum(row.d500, 1) }} units</td>
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
                <span>{{ mgText }}mg peptide &divide; {{ waterText }}mL water = <strong class="text-[color:var(--color-ink)]">{{ mgPerMlText }}mg/mL</strong> ({{ concentrationText }} mcg/mL)</span>
              </div>
              <div v-if="mode !== 'reconstitution'" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-bold flex items-center justify-center flex-shrink-0">2</span>
                <span>{{ doseMcgText }}mcg &divide; {{ concentrationText }} mcg/mL = <strong class="text-[color:var(--color-ink)]">{{ volumeText }}mL</strong> per dose</span>
              </div>
              <div v-if="mode !== 'reconstitution'" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-bold flex items-center justify-center flex-shrink-0">3</span>
                <span>{{ volumeText }}mL &times; 100 = <strong class="text-[color:var(--color-ink)]">{{ unitsText }} units</strong> on a {{ syringeUnits }}-unit syringe</span>
              </div>
              <div v-if="mode !== 'reconstitution'" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-bold flex items-center justify-center flex-shrink-0">4</span>
                <span>{{ vialMcgText }}mcg in the vial &divide; {{ doseMcgText }}mcg per dose = <strong class="text-[color:var(--color-ink)]">{{ dosesPerVialText }} doses</strong> per vial</span>
              </div>
            </div>
          </div>

          <!-- RUO notice, below the tool so the answer isn't pushed down on mobile. -->
          <div class="flex items-start gap-3 p-4 rounded-[12px] bg-amber-50 border border-amber-200">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <div>
              <p class="text-[12px] font-semibold text-amber-900">Research Use Only (RUO)</p>
              <p class="text-[11px] text-amber-800 leading-relaxed mt-0.5">This calculator is provided as a reference tool for laboratory research peptide reconstitution. All products and calculations are intended for <strong>in-vitro research and laboratory use only</strong>. Not for human consumption, diagnostic, or therapeutic use. Not medical advice.</p>
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

          <!-- Share this calculation — explains the shareable-URL flow in
               plain language. Every input (compound, mg, water, dose,
               syringe, frequency) is already encoded in the URL above, so
               copying the link = sending the exact same setup. -->
          <div class="rounded-[16px] border border-[color:var(--color-accent-200)] bg-[color:var(--color-accent-50)] p-6">
            <div class="flex items-start gap-4">
              <div class="shrink-0 w-10 h-10 rounded-[10px] bg-white border border-[color:var(--color-accent-200)] flex items-center justify-center text-[color:var(--color-accent-600)]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
              </div>
              <div class="flex-1 min-w-0">
                <h2 class="ui-display text-lg font-semibold text-[color:var(--color-ink)] mb-1">Share this calculation</h2>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed mb-3">
                  Every change you make here is saved into the page's URL. Send the link to anyone &mdash; a friend, a Reddit thread, a Discord group &mdash; and they'll open the calculator preloaded with your exact
                  <template v-if="currentPreset"><strong class="text-[color:var(--color-ink)]">{{ currentPreset.name }}</strong> preset, </template>
                  <strong class="text-[color:var(--color-ink)]">{{ mgText }}mg vial</strong>,
                  <strong class="text-[color:var(--color-ink)]">{{ waterText }}mL</strong> BAC water, and
                  <strong class="text-[color:var(--color-ink)]">{{ doseLabel }} dose</strong>. No signup, no login, same calculation on the other end.
                </p>
                <div class="flex flex-wrap gap-2 mb-3">
                  <button @click="copyShareLink" :class="['inline-flex items-center gap-2 h-9 px-4 rounded-[8px] text-[13px] font-semibold transition-all', copiedShare ? 'bg-emerald-600 text-white' : 'bg-[color:var(--color-ink)] text-white hover:opacity-90']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><template v-if="copiedShare"><path d="M20 6L9 17l-5-5"/></template><template v-else><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></template></svg>
                    {{ copiedShare ? 'Link copied' : 'Copy shareable link' }}
                  </button>
                  <button @click="copyResult" :class="['inline-flex items-center gap-2 h-9 px-4 rounded-[8px] text-[13px] font-semibold transition-all', copiedResult ? 'bg-emerald-600 text-white' : 'bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)]']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><template v-if="copiedResult"><path d="M20 6L9 17l-5-5"/></template><template v-else><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></template></svg>
                    {{ copiedResult ? 'Result copied' : 'Copy result as text' }}
                  </button>
                  <button v-if="canNativeShare" @click="nativeShare" class="inline-flex items-center gap-2 h-9 px-4 rounded-[8px] text-[13px] font-semibold bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    Share&hellip;
                  </button>
                </div>
                <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-1">Your shareable link</div>
                <div class="flex items-center gap-2">
                  <input :value="shareUrl" readonly @focus="e => e.target.select()" class="flex-1 h-9 px-3 text-[12px] ui-mono border border-[color:var(--color-hairline)] rounded-[8px] bg-white text-[color:var(--color-ink-muted)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
                </div>
              </div>
            </div>
          </div>

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
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Yes — completely free, no signup, no email required, no paywall. Supports 55+ preset compounds and works in reconstitution, dosage, and schedule modes. Shareable results via URL parameters.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">Can I share my calculator settings?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Yes — every change to the calculator updates the URL with your current settings. Copy the URL (or click the "Copy shareable link" button above), and the person opening it sees the exact same inputs and results.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">What's the difference between bacteriostatic water and sterile water?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Bacteriostatic water contains <strong>0.9% benzyl alcohol</strong>, which inhibits bacterial growth and lets a reconstituted peptide stay stable for 28–30 days refrigerated. Sterile water contains no preservative, so a reconstituted vial should be used within a few hours. For multi-use research vials, bacteriostatic water is standard.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">What size insulin syringe should I use — 30, 50, or 100 units?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Choose based on the aliquot volume you need. A <strong>30-unit (0.3 mL)</strong> syringe gives the finest resolution for small aliquots under 20 units. A <strong>50-unit (0.5 mL)</strong> syringe is the common middle ground. A <strong>100-unit (1 mL)</strong> syringe is best when you need to draw volumes above 0.5 mL in one pull. The calculator shows the required units for whichever syringe you pick.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">How do I convert between mcg, mg, and IU?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed"><strong>1 mg = 1,000 mcg.</strong> So a 5mg vial = 5,000 mcg of peptide. IU (international units) are compound-specific — for example, 1 IU of HGH ≈ 0.33 mg, but every peptide has its own IU-to-mg ratio set by its monograph. For reconstitution math, always work in mg and mcg; convert IU at the end if the vendor labels in IU.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">Can I split one vial into multiple aliquots over several days?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Yes — reconstituted peptide stays stable 28–30 days refrigerated at 2–8°C, so one vial typically covers a multi-week cycle. The Schedule mode above shows exactly how many aliquots per vial and how long one vial lasts at your frequency.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">Can I freeze reconstituted peptides to make them last longer?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">No. Freezing a reconstituted peptide forms ice crystals that disrupt the peptide's structure and reduce its potency. The standard practice is to keep lyophilized (unmixed) powder frozen at −20°C for long-term storage, and only reconstitute what you need over the following 28–30 days — kept in the refrigerator (not the freezer) once mixed.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">How do I know if my peptide has gone bad?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">A healthy reconstituted solution is <strong>clear and colorless</strong>. Warning signs: cloudiness, visible particulates, discoloration (yellow or brown tint), odor, or a precipitate at the bottom of the vial. Any of these indicate degradation and the vial should be discarded. Note that some peptides (notably NAD+ and GHK-Cu) are naturally coloured — know the baseline for your specific compound.</p>
              </div>
              <div>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-1">Can I mix two peptides into the same vial?</h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">Some combinations are commercially sold pre-blended (CJC-1295 + Ipamorelin, BPC-157 + TB-500) and are stable together in solution. For arbitrary combinations we recommend keeping them in separate vials — some peptides interact chemically, and co-reconstitution can shorten the stable shelf life of both. If you need to co-administer, draw each dose from its own vial into the same syringe just before use.</p>
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

          <!-- Storage + handling reference -->
          <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h2 class="ui-display text-xl font-semibold text-[color:var(--color-ink)] mb-3">Storage &amp; handling by state</h2>
            <div class="grid gap-4 md:grid-cols-3 text-[13px] leading-relaxed">
              <div class="rounded-[10px] border border-[color:var(--color-hairline-soft)] p-4">
                <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-1.5">Lyophilized (unmixed)</div>
                <ul class="space-y-1.5 text-[color:var(--color-ink-muted)] list-disc list-inside">
                  <li>Long-term: <strong class="text-[color:var(--color-ink)]">freezer, &minus;20&deg;C</strong></li>
                  <li>Short-term: refrigerator, 2&ndash;8&deg;C</li>
                  <li>Stability: 24+ months properly stored</li>
                  <li>Keep away from light + humidity</li>
                </ul>
              </div>
              <div class="rounded-[10px] border border-[color:var(--color-hairline-soft)] p-4">
                <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-1.5">Reconstituted</div>
                <ul class="space-y-1.5 text-[color:var(--color-ink-muted)] list-disc list-inside">
                  <li>Storage: <strong class="text-[color:var(--color-ink)]">refrigerator, 2&ndash;8&deg;C</strong></li>
                  <li>Stability: 28&ndash;30 days typical</li>
                  <li>Never freeze (destroys the peptide)</li>
                  <li>Discard if cloudy, discolored, or particulate</li>
                </ul>
              </div>
              <div class="rounded-[10px] border border-[color:var(--color-hairline-soft)] p-4">
                <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-1.5">In transit / shipping</div>
                <ul class="space-y-1.5 text-[color:var(--color-ink-muted)] list-disc list-inside">
                  <li>Short exposure to room temperature is tolerated</li>
                  <li>Refrigerate on arrival, don't delay</li>
                  <li>Vials sealed in foil pouches handle shipping best</li>
                  <li>Document any heat damage before use</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Troubleshooting -->
          <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] p-6">
            <h2 class="ui-display text-xl font-semibold text-[color:var(--color-ink)] mb-3">Troubleshooting common issues</h2>
            <div class="space-y-3 text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
              <p><strong class="text-[color:var(--color-ink)]">Vial doesn't fully dissolve &mdash;</strong> gently swirl the vial by rolling it between your palms. Do not shake or vortex. If particles persist after 10 minutes at room temperature, add 0.25&ndash;0.5 mL more BAC water and swirl again. Larger peptides and some blends take longer to dissolve than small ones.</p>
              <p><strong class="text-[color:var(--color-ink)]">Air bubble in the syringe &mdash;</strong> hold the syringe needle-up, tap the barrel to float the bubble to the top, then slowly press the plunger to expel the air. Redraw to the correct unit mark if you've expelled some solution with the bubble.</p>
              <p><strong class="text-[color:var(--color-ink)]">Solution turned cloudy or yellow &mdash;</strong> degradation. The peptide should be discarded. Common causes: freezing a reconstituted vial, exposure to light or heat, bacterial contamination from a compromised seal, or storage past the ~30-day refrigerated window.</p>
              <p><strong class="text-[color:var(--color-ink)]">Can't draw an accurate aliquot &mdash;</strong> switch to a smaller syringe. If the calculator shows under 10 units on a 100-unit syringe, you'll get finer resolution on a 50- or 30-unit syringe. Alternatively, reconstitute with less water so each unit represents fewer mcg.</p>
              <p><strong class="text-[color:var(--color-ink)]">Lost track of what's in the vial &mdash;</strong> label the vial on reconstitution with the compound name, reconstitution date, and final concentration (mcg/mL). The calculator's "Copy result" button generates a one-line summary suitable for a vial label.</p>
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

    <!-- Mobile sticky result: shows the answer while the inputs are on
         screen and the result card isn't. Tap to jump to the full result. -->
    <transition
      enter-active-class="transition-transform duration-200 ease-out"
      enter-from-class="translate-y-full"
      enter-to-class="translate-y-0"
      leave-active-class="transition-transform duration-150 ease-in"
      leave-from-class="translate-y-0"
      leave-to-class="translate-y-full"
    >
      <button
        v-if="showStickyBar"
        type="button"
        @click="scrollToResult"
        class="lg:hidden fixed inset-x-0 bottom-0 z-30 w-full text-left bg-white/95 backdrop-blur border-t border-[color:var(--color-hairline)] shadow-[0_-4px_16px_rgba(10,11,14,0.08)] px-5 pt-3"
        style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom))"
      >
        <div class="flex items-center justify-between gap-3">
          <div v-if="mode !== 'reconstitution'">
            <div class="text-[10px] uppercase tracking-[0.12em] font-bold text-[color:var(--color-accent-600)]">Draw</div>
            <div class="ui-mono text-[22px] font-bold leading-tight text-[color:var(--color-ink)]">{{ unitsText }} <span class="text-[13px] font-semibold text-[color:var(--color-ink-muted)]">units</span></div>
          </div>
          <div v-else>
            <div class="text-[10px] uppercase tracking-[0.12em] font-bold text-[color:var(--color-accent-600)]">Concentration</div>
            <div class="ui-mono text-[22px] font-bold leading-tight text-[color:var(--color-ink)]">{{ concentrationText }} <span class="text-[13px] font-semibold text-[color:var(--color-ink-muted)]">mcg/mL</span></div>
          </div>
          <div class="text-right text-[12px] text-[color:var(--color-ink-muted)]">
            <div v-if="mode !== 'reconstitution'">{{ volumeText }} mL &middot; {{ syringeUnits }}U syringe</div>
            <div class="font-semibold text-[color:var(--color-accent-700)]">Full result &darr;</div>
          </div>
        </div>
      </button>
    </transition>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import CalcField from '@/components/calculator/CalcField.vue'
import PriceThisVial from '@/components/calculator/PriceThisVial.vue'

const props = defineProps({
  seo: { type: Object, default: () => ({}) },
  presets: { type: Array, default: () => [] },
  // Cheapest live listing for this page's preset (null when none).
  pricing: { type: Object, default: null },
  // "{Compound} Calculator" on /calculator/{slug}; null on /calculator.
  heading: { type: String, default: null },
})

// Reconstituted peptide keeps roughly this long refrigerated.
const STABILITY_DAYS = 28
const GENERIC_DEFAULTS = { mg: 5, water: 2, dose: 250 }

const pageHeading = computed(() => props.heading || 'The Best Peptide Calculator')

// --- Formatting helpers -------------------------------------------------
function fmtNum(n, maxDigits) {
  if (typeof n !== 'number' || !Number.isFinite(n)) return '—'
  return n.toLocaleString('en-US', { maximumFractionDigits: maxDigits })
}
function isPos(v) {
  return typeof v === 'number' && Number.isFinite(v) && v > 0
}
function compoundOf(name) {
  return String(name || '').replace(/\s*\([^)]*\)\s*$/, '').trim()
}

// --- Share-link support -----------------------------------------------
// Every meaningful input syncs to URL query params so users can copy the
// address bar and share their exact configuration.
const copiedShare = ref(false)
const copiedResult = ref(false)
const shareUrl = ref('')
const canNativeShare = ref(false)

async function nativeShare() {
  if (!navigator.share) return
  const text = mode.value === 'reconstitution'
    ? `My peptide reconstitution: ${mgText.value}mg + ${waterText.value}mL BAC water = ${concentrationText.value} mcg/mL`
    : `${currentPreset.value ? currentPreset.value.name : 'My peptide dose'}: ${doseLabel.value} = ${unitsText.value} units`
  try {
    await navigator.share({ title: 'Peptide calculation', text, url: shareUrl.value || window.location.href })
  } catch { /* user cancelled */ }
}
async function copyShareLink() {
  try {
    await navigator.clipboard.writeText(shareUrl.value || window.location.href)
    copiedShare.value = true
    setTimeout(() => (copiedShare.value = false), 2000)
  } catch { /* clipboard blocked; user can still copy the URL manually */ }
}
// Copies the actual answer text — what people paste into group chats
// and forums. More useful than a URL when someone just wants the number.
async function copyResult() {
  const text = mode.value === 'reconstitution'
    ? `${mgText.value}mg peptide + ${waterText.value}mL BAC water = ${concentrationText.value} mcg/mL (via Peptidemap)`
    : `Draw ${unitsText.value} units (${volumeText.value} mL) on a ${syringeUnits.value}-unit syringe for a ${doseLabel.value} dose from a ${concentrationText.value} mcg/mL solution (via Peptidemap)`
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
// Presets come from the server (app/Support/CalculatorPresets.php), the
// single source of truth for the dropdown and /calculator/{slug} routing.
const presets = computed(() => props.presets || [])

const presetGroups = computed(() => {
  const groups = {}
  for (const p of presets.value) {
    (groups[p.group] ||= []).push(p)
  }
  return Object.entries(groups).map(([label, items]) => ({ label, items }))
})

// Selected preset slug; '' means custom values.
const preset = ref('')
const currentPreset = computed(() => presets.value.find(p => p.slug === preset.value) || null)
const currentCompoundName = computed(() => (currentPreset.value ? compoundOf(currentPreset.value.name) : ''))
const pagePresetSlug = computed(() => props.seo?.prefilled_preset_slug || '')

// Accepts a slug, or a display name from links shared before slugs were used.
function findPreset(key) {
  if (!key) return null
  const k = String(key)
  return presets.value.find(p => p.slug === k) || presets.value.find(p => p.name === k) || null
}

function defaultUnitFor(p) {
  return p && p.group === 'GLP-1 / Weight' ? 'mg' : 'mcg'
}

function applyPreset() {
  const p = currentPreset.value
  if (!p) return
  peptideMg.value = p.mg
  waterMl.value = p.water
  doseMcg.value = p.dose
  doseUnit.value = defaultUnitFor(p)
}

// --- Inputs ---
// Dose is always stored in mcg; the mcg/mg toggle only changes entry and display.
const peptideMg = ref(GENERIC_DEFAULTS.mg)
const waterMl = ref(GENERIC_DEFAULTS.water)
const doseMcg = ref(GENERIC_DEFAULTS.dose)
const doseUnit = ref('mcg')
const doseUnits = ['mcg', 'mg']
const syringeUnits = ref(100)
const syringes = [30, 50, 100]
const frequency = ref('daily')
const frequencyOptions = [
  { value: 'daily', label: 'Daily' },
  { value: 'eod', label: 'Every other day' },
  { value: '3x', label: '3x per week' },
  { value: '2x', label: '2x per week' },
  { value: 'weekly', label: 'Weekly' },
  { value: 'biweekly', label: 'Every 2 weeks' },
]

const doseDisplay = computed({
  get() {
    if (!isPos(doseMcg.value)) return null
    return doseUnit.value === 'mg' ? doseMcg.value / 1000 : doseMcg.value
  },
  set(v) {
    if (!isPos(v)) { doseMcg.value = null; return }
    const mcg = doseUnit.value === 'mg' ? v * 1000 : v
    // Round off float noise (1.1 mg * 1000 = 1100.0000000000002).
    doseMcg.value = Math.round(mcg * 1000) / 1000
  },
})

// --- Computed results ---
const inputsValid = computed(() => isPos(peptideMg.value) && isPos(waterMl.value))
const doseValid = computed(() => isPos(doseMcg.value))

const concentration = computed(() => (inputsValid.value ? (peptideMg.value * 1000) / waterMl.value : null))
const concentrationText = computed(() => fmtNum(concentration.value, 0))
const mgText = computed(() => fmtNum(peptideMg.value, 3))
const waterText = computed(() => fmtNum(waterMl.value, 3))
const mgPerMlText = computed(() => (inputsValid.value ? (peptideMg.value / waterMl.value).toFixed(2) : '—'))
const vialMcgText = computed(() => (isPos(peptideMg.value) ? fmtNum(peptideMg.value * 1000, 3) : '—'))

// Units on a U-100 insulin syringe (100 units per mL), shown to 0.1 unit.
const unitsRaw = computed(() => (concentration.value && doseValid.value
  ? (doseMcg.value / concentration.value) * 100
  : null))
const unitsRounded = computed(() => (unitsRaw.value === null ? null : Math.round(unitsRaw.value * 10) / 10))
const hasDraw = computed(() => unitsRounded.value !== null)
const unitsText = computed(() => {
  if (unitsRounded.value === null) return '—'
  return Number.isInteger(unitsRounded.value) ? String(unitsRounded.value) : unitsRounded.value.toFixed(1)
})
// Volume is derived from the rounded units so "mL x 100 = units" reads exactly.
const volumeText = computed(() => (unitsRounded.value === null ? '—' : (unitsRounded.value / 100).toFixed(3)))

const fillPercent = computed(() => (unitsRaw.value === null ? 0 : (unitsRaw.value / syringeUnits.value) * 100))
const overCapacity = computed(() => unitsRaw.value !== null && unitsRaw.value > syringeUnits.value + 1e-9)
const tinyDraw = computed(() => unitsRaw.value !== null && unitsRaw.value < 5)
const doseExceedsVial = computed(() => doseValid.value && isPos(peptideMg.value) && doseMcg.value > peptideMg.value * 1000 + 1e-9)
const plungerText = computed(() => {
  if (!hasDraw.value) return 'Enter your numbers'
  if (overCapacity.value) return `More than ${syringeUnits.value} units`
  return `Pull plunger to ${unitsText.value} units`
})

const doseMcgText = computed(() => fmtNum(doseMcg.value, 3))
const doseMgText = computed(() => (doseValid.value ? fmtNum(doseMcg.value / 1000, 4) : '—'))
const doseLabel = computed(() => {
  if (!doseValid.value) return '—'
  return doseUnit.value === 'mg'
    ? `${doseMgText.value} mg (${doseMcgText.value} mcg)`
    : `${doseMcgText.value} mcg (${doseMgText.value} mg)`
})

// Whole doses in the vial. The epsilon absorbs float error that used to
// turn 50.0 into 49.999... and floor it to 49.
const dosesPerVial = computed(() => {
  if (!isPos(peptideMg.value) || !doseValid.value) return null
  return Math.floor((peptideMg.value * 1000) / doseMcg.value + 1e-9)
})
const dosesPerVialText = computed(() => (dosesPerVial.value === null ? '—' : String(dosesPerVial.value)))

// Schedule
const administrationsPerWeek = computed(() => {
  const map = { daily: 7, eod: 3.5, '3x': 3, '2x': 2, weekly: 1, biweekly: 0.5 }
  return map[frequency.value] || 1
})

const vialDurationDays = computed(() => {
  if (!dosesPerVial.value || administrationsPerWeek.value <= 0) return null
  return Math.floor(dosesPerVial.value / (administrationsPerWeek.value / 7) + 1e-9)
})
const vialDurationText = computed(() => (vialDurationDays.value === null ? '—' : String(vialDurationDays.value)))
const stabilityExceeded = computed(() => vialDurationDays.value !== null && vialDurationDays.value > STABILITY_DAYS)

// A vial is replaced at least every STABILITY_DAYS once mixed.
const vialsPerMonth = computed(() => {
  if (!vialDurationDays.value) return '—'
  const v = 30 / Math.min(vialDurationDays.value, STABILITY_DAYS)
  return v < 1 ? '< 1' : v.toFixed(1)
})

// Reconstitution table (units to 0.1, matching the main result)
function unitsFor(doseMcgValue, conc) {
  return Math.round((doseMcgValue / conc) * 1000) / 10
}
const reconTable = computed(() => {
  if (!isPos(peptideMg.value)) return []
  return [0.5, 1, 1.5, 2, 2.5, 3, 4, 5].map(w => {
    const conc = (peptideMg.value * 1000) / w
    return { water: w, conc, d100: unitsFor(100, conc), d250: unitsFor(250, conc), d500: unitsFor(500, conc) }
  })
})

// --- Pricing ---
// Full card only for the preset the page loaded; other presets get a link.
const pricingForCurrent = computed(() => {
  const p = props.pricing
  return p && currentPreset.value && p.preset_slug === currentPreset.value.slug ? p : null
})
const compareHref = computed(() => {
  const p = currentPreset.value
  return p && p.compound ? `/compare/${p.compound}` : null
})

// --- URL <-> state sync -----------------------------------------------
// Hydrate from ?mode=&preset=&mg=&water=&dose=&syringe=&freq= on mount,
// then mirror every change into the URL with replaceState. Params that
// match the current preset (or the generic defaults) are left out, and the
// page's own preset needs no ?preset= at all, so untouched preset pages
// keep a clean URL.
onMounted(() => {
  if (typeof window === 'undefined') return
  canNativeShare.value = typeof navigator !== 'undefined' && typeof navigator.share === 'function'
  const params = new URL(window.location.href).searchParams

  // An explicit ?preset= wins over the page's preset so shared links
  // reproduce the sender's choice; ?preset=custom means no preset.
  const qp = params.get('preset')
  const start = qp === 'custom' ? null : (findPreset(qp) || findPreset(pagePresetSlug.value))
  if (start) { preset.value = start.slug; applyPreset() }

  const m = params.get('mode'); if (m && modes.some(x => x.key === m)) mode.value = m
  const num = (k) => { const v = parseFloat(params.get(k)); return Number.isFinite(v) && v > 0 ? v : null }
  const mg = num('mg'); if (mg) peptideMg.value = mg
  const w = num('water'); if (w) waterMl.value = w
  const d = num('dose'); if (d) doseMcg.value = d
  const s = parseInt(params.get('syringe'), 10); if (syringes.includes(s)) syringeUnits.value = s
  const f = params.get('freq'); if (f && frequencyOptions.some(o => o.value === f)) frequency.value = f
  shareUrl.value = window.location.href

  setupStickyObserver()
})

watch([mode, preset, peptideMg, waterMl, doseMcg, syringeUnits, frequency], () => {
  if (typeof window === 'undefined') return
  const url = new URL(window.location.href)
  const set = (k, v, def) => {
    if (v === undefined || v === null || v === '' || (def !== undefined && v === def)) url.searchParams.delete(k)
    else url.searchParams.set(k, String(v))
  }

  let presetParam = null
  if (currentPreset.value) {
    presetParam = currentPreset.value.slug === pagePresetSlug.value ? null : currentPreset.value.slug
  } else if (pagePresetSlug.value) {
    presetParam = 'custom'
  }
  set('preset', presetParam)

  // Custom values on a preset page are always written out; otherwise only
  // values that differ from the active preset (or generic defaults).
  const base = currentPreset.value || (pagePresetSlug.value ? null : GENERIC_DEFAULTS)
  set('mode', mode.value, 'dosage')
  set('mg', peptideMg.value, base ? base.mg : undefined)
  set('water', waterMl.value, base ? base.water : undefined)
  set('dose', doseMcg.value, base ? base.dose : undefined)
  set('syringe', syringeUnits.value, 100)
  set('freq', frequency.value, 'daily')
  // Keep Inertia's history state; only the URL changes.
  window.history.replaceState(window.history.state, '', url.toString())
  shareUrl.value = url.toString()
})

// --- Mobile sticky result bar -----------------------------------------
const inputsRef = ref(null)
const resultRef = ref(null)
const inputsVisible = ref(false)
const resultVisible = ref(true)
let stickyObserver = null

function setupStickyObserver() {
  if (typeof IntersectionObserver === 'undefined') return
  stickyObserver = new IntersectionObserver((entries) => {
    for (const entry of entries) {
      if (entry.target === inputsRef.value) inputsVisible.value = entry.isIntersecting
      if (entry.target === resultRef.value) resultVisible.value = entry.isIntersecting
    }
  }, { threshold: 0.15 })
  if (inputsRef.value) stickyObserver.observe(inputsRef.value)
  if (resultRef.value) stickyObserver.observe(resultRef.value)
}
onBeforeUnmount(() => {
  if (stickyObserver) stickyObserver.disconnect()
})

const showStickyBar = computed(() => inputsVisible.value && !resultVisible.value)

function scrollToResult() {
  if (resultRef.value) resultRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
</script>
