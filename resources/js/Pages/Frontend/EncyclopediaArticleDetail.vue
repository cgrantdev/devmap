<template>
  <FrontLayout>
    <div class="min-h-screen bg-gray-50">
      <!-- Header Section (Dark Background) -->
      <div class="bg-gradient-to-br from-slate-700 via-slate-600 to-slate-700 border-b border-slate-300 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
          <!-- Back Button -->
          <button 
            @click="router.visit('/encyclopedia')"
            class="flex items-center gap-2 text-slate-100 hover:text-white mb-6 transition-colors group"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-4 h-4 group-hover:-translate-x-1 transition-transform" aria-hidden="true">
              <path d="m12 19-7-7 7-7"></path>
              <path d="M19 12H5"></path>
            </svg>
            Back to Encyclopedia
          </button>

          <div class="mb-8 flex items-start justify-between gap-8">
            <!-- Title Section -->
            <div class="flex items-start gap-5">
              <div class="p-4 bg-white/10 rounded-xl backdrop-blur-sm border border-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flask-conical w-10 h-10 text-white" aria-hidden="true">
                  <path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"></path>
                  <path d="M6.453 15h11.094"></path>
                  <path d="M8.5 2h7"></path>
                </svg>
              </div>
              <div>
                <h1 class="text-5xl font-semibold text-white mb-2">{{ categoryName || name }}</h1>
                <p class="text-xl text-slate-200">{{ subtitle }}</p>
              </div>
            </div>

            <!-- Tags and Research Info -->
            <div class="flex flex-col gap-3">
              <!-- Tags -->
              <div class="flex items-center gap-2">
                <span 
                  v-for="(tag, index) in tags" 
                  :key="index"
                  :class="getTagColorClass(tag)"
                  class="px-3 py-1 rounded-full text-sm font-medium backdrop-blur-sm"
                >
                  {{ tag }}
                </span>
              </div>

              <!-- Primary Research -->
              <div class="text-center">
                <div class="text-white/80 text-sm italic">Primary Research: University of Zagreb (Croatia)</div>
                <a                   
                  :href="primaryResearch.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-1 text-white/50 hover:text-white/80 text-xs mt-1 transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link w-3 h-3" aria-hidden="true">
                    <path d="M15 3h6v6"></path>
                    <path d="M10 14 21 3"></path>
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                  </svg>
                  View Research
                </a>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20 shadow-lg">
              <h3 class="text-white/80 text-xs uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-atom w-4 h-4" aria-hidden="true">
                  <circle cx="12" cy="12" r="1"></circle>
                  <path d="M20.2 20.2c2.04-2.03.02-7.36-4.5-11.9-4.54-4.52-9.87-6.54-11.9-4.5-2.04 2.03-.02 7.36 4.5 11.9 4.54 4.52 9.87 6.54 11.9 4.5Z"></path>
                  <path d="M15.7 15.7c4.52-4.54 6.54-9.87 4.5-11.9-2.03-2.04-7.36-.02-11.9 4.5-4.52 4.54-6.54 9.87-4.5 11.9 2.03 2.04 7.36.02 11.9-4.5Z"></path>
                </svg>
                Molecular Information
              </h3>
              <div class="space-y-4">
                <div>
                  <div class="text-white/60 text-xs mb-1">Formula</div>
                  <div class="text-white font-mono text-xl">{{ molecularInfo.formula || '-' }}</div>
                </div>
                <div>
                  <div class="text-white/60 text-xs mb-1">Molecular Weight</div>
                  <div class="text-white font-semibold text-xl">{{ molecularInfo.molecularWeight || '-' }}</div>
                </div>
                <div>
                  <div class="text-white/60 text-xs mb-1">CAS Registry Number</div>
                  <div class="text-white font-mono text-xl">{{ molecularInfo.casNumber || '-' }}</div>
                </div>
              </div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20 shadow-lg lg:col-span-2">
              <h3 class="text-white/80 text-xs uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dna w-4 h-4" aria-hidden="true">
                  <path d="m10 16 1.5 1.5"></path>                              
                  <path d="m14 8-1.5-1.5"></path>
                  <path d="M15 2c-1.798 1.998-2.518 3.995-2.807 5.993"></path>
                  <path d="m16.5 10.5 1 1"></path>
                  <path d="m17 6-2.891-2.891"></path>
                  <path d="M2 15c6.667-6 13.333 0 20-6"></path>
                  <path d="m20 9 .891.891"></path>
                  <path d="M3.109 14.109 4 15"></path>
                  <path d="m6.5 12.5 1 1"></path>
                  <path d="m7 18 2.891 2.891"></path>
                  <path d="M9 22c1.798-1.998 2.518-3.995 2.807-5.993"></path>
                </svg>
                Amino Acid Sequence{{ aminoAcidSequence.residueCount > 0 ? ` (${aminoAcidSequence.residueCount} Residues)` : '' }}
              </h3>
              <div v-if="aminoAcidSequence.sequence" class="bg-white/5 rounded-lg p-4 border border-white/10">
                <p class="text-white font-mono text-sm leading-relaxed">
                  {{ aminoAcidSequence.sequence }}
                </p>
              </div>
              <div v-else class="bg-white/5 rounded-lg p-4 border border-white/10">
                <p class="text-white/60 text-sm">No sequence data available</p>
              </div>
              <div class="grid grid-cols-2 gap-4 mt-4">
                <div v-if="aminoAcidComposition && aminoAcidComposition.length > 0">
                  <div class="text-white/60 text-xs mb-2">Composition</div>
                  <div class="space-y-1 text-xs">
                    <div v-for="(comp, index) in aminoAcidComposition" :key="index" class="text-white/80">{{ comp }}</div>
                  </div>
                </div>
                <div v-if="aminoAcidSequence.properties && (aminoAcidSequence.properties.netCharge || aminoAcidSequence.properties.hydrophobic || aminoAcidSequence.properties.stability || aminoAcidSequence.properties.solubility)">
                  <div class="text-white/60 text-xs mb-2">Properties</div>
                  <div class="space-y-1 text-xs">
                    <div v-if="aminoAcidSequence.properties.netCharge" class="text-white/80">Net Charge: {{ aminoAcidSequence.properties.netCharge }}</div>
                    <div v-if="aminoAcidSequence.properties.hydrophobic" class="text-white/80">Hydrophobic: {{ aminoAcidSequence.properties.hydrophobic }}</div>
                    <div v-if="aminoAcidSequence.properties.stability" class="text-white/80">Stability: {{ aminoAcidSequence.properties.stability }}</div>
                    <div v-if="aminoAcidSequence.properties.solubility" class="text-white/80">Solubility: {{ aminoAcidSequence.properties.solubility }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <!-- Left Column -->
          <div class="lg:col-span-1">
            <div class="sticky top-24">
              <!-- Contents Navigation -->
              <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-3 flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-4 h-4" aria-hidden="true">
                    <path d="M12 7v14"></path>
                    <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
                  </svg>
                  Contents
                </h3>
                <nav class="space-y-1">
                  <a 
                    v-for="(item, index) in contents" 
                    :key="index"
                    :href="`#${item.id}`"
                    class="block w-full text-left text-sm py-1.5 px-2 rounded transition-colors text-slate-600 hover:bg-slate-100"
                  >
                    {{ item.label }}
                  </a>
                </nav>
              </div>
  
              <!-- Sponsored Sections -->              
              <div class="mt-6">
                <a href="#" class="block border border-slate-300 rounded-lg overflow-hidden hover:border-slate-400 transition-all hover:shadow-md group cursor-pointer">
                  <div class="relative h-48 bg-gradient-to-br from-slate-600 via-slate-700 to-slate-800 p-4 flex flex-col justify-between">
                    <div class="text-xs uppercase tracking-wider text-slate-300 font-medium">SPONSORED</div>
                    <div class="space-y-2">
                      <div class="text-white text-xl font-bold leading-tight">Limitless Life</div>
                      <div class="text-slate-200 text-sm leading-snug">Premium Peptides - Fast Shipping</div>
                      <div class="inline-block px-3 py-1 bg-white/10 backdrop-blur-sm rounded text-xs text-white font-mono border border-white/20">Code: PMAP</div>
                    </div>
                    <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                      <div class="absolute top-4 right-4 w-20 h-20 border-2 border-white rounded-full"></div>
                      <div class="absolute top-8 right-8 w-12 h-12 border-2 border-white rounded-full"></div>
                    </div>
                  </div>
                </a>
              </div>
              <div class="mt-6">
                <a href="#" class="block border border-slate-300 rounded-lg overflow-hidden hover:border-slate-400 transition-all hover:shadow-md group cursor-pointer">
                  <div class="relative h-48 bg-gradient-to-br from-slate-600 via-slate-700 to-slate-800 p-4 flex flex-col justify-between">
                    <div class="text-xs uppercase tracking-wider text-slate-300 font-medium">SPONSORED</div>
                    <div class="space-y-2">
                      <div class="text-white text-xl font-bold leading-tight">Peptide Sciences</div>
                      <div class="text-slate-200 text-sm leading-snug">Research-Grade Quality</div>
                      <div class="inline-block px-3 py-1 bg-white/10 backdrop-blur-sm rounded text-xs text-white font-mono border border-white/20">Code: PMAP</div>
                    </div>
                    <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                      <div class="absolute top-4 right-4 w-20 h-20 border-2 border-white rounded-full"></div>
                      <div class="absolute top-8 right-8 w-12 h-12 border-2 border-white rounded-full"></div>
                    </div>
                  </div>
                </a>
              </div>
              <div class="mt-6">
                <a href="#" class="block border border-slate-300 rounded-lg overflow-hidden hover:border-slate-400 transition-all hover:shadow-md group cursor-pointer">
                  <div class="relative h-48 bg-gradient-to-br from-slate-600 via-slate-700 to-slate-800 p-4 flex flex-col justify-between">
                    <div class="text-xs uppercase tracking-wider text-slate-300 font-medium">SPONSORED</div>
                    <div class="space-y-2">
                      <div class="text-white text-xl font-bold leading-tight">XYZ Peptides</div>
                      <div class="text-slate-200 text-sm leading-snug">Same-Day Shipping</div>
                      <div class="inline-block px-3 py-1 bg-white/10 backdrop-blur-sm rounded text-xs text-white font-mono border border-white/20">Code: PMAP</div>
                    </div>
                    <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                      <div class="absolute top-4 right-4 w-20 h-20 border-2 border-white rounded-full"></div>
                      <div class="absolute top-8 right-8 w-12 h-12 border-2 border-white rounded-full"></div>
                    </div>
                  </div>
                </a>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="lg:col-span-3">
            <div class="bg-white border border-slate-200 rounded-lg shadow-sm"> 
              <!-- Key Points -->
              <div id="key-points" class="border-b border-slate-200 bg-gradient-to-br from-slate-50 to-white p-6">
                <div class="flex items-center gap-3 mb-4">
                  <div class="p-2 bg-slate-700 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-5 h-5 text-white" aria-hidden="true">
                      <path d="M16 7h6v6"></path>
                      <path d="m22 7-8.5 8.5-5-5L2 17"></path>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Key Points</h2>
                </div>
                <ul class="space-y-3">
                  <li 
                    v-for="(point, index) in keyPoints" 
                    :key="index"
                    class="flex items-start gap-3 group"
                  >
                    <div :class="`w-2 h-2 rounded-full bg-gradient-to-br ${getKeyPointColorClass(index)} mt-2 flex-shrink-0 group-hover:scale-125 transition-transform`"></div>
                    <span class="text-slate-700 leading-relaxed">{{ point }}</span>
                  </li>
                </ul>
              </div>
  
              <!-- Research Use Only Warning -->
              <div class="border-b border-slate-200 bg-gradient-to-br from-amber-50 to-orange-50 p-6">
                <div class="flex items-start gap-3">
                  <div class="p-2 bg-amber-500 rounded-lg flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-triangle-alert w-5 h-5 text-white" aria-hidden="true">
                      <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                      <path d="M12 9v4"></path>
                      <path d="M12 17h.01"></path>
                    </svg>
                  </div>
                  <div>
                    <h3 class="font-semibold text-amber-900 mb-1">Research Use Only</h3>
                    <p class="text-sm text-amber-800 leading-relaxed">
                      {{ name }} is an experimental compound not approved for human use by any regulatory agency. This article is for informational and educational purposes only and does not constitute medical advice. Peptidemaps does not provide recommendations on safety, usage, or dosages.
                    </p>
                  </div>
                </div>
              </div>

              <div class="p-8 prose prose-slate max-w-none">
                <!-- Overview -->
                <section id="overview" class="mb-12">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-stethoscope w-5 h-5 text-white" aria-hidden="true">
                        <path d="M11 2v2"></path>
                        <path d="M5 2v2"></path>
                        <path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"></path>
                        <path d="M8 15a6 6 0 0 0 12 0v-3"></path>
                        <circle cx="20" cy="10" r="2"></circle>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">Overview</h2>
                  </div>
                  <div v-if="overview" class="text-slate-700 leading-relaxed mb-4" v-html="overview.replace(/\n/g, '<br>')"></div>
                  <p v-else class="text-slate-700 leading-relaxed mb-4">No overview available.</p>
                </section>

                <!-- Sponsored Advertisement -->
                <div class="mb-12">
                  <a href="#" class="block border border-slate-300 rounded-lg overflow-hidden hover:border-slate-400 transition-all hover:shadow-lg group cursor-pointer">
                    <div class="relative h-32 bg-gradient-to-r from-slate-600 via-slate-700 to-slate-800 p-6 flex items-center justify-between">
                      <div class="flex-1 space-y-2 z-10">
                        <div class="text-xs uppercase tracking-wider text-slate-300 font-medium">SPONSORED</div>
                        <div class="text-white text-2xl font-bold leading-tight">Limitless Life Nootropics</div>
                        <div class="text-slate-200 text-base">Premium BPC-157 with Third-Party Testing & Fast Shipping</div>
                      </div>
                      <div class="flex flex-col items-end gap-2 z-10">                      
                        <div class="px-4 py-2 bg-white/10 backdrop-blur-sm rounded-lg text-white font-mono text-sm border border-white/20">Code: PMAP</div>
                      </div>
                      <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-1/2 left-1/4 w-40 h-40 border-2 border-white rounded-full transform -translate-y-1/2"></div>
                        <div class="absolute top-1/2 right-1/4 w-32 h-32 border-2 border-white rounded-full transform -translate-y-1/2"></div>
                      </div>
                    </div>
                  </a>
                </div>

                <!-- Areas of Research -->
                <section id="areas-of-research" class="mb-12">
                  <div class="flex items-center gap-3 mb-6 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flask-conical w-5 h-5 text-white" aria-hidden="true">
                        <path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"></path>
                        <path d="M6.453 15h11.094"></path>
                        <path d="M8.5 2h7"></path>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">Areas of Research</h2>
                  </div>
                  <p v-if="areasOfResearchIntro" class="text-slate-700 leading-relaxed mb-6" v-html="areasOfResearchIntro.replace(/\n/g, '<br>')"></p>
                  <div v-if="areasOfResearch && areasOfResearch.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                      v-for="(area, index) in areasOfResearch" 
                      :key="index"
                      :class="`rounded-lg border ${getAreaColorClasses(area.name).border} bg-gray-50 p-5 hover:shadow-lg transition-all duration-300 group`"
                    >
                      <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div :class="`p-3 bg-gradient-to-br ${getAreaColorClasses(area.name).iconBg} rounded-lg flex-shrink-0 group-hover:scale-110 transition-transform`">
                          <!-- Bone Icon (Orthopedic) -->
                          <svg v-if="getAreaIconType(area.name) === 'bone'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bone w-6 h-6 text-white" aria-hidden="true">
                            <path d="M17 10c.7-.7 1.69 0 2.5 0a2.5 2.5 0 1 0 0-5 .5.5 0 0 1-.5-.5 2.5 2.5 0 1 0-5 0c0 .81.7 1.8 0 2.5l-7 7c-.7.7-1.69 0-2.5 0a2.5 2.5 0 0 0 0 5c.28 0 .5.22.5.5a2.5 2.5 0 1 0 5 0c0-.81-.7-1.8 0-2.5Z"></path>
                          </svg>
                          <!-- Heartbeat Icon (Wound Healing) -->
                          <svg v-else-if="getAreaIconType(area.name) === 'heartbeat'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-activity w-6 h-6 text-white" aria-hidden="true">
                            <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path>
                          </svg>
                          <!-- Heart Icon (Gastrointestinal) -->
                          <svg v-else-if="getAreaIconType(area.name) === 'heart'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart w-6 h-6 text-white" aria-hidden="true">
                            <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"></path>
                          </svg>
                          <!-- Brain Icon (Neuroprotection) -->
                          <svg v-else-if="getAreaIconType(area.name) === 'brain'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-brain w-6 h-6 text-white" aria-hidden="true">
                            <path d="M12 18V5"></path>
                            <path d="M15 13a4.17 4.17 0 0 1-3-4 4.17 4.17 0 0 1-3 4"></path>
                            <path d="M17.598 6.5A3 3 0 1 0 12 5a3 3 0 1 0-5.598 1.5"></path>
                            <path d="M17.997 5.125a4 4 0 0 1 2.526 5.77"></path>
                            <path d="M18 18a4 4 0 0 0 2-7.464"></path>
                            <path d="M19.967 17.483A4 4 0 1 1 12 18a4 4 0 1 1-7.967-.517"></path>
                            <path d="M6 18a4 4 0 0 1-2-7.464"></path>
                            <path d="M6.003 5.125a4 4 0 0 0-2.526 5.77"></path>
                          </svg>
                          <!-- Shield Icon (Anti-Inflammatory) -->
                          <svg v-else-if="getAreaIconType(area.name) === 'shield'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield w-6 h-6 text-white" aria-hidden="true">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                          </svg>
                          <!-- Zap/Lightning Icon (Angiogenesis) -->
                          <svg v-else-if="getAreaIconType(area.name) === 'zap'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap w-6 h-6 text-white" aria-hidden="true">
                            <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                          </svg>
                          <!-- Default Circle Icon -->
                          <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide w-6 h-6 text-white" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                          </svg>
                        </div>
                        <!-- Content -->
                        <div>
                          <h3 v-if="area.name" :class="`font-semibold ${getAreaColorClasses(area.name).text} mb-2`">{{ area.name }}</h3>
                          <p v-if="area.description" class="text-sm text-slate-600 leading-relaxed" v-html="area.description.replace(/\n/g, '<br>')"></p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <p v-else class="text-slate-700 leading-relaxed mb-6">No research areas available.</p>
                  <!-- Research Note -->
                  <div class="mt-6 bg-slate-50 border border-slate-200 rounded-lg p-5">
                    <div class="flex items-start gap-3">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-5 h-5 text-slate-600 flex-shrink-0 mt-0.5" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <path d="M16 3.128a4 4 0 0 1 0 7.744"></path>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                      </svg>
                      <div>
                        <p class="text-sm text-slate-700 leading-relaxed">
                          <strong>Research Note:</strong>
                           All findings listed above are derived from preclinical animal and cell studies. Human clinical evidence remains limited, and these research areas should not be interpreted as proven therapeutic applications.
                        </p>
                      </div>
                    </div>
                  </div>
                </section>

                <!-- Background -->
                <section id="background" class="mb-12">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-5 h-5 text-white" aria-hidden="true">
                        <path d="M12 7v14"></path>
                        <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">Background{{ subtitle ? ': ' + subtitle : '' }}</h2>
                  </div>
                  <div v-if="background" class="text-slate-700 leading-relaxed mb-4" v-html="background.replace(/\n/g, '<br>')"></div>
                  <p v-else class="text-slate-700 leading-relaxed mb-4">No background information available.</p>
                </section>

                <!-- Mechanism of Action -->
                <section id="mechanism-of-action" class="mb-12">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-activity w-5 h-5 text-white" aria-hidden="true">
                        <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">Mechanism of Action</h2>
                  </div>                
                  <!-- Introductory Paragraph -->
                  <p v-if="mechanismOfActionIntro" class="text-slate-700 leading-relaxed mb-4" v-html="mechanismOfActionIntro.replace(/\n/g, '<br>')"></p>
                    
                  <!-- Subsections -->
                  <div 
                    v-for="(subsection, index) in mechanismSubsections" 
                    :key="index"                  
                  >
                    <!-- Subsection Title -->
                    <h3 v-if="subsection.title" class="text-xl font-semibold text-slate-900 mb-3 mt-6 flex items-center gap-2">
                      <svg v-if="subsection.title=== 'Pro-Healing Pathways'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-5 h-5 text-emerald-600" aria-hidden="true">
                        <path d="M16 7h6v6"></path>
                        <path d="m22 7-8.5 8.5-5-5L2 17"></path>
                      </svg>
                      <svg v-else-if="subsection.title === 'Anti-Inflammatory Actions'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield w-5 h-5 text-amber-600" aria-hidden="true">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                      </svg>
                      <svg v-else-if="subsection.title === 'Neuroprotective Properties'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-brain w-5 h-5 text-purple-600" aria-hidden="true">
                        <path d="M12 18V5"></path>
                        <path d="M15 13a4.17 4.17 0 0 1-3-4 4.17 4.17 0 0 1-3 4"></path>
                        <path d="M17.598 6.5A3 3 0 1 0 12 5a3 3 0 1 0-5.598 1.5"></path>
                        <path d="M17.997 5.125a4 4 0 0 1 2.526 5.77"></path>
                        <path d="M18 18a4 4 0 0 0 2-7.464"></path>
                        <path d="M19.967 17.483A4 4 0 1 1 12 18a4 4 0 1 1-7.967-.517"></path>
                        <path d="M6 18a4 4 0 0 1-2-7.464"></path>
                        <path d="M6.003 5.125a4 4 0 0 0-2.526 5.77"></path>
                      </svg>
                      {{ subsection.title }}
                    </h3>
  
                    <!-- Subsection Intro -->
                    <p v-if="subsection.intro" class="text-slate-700 leading-relaxed mb-4" v-html="subsection.intro.replace(/\n/g, '<br>')"></p>
  
                    <!-- Bullet Points -->
                    <ul v-if="subsection.items && subsection.items.length > 0" class="list-disc pl-6 mb-4 space-y-2 text-slate-700">
                      <li 
                        v-for="(item, itemIndex) in subsection.items" 
                        :key="itemIndex"                      
                      >                      
                        <span v-if="item.item"><strong>{{ item.item }}:</strong></span>
                        <span v-if="item.description" v-html="item.description.replace(/\n/g, '<br>')"></span>
                        <span v-else-if="typeof item === 'string'">{{ item }}</span>
                      </li>
                    </ul>
                  </div>                
                </section>

                                <!-- Sponsored Advertisement -->
                <div class="mb-12">
                  <a href="#" class="block border border-slate-300 rounded-lg overflow-hidden hover:border-slate-400 transition-all hover:shadow-lg group cursor-pointer">
                    <div class="relative h-32 bg-gradient-to-r from-slate-600 via-slate-700 to-slate-800 p-6 flex items-center justify-between">
                      <div class="flex-1 space-y-2 z-10">
                        <div class="text-xs uppercase tracking-wider text-slate-300 font-medium">SPONSORED</div>
                        <div class="text-white text-2xl font-bold leading-tight">Peptide Sciences</div>
                        <div class="text-slate-200 text-base">Research-Grade Peptides | Certified Quality & Purity</div>
                      </div>
                      <div class="flex flex-col items-end gap-2 z-10">                      
                        <div class="px-4 py-2 bg-white/10 backdrop-blur-sm rounded-lg text-white font-mono text-sm border border-white/20">Code: PMAP</div>
                      </div>
                      <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-1/2 left-1/4 w-40 h-40 border-2 border-white rounded-full transform -translate-y-1/2"></div>
                        <div class="absolute top-1/2 right-1/4 w-32 h-32 border-2 border-white rounded-full transform -translate-y-1/2"></div>
                      </div>
                    </div>
                  </a>
                </div>

                <!-- Preclinical Research -->
                <section id="preclinical-research" class="mb-12">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flask-conical w-5 h-5 text-white" aria-hidden="true">
                        <path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"></path>
                        <path d="M6.453 15h11.094"></path>
                        <path d="M8.5 2h7"></path>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">Preclinical Research and Findings</h2>
                  </div>
    
                  <!-- Introductory Paragraph -->
                  <p v-if="preclinicalIntro" class="text-slate-700 leading-relaxed mb-6" v-html="preclinicalIntro.replace(/\n/g, '<br>')"></p>
                    
                  <!-- Subsections -->
                  <div 
                    v-for="(subsection, index) in preclinicalSubsections" 
                    :key="index"                    
                  >
                    <!-- Subsection Title -->
                    <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6 flex items-center gap-2">
                      <svg 
                        v-if="getPreclinicalIconType(subsection) === 'bone'"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="24" 
                        height="24" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        class="lucide lucide-bone w-5 h-5 text-blue-600"
                        aria-hidden="true"
                      >
                        <path d="M17 10c.7-.7 1.69 0 2.5 0a2.5 2.5 0 1 0 0-5 .5.5 0 0 1-.5-.5 2.5 2.5 0 1 0-5 0c0 .81.7 1.8 0 2.5l-7 7c-.7.7-1.69 0-2.5 0a2.5 2.5 0 0 0 0 5c.28 0 .5.22.5.5a2.5 2.5 0 1 0 5 0c0-.81-.7-1.8 0-2.5Z"></path>
                      </svg>
                      <svg 
                        v-else-if="getPreclinicalIconType(subsection) === 'heart'"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="24" 
                        height="24" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        class="lucide lucide-heart w-5 h-5 text-rose-600"
                        aria-hidden="true"
                      >
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path>
                      </svg>
                      <svg 
                        v-else-if="getPreclinicalIconType(subsection) === 'brain'"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="24" 
                        height="24" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        class="text-purple-600"
                        aria-hidden="true"
                      >
                        <path d="M12 5a3 3 0 1 0-5.997.142 4 4 0 0 0-2.526 5.77 4 4 0 0 0 .556 6.588 4 4 0 0 0 7.636 2.106 3 3 0 0 0 .164-5.478 4 4 0 0 0-2.536-5.77 4 4 0 0 0-2.197-1.256z"></path>
                        <path d="M12 5a3 3 0 1 1 5.997.142 4 4 0 0 1 2.526 5.77 4 4 0 0 1-.556 6.588 4 4 0 0 1-7.636 2.106 3 3 0 0 1-.164-5.478 4 4 0 0 1 2.536-5.77 4 4 0 0 1 2.197-1.256z"></path>
                      </svg>
                      {{ subsection.title }}
                    </h3>
  
                    <!-- Research Finding Cards -->
                    <div 
                      v-for="(finding, findingIndex) in subsection.findings" 
                      :key="findingIndex"
                      :class="[
                        'rounded-lg border p-5 mb-4',
                        getPreclinicalFindingColorClasses(subsection, findingIndex).border,
                        getPreclinicalFindingColorClasses(subsection, findingIndex).bg
                      ]"
                    >
                      <h4 class="font-semibold text-slate-900 mb-2">{{ finding.title }}</h4>
                      <p 
                        v-for="(paragraph, pIndex) in (Array.isArray(finding.content) ? finding.content : [finding.content])" 
                        :key="pIndex"
                        class="text-slate-700 text-sm leading-relaxed"
                      >
                        {{ paragraph }}
                      </p>                    
                    </div>                  
                  </div>                
    
                  <!-- Disclaimer -->
                  <p class="text-slate-700 leading-relaxed mt-6 mb-4">
                    {{ preclinicalDisclaimer }}
                  </p>                
                </section>

                <!-- Human Use & Evidence -->
                <section v-if="humanUseIntro || (humanUseSubsections && humanUseSubsections.length > 0)" id="human-use-evidence" class="mb-12">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-5 h-5 text-white" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <path d="M16 3.128a4 4 0 0 1 0 7.744"></path>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">Human Use and Evidence</h2>
                  </div>
    
                  <!-- Introductory Paragraph -->
                  <p v-if="humanUseIntro" class="text-slate-700 leading-relaxed mb-4" v-html="humanUseIntro.replace(/\n/g, '<br>')"></p>                
    
                  <!-- Subsections -->
                  <template v-for="(subsection, index) in humanUseSubsections" :key="index">
                    <h3 v-if="subsection.title" class="text-xl font-semibold text-slate-900 mb-3 mt-6">{{ subsection.title }}</h3>
                    <div v-if="subsection.entries && subsection.entries.length > 0" class="mb-4">
                      <template v-for="(processedEntry, entryIndex) in processEntries(subsection.entries)" :key="entryIndex">
                        <!-- Render grouped items as a list -->
                        <ul v-if="processedEntry.type === 'item-group'" class="list-disc pl-6 mb-4 space-y-2 text-slate-700">
                          <li v-for="(item, itemIndex) in processedEntry.items" :key="itemIndex" v-html="item.replace(/\n/g, '<br>')"></li>
                  </ul>
                        <!-- Render content as paragraph -->
                        <p v-else-if="processedEntry.type === 'content'" class="text-slate-700 leading-relaxed mb-4" v-html="processedEntry.value.replace(/\n/g, '<br>')"></p>
                        <!-- Fallback for entries without type -->
                        <p v-else-if="processedEntry.value" class="text-slate-700 leading-relaxed mb-4" v-html="processedEntry.value.replace(/\n/g, '<br>')"></p>
                      </template>
                    </div>
                  </template>
                </section>

                <!-- Regulatory Status -->
                <section v-if="regulatorySubsections && regulatorySubsections.length > 0" id="regulatory-status" class="mb-12">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield w-5 h-5 text-white" aria-hidden="true">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">
                      Regulatory Status and Considerations
                    </h2>
                  </div>               
  
                  <!-- Subsections -->
                  <template v-for="(subsection, index) in regulatorySubsections" :key="index">
                    <!-- Second and third subsections (index 1 and 2) use styled container -->
                    <h3 v-if="subsection.title" class="text-xl font-semibold text-slate-900 mb-3">{{ subsection.title }}</h3>
                    <div v-if="index === 1 || index === 2" class="bg-slate-50 border border-slate-200 rounded-lg p-5 mb-4">
                      <div v-if="subsection.entries && subsection.entries.length > 0">
                        <template v-for="(processedEntry, entryIndex) in processEntries(subsection.entries)" :key="entryIndex">
                          <!-- Render grouped items as a list -->
                          <ul v-if="processedEntry.type === 'item-group'" class="list-disc pl-6 mb-4 space-y-2 text-slate-700">
                            <li v-for="(item, itemIndex) in processedEntry.items" :key="itemIndex" v-html="item.replace(/\n/g, '<br>')"></li>
                          </ul>
                          <!-- Render content as paragraph -->
                          <p v-else-if="processedEntry.type === 'content'" class="text-slate-700 text-sm leading-relaxed mb-2" v-html="processedEntry.value.replace(/\n/g, '<br>')"></p>
                          <!-- Fallback for entries without type -->
                          <p v-else-if="processedEntry.value" class="text-slate-700 text-sm leading-relaxed mb-2" v-html="processedEntry.value.replace(/\n/g, '<br>')"></p>
                        </template>
                  </div>
                  </div>
                    <!-- Other subsections use default template -->
                    <template v-else>
                      <!-- <h3 v-if="subsection.title" class="text-xl font-semibold text-slate-900 mb-3 mt-6">{{ subsection.title }}</h3> -->
                      <div v-if="subsection.entries && subsection.entries.length > 0" class="mb-4">
                        <template v-for="(processedEntry, entryIndex) in processEntries(subsection.entries)" :key="entryIndex">
                          <!-- Render grouped items as a list -->
                          <ul v-if="processedEntry.type === 'item-group'" class="list-disc pl-6 mb-4 space-y-2 text-slate-700">
                            <li v-for="(item, itemIndex) in processedEntry.items" :key="itemIndex" v-html="item.replace(/\n/g, '<br>')"></li>
                  </ul>
                          <!-- Render content as paragraph -->
                          <p v-else-if="processedEntry.type === 'content'" class="text-slate-700 leading-relaxed mb-4" v-html="processedEntry.value.replace(/\n/g, '<br>')"></p>
                          <!-- Fallback for entries without type -->
                          <p v-else-if="processedEntry.value" class="text-slate-700 leading-relaxed mb-4" v-html="processedEntry.value.replace(/\n/g, '<br>')"></p>
                        </template>
                      </div>
                    </template>
                  </template>
    
                  <!-- Important Note Warning Box -->
                  <div v-if="regulatoryImportantNote" class="bg-amber-50 border border-amber-200 rounded-lg p-5 mt-6">
                    <p class="text-amber-900 text-sm leading-relaxed" v-html="regulatoryImportantNote.replace(/\n/g, '<br>')"></p>
                  </div>
                </section>

                <!-- Potential Applications -->
                <section v-if="potentialApplicationsIntro || (potentialApplications && potentialApplications.length > 0)" id="potential-applications" class="mb-12">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-stethoscope w-5 h-5 text-white" aria-hidden="true">
                        <path d="M11 2v2"></path>
                        <path d="M5 2v2"></path>
                        <path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"></path>
                        <path d="M8 15a6 6 0 0 0 12 0v-3"></path>
                        <circle cx="20" cy="10" r="2"></circle>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">
                      Potential Therapeutic Applications
                    </h2>
                  </div>
    
                  <!-- Introductory Paragraph -->
                  <p v-if="potentialApplicationsIntro" class="text-slate-700 leading-relaxed mb-4" v-html="potentialApplicationsIntro.replace(/\n/g, '<br>')"></p>
    
                  <!-- Application Cards -->
                  <div v-if="potentialApplications && potentialApplications.length > 0" class="space-y-4">
                    <div 
                      v-for="(app, index) in potentialApplications" 
                      :key="index"
                      :class="[
                        'rounded-lg border p-5',
                        getApplicationColorClasses(app.title, index).border,
                        getApplicationColorClasses(app.title, index).bg
                      ]"
                    >
                      <!-- Icon -->
                      <h4 class="font-semibold text-slate-900 mb-2 flex items-center gap-2">                      
                        <svg 
                          v-if="getApplicationIconType(app.title) === 'bone'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-bone w-5 h-5 text-blue-600"
                          aria-hidden="true"
                        >
                          <path d="M17 10c.7-.7 1.69 0 2.5 0a2.5 2.5 0 1 0 0-5 .5.5 0 0 1-.5-.5 2.5 2.5 0 1 0-5 0c0 .81.7 1.8 0 2.5l-7 7c-.7.7-1.69 0-2.5 0a2.5 2.5 0 0 0 0 5c.28 0 .5.22.5.5a2.5 2.5 0 1 0 5 0c0-.81-.7-1.8 0-2.5Z"></path>
                        </svg>
                        <svg 
                          v-else-if="getApplicationIconType(app.title) === 'heart'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-heart w-5 h-5 text-rose-600"
                          aria-hidden="true"
                        >
                          <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"></path>
                        </svg>
                        <svg 
                          v-else-if="getApplicationIconType(app.title) === 'brain'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-brain w-5 h-5 text-purple-600"
                          aria-hidden="true"
                        >
                          <path d="M12 18V5"></path>
                          <path d="M15 13a4.17 4.17 0 0 1-3-4 4.17 4.17 0 0 1-3 4"></path>
                          <path d="M17.598 6.5A3 3 0 1 0 12 5a3 3 0 1 0-5.598 1.5"></path>
                          <path d="M17.997 5.125a4 4 0 0 1 2.526 5.77"></path>
                          <path d="M18 18a4 4 0 0 0 2-7.464"></path>
                          <path d="M19.967 17.483A4 4 0 1 1 12 18a4 4 0 1 1-7.967-.517"></path>
                          <path d="M6 18a4 4 0 0 1-2-7.464"></path>
                          <path d="M6.003 5.125a4 4 0 0 0-2.526 5.77"></path>
                        </svg>
                        <svg 
                          v-else-if="getApplicationIconType(app.title) === 'shield'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-shield w-5 h-5 text-emerald-600"
                          aria-hidden="true"
                        >
                          <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        </svg>
                        <svg 
                          v-else-if="getApplicationIconType(app.title) === 'heartbeat'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-heart-pulse w-5 h-5 text-emerald-600"
                          aria-hidden="true"
                        >
                          <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path>
                          <path d="M3.13 14a7.5 7.5 0 0 0 2.239 3.235"></path>
                          <path d="M6.34 18.12a12 12 0 0 1-.12-2.74"></path>
                        </svg>
                        <svg 
                          v-else-if="getApplicationIconType(app.title) === 'zap'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-zap w-5 h-5 text-cyan-600"
                          aria-hidden="true"
                        >
                          <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                        </svg>
                        <svg 
                          v-else
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-circle w-5 h-5 text-slate-600"
                          aria-hidden="true"
                        >
                          <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                        {{ app.title }}
                      </h4>                      
                      <!-- Content -->
                      <p class="text-slate-700 text-sm leading-relaxed" v-html="app.description.replace(/\n/g, '<br>')"></p>
                    </div>
                  </div>
    
                  <!-- Important Context -->
                  <div v-if="potentialApplicationsImportantContext" class="bg-blue-50 border border-blue-200 rounded-lg p-5 mt-6">
                    <p class="text-blue-900 text-sm leading-relaxed">
                      <strong>Important Context:</strong>
                      {{ potentialApplicationsImportantContext }}
                    </p>
                  </div>
                </section>

                <!-- Conclusion -->
                <section v-if="conclusion" class="mb-12">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-5 h-5 text-white" aria-hidden="true">
                        <path d="M12 7v14"></path>
                        <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
                        <circle cx="20" cy="10" r="2"></circle>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">
                      Conclusion
                    </h2>
                  </div>
                  <template v-if="Array.isArray(conclusion)">
                    <p v-for="(paragraph, index) in conclusion" :key="index" class="text-slate-700 leading-relaxed mb-4" v-html="paragraph.replace(/\n/g, '<br>')"></p>
                  </template>
                  <template v-else>
                    <template v-for="(paragraph, index) in conclusion.split(/\n\n+/)">
                      <p v-if="paragraph.trim()" :key="index" class="text-slate-700 leading-relaxed mb-4" v-html="paragraph.trim().replace(/\n/g, '<br>')"></p>
                    </template>
                  </template>
                </section>

                <!-- References -->
                <section id="references" class="mb-8">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="p-2 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link w-5 h-5 text-white" aria-hidden="true">
                        <path d="M15 3h6v6"></path>
                        <path d="M10 14 21 3"></path>
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                      </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">
                      References
                    </h2>
                  </div>
                  <div class="space-y-3 text-sm">
                    <div 
                      v-for="(ref, index) in references" 
                      :key="index"
                      class="bg-slate-50 border-l-4 border-slate-600 p-4 hover:bg-slate-100 transition-colors"
                    >
                      <p class="text-slate-700 mb-1">
                        <strong>{{ index + 1 }}. {{ ref.authors }}, {{ ref.title }}</strong>
                      </p>
                      <p class="text-slate-600 mb-2">{{ ref.description }}</p>
                      <p class="text-xs text-slate-500">{{ ref.citation }}</p>
                      <div v-if="ref.links && ref.links.length > 0" class="flex flex-wrap gap-2 mt-2">
                        <a 
                          v-for="(link, linkIndex) in ref.links.filter(l => l.url && l.url.trim())" 
                          :key="linkIndex"
                          :href="processReferenceLink(link.url)"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-slate-600 hover:text-slate-900 flex items-center gap-1 transition-colors"
                        >                      
                          <svg                         
                            xmlns="http://www.w3.org/2000/svg" 
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="lucide lucide-external-link w-3 h-3"
                          >
                            <path d="M15 3h6v6"></path>
                            <path d="M10 14 21 3"></path>
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                          </svg>
                          {{ link.label || link.url }}
                        </a>
                      </div>
                    </div>                  
                  </div>  
                </section>

                <!-- Footer Disclaimer -->
                <div class="text-sm text-slate-500 pt-6 border-t border-slate-200">
                  <p>This article is for informational purposes only. Last updated: February 2026</p>
                  <p class="mt-1">Source: Comprehensive research overview compiled from published scientific literature</p>
                </div>
              </div>     
            </div>
          </div>
        </div>
      </div>
    </div>
  </FrontLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'

