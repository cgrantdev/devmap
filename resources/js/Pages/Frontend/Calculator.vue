<template>
  <FrontLayout>
    <!-- Header Section -->
    <section class="text-white py-12" style="background-color: oklch(0.446 0.043 257.281);">
      <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4 mb-4">
          <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
          </svg>
          <h1 class="text-4xl font-bold">Dosage Calculator</h1>
        </div>
        <p class="text-lg text-blue-100">
          Calculate the exact volume needed for your peptide reconstitution. Enter your vial specifications and desired dosage below.
        </p>
      </div>
    </section>

    <!-- Disclaimer Section -->
    <section class="mt-4">
      <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-yellow-50 rounded-lg py-4 px-6" style="border: 1px solid yellow;">
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-0.5">
              <svg class="w-6 h-6" viewBox="0 0 20 20" fill="none">
                <circle cx="10" cy="10" r="9" fill="#92400e" />
                <path d="M10 6v4M10 14h.01" stroke="white" stroke-width="2" stroke-linecap="round" />
              </svg>
            </div>
            <div class="text-sm" style="color: #92400e;">
              <h3 class="font-bold text-base mb-2">Important Disclaimer</h3>
              <p class="mb-2">
                This calculator is provided for informational and educational purposes only. It is designed to help you understand peptide reconstitution mathematics.
              </p>
              <p>
                <strong>This tool does NOT provide medical advice.</strong> Always consult with a qualified healthcare professional before using any peptides. Peptides are for research purposes only.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <section class="py-8 bg-gray-50">
      <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Calculator Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
          <div class="flex items-center gap-3 mb-6">
            <svg class="w-6 h-6" style="color: #4c1d95;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h2 class="text-2xl font-semibold" style="color: #4c1d95;">Reconstitution Calculator</h2>
          </div>

          <div class="space-y-6">
            <!-- Peptide Amount Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Peptide Amount (mg)
              </label>
              <div class="relative">
                <input
                  v-model.number="form.peptideAmount"
                  type="number"
                  step="0.1"
                  min="0"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-16"
                  placeholder="5"
                />
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">mg</span>
              </div>
              <p class="mt-1 text-sm text-gray-500">
                The amount of peptide in your vial (usually printed on the label)
              </p>
            </div>

            <!-- Bacteriostatic Water Volume Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Bacteriostatic Water Volume (ml)
              </label>
              <div class="relative">
                <input
                  v-model.number="form.waterVolume"
                  type="number"
                  step="0.1"
                  min="0"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-16"
                  placeholder="2"
                />
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">ml</span>
              </div>
              <p class="mt-1 text-sm text-gray-500">
                The volume of bacteriostatic water you'll add to reconstitute
              </p>
            </div>

            <!-- Desired Dosage Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Desired Dosage (mcg)
              </label>
              <div class="relative">
                <input
                  v-model.number="form.desiredDosage"
                  type="number"
                  step="1"
                  min="0"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-16"
                  placeholder="250"
                />
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">mcg</span>
              </div>
              <p class="mt-1 text-sm text-gray-500">
                The dosage you want per injection (in micrograms)
              </p>
            </div>

            <!-- Calculate Button -->
            <div class="flex justify-center pt-4">
              <button
                @click="calculate"
                class="text-white font-medium py-3 px-8 rounded-lg flex items-center gap-2 transition-opacity duration-200 hover:opacity-90"
                style="background-color: oklch(0.446 0.043 257.281);"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Calculate Volume
              </button>
            </div>
          </div>
        </div>

        <!-- Result Section -->
        <div v-if="result" class="bg-white rounded-lg shadow-md p-6 mb-6 border border-blue-200">
          <div class="flex items-center gap-3 mb-6">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
            <h2 class="text-2xl font-semibold text-gray-800">Result</h2>
          </div>

          <div class="mb-6">
            <p class="text-sm text-gray-600 mb-2">Volume to inject:</p>
            <p class="text-4xl font-bold text-blue-700">{{ result.volumeToInject }} ml</p>
            <p class="text-sm text-gray-500 mt-2">
              ({{ result.units }} units on a 1ml insulin syringe)
            </p>
          </div>

          <!-- Calculation Details -->
          <div class="rounded-lg p-4 border border-blue-200" style="background-color: #dbeafe;">
            <h3 class="font-semibold mb-3" style="color: #1e40af;">Calculation Details:</h3>
            <ul class="space-y-2 text-sm" style="color: #1e40af;">
              <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Peptide: {{ form.peptideAmount }}mg in {{ form.waterVolume }}ml</span>
              </li>
              <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Concentration: {{ result.concentration }} mcg/ml</span>
              </li>
              <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>For {{ form.desiredDosage }}mcg dose: inject {{ result.volumeToInject }}ml</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- How to Use Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
          <div class="flex items-center gap-3 mb-6">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="text-2xl font-semibold text-gray-800">How to Use This Calculator</h2>
          </div>

          <ol class="list-decimal list-inside space-y-3 text-gray-700 mb-6">
            <li>Enter the amount of peptide powder in your vial (in milligrams)</li>
            <li>Enter how much bacteriostatic water you plan to add (in milliliters)</li>
            <li>Enter your desired dosage per injection (in micrograms)</li>
            <li>Click "Calculate Volume" to see how much to inject</li>
          </ol>

          <div class="bg-gray-50 rounded-lg p-4">
            <p class="font-semibold text-gray-800 mb-2">Example:</p>
            <p class="text-sm text-gray-700">
              If you have a 5mg vial, add 2ml of bacteriostatic water, and want a 250mcg dose:<br>
              <span class="font-mono text-xs mt-2 block">
                Concentration = 5mg ÷ 2ml = 2.5mg/ml = 2,500mcg/ml<br>
                Volume needed = 250mcg ÷ 2,500mcg/ml = 0.1ml (10 units)
              </span>
            </p>
          </div>
        </div>

        <!-- Bottom Info Sections -->
        <div class="grid md:grid-cols-2 gap-6">
          <!-- Unit Conversions -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Unit Conversions</h2>
            <ul class="space-y-2 text-sm text-gray-700">
              <li>1 mg = 1,000 mcg</li>
              <li>1 ml = 100 units (insulin syringe)</li>
              <li>0.1 ml = 10 units</li>
              <li>0.01 ml = 1 unit</li>
            </ul>
          </div>

          <!-- Common Syringe Sizes -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Common Syringe Sizes</h2>
            <ul class="space-y-2 text-sm text-gray-700">
              <li>• 0.3ml (30 unit) insulin syringe</li>
              <li>• 0.5ml (50 unit) insulin syringe</li>
              <li>• 1ml (100 unit) insulin syringe (most common)</li>
            </ul>
          </div>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import FrontLayout from '../Layouts/FrontLayout.vue'

const form = reactive({
  peptideAmount: 5,
  waterVolume: 2,
  desiredDosage: 250
})

const result = ref(null)

const calculate = () => {
  // Validate inputs
  if (!form.peptideAmount || !form.waterVolume || !form.desiredDosage) {
    return
  }

  if (form.peptideAmount <= 0 || form.waterVolume <= 0 || form.desiredDosage <= 0) {
    return
  }

  // Calculate concentration in mcg/ml
  // First convert mg to mcg, then divide by volume
  const concentrationMcgPerMl = (form.peptideAmount * 1000) / form.waterVolume

  // Calculate volume needed for desired dosage
  const volumeToInject = form.desiredDosage / concentrationMcgPerMl

  // Calculate units (1ml = 100 units)
  const units = Math.round(volumeToInject * 100)

  result.value = {
    concentration: concentrationMcgPerMl.toLocaleString('en-US', { maximumFractionDigits: 0 }),
    volumeToInject: volumeToInject.toFixed(3),
    units: units
  }
}
</script>
