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
                <h1 class="text-5xl font-semibold text-white mb-2">{{ name }}</h1>
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
                  class="px-3 py-1 bg-blue-500/20 text-blue-100 border border-blue-400/30 rounded-full text-sm font-medium backdrop-blur-sm"
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
                  <div class="text-white font-mono text-xl">C₆₂H₉₈N₁₆O₂₂</div>
                </div>
                <div>
                  <div class="text-white/60 text-xs mb-1">Molecular Weight</div>
                  <div class="text-white font-semibold text-xl">1,419.53 g/mol</div>
                </div>
                <div>
                  <div class="text-white/60 text-xs mb-1">CAS Registry Number</div>
                  <div class="text-white font-mono text-xl">137525-51-0</div>
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
                Amino Acid Sequence (15 Residues)
              </h3>
              <div class="bg-white/5 rounded-lg p-4 border border-white/10">
                <p class="text-white font-mono text-sm leading-relaxed">
                  <span class="text-slate-200">Gly</span>
                  -
                  <span class="text-slate-200">Glu</span>
                  -
                  <span class="text-slate-200">Pro</span>
                  -
                  <span class="text-slate-200">Pro</span>
                  -
                  <span class="text-slate-200">Pro</span>
                  -
                  <span class="text-slate-200">Gly</span>
                  -
                  <span class="text-slate-200">Lys</span>
                  -
                  <span class="text-slate-200">Pro</span>
                  -
                  <span class="text-slate-200">Ala</span>
                  -
                  <span class="text-slate-200">Asp</span>
                  -
                  <span class="text-slate-200">Asp</span>
                  -
                  <span class="text-slate-200">Ala</span>
                  -
                  <span class="text-slate-200">Gly</span>
                  -
                  <span class="text-slate-200">Leu</span>
                  -
                  <span class="text-slate-200">Val</span>                  
                </p>
              </div>
              <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                  <div class="text-white/60 text-xs mb-2">Composition</div>
                  <div class="space-y-1 text-xs">
                    <div class="text-white/80">Glycine (Gly): 3</div>
                    <div class="text-white/80">Proline (Pro): 4</div>
                    <div class="text-white/80">Aspartic Acid (Asp): 2</div>
                    <div class="text-white/80">Alanine (Ala): 2</div>
                  </div>
                </div>
                <div>
                  <div class="text-white/60 text-xs mb-2">Properties</div>
                  <div class="space-y-1 text-xs">
                    <div class="text-white/80">Net Charge: -2 (at pH 7)</div>
                    <div class="text-white/80">Hydrophobic: 40%</div>
                    <div class="text-white/80">Stability: High</div>
                    <div class="text-white/80">Solubility: Water soluble</div>
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
                    <div class="w-2 h-2 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 mt-2 flex-shrink-0 group-hover:scale-125 transition-transform"></div>
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
                      BPC-157 is an experimental compound not approved for human use by any regulatory agency. This article is for informational and educational purposes only and does not constitute medical advice. Peptidemaps does not provide recommendations on safety, usage, or dosages.
                    </p>
                  </div>
                </div>
              </div>

              <div class="p-8 prose prose-slate max-w-none">
                <!-- Overview -->
                <section id="overview" class="mb-12">
                  <div class="flex items-center gap-3 mb-4 pb-3 border-b-2 border-slate-200">
                    <div class="">
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
                  <p class="text-slate-700 leading-relaxed mb-4">
                    BPC-157 (Body Protection Compound-157) is a synthetic peptide composed of 15 amino acids (a pentadecapeptide) that has garnered significant attention in preclinical research for its potential healing properties. 
                    The peptide's amino acid sequence (Gly-Glu-Pro-Pro-Pro-Gly-Lys-Pro-Ala-Asp-Asp-Ala-Gly-Leu-Val) does not match any naturally occurring peptide in the gut, yet it was isolated as a stable fragment from human gastric juice.
                  </p>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    The peptide has demonstrated remarkable stability in harsh environments, remaining intact in gastric acid and water for extended periods. Research shows it can survive over 24 hours in human gastric juice, 
                    which is unusual for peptides. With a molecular weight of approximately 1,419 Daltons, BPC-157 has been investigated as a potential drug candidate for various injuries and disorders, though it remains in experimental stages.
                  </p>
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
                  <p class="text-slate-700 leading-relaxed mb-6">
                    BPC-157 has been studied across multiple research domains in preclinical models. The following areas represent the primary focus of scientific investigation:
                  </p>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                      v-for="(area, index) in areasOfResearch" 
                      :key="index"
                      :class="[
                        'rounded-lg border p-5 hover:shadow-lg transition-all duration-300 group',
                        area.color === 'blue' ? 'border-blue-200 bg-blue-50' : '',
                        area.color === 'emerald' ? 'border-emerald-200 bg-emerald-50' : '',
                        area.color === 'rose' ? 'border-rose-200 bg-rose-50' : '',
                        area.color === 'purple' ? 'border-purple-200 bg-purple-50' : '',
                        area.color === 'amber' ? 'border-amber-200 bg-amber-50' : '',
                        area.color === 'cyan' ? 'border-cyan-200 bg-cyan-50' : '',
                        !area.color ? 'border-gray-200 bg-gray-50' : ''
                      ]"
                    >
                      <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div 
                          :class="[
                            'p-3 bg-gradient-to-br rounded-lg flex-shrink-0 group-hover:scale-110 transition-transform',
                            area.color === 'blue' ? 'from-blue-500 to-blue-600' : '',
                            area.color === 'emerald' ? 'from-emerald-500 to-emerald-600' : '',
                            area.color === 'rose' ? 'from-rose-500 to-rose-600' : '',
                            area.color === 'purple' ? 'from-purple-500 to-purple-600' : '',
                            area.color === 'amber' ? 'from-amber-500 to-amber-600' : '',
                            area.color === 'cyan' ? 'from-cyan-500 to-cyan-600' : '',
                            !area.color ? 'from-gray-500 to-gray-600' : ''
                          ]"
                        >
                          <svg 
                            v-if="area.icon === 'bone'"
                            xmlns="http://www.w3.org/2000/svg" 
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="white" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="lucide lucide-bone w-6 h-6 text-white"
                            aria-hidden="true"
                          >
                            <path d="M17 10c.7-.7 1.69 0 2.5 0a2.5 2.5 0 1 0 0-5 .5.5 0 0 1-.5-.5 2.5 2.5 0 1 0-5 0c0 .81.7 1.8 0 2.5l-7 7c-.7.7-1.69 0-2.5 0a2.5 2.5 0 0 0 0 5c.28 0 .5.22.5.5a2.5 2.5 0 1 0 5 0c0-.81-.7-1.8 0-2.5Z"></path>
                          </svg>
                          <svg 
                            v-else-if="area.icon === 'heartbeat'"
                            xmlns="http://www.w3.org/2000/svg" 
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="white" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="lucide lucide-activity w-6 h-6 text-white"
                            aria-hidden="true"
                          >
                            <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path>
                          </svg>
                          <svg 
                            v-else-if="area.icon === 'heart'"
                            xmlns="http://www.w3.org/2000/svg" 
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="white" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="lucide lucide-heart w-6 h-6 text-white"
                            aria-hidden="true"
                          >
                            <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"></path>
                          </svg>
                          <svg 
                            v-else-if="area.icon === 'brain'"
                            xmlns="http://www.w3.org/2000/svg" 
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="white" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="lucide lucide-brain w-6 h-6 text-white"
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
                            v-else-if="area.icon === 'shield'"
                            xmlns="http://www.w3.org/2000/svg" 
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="white" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="lucide lucide-shield w-6 h-6 text-white"
                            aria-hidden="true"
                          >
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                          </svg>
                          <svg 
                            v-else-if="area.icon === 'zap'"
                            xmlns="http://www.w3.org/2000/svg" 
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="white" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="lucide lucide-zap w-6 h-6 text-white"
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
                            stroke="white" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                            class="lucide lucide-circle w-6 h-6 text-white"
                            aria-hidden="true"
                          >
                            <circle cx="12" cy="12" r="10"></circle>
                          </svg>
                        </div>
                        <!-- Content -->
                        <div>
                          <h3 class="font-semibold text-cyan-700 mb-2">Angiogenesis</h3>
                          <p class="text-sm text-slate-600 leading-relaxed">New blood vessel formation and improved circulation</p>
                        </div>
                      </div>
                    </div>
                  </div>
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
                    <h2 class="text-2xl font-semibold text-slate-900 m-0">Background: What is BPC-157?</h2>
                  </div>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    BPC-157 was originally discovered in human gastric juice during gastroenterology research. In simple terms, a peptide is a short chain of amino acids – the building blocks of proteins. 
                    This particular peptide has been nicknamed the "body protection compound" in research circles due to the broad protective effects observed on the body's tissues in laboratory studies.
                  </p>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    Unlike many peptides that are quickly broken down by digestive enzymes, BPC-157 demonstrates exceptional stability. This characteristic means the peptide could potentially be administered orally and still exert effects, unlike most peptides that must be given by injection. 
                    In rodent studies, BPC-157 proved effective when administered through various routes including intramuscular and intravenous injection, demonstrating bioavailability and activity in the body.
                  </p>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    It's important to clarify that BPC-157 is an experimental compound. It is not approved as a medication by regulatory agencies and is officially designated for research use only at present. 
                    Despite this regulatory status, interest in BPC-157 has grown substantially due to promising early research findings in preclinical models.
                  </p>
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
                  <p class="text-slate-700 leading-relaxed mb-4">
                    BPC-157 appears to act on a wide range of biological pathways to protect and heal tissues. In scientific terms, it's often described as having "pleiotropic" effects – meaning it produces many different beneficial actions in the body. 
                    Key mechanisms identified in preclinical research include promoting blood vessel growth, supporting cell survival, reducing inflammation, and influencing the nervous system.
                  </p>
                    
                  <!-- Subsections -->
                  <div 
                    v-for="(subsection, index) in mechanismSubsections" 
                    :key="index"                  
                  >
                    <!-- Subsection Title -->
                    <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6 flex items-center gap-2">
                      <svg 
                        v-if="subsection.icon === 'arrow-up'"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="20" 
                        height="20" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        class="lucide lucide-trending-up w-5 h-5 text-emerald-600"
                        aria-hidden="true"
                      >
                        <path d="M16 7h6v6"></path>
                        <path d="m22 7-8.5 8.5-5-5L2 17"></path>
                      </svg>
                      <svg 
                        v-else-if="subsection.icon === 'shield'"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="20" 
                        height="20" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        class="lucide lucide-shield w-5 h-5 text-amber-600"
                        aria-hidden="true"
                      >
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                      </svg>
                      <svg 
                        v-else-if="subsection.icon === 'brain'"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="20" 
                        height="20" 
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
                      Pro-Healing Pathways
                    </h3>
  
                    <!-- Subsection Intro -->
                    <p class="text-slate-700 leading-relaxed mb-4">
                      Research has shown that BPC-157 increases several growth-related mediators in preclinical models:
                    </p>
  
                    <!-- Bullet Points -->
                    <ul class="list-disc pl-6 mb-4 space-y-2 text-slate-700">
                      <li 
                        v-for="(item, itemIndex) in subsection.items" 
                        :key="itemIndex"                      
                      >                      
                       <strong>VEGF (Vascular Endothelial Growth Factor):</strong>
                        Promotes new blood vessel formation (angiogenesis), crucial for delivering nutrients and oxygen to damaged tissues
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
                  <p class="text-slate-700 leading-relaxed mb-4">
                    Although human data are limited, preclinical studies in animals and cell cultures have revealed notable findings across multiple organ systems. Researchers have tested BPC-157 in models of tissue injury, inflammatory diseases, and neurological disorders.
                  </p>
                    
                  <!-- Subsections -->
                  <div 
                    v-for="(subsection, index) in preclinicalSubsections" 
                    :key="index"                    
                  >
                    <!-- Subsection Title -->
                    <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6 flex items-center gap-2">
                      <svg 
                        v-if="subsection.icon === 'wrench'"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="20" 
                        height="20" 
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
                        v-else-if="subsection.icon === 'heart'"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="20" 
                        height="20" 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                        class="text-pink-600"
                        aria-hidden="true"
                      >
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path>
                      </svg>
                      <svg 
                        v-else-if="subsection.icon === 'brain'"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="20" 
                        height="20" 
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
                        subsection.color === 'blue' ? 'border-blue-200 bg-blue-50' : '',
                        subsection.color === 'rose' ? 'border-rose-200 bg-rose-50' : '',
                        subsection.color === 'emerald' ? 'border-emerald-200 bg-emerald-50' : '',
                        subsection.color === 'purple' ? 'border-purple-200 bg-purple-50' : '',
                        subsection.color === 'amber' ? 'border-amber-200 bg-amber-50' : '',
                        !subsection.color ? 'border-gray-200 bg-gray-50' : ''
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
                    Across preclinical studies, BPC-157 has repeatedly demonstrated abilities in animal models that span multiple tissue types. However, 
                    it's crucial to remember that these are early-stage findings, and positive results in animals do not guarantee the same will occur in humans.
                  </p>                
                </section>

                <!-- Human Use & Evidence -->
                <section id="human-use-evidence" class="mb-12">
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
                  <p class="text-slate-700 leading-relaxed mb-4">
                    Despite the lack of formal approval, BPC-157 has made its way into experimental use, largely driven by compelling preclinical data. 
                    It's important to emphasize: there are no large-scale, definitive clinical trials of BPC-157 in humans yet, and any use outside of research is considered off-label and unapproved.
                  </p>                
    
                  <!-- Subsections -->
                  <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6">Pilot Safety Studies</h3>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    To date, only minimal human testing has been published. A recent pilot study in 2025 administered BPC-157 intravenously to two healthy adult volunteers to assess safety.
                     The participants received 10 mg on the first day and 20 mg on the second day by slow infusion. The results were encouraging in terms of safety: no adverse effects were observed, and detailed blood tests showed no harmful changes in liver, kidney, heart, or metabolic function after the infusions.
                  </p>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    While this finding supports the idea that BPC-157 may be safe in the short term, the sample size was only two people – far too small to draw broad conclusions. More rigorous safety trials with larger participant groups are needed to confirm safety in humans.
                  </p>
                  <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6">Anecdotal Clinical Use</h3>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    In the absence of official trials, some healthcare practitioners (particularly in sports medicine or integrative medicine) have experimented with BPC-157 as an off-label therapy. For example:
                  </p>
                  <ul class="list-disc pl-6 mb-4 space-y-2 text-slate-700">
                    <li>
                      A physician-authored report described 12 patients with knee injuries or osteoarthritis who received BPC-157 injections into the knee joint, with 11 of 12 reporting improvements. However, this was not a controlled study with no placebo group, and outcomes were based on patient self-reports.
                    </li>
                    <li>
                      A small pilot trial at a private clinic treated women suffering from chronic bladder pain by injecting BPC-157 into the bladder wall, noting symptom relief and no immediate side effects. This too lacked a control group and was an open-label observation.
                    </li>
                  </ul>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    Such reports must be viewed cautiously, as they are not the gold standard evidence required for medical approval. They do, however, suggest interest in exploring BPC-157's potential despite the unknowns.
                  </p>
                  <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6">Unpublished Clinical Trial</h3>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    A formal Phase I clinical trial (code-named PCO-02) was conducted around 2015 on 42 healthy volunteers to evaluate BPC-157's pharmacokinetics and safety. 
                    However, the results of that trial were never published. The trial was completed, but the data were not reported, which has raised questions in the research community. Without publication, the findings remain unknown.
                  </p>
                  <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6">Current Status</h3>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    Human experience with BPC-157 remains in early, experimental stages. Small studies and individual experiments hint at effects consistent with animal research, but robust clinical evidence to verify efficacy or proper protocols is lacking. 
                    Experts consistently stress that BPC-157 should not be used in routine medical practice yet. It remains a compound with potential, but until large controlled trials are conducted, any human use is effectively experimental.
                  </p>
                </section>

                <!-- Regulatory Status -->
                <section id="regulatory-status" class="mb-12">
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
  
                  <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6">Current Approval Status</h3>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    As of now, no country has approved BPC-157 as a prescription drug. It's not available through legitimate pharmacies for medical use. The peptide is officially designated for research use only.
                  </p>
                  <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6">United States</h3>
                  <div class="bg-slate-50 border border-slate-200 rounded-lg p-5 mb-4">
                    <p class="text-slate-700 text-sm leading-relaxed mb-2">
                      The U.S. Food and Drug Administration (FDA) has taken action against companies marketing BPC-157 as a dietary supplement, stating it does not meet the definition of a legal supplement ingredient. 
                      There is also no legal basis for compounding pharmacies to include BPC-157 in medications, though regulatory agencies have found some clinics doing so illegally.
                    </p>
                    <p class="text-slate-700 text-sm leading-relaxed">
                      Any product being sold is unregulated. Many websites sell BPC-157 as a "research chemical" with disclaimers that it's not for human use. 
                      This grey market means quality control is questionable – products might not contain the stated dose or could have impurities.
                    </p>
                  </div>
                  <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6">World Anti-Doping Agency (WADA)</h3>
                  <div class="bg-slate-50 border border-slate-200 rounded-lg p-5 mb-4">
                    <p class="text-slate-700 text-sm leading-relaxed mb-2">
                      WADA has banned BPC-157 for athletes under the class of "S0: Non-approved substances." This means athletes can face sanctions if caught using it in sport. The ban signals that BPC-157 is considered experimental and not recognized as a legitimate medication.
                    </p>
                  </div>
                  <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6">Animal Safety Data</h3>                
                  <p class="text-slate-700 leading-relaxed mb-4">
                    One attractive feature of BPC-157 in research is its apparently favorable safety profile in animals. Toxicology studies have found no lethal dose even at extremely high amounts:
                  </p>
                  <ul class="list-disc pl-6 mb-4 space-y-2 text-slate-700">
                    <li>
                      In rats, a single injection of 20 mg per kilogram (a massive dose for a peptide) caused no deaths or obvious toxic effects
                    </li>
                    <li>
                      In dogs, daily injections for a month at doses up to 1 mg/kg showed no significant adverse effects compared to control animals
                    </li>
                    <li>
                      Studies reported no changes in body weight, behavior, or organ health attributable to BPC-157
                    </li>
                  </ul>
                  <h3 class="text-xl font-semibold text-slate-900 mb-3 mt-6">Theoretical Concerns</h3>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    The most prominent theoretical concern is BPC-157's ability to promote growth factors and blood vessel formation. While beneficial for healing injuries, this raises a question: could BPC-157 unintentionally help existing cancer cells grow or spread? 
                    Cancerous tumors often exploit the same pathways (like FAK/paxillin and VEGF) to invade tissues and nourish themselves with blood supply.
                  </p>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    This idea is currently theoretical – no study has shown BPC-157 causes cancer in animals or humans. In fact, one lab test showed BPC-157 inhibited growth of a particular cancer cell line in culture. Nonetheless, experts urge caution until more is known about long-term effects.
                  </p>
                  
    
                  <!-- Important Note Warning Box -->
                  <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 mt-6">
                    <p class="text-amber-900 text-sm leading-relaxed">
                      <strong>Important Note:</strong>
                       "Absence of evidence is not evidence of absence" when it comes to safety. 
                       Because BPC-157 has not been extensively studied in people, there could be side effects that haven't been observed in small samples. Minor side effects like headaches, dizziness, or injection site reactions might not show up in tiny studies but could in larger populations.
                    </p>
                  </div>
                </section>

                <!-- Potential Applications -->
                <section id="potential-applications" class="mb-12">
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
                  <p class="text-slate-700 leading-relaxed mb-4">
                    If ongoing research continues to yield positive results, BPC-157 could be developed in the future for a variety of medical applications. 
                    Here are some potential uses that researchers envision, based on the peptide's demonstrated effects in preclinical studies:
                  </p>
    
                  <!-- Application Cards -->
                  <div class="space-y-4">
                    <div 
                      v-for="(app, index) in potentialApplications" 
                      :key="index"
                      :class="[
                        'rounded-lg border p-5',
                        app.color === 'blue' ? 'border-blue-200 bg-blue-50' : '',
                        app.color === 'rose' ? 'border-rose-200 bg-rose-50' : '',
                        app.color === 'purple' ? 'border-purple-200 bg-purple-50' : '',
                        app.color === 'emerald' ? 'border-emerald-200 bg-emerald-50' : '',
                        !app.color ? 'border-gray-200 bg-gray-50' : ''
                      ]"
                    >
                      <!-- Icon -->
                      <div 
                        :class="[
                          'w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0',
                          app.color === 'blue' ? 'bg-blue-500' : '',
                          app.color === 'rose' ? 'bg-rose-500' : '',
                          app.color === 'purple' ? 'bg-purple-500' : '',
                          app.color === 'emerald' ? 'bg-emerald-500' : '',
                          !app.color ? 'bg-gray-500' : ''
                        ]"
                      >
                        <svg 
                          v-if="app.icon === 'bone'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="white" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-bone w-5 h-5 text-blue-600"
                          aria-hidden="true"
                        >
                          <path d="M17 10c.7-.7 1.69 0 2.5 0a2.5 2.5 0 1 0 0-5 .5.5 0 0 1-.5-.5 2.5 2.5 0 1 0-5 0c0 .81.7 1.8 0 2.5l-7 7c-.7.7-1.69 0-2.5 0a2.5 2.5 0 0 0 0 5c.28 0 .5.22.5.5a2.5 2.5 0 1 0 5 0c0-.81-.7-1.8 0-2.5Z"></path>
                        </svg>
                        <svg 
                          v-else-if="app.icon === 'heart'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="white" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-heart w-5 h-5 text-rose-600"
                          aria-hidden="true"
                        >
                          <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"></path>
                        </svg>
                        <svg 
                          v-else-if="app.icon === 'brain'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="white" 
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
                          v-else-if="app.icon === 'shield'"
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="white" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-shield w-5 h-5 text-emerald-600"
                          aria-hidden="true"
                        >
                          <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        </svg>
                        <svg 
                          v-else
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="white" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          class="lucide lucide-circle"
                          aria-hidden="true"
                        >
                          <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                      </div>
                      <!-- Content -->
                      <h4 class="font-semibold text-slate-900 mb-2 flex items-center gap-2">{{ app.title || app }}</h4>
                      <p class="text-slate-700 text-sm leading-relaxed">{{ app.description }}</p>
                    </div>
                  </div>
    
                  <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mt-6">
                    <p class="text-blue-900 text-sm leading-relaxed">
                      <strong>Important Context:</strong>
                       These applications are potential – they represent what could be possible if BPC-157's effects in humans mirror those seen in animals. 
                       Realizing these uses will require rigorous clinical trials to confirm efficacy and safety for each specific condition. The range of benefits seen in preclinical research has few parallels, which is why BPC-157 is discussed with both excitement and cautious optimism in the medical research community.
                    </p>
                  </div>
                </section>

                <!-- Conclusion -->
                <section class="mb-12">
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
                  <p class="text-slate-700 leading-relaxed mb-4">
                    BPC-157 stands out as a compound at the frontier of medical research. From a research perspective, it demonstrates several noteworthy characteristics: it's small and stable, easy to administer in various forms, and has shown remarkable healing capabilities in preclinical models across the board – from mending torn tendons to healing ulcerated gastrointestinal tissues and protecting neural cells.
                  </p>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    However, a key message is caution. As of now, BPC-157 is not an approved treatment for any condition and remains in the realm of experimental research. The peptide is currently available only as a research tool, and any human use is essentially an off-label experiment. While current findings in the laboratory are inspiring, science demands evidence through well-designed human studies.
                  </p>
                  <p class="text-slate-700 leading-relaxed mb-4">
                    The current state of knowledge presents a paradox: enthusiastic reports and underground usage suggest it may help people recover from injuries, yet solid clinical data to fully back up those claims and ensure long-term safety is lacking. Until comprehensive human studies are completed, experts advise that BPC-157 should be confined to laboratories and carefully controlled trials.
                  </p>
                  <p class="text-slate-700 leading-relaxed">
                    Researchers around the world are actively studying this peptide, as evidenced by increasing publications and even patents, because its multi-faceted actions are unlike anything in current therapeutic arsenals. 
                    For those learning about BPC-157, the best course is to stay informed but cautious. With time and rigorous research, we will discover whether BPC-157 can safely fulfill its healing reputation and possibly become a mainstream therapeutic agent. Until then, it remains a compelling scientific discovery on the path from laboratory to clinical application.
                  </p>                
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
                      <a 
                        v-if="ref.links && ref.links.length > 0"
                        v-for="(link, linkIndex) in ref.links" 
                        :key="linkIndex"
                        :href="link.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-slate-600 hover:text-slate-900 flex items-center gap-1 mt-1"
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

defineProps({
  id: {
    type: [Number, String],
    required: true
  },
  name: {
    type: String,
    required: true
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
</script>