// Format mechanism item text to bold key terms
const formatMechanismItem = (item) => {
  if (typeof item === 'string') {
    // If item is already formatted HTML, return as is
    if (item.includes('<strong>') || item.includes('<b>')) {
      return item
    }
    // Otherwise, try to bold common patterns
    // Bold acronyms with optional parentheses: "VEGF (Vascular Endothelial Growth Factor)"
    let formatted = item.replace(/([A-Z]{2,}(?:\s*\([^)]+\))?)/g, '<strong>$1</strong>')
    // Bold titles followed by colon: "Cell Survival Signals:"
    formatted = formatted.replace(/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+):/g, '<strong>$1:</strong>')
    return formatted
  }
  // If item is an object with title and description
  if (item && typeof item === 'object') {
    const title = item.title ? `<strong>${item.title}</strong>` : ''
    const desc = item.description || ''
    return title ? `${title} ${desc}` : desc
  }
  return item
}

// Function to get tag color classes based on tag value
const getTagColorClass = (tag) => {
  if (!tag) {
    return 'bg-blue-500/20 text-blue-100 border border-blue-400/30'
  }
  
  const tagLower = tag.toString().toLowerCase().trim()
  
  if (tagLower.includes('pentadecapeptide')) {
    return 'bg-blue-500/20 text-blue-100 border border-blue-400/30'
  } else if (tagLower.includes('research only') || tagLower === 'research only') {
    return 'bg-amber-500/20 text-amber-100 border border-amber-400/30'
  } else if (tagLower.includes('gastric origin') || tagLower === 'gastric origin') {
    return 'bg-emerald-500/20 text-emerald-100 border border-emerald-400/30'
  }
  
  // Default color (blue) if no match
  return 'bg-blue-500/20 text-blue-100 border border-blue-400/30'
}

