<template>
  <FrontLayout>
    <section class="py-16 bg-white">
      <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Peptide Research Calculator</h1>
        <p class="text-gray-600 text-lg mb-12">Useful calculators for peptide dosage, reconstitution, and research calculations.</p>
        
        <div class="space-y-12">
          <!-- Dosage Calculator -->
          <div class="bg-white border border-gray-200 rounded-lg p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Peptide Dosage Calculator</h2>
            <p class="text-gray-600 mb-6">Calculate the appropriate dosage based on body weight and desired dosage per kilogram.</p>
            
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <label class="block mb-2 font-semibold text-gray-800">Body Weight (kg)</label>
                <input 
                  v-model.number="dosageCalc.weight" 
                  type="number" 
                  step="0.1"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., 70"
                />
              </div>
              <div>
                <label class="block mb-2 font-semibold text-gray-800">Dosage per kg (mcg/kg)</label>
                <input 
                  v-model.number="dosageCalc.dosePerKg" 
                  type="number" 
                  step="0.1"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., 200"
                />
              </div>
            </div>
            
            <div v-if="dosageResult" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p class="text-gray-800 font-semibold">Recommended Dosage:</p>
              <p class="text-2xl font-bold text-blue-600">{{ dosageResult }} mcg</p>
              <p class="text-sm text-gray-600 mt-2">({{ (dosageResult / 1000).toFixed(2) }} mg)</p>
            </div>
          </div>

          <!-- Reconstitution Calculator -->
          <div class="bg-white border border-gray-200 rounded-lg p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Reconstitution Calculator</h2>
            <p class="text-gray-600 mb-6">Calculate the concentration of reconstituted peptide solution.</p>
            
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <label class="block mb-2 font-semibold text-gray-800">Peptide Amount (mg)</label>
                <input 
                  v-model.number="reconCalc.peptideAmount" 
                  type="number" 
                  step="0.1"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., 5"
                />
              </div>
              <div>
                <label class="block mb-2 font-semibold text-gray-800">Bacteriostatic Water (ml)</label>
                <input 
                  v-model.number="reconCalc.waterAmount" 
                  type="number" 
                  step="0.1"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., 2"
                />
              </div>
            </div>
            
            <div v-if="reconResult" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
              <p class="text-gray-800 font-semibold">Final Concentration:</p>
              <p class="text-2xl font-bold text-green-600">{{ reconResult }} mg/ml</p>
              <p class="text-sm text-gray-600 mt-2">({{ (reconResult * 1000).toFixed(0) }} mcg/ml)</p>
            </div>
          </div>

          <!-- Volume Calculator -->
          <div class="bg-white border border-gray-200 rounded-lg p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Volume Calculator</h2>
            <p class="text-gray-600 mb-6">Calculate the volume needed to achieve a specific dosage from a reconstituted solution.</p>
            
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <label class="block mb-2 font-semibold text-gray-800">Desired Dosage (mcg)</label>
                <input 
                  v-model.number="volumeCalc.desiredDose" 
                  type="number" 
                  step="0.1"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., 500"
                />
              </div>
              <div>
                <label class="block mb-2 font-semibold text-gray-800">Concentration (mcg/ml)</label>
                <input 
                  v-model.number="volumeCalc.concentration" 
                  type="number" 
                  step="0.1"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., 2500"
                />
              </div>
            </div>
            
            <div v-if="volumeResult" class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
              <p class="text-gray-800 font-semibold">Volume Needed:</p>
              <p class="text-2xl font-bold text-purple-600">{{ volumeResult }} ml</p>
              <p class="text-sm text-gray-600 mt-2">({{ (volumeResult * 100).toFixed(1) }} units on 100-unit syringe)</p>
            </div>
          </div>

          <!-- Important Notes -->
          <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Important Research Notes</h3>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
              <li>These calculators are for research purposes only and should not be used for human consumption or therapeutic purposes.</li>
              <li>Always consult published research literature and protocols for appropriate dosages in your specific research context.</li>
              <li>Dosage requirements may vary significantly based on the specific peptide, research model, and experimental design.</li>
              <li>Proper storage and handling of reconstituted peptides is critical for maintaining stability and efficacy.</li>
              <li>These calculations are estimates and should be verified with qualified researchers and laboratory protocols.</li>
            </ul>
          </div>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import FrontLayout from '../Layouts/FrontLayout.vue'

const dosageCalc = ref({
  weight: null,
  dosePerKg: null
})

const reconCalc = ref({
  peptideAmount: null,
  waterAmount: null
})

const volumeCalc = ref({
  desiredDose: null,
  concentration: null
})

const dosageResult = computed(() => {
  if (dosageCalc.value.weight && dosageCalc.value.dosePerKg) {
    return (dosageCalc.value.weight * dosageCalc.value.dosePerKg).toFixed(2)
  }
  return null
})

const reconResult = computed(() => {
  if (reconCalc.value.peptideAmount && reconCalc.value.waterAmount && reconCalc.value.waterAmount > 0) {
    return (reconCalc.value.peptideAmount / reconCalc.value.waterAmount).toFixed(2)
  }
  return null
})

const volumeResult = computed(() => {
  if (volumeCalc.value.desiredDose && volumeCalc.value.concentration && volumeCalc.value.concentration > 0) {
    return (volumeCalc.value.desiredDose / volumeCalc.value.concentration).toFixed(3)
  }
  return null
})
</script>
