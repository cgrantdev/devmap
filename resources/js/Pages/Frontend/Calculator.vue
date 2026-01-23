<template>
  <FrontLayout>
    <!-- Header Section -->
    <section class="bg-gradient-to-br from-slate-700 to-slate-600 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-center gap-3 mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calculator w-12 h-12" aria-hidden="true">
            <rect width="16" height="20" x="4" y="2" rx="2"></rect>
            <line x1="8" x2="16" y1="6" y2="6"></line>
            <line x1="16" x2="16" y1="14" y2="18"></line>
            <path d="M16 10h.01"></path>
            <path d="M12 10h.01"></path>
            <path d="M8 10h.01"></path>
            <path d="M12 14h.01"></path>
            <path d="M8 14h.01"></path>
            <path d="M12 18h.01"></path>
            <path d="M8 18h.01"></path>
          </svg>
          <h1 class="text-5xl">Dosage Calculator</h1>
        </div>
        <p class="text-xl text-slate-100 max-w-3xl">
          Calculate the exact volume needed for your peptide reconstitution. Enter your vial specifications and desired dosage below.
        </p>
      </div>
    </section>

    <!-- Disclaimer Section -->
    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="mb-8 bg-yellow-50 border-2 border-yellow-300 rounded-lg p-6">
        <div class="flex items-start gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert w-6 h-6 text-yellow-700 flex-shrink-0 mt-0.5" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" x2="12" y1="8" y2="12"></line>
            <line x1="12" x2="12.01" y1="16" y2="16"></line>
          </svg>
          <div>
            <h3 class="text-yellow-900 mb-2">Important Disclaimer</h3>
            <p class="text-sm text-yellow-800 mb-2">
              This calculator is provided for informational and educational purposes only. It is designed to help you understand peptide reconstitution mathematics.
            </p>
            <p class="text-sm text-yellow-800">
              <strong>This tool does NOT provide medical advice.</strong> Always consult with a qualified healthcare professional before using any peptides. Peptides are for research purposes only.
            </p>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <!-- Calculator Form -->
        <h2 class="text-2xl text-gray-900 mb-6 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-beaker w-6 h-6 text-blue-600" aria-hidden="true">
            <path d="M4.5 3h15"></path>
            <path d="M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"></path>
            <path d="M6 14h12"></path>
          </svg>
          Reconstitution Calculator
        </h2>

        <form class="space-y-6">
          <!-- Peptide Amount Input -->
          <div>
            <label class="block text-sm text-gray-700 mb-2">Peptide Amount (mg)</label>
            <div class="relative">
              <input
                v-model.number="form.peptideAmount"
                type="number"
                step="0.1"
                min="0"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                placeholder="e.g., 5"
                required value="5"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">mg</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              The amount of peptide in your vial (usually printed on the label)
            </p>
          </div>

          <!-- Bacteriostatic Water Volume Input -->
          <div>
            <label class="block text-sm text-gray-700 mb-2">Bacteriostatic Water Volume (ml)</label>
            <div class="relative">
              <input
                v-model.number="form.waterVolume"
                type="number"
                step="0.1"
                min="0"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                placeholder="e.g., 2"
                required value="2"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">ml</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              The volume of bacteriostatic water you'll add to reconstitute
            </p>
          </div>

          <!-- Desired Dosage Input -->
          <div>
            <label class="block text-sm text-gray-700 mb-2">Desired Dosage (mcg)</label>
            <div class="relative">
              <input
                v-model.number="form.desiredDosage"
                type="number"
                step="1"
                min="0"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                placeholder="e.g., 250"
                required value="250"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">mcg</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              The dosage you want per injection (in micrograms)
            </p>
          </div>

          <!-- Calculate Button -->
          <button
            type="button"
            @click="calculate"
            class="w-full bg-slate-700 hover:bg-slate-600 text-white py-4 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm"            
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calculator w-5 h-5" aria-hidden="true">
              <rect width="16" height="20" x="4" y="2" rx="2"></rect>
              <line x1="8" x2="16" y1="6" y2="6"></line>
              <line x1="16" x2="16" y1="14" y2="18"></line>
              <path d="M16 10h.01"></path>
              <path d="M12 10h.01"></path>
              <path d="M8 10h.01"></path>
              <path d="M12 14h.01"></path>
              <path d="M8 14h.01"></path>
              <path d="M12 18h.01"></path>
              <path d="M8 18h.01"></path>
            </svg>
            Calculate Volume
          </button>
        </form>

        <!-- Result Section -->
        <div v-if="result" class="mt-8 bg-gradient-to-br from-blue-50 to-purple-50 border-2 border-blue-200 rounded-lg p-6">
          <div class="flex items-center gap-3 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-droplet w-6 h-6 text-blue-600" aria-hidden="true">
              <path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"></path>
            </svg>
            <h3 class="text-xl text-gray-900">Result</h3>
          </div>
  
          <div class="bg-white rounded-lg p-6 mb-4">
            <p class="text-sm text-gray-600 mb-2">Volume to inject:</p>
            <p class="text-4xl text-blue-600 mb-1">{{ result.volumeToInject }} ml</p>
            <p class="text-sm text-gray-500">
              ({{ result.units }} units on a 1ml insulin syringe)
            </p>
          </div>
  
          <!-- Calculation Details -->
          <div class="bg-blue-100 border border-blue-200 rounded-lg p-4">
            <h4 class="text-sm text-blue-900 mb-2">Calculation Details:</h4>
            <ul class="space-y-1 text-sm text-blue-800">
              <li>
                <span>• Peptide: {{ form.peptideAmount }}mg in {{ form.waterVolume }}ml</span>
              </li>
              <li>
                <span>• Concentration: {{ result.concentration }} mcg/ml</span>
              </li>
              <li>
                <span>• For {{ form.desiredDosage }}mcg dose: inject {{ result.volumeToInject }}ml</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- How to Use Section -->
      <div class="mt-8 bg-slate-50 border border-slate-200 rounded-lg p-6">
        <div class="flex items-start gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info w-5 h-5 text-slate-600 flex-shrink-0 mt-0.5" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 16v-4"></path>
            <path d="M12 8h.01"></path>
          </svg>
          <div>
            <h3 class="text-slate-900 mb-2">How to Use This Calculator</h3>
            <ol class="space-y-2 text-sm text-slate-700">
              <li class="flex gap-2">
                <span class="font-medium">1.</span>
                <span>Enter the amount of peptide powder in your vial (in milligrams)</span>
              </li>
              <li class="flex gap-2">
                <span class="font-medium">2.</span>
                <span>Enter how much bacteriostatic water you plan to add (in milliliters)</span>
              </li>
              <li class="flex gap-2">
                <span class="font-medium">3.</span>
                <span>Enter your desired dosage per injection (in micrograms)</span>
              </li>
              <li class="flex gap-2">
                <span class="font-medium">4.</span>
                <span>Click "Calculate Volume" to see how much to inject</span>
              </li>
            </ol>
    
            <div class="mt-4 pt-4 border-t border-slate-300">
              <p class="text-sm text-slate-700 mb-2">
                <strong>Example:</strong>
                If you have a 5mg vial, add 2ml of bacteriostatic water, and want a 250mcg dose:
              </p>
              <p class="text-sm text-slate-600">
                Concentration = 5mg ÷ 2ml = 2.5mg/ml = 2,500mcg/ml
                <br>
                Volume needed = 250mcg ÷ 2,500mcg/ml = 0.1ml (10 units)                
              </p>
            </div>
          </div>
        </div>

      </div>

      <!-- Bottom Info Sections -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Unit Conversions -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
          <h3 class="text-gray-900 mb-3">Unit Conversions</h3>
          <ul class="space-y-2 text-sm text-gray-700">
            <li class="flex justify-between">
              <span>1 mg =</span>
              <span>1,000 mcg</span>
            </li>
            <li class="flex justify-between">
              <span>1 ml =</span>
              <span>100 units (insulin syringe)</span>
            </li>
            <li class="flex justify-between">
              <span>0.1 ml =</span>
              <span>10 units</span>
            </li>
            <li class="flex justify-between">
              <span>0.01 ml =</span>
              <span>1 unit</span>
            </li>
          </ul>
        </div>

        <!-- Common Syringe Sizes -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
          <h3 class="text-gray-900 mb-3">Common Syringe Sizes</h3>
          <ul class="space-y-2 text-sm text-gray-700">
            <li class="flex items-start gap-2">
              <div class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></div>
              <span>0.3ml (30 unit) insulin syringe</span>
            </li>
            <li class="flex items-start gap-2">
              <div class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></div>
              <span>0.5ml (50 unit) insulin syringe</span>
            </li>
            <li class="flex items-start gap-2">
              <div class="w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></div>
              <span>1ml (100 unit) insulin syringe (most common)</span>
            </li>
          </ul>
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