// Function to get color class for key points based on index
const getKeyPointColorClass = (index) => {
  const colors = [
    'from-blue-500 to-blue-600',      // blue
    'from-emerald-500 to-emerald-600', // emerald
    'from-purple-500 to-purple-600',   // purple
    'from-amber-500 to-amber-600',     // amber
    'from-rose-500 to-rose-600'        // rose
  ]
  return colors[index % colors.length]
}

// Function to get area icon type based on area name
const getAreaIconType = (areaName) => {
  if (!areaName) return 'default'
  
  const nameLower = areaName.toLowerCase()
  
  // Orthopedic Recovery - bone icon
  if (nameLower.includes('orthopedic') || nameLower.includes('orthopaedic') || nameLower.includes('bone') || nameLower.includes('tendon') || nameLower.includes('ligament')) {
    return 'bone'
  }
  
  // Wound Healing - heartbeat/pulse icon
  if (nameLower.includes('wound') || nameLower.includes('healing') || nameLower.includes('skin') || nameLower.includes('tissue regeneration')) {
    return 'heartbeat'
  }
  
  // Gastrointestinal Health - heart icon
  if (nameLower.includes('gastrointestinal') || nameLower.includes('stomach') || nameLower.includes('intestinal') || nameLower.includes('digestive') || nameLower.includes('gut')) {
    return 'heart'
  }
  
  // Neuroprotection - brain icon
  if (nameLower.includes('neuro') || nameLower.includes('brain') || nameLower.includes('cognitive') || nameLower.includes('neural')) {
    return 'brain'
  }
  
  // Anti-Inflammatory - shield icon
  if (nameLower.includes('anti-inflammatory') || nameLower.includes('inflammatory') || nameLower.includes('inflammation') || nameLower.includes('immune')) {
    return 'shield'
  }
  
  // Angiogenesis - lightning bolt icon
  if (nameLower.includes('angiogenesis') || nameLower.includes('blood vessel') || nameLower.includes('circulation') || nameLower.includes('vascular')) {
    return 'zap'
  }
  
  // Default - circle icon
  return 'default'
}

// Function to get area color classes (border and text) based on area name
const getAreaColorClasses = (areaName) => {
  if (!areaName) {
    return {
      border: 'border-gray-200',
      text: 'text-slate-900',
      iconBg: 'from-gray-500 to-gray-600'
    }
  }
  
  const nameLower = areaName.toLowerCase()
  
  // Orthopedic Recovery - blue
  if (nameLower.includes('orthopedic') || nameLower.includes('orthopaedic') || nameLower.includes('bone') || nameLower.includes('tendon') || nameLower.includes('ligament')) {
    return {
      border: 'border-blue-200',
      text: 'text-blue-900',
      iconBg: 'from-blue-500 to-blue-600'
    }
  }
  
  // Wound Healing - green/emerald
  if (nameLower.includes('wound') || nameLower.includes('healing') || nameLower.includes('skin') || nameLower.includes('tissue regeneration')) {
    return {
      border: 'border-emerald-200',
      text: 'text-emerald-900',
      iconBg: 'from-emerald-500 to-emerald-600'
    }
  }
  
  // Gastrointestinal Health - red/rose
  if (nameLower.includes('gastrointestinal') || nameLower.includes('stomach') || nameLower.includes('intestinal') || nameLower.includes('digestive') || nameLower.includes('gut')) {
    return {
      border: 'border-rose-200',
      text: 'text-rose-900',
      iconBg: 'from-rose-500 to-rose-600'
    }
  }
  
  // Neuroprotection - purple
  if (nameLower.includes('neuro') || nameLower.includes('brain') || nameLower.includes('cognitive') || nameLower.includes('neural')) {
    return {
      border: 'border-purple-200',
      text: 'text-purple-900',
      iconBg: 'from-purple-500 to-purple-600'
    }
  }
  
  // Anti-Inflammatory - orange/amber
  if (nameLower.includes('anti-inflammatory') || nameLower.includes('inflammatory') || nameLower.includes('inflammation') || nameLower.includes('immune')) {
    return {
      border: 'border-amber-200',
      text: 'text-amber-900',
      iconBg: 'from-amber-500 to-amber-600'
    }
  }
  
  // Angiogenesis - cyan/sky blue
  if (nameLower.includes('angiogenesis') || nameLower.includes('blood vessel') || nameLower.includes('circulation') || nameLower.includes('vascular')) {
    return {
      border: 'border-cyan-200',
      text: 'text-cyan-900',
      iconBg: 'from-cyan-500 to-cyan-600'
    }
  }
  
  // Default - gray
  return {
    border: 'border-gray-200',
    text: 'text-slate-900',
    iconBg: 'from-gray-500 to-gray-600'
  }
}

// Function to get application icon type based on title
const getApplicationIconType = (title) => {
  if (!title) return 'default'
  
  const titleLower = title.toLowerCase()
  
  // Orthopedic/Bone - bone icon
  if (titleLower.includes('orthopedic') || titleLower.includes('orthopaedic') || titleLower.includes('bone') || titleLower.includes('tendon') || titleLower.includes('ligament') || titleLower.includes('joint')) {
    return 'bone'
  }
  
  // Gastrointestinal - heart icon
  if (titleLower.includes('gastrointestinal') || titleLower.includes('stomach') || titleLower.includes('intestinal') || titleLower.includes('digestive') || titleLower.includes('gut')) {
    return 'heart'
  }
  
  // Neuroprotection - brain icon
  if (titleLower.includes('neuro') || titleLower.includes('brain') || titleLower.includes('cognitive') || titleLower.includes('neural')) {
    return 'brain'
  }
  
  // Anti-Inflammatory/Immune - shield icon
  if (titleLower.includes('anti-inflammatory') || titleLower.includes('inflammatory') || titleLower.includes('inflammation') || titleLower.includes('organ')) {
    return 'shield'
  }
  
  // Wound Healing - heartbeat icon
  if (titleLower.includes('wound') || titleLower.includes('healing') || titleLower.includes('skin') || titleLower.includes('tissue regeneration')) {
    return 'heartbeat'
  }
  
  // Angiogenesis/Vascular - zap icon
  if (titleLower.includes('angiogenesis') || titleLower.includes('blood vessel') || titleLower.includes('circulation') || titleLower.includes('vascular')) {
    return 'zap'
  }
  
  // Default - circle icon
  return 'default'
}

// Function to get application color classes based on title
const getApplicationColorClasses = (title, index) => {
  if (!title) {
    // Cycle through colors if no title match
    const colors = [
      { border: 'border-blue-200', bg: 'bg-blue-50', iconBg: 'bg-blue-500' },
      { border: 'border-emerald-200', bg: 'bg-emerald-50', iconBg: 'bg-emerald-500' },
      { border: 'border-purple-200', bg: 'bg-purple-50', iconBg: 'bg-purple-500' },
      { border: 'border-amber-200', bg: 'bg-amber-50', iconBg: 'bg-amber-500' },
      { border: 'border-rose-200', bg: 'bg-rose-50', iconBg: 'bg-rose-500' }
    ]
    return colors[index % colors.length]
  }
  
  const titleLower = title.toLowerCase()
  
  // Orthopedic/Bone - blue
  if (titleLower.includes('orthopedic') || titleLower.includes('orthopaedic') || titleLower.includes('bone') || titleLower.includes('tendon') || titleLower.includes('ligament') || titleLower.includes('joint')) {
    return {
      border: 'border-blue-200',
      bg: 'bg-blue-50',
      iconBg: 'bg-blue-500'
    }
  }
  
  // Gastrointestinal - rose
  if (titleLower.includes('gastrointestinal') || titleLower.includes('stomach') || titleLower.includes('intestinal') || titleLower.includes('digestive') || titleLower.includes('gut')) {
    return {
      border: 'border-rose-200',
      bg: 'bg-rose-50',
      iconBg: 'bg-rose-500'
    }
  }
  
  // Neuroprotection - purple
  if (titleLower.includes('neuro') || titleLower.includes('brain') || titleLower.includes('cognitive') || titleLower.includes('neural')) {
    return {
      border: 'border-purple-200',
      bg: 'bg-purple-50',
      iconBg: 'bg-purple-500'
    }
  }
  
  // Anti-Inflammatory/Immune - amber
  if (titleLower.includes('anti-inflammatory') || titleLower.includes('inflammatory') || titleLower.includes('inflammation') || titleLower.includes('immune')) {
    return {
      border: 'border-amber-200',
      bg: 'bg-amber-50',
      iconBg: 'bg-amber-500'
    }
  }
  
  // Wound Healing - emerald
  if (titleLower.includes('organ') || titleLower.includes('healing') || titleLower.includes('skin') || titleLower.includes('tissue regeneration')) {
    return {
      border: 'border-emerald-200',
      bg: 'bg-emerald-50',
      iconBg: 'bg-emerald-500'
    }
  }
  
  // Angiogenesis/Vascular - cyan
  if (titleLower.includes('angiogenesis') || titleLower.includes('blood vessel') || titleLower.includes('circulation') || titleLower.includes('vascular')) {
    return {
      border: 'border-cyan-200',
      bg: 'bg-cyan-50',
      iconBg: 'bg-cyan-500'
    }
  }
  
  // Default - cycle through colors
  const colors = [
    { border: 'border-blue-200', bg: 'bg-blue-50', iconBg: 'bg-blue-500' },
    { border: 'border-emerald-200', bg: 'bg-emerald-50', iconBg: 'bg-emerald-500' },
    { border: 'border-purple-200', bg: 'bg-purple-50', iconBg: 'bg-purple-500' },
    { border: 'border-amber-200', bg: 'bg-amber-50', iconBg: 'bg-amber-500' },
    { border: 'border-rose-200', bg: 'bg-rose-50', iconBg: 'bg-rose-500' }
  ]
  return colors[index % colors.length]
}

// Function to get preclinical subsection icon type (determines which SVG icon is displayed)
const getPreclinicalIconType = (subsection) => {
  if (!subsection || !subsection.title) return null
  
  // Match the exact logic from the template v-if conditions
  if (subsection.title === 'Musculoskeletal Tissue Research') {
    return 'bone' // bone icon with text-blue-600
  }
  
  if (subsection.title === 'Gastrointestinal Research') {
    return 'heart' // heart icon with text-rose-600
  }
  
  if (subsection.title === 'brain') {
    return 'brain' // brain icon with text-purple-600
  }
  
  return null // no icon displayed
}

// Function to get preclinical finding color classes based on subsection's SVG icon color
const getPreclinicalFindingColorClasses = (subsection, findingIndex) => {
  const iconType = getPreclinicalIconType(subsection)
  
  // Map icon type to color classes (matching the icon's text color)
  if (iconType === 'bone') {
    // bone icon has text-blue-600
    return {
      border: 'border-blue-200',
      bg: 'bg-blue-50'
    }
  }
  
  if (iconType === 'heart') {
    // heart icon has text-rose-600
    return {
      border: 'border-rose-200',
      bg: 'bg-rose-50'
    }
  }
  
  if (iconType === 'brain') {
    // brain icon has text-purple-600
    return {
      border: 'border-purple-200',
      bg: 'bg-purple-50'
    }
  }
  
  // No icon displayed - cycle through colors (amber, purple, emerald)
  const colors = [
    { border: 'border-amber-200', bg: 'bg-amber-50' },
    { border: 'border-purple-200', bg: 'bg-purple-50' },
    { border: 'border-emerald-200', bg: 'bg-emerald-50' }
  ]
  return colors[findingIndex % colors.length]
}

// Amino acid mapping: code -> { fullName, threeLetter }
const aminoAcidMap = {
  'A': { fullName: 'Alanine', threeLetter: 'Ala' },
  'Ala': { fullName: 'Alanine', threeLetter: 'Ala' },
  'R': { fullName: 'Arginine', threeLetter: 'Arg' },
  'Arg': { fullName: 'Arginine', threeLetter: 'Arg' },
  'N': { fullName: 'Asparagine', threeLetter: 'Asn' },
  'Asn': { fullName: 'Asparagine', threeLetter: 'Asn' },
  'D': { fullName: 'Aspartic Acid', threeLetter: 'Asp' },
  'Asp': { fullName: 'Aspartic Acid', threeLetter: 'Asp' },
  'C': { fullName: 'Cysteine', threeLetter: 'Cys' },
  'Cys': { fullName: 'Cysteine', threeLetter: 'Cys' },
  'Q': { fullName: 'Glutamine', threeLetter: 'Gln' },
  'Gln': { fullName: 'Glutamine', threeLetter: 'Gln' },
  'E': { fullName: 'Glutamic Acid', threeLetter: 'Glu' },
  'Glu': { fullName: 'Glutamic Acid', threeLetter: 'Glu' },
  'G': { fullName: 'Glycine', threeLetter: 'Gly' },
  'Gly': { fullName: 'Glycine', threeLetter: 'Gly' },
  'H': { fullName: 'Histidine', threeLetter: 'His' },
  'His': { fullName: 'Histidine', threeLetter: 'His' },
  'I': { fullName: 'Isoleucine', threeLetter: 'Ile' },
  'Ile': { fullName: 'Isoleucine', threeLetter: 'Ile' },
  'L': { fullName: 'Leucine', threeLetter: 'Leu' },
  'Leu': { fullName: 'Leucine', threeLetter: 'Leu' },
  'K': { fullName: 'Lysine', threeLetter: 'Lys' },
  'Lys': { fullName: 'Lysine', threeLetter: 'Lys' },
  'M': { fullName: 'Methionine', threeLetter: 'Met' },
  'Met': { fullName: 'Methionine', threeLetter: 'Met' },
  'F': { fullName: 'Phenylalanine', threeLetter: 'Phe' },
  'Phe': { fullName: 'Phenylalanine', threeLetter: 'Phe' },
  'P': { fullName: 'Proline', threeLetter: 'Pro' },
  'Pro': { fullName: 'Proline', threeLetter: 'Pro' },
  'S': { fullName: 'Serine', threeLetter: 'Ser' },
  'Ser': { fullName: 'Serine', threeLetter: 'Ser' },
  'T': { fullName: 'Threonine', threeLetter: 'Thr' },
  'Thr': { fullName: 'Threonine', threeLetter: 'Thr' },
  'W': { fullName: 'Tryptophan', threeLetter: 'Trp' },
  'Trp': { fullName: 'Tryptophan', threeLetter: 'Trp' },
  'Y': { fullName: 'Tyrosine', threeLetter: 'Tyr' },
  'Tyr': { fullName: 'Tyrosine', threeLetter: 'Tyr' },
  'V': { fullName: 'Valine', threeLetter: 'Val' },
  'Val': { fullName: 'Valine', threeLetter: 'Val' }
}

// Function to analyze amino acid sequence and return composition
const analyzeAminoAcidSequence = (sequence) => {
  if (!sequence || typeof sequence !== 'string') {
    return []
  }
  
  const trimmedSeq = sequence.trim()
  if (!trimmedSeq) {
    return []
  }
  
  let residues = []
  
  // First, try splitting by common delimiters: dash, space, comma
  const delimited = trimmedSeq.split(/[-,\s]+/).filter(res => res.trim().length > 0)
  
  // If we got multiple parts, use them
  if (delimited.length > 1) {
    residues = delimited
  } else {
    // If no delimiters found, check if it's a continuous string of single-letter codes
    // Single-letter codes are typically 1 character and uppercase
    const singleLetterMatch = trimmedSeq.match(/^[A-Z]+$/i)
    if (singleLetterMatch) {
      // Split into individual characters
      residues = trimmedSeq.split('').filter(c => c.trim().length > 0)
    } else {
      // Try to match three-letter codes (like "GlyProAspAla")
      const threeLetterMatch = trimmedSeq.match(/([A-Z][a-z]{2})+/g)
      if (threeLetterMatch) {
        // Extract three-letter codes
        residues = trimmedSeq.match(/[A-Z][a-z]{2}/g) || []
      } else {
        // Fallback: use the whole string as one residue
        residues = [trimmedSeq]
      }
    }
  }
  
  // Count occurrences of each amino acid
  const counts = {}
  
  residues.forEach(residue => {
    const trimmed = residue.trim()
    // Try to find the amino acid in our map (case-insensitive)
    const key = Object.keys(aminoAcidMap).find(
      k => k.toLowerCase() === trimmed.toLowerCase()
    )
    
    if (key) {
      const aminoAcid = aminoAcidMap[key]
      const fullName = aminoAcid.fullName
      if (!counts[fullName]) {
        counts[fullName] = 0
      }
      counts[fullName]++
    }
  })
  
  // Convert to array format: "Glycine (Gly): 3"
  const composition = Object.keys(counts)
    .sort() // Sort alphabetically by full name
    .map(fullName => {
      const count = counts[fullName]
      const threeLetter = aminoAcidMap[Object.keys(aminoAcidMap).find(
        k => aminoAcidMap[k].fullName === fullName
      )].threeLetter
      return `${fullName} (${threeLetter}): ${count}`
    })
  
  return composition
}

// Helper function to process entries and group consecutive items
const processEntries = (entries) => {
  if (!entries || !Array.isArray(entries)) return []
  
  const processed = []
  let i = 0
  
  while (i < entries.length) {
    const entry = entries[i]
    
    if (entry.type === 'item') {
      // Collect consecutive items
      const items = []
      while (i < entries.length && entries[i].type === 'item') {
        items.push(entries[i].value)
        i++
      }
      processed.push({ type: 'item-group', items })
    } else {
      // Add content or other types as-is
      processed.push(entry)
      i++
    }
  }
  
  return processed
}

// Helper function to process reference link URLs
const processReferenceLink = (url) => {
  if (!url || typeof url !== 'string') return '#'
  
  const trimmedUrl = url.trim()
  
  // Check if it's already a normal URL
  if (trimmedUrl.startsWith('http://') || trimmedUrl.startsWith('https://')) {
    return trimmedUrl
  }
  
  // Check for PubMed format: "PubMed: 40005999" or "PubMed:40005999" (case-insensitive)
  const pubmedMatch = trimmedUrl.match(/^PubMed:\s*(\d+)$/i)
  if (pubmedMatch) {
    return `https://pubmed.ncbi.nlm.nih.gov/${pubmedMatch[1]}/`
  }
  
  // Check for PMC format: "PMC Article: PMC11053547" or "PMC Article:PMC11053547" (case-insensitive)
  // Also handles variations like "PMC: PMC11053547" or just "PMC11053547" after "PMC Article:"
  const pmcMatch = trimmedUrl.match(/^PMC\s+Article:\s*(PMC\d+)$/i)
  if (pmcMatch) {
    return `https://pmc.ncbi.nlm.nih.gov/articles/${pmcMatch[1]}/`
  }
  
  // Return original URL if no pattern matches
  return trimmedUrl
}

// Get props
const props = defineProps({
  id: {
    type: [Number, String],
    required: true
  },
  name: {
    type: String,
    required: true
  },
  categoryName: {
    type: String,
    default: ''
  },
  slug: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  tags: {
    type: Array,
    default: () => []
  },
  primaryResearch: {
    type: Object,
    default: () => ({
      institution: '',
      url: ''
    })
  },
  molecularInfo: {
    type: Object,
    default: () => ({
      formula: '',
      molecularWeight: '',
      casNumber: ''
    })
  },
  contents: {
    type: Array,
    default: () => [
      { id: 'overview', label: 'Overview' },
      { id: 'areas-of-research', label: 'Areas of Research' },
      { id: 'background', label: 'Background' },
      { id: 'mechanism-of-action', label: 'Mechanism of Action' },
      { id: 'preclinical-research', label: 'Preclinical Research' },
      { id: 'human-use-evidence', label: 'Human Use & Evidence' },
      { id: 'regulatory-status', label: 'Regulatory Status' },
      { id: 'potential-applications', label: 'Potential Applications' },
      { id: 'references', label: 'References' }
    ]
  },
  sponsoredSections: {
    type: Array,
    default: () => []
  },
  aminoAcidSequence: {
    type: Object,
    default: () => ({
      residueCount: 0,
      sequence: '',
      composition: [],
      properties: {
        netCharge: '',
        hydrophobic: '',
        stability: '',
        solubility: ''
      }
    })
  },
  keyPoints: {
    type: Array,
    default: () => []
  },
  overview: {
    type: String,
    default: ''
  },
  areasOfResearch: {
    type: Array,
    default: () => []
  },
  areasOfResearchIntro: {
    type: String,
    default: ''
  },
  sponsoredAd: {
    type: Object,
    default: () => ({
      title: '',
      description: '',
      code: ''
    })
  },
  background: {
    type: String,
    default: ''
  },
  backgroundParagraphs: {
    type: Array,
    default: () => []
  },
  researchNote: {
    type: String,
    default: ''
  },
  mechanismOfAction: {
    type: String,
    default: ''
  },
  mechanismOfActionIntro: {
    type: String,
    default: ''
  },
  mechanismSubsections: {
    type: Array,
    default: () => []
  },
  preclinicalResearch: {
    type: String,
    default: ''
  },
  preclinicalIntro: {
    type: String,
    default: ''
  },
  preclinicalSubsections: {
    type: Array,
    default: () => []
  },
  preclinicalDisclaimer: {
    type: String,
    default: ''
  },
  humanUseEvidence: {
    type: String,
    default: ''
  },
  humanUseIntro: {
    type: String,
    default: ''
  },
  humanUseSubsections: {
    type: Array,
    default: () => []
  },
  regulatoryStatus: {
    type: String,
    default: ''
  },
  regulatorySubsections: {
    type: Array,
    default: () => []
  },
  regulatoryImportantNote: {
    type: String,
    default: ''
  },
  potentialApplications: {
    type: Array,
    default: () => []
  },
  potentialApplicationsIntro: {
    type: String,
    default: ''
  },
  potentialApplicationsImportantContext: {
    type: String,
    default: ''
  },
  potentialApplicationsAdditional: {
    type: Array,
    default: () => []
  },
  conclusion: {
    type: [String, Array],
    default: ''
  },
  references: {
    type: Array,
    default: () => []
  },
  referencesFooter: {
    type: [String, Array],
    default: ''
  }
})

// Computed property for amino acid composition
const aminoAcidComposition = computed(() => {
  if (props.aminoAcidSequence && props.aminoAcidSequence.sequence) {
    return analyzeAminoAcidSequence(props.aminoAcidSequence.sequence)
  }
  return []
})
</script>
