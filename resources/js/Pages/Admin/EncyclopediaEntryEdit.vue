<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">{{ entry ? 'Edit Encyclopedia Entry' : 'Create New Encyclopedia Entry' }}</h1>
      <p class="text-slate-500 mt-2">{{ entry ? 'Update encyclopedia entry details' : 'Add a new encyclopedia entry' }}</p>
    </div>
    
    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
      {{ $page.props.flash.success }}
    </div>
    
    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
      {{ $page.props.flash.error }}
    </div>
    
    <div class="flex gap-6">
      <!-- Main Content Area -->
      <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <form @submit.prevent="submit">
          <!-- Error Message -->
          <div v-if="Object.keys(form.errors).length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
            <p class="font-medium">Please fix the following errors:</p>
            <ul class="list-disc list-inside mt-2">
              <li v-for="(error, field) in form.errors" :key="field" class="text-sm">
                {{ Array.isArray(error) ? error[0] : error }}
              </li>
            </ul>
          </div>
          
          <div class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Basic Information</h2>
              
              <div class="mb-4">
                <label class="block mb-1.5 font-semibold text-slate-800">Encyclopedia Tag *</label>
                <select v-model="form.education_tag" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" required>
                  <option :value="null">-- Select Tag --</option>
                  <option value="Healing & Recovery">Healing & Recovery</option>
                  <option value="Growth & Recovery">Growth & Recovery</option>
                  <option value="Performance">Performance</option>
                  <option value="Anti-Aging">Anti-Aging</option>
                </select>
                <p class="text-sm text-slate-500 mt-1">Select the category tag for this encyclopedia entry</p>
              </div>
              
              <div class="mb-4">
                <label class="block mb-1.5 font-semibold text-slate-800">Research URL (External Link)</label>
                <input v-model="form.research_url" type="url" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" placeholder="https://pubmed.ncbi.nlm.nih.gov/?term=bpc+157+sikiric" />
                <p class="text-sm text-slate-500 mt-1">External link to research (e.g., PubMed search URL). This will be used for the "View Research" link.</p>
              </div>
              
              <div class="mb-4">
                <label class="block mb-1.5 font-semibold text-slate-800">Peptide Full Name</label>
                <input v-model="form.peptide_full_name" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter full peptide name" />
              </div>
              
              <!-- Entry Tags -->
              <div class="mb-4">
                <label class="block mb-1.5 font-semibold text-slate-800">Entry Tags</label>
                <div v-for="(tag, index) in form.tags" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.tags[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter tag" />
                  <button type="button" @click="removeTag(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addTag" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Tag</button>
              </div>
            </div>
            
            <!-- Molecular Information -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Molecular Information</h2>
              
              <div class="space-y-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Formula:</label>
                  <input v-model="form.molecular_formula" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., C₆₂H₉₈N₁₆O₂₂" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Molecular Weight:</label>
                  <input v-model="form.molecular_weight" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., 1,419.53 g/mol" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">CAS Registry Number:</label>
                  <input v-model="form.cas_registry_number" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., 137525-51-0" />
                </div>
              </div>
            </div>
            
            <!-- Amino Acid Sequence -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Amino Acid Sequence</h2>
              <textarea v-model="form.amino_acid_sequence" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base mb-4" rows="3" placeholder="Enter amino acid sequence (e.g., Gly-Glu-Pro-Pro-Pro-Gly-Lys-Pro-Ala-Asp-Asp-Ala-Gly-Leu-Val)"></textarea>
              
              <div class="space-y-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Net Charge:</label>
                  <input v-model="form.amino_acid_net_charge" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., -2 (at pH 7)" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Hydrophobic:</label>
                  <input v-model="form.amino_acid_hydrophobic" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., 40%" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Stability:</label>
                  <input v-model="form.amino_acid_stability" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., High" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Solubility:</label>
                  <input v-model="form.amino_acid_solubility" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., Water soluble" />
                </div>
              </div>
            </div>
            
            <!-- Key Points -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Key Points</h2>
              <div v-for="(point, index) in form.key_points" :key="index" class="flex gap-2 mb-2">
                <input v-model="form.key_points[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter key point" />
                <button type="button" @click="removeKeyPoint(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
              </div>
              <button type="button" @click="addKeyPoint" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Key Point</button>
            </div>
            
            <!-- Overview -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Overview</h2>
              <textarea v-model="form.overview" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="6" placeholder="Enter overview information"></textarea>
            </div>
            
            <!-- Areas of Research -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Areas of Research</h2>
              
              <div class="mb-4">
                <label class="block mb-1.5 font-medium text-slate-700">Introduction</label>
                <textarea v-model="form.areas_of_research_intro" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="4" placeholder="Enter introduction to areas of research"></textarea>
              </div>
              
              <div v-for="(area, index) in form.areas_of_research" :key="index" class="mb-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="font-medium text-slate-700">Area {{ index + 1 }}</h4>
                  <button type="button" @click="removeAreaOfResearch(index)" class="px-3 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-sm font-medium">Remove</button>
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Area Name:</label>
                  <input v-model="form.areas_of_research[index].name" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter area name" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Description:</label>
                  <textarea v-model="form.areas_of_research[index].description" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" rows="3" placeholder="Enter description"></textarea>
                </div>
              </div>
              <button type="button" @click="addAreaOfResearch" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Area of Research</button>
            </div>
            
            <!-- Background -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Background</h2>
              <textarea v-model="form.background" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="6" placeholder="Enter background information"></textarea>
            </div>
            
            <!-- Mechanism of Action -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Mechanism of Action</h2>
              
              <div class="mb-4">
                <label class="block mb-1.5 font-medium text-slate-700">Introduction</label>
                <textarea v-model="form.mechanism_of_action_intro" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="4" placeholder="Enter introduction to mechanism of action"></textarea>
              </div>
              
              <div v-for="(subsection, index) in form.mechanism_subsections" :key="index" class="mb-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="font-medium text-slate-700">Subsection {{ index + 1 }}</h4>
                  <button type="button" @click="removeMechanismSubsection(index)" class="px-3 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-sm font-medium">Remove</button>
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Title:</label>
                  <input v-model="form.mechanism_subsections[index].title" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter subsection title" />
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Introduction:</label>
                  <textarea v-model="form.mechanism_subsections[index].intro" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" rows="2" placeholder="Enter subsection introduction"></textarea>
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Items:</label>
                  <div v-for="(item, itemIndex) in form.mechanism_subsections[index].items" :key="itemIndex" class="mb-3 p-3 border border-slate-200 rounded-xl bg-white">
                    <div class="flex justify-between items-center mb-2">
                      <h5 class="font-medium text-slate-700 text-sm">Item {{ itemIndex + 1 }}</h5>
                      <button type="button" @click="removeMechanismItem(index, itemIndex)" class="px-2 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-xs font-medium">Remove</button>
                    </div>
                    <div class="mb-2">
                      <label class="block mb-1 text-xs font-medium text-slate-600">Item:</label>
                      <input v-model="form.mechanism_subsections[index].items[itemIndex].item" type="text" class="w-full border border-slate-200 rounded-xl px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-sm" placeholder="Enter item" />
                    </div>
                    <div>
                      <label class="block mb-1 text-xs font-medium text-slate-600">Description:</label>
                      <textarea v-model="form.mechanism_subsections[index].items[itemIndex].description" class="w-full border border-slate-200 rounded-xl px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-sm" rows="2" placeholder="Enter description"></textarea>
                    </div>
                  </div>
                  <button type="button" @click="addMechanismItem(index)" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium text-sm">+ Add Item</button>
                </div>
              </div>
              <button type="button" @click="addMechanismSubsection" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Subsection</button>
            </div>
            
            <!-- Preclinical Research -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Preclinical Research and Findings</h2>
              
              <div class="mb-4">
                <label class="block mb-1.5 font-medium text-slate-700">Introduction</label>
                <textarea v-model="form.preclinical_intro" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="4" placeholder="Enter introduction to preclinical research"></textarea>
              </div>
              
              <div v-for="(subsection, index) in form.preclinical_subsections" :key="index" class="mb-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="font-medium text-slate-700">Subsection {{ index + 1 }}</h4>
                  <button type="button" @click="removePreclinicalSubsection(index)" class="px-3 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-sm font-medium">Remove</button>
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Title:</label>
                  <input v-model="form.preclinical_subsections[index].title" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter subsection title" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Findings:</label>
                  <div v-for="(finding, findingIndex) in form.preclinical_subsections[index].findings" :key="findingIndex" class="mb-3 p-3 border border-slate-200 rounded-xl bg-white">
                    <div class="flex justify-between items-center mb-2">
                      <h5 class="font-medium text-slate-700 text-sm">Finding {{ findingIndex + 1 }}</h5>
                      <button type="button" @click="removePreclinicalFinding(index, findingIndex)" class="px-2 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-xs font-medium">Remove</button>
                    </div>
                    <div class="mb-2">
                      <label class="block mb-1 text-xs font-medium text-slate-600">Title:</label>
                      <input v-model="form.preclinical_subsections[index].findings[findingIndex].title" type="text" class="w-full border border-slate-200 rounded-xl px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-sm" placeholder="Enter finding title" />
                    </div>
                    <div>
                      <label class="block mb-1 text-xs font-medium text-slate-600">Content:</label>
                      <textarea v-model="form.preclinical_subsections[index].findings[findingIndex].content" class="w-full border border-slate-200 rounded-xl px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-sm" rows="2" placeholder="Enter finding content"></textarea>
                    </div>
                  </div>
                  <button type="button" @click="addPreclinicalFinding(index)" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium text-sm">+ Add Finding</button>
                </div>
              </div>
              <button type="button" @click="addPreclinicalSubsection" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Subsection</button>
              
              <div class="mt-4">
                <label class="block mb-1.5 font-medium text-slate-700">Disclaimer</label>
                <textarea v-model="form.preclinical_disclaimer" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="3" placeholder="Enter disclaimer text"></textarea>
              </div>
            </div>
            
            <!-- Human Use & Evidence -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Human Use and Evidence</h2>
              
              <div class="mb-4">
                <label class="block mb-1.5 font-medium text-slate-700">Introduction</label>
                <textarea v-model="form.human_use_intro" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="4" placeholder="Enter introduction"></textarea>
              </div>
              
              <div v-for="(subsection, index) in form.human_use_subsections" :key="index" class="mb-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="font-medium text-slate-700">Subsection {{ index + 1 }}</h4>
                  <button type="button" @click="removeHumanUseSubsection(index)" class="px-3 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-sm font-medium">Remove</button>
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Title:</label>
                  <input v-model="form.human_use_subsections[index].title" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter subsection title" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Entries:</label>
                  <div v-for="(entry, entryIndex) in form.human_use_subsections[index].entries" :key="entryIndex" class="mb-2">
                    <div v-if="entry.type === 'item'" class="flex gap-2">
                      <input v-model="entry.value" type="text" class="flex-1 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter item" />
                      <button type="button" @click="removeHumanUseEntry(index, entryIndex)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium text-sm">Remove</button>
                    </div>
                    <div v-else class="flex gap-2">
                      <textarea v-model="entry.value" class="flex-1 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" rows="2" placeholder="Enter content"></textarea>
                      <button type="button" @click="removeHumanUseEntry(index, entryIndex)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium text-sm">Remove</button>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <button type="button" @click="addHumanUseItem(index)" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium text-sm">+ Add Item</button>
                    <button type="button" @click="addHumanUseContent(index)" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium text-sm">+ Add Content</button>
                  </div>
                </div>
              </div>
              <button type="button" @click="addHumanUseSubsection" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Subsection</button>
            </div>
            
            <!-- Regulatory Status -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Regulatory Status and Considerations</h2>
              
              <div v-for="(subsection, index) in form.regulatory_subsections" :key="index" class="mb-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="font-medium text-slate-700">Subsection {{ index + 1 }}</h4>
                  <button type="button" @click="removeRegulatorySubsection(index)" class="px-3 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-sm font-medium">Remove</button>
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Title:</label>
                  <input v-model="form.regulatory_subsections[index].title" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter subsection title" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Entries:</label>
                  <div v-for="(entry, entryIndex) in form.regulatory_subsections[index].entries" :key="entryIndex" class="mb-2">
                    <div v-if="entry.type === 'item'" class="flex gap-2">
                      <input v-model="entry.value" type="text" class="flex-1 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter item" />
                      <button type="button" @click="removeRegulatoryEntry(index, entryIndex)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium text-sm">Remove</button>
                    </div>
                    <div v-else class="flex gap-2">
                      <textarea v-model="entry.value" class="flex-1 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" rows="2" placeholder="Enter content"></textarea>
                      <button type="button" @click="removeRegulatoryEntry(index, entryIndex)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium text-sm">Remove</button>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <button type="button" @click="addRegulatoryItem(index)" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium text-sm">+ Add Item</button>
                    <button type="button" @click="addRegulatoryContent(index)" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium text-sm">+ Add Content</button>
                  </div>
                </div>
              </div>
              <button type="button" @click="addRegulatorySubsection" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Subsection</button>
              
              <div class="mt-4">
                <label class="block mb-1.5 font-medium text-slate-700">Important Note</label>
                <textarea v-model="form.regulatory_important_note" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="3" placeholder="Enter important note"></textarea>
              </div>
            </div>
            
            <!-- Potential Applications -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Potential Therapeutic Applications</h2>
              
              <div class="mb-4">
                <label class="block mb-1.5 font-medium text-slate-700">Introduction</label>
                <textarea v-model="form.potential_applications_intro" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="4" placeholder="Enter introduction"></textarea>
              </div>
              
              <div v-for="(app, index) in form.potential_applications" :key="index" class="mb-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="font-medium text-slate-700">Application {{ index + 1 }}</h4>
                  <button type="button" @click="removePotentialApplication(index)" class="px-3 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-sm font-medium">Remove</button>
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Title:</label>
                  <input v-model="form.potential_applications[index].title" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter application title" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Description:</label>
                  <textarea v-model="form.potential_applications[index].description" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" rows="3" placeholder="Enter description"></textarea>
                </div>
              </div>
              <button type="button" @click="addPotentialApplication" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Application</button>
              
              <div class="mt-4">
                <label class="block mb-1.5 font-medium text-slate-700">Important Context</label>
                <textarea v-model="form.potential_applications_important_context" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="4" placeholder="Enter important context information"></textarea>
              </div>
            </div>
            
            <!-- Conclusion -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Conclusion</h2>
              <textarea v-model="form.conclusion" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" rows="6" placeholder="Enter conclusion"></textarea>
            </div>
            
            <!-- References -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">References</h2>
              <div v-for="(ref, index) in form.references" :key="index" class="mb-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="font-medium text-slate-700">Reference {{ index + 1 }}</h4>
                  <button type="button" @click="removeReference(index)" class="px-3 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-sm font-medium">Remove</button>
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Authors:</label>
                  <input v-model="form.references[index].authors" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter authors" />
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Title:</label>
                  <input v-model="form.references[index].title" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter title" />
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Description:</label>
                  <textarea v-model="form.references[index].description" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" rows="2" placeholder="Enter description"></textarea>
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Citation:</label>
                  <input v-model="form.references[index].citation" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter citation" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Links:</label>
                  <div v-for="(link, linkIndex) in form.references[index].links" :key="linkIndex" class="flex gap-2 mb-2">
                    <input v-model="form.references[index].links[linkIndex].url" type="text" class="flex-1 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="URL" />
                    <input v-model="form.references[index].links[linkIndex].label" type="text" class="w-32 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Label" />
                    <button type="button" @click="removeReferenceLink(index, linkIndex)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium text-sm">Remove</button>
                  </div>
                  <button type="button" @click="addReferenceLink(index)" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium text-sm">+ Add Link</button>
                </div>
              </div>
              <button type="button" @click="addReference" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Reference</button>
            </div>
          </div>
        </form>
      </div>
      
      <!-- Right Sidebar -->
      <div class="w-80 flex-shrink-0 space-y-6">
        <!-- Save Button -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex gap-3">
            <Link href="/admin/encyclopedia-entries" class="flex-1 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-center transition-all duration-200 font-medium">Cancel</Link>
            <button 
              @click="submit" 
              :disabled="form.processing" 
              class="flex-1 px-4 py-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 font-medium transition-all duration-200 shadow-sm"
            >
              {{ form.processing ? 'Saving...' : (entry ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
        
        <!-- Sidebar Content -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-6">
          <div>
            <label class="block mb-2 font-semibold text-slate-800">Category *</label>
            <select v-model="form.product_category_id" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" required>
              <option :value="null">-- Select Category --</option>
              <option 
                v-for="category in categories" 
                :key="category.id" 
                :value="category.id"
              >
                {{ category.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block mb-2 font-semibold text-slate-800">Status *</label>
            <select v-model="form.status" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all font-sans text-base" required>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
            <p class="text-sm text-slate-500 mt-1" v-if="form.status === 'published'">
              Published date will be set automatically when you save
            </p>
          </div>
          
          <div v-if="entry?.published_at" class="pt-4 border-t border-slate-100">
            <label class="block mb-1 font-medium text-sm text-slate-600">Published Date</label>
            <p class="text-sm text-slate-700">{{ entry.published_at }}</p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  entry: Object,
  categories: {
    type: Array,
    default: () => []
  }
})

const page = usePage()

// Helper function to normalize mechanism items (handle legacy string format)
const normalizeMechanismItems = (items) => {
  if (!items || !Array.isArray(items)) return []
  return items.map(item => {
    if (typeof item === 'string') {
      return { item: item, description: '' }
    }
    return { item: item.item || '', description: item.description || '' }
  })
}

// Helper function to normalize mechanism subsections
const normalizeMechanismSubsections = (subsections) => {
  if (!subsections || !Array.isArray(subsections)) return []
  return subsections.map(sub => ({
    ...sub,
    items: normalizeMechanismItems(sub.items)
  }))
}

// Helper function to normalize human use and regulatory subsections (combine items/contents into entries)
const normalizeSubsections = (subsections) => {
  if (!subsections || !Array.isArray(subsections)) return []
  return subsections.map(sub => {
    const entries = []
    
    // If entries already exist in the new format, use them
    if (sub.entries && Array.isArray(sub.entries)) {
      sub.entries.forEach(entry => {
        if (entry && (entry.value || entry.value === '')) {
          entries.push({
            type: entry.type || 'item',
            value: entry.value || ''
          })
        }
      })
    }
    
    // Convert old items array to entries
    if (sub.items && Array.isArray(sub.items)) {
      sub.items.forEach(item => {
        if (item && (typeof item === 'string' ? item.trim() !== '' : true)) {
          entries.push({ type: 'item', value: typeof item === 'string' ? item : (item.value || '') })
        }
      })
    }
    
    // Convert old contents array to entries
    if (sub.contents && Array.isArray(sub.contents)) {
      sub.contents.forEach(content => {
        if (content && (typeof content === 'string' ? content.trim() !== '' : true)) {
          entries.push({ type: 'content', value: typeof content === 'string' ? content : (content.value || '') })
        }
      })
    }
    
    // If there's old content field, add it as a content entry
    if (sub.content && typeof sub.content === 'string' && sub.content.trim() !== '') {
      entries.push({ type: 'content', value: sub.content })
    }
    
    return {
      title: sub.title || '',
      entries: entries
    }
  })
}

const form = useForm({
  title: props.entry?.title || '',
  education_tag: props.entry?.education_tag || null,
  research_url: props.entry?.research_url || '',
  peptide_full_name: props.entry?.peptide_full_name || '',
  tags: props.entry?.tags || [],
  molecular_formula: props.entry?.molecular_formula || '',
  molecular_weight: props.entry?.molecular_weight || '',
  cas_registry_number: props.entry?.cas_registry_number || '',
  amino_acid_sequence: props.entry?.amino_acid_sequence || '',
  amino_acid_net_charge: props.entry?.amino_acid_net_charge || '',
  amino_acid_hydrophobic: props.entry?.amino_acid_hydrophobic || '',
  amino_acid_stability: props.entry?.amino_acid_stability || '',
  amino_acid_solubility: props.entry?.amino_acid_solubility || '',
  key_points: props.entry?.key_points || [],
  overview: props.entry?.overview || '',
  areas_of_research_intro: props.entry?.areas_of_research_intro || '',
  areas_of_research: props.entry?.areas_of_research || [],
  background: props.entry?.background || '',
  mechanism_of_action_intro: props.entry?.mechanism_of_action_intro || '',
  mechanism_subsections: normalizeMechanismSubsections(props.entry?.mechanism_subsections),
  preclinical_intro: props.entry?.preclinical_intro || '',
  preclinical_subsections: props.entry?.preclinical_subsections || [],
  preclinical_disclaimer: props.entry?.preclinical_disclaimer || '',
  human_use_intro: props.entry?.human_use_intro || '',
  human_use_subsections: normalizeSubsections(props.entry?.human_use_subsections),
  regulatory_subsections: normalizeSubsections(props.entry?.regulatory_subsections),
  regulatory_important_note: props.entry?.regulatory_important_note || '',
  potential_applications_intro: props.entry?.potential_applications_intro || '',
  potential_applications: props.entry?.potential_applications || [],
  potential_applications_important_context: props.entry?.potential_applications_important_context || '',
  conclusion: props.entry?.conclusion || '',
  references: props.entry?.references || [],
  product_category_id: props.entry?.product_category_id || null,
  status: props.entry?.status || 'draft',
  _token: page.props.csrf_token,
})

// Tags
const addTag = () => {
  form.tags.push('')
}

const removeTag = (index) => {
  form.tags.splice(index, 1)
}

// Key Points
const addKeyPoint = () => {
  form.key_points.push('')
}

const removeKeyPoint = (index) => {
  form.key_points.splice(index, 1)
}

// Areas of Research
const addAreaOfResearch = () => {
  form.areas_of_research.push({
    name: '',
    description: ''
  })
}

const removeAreaOfResearch = (index) => {
  form.areas_of_research.splice(index, 1)
}

// Mechanism of Action
const addMechanismSubsection = () => {
  form.mechanism_subsections.push({
    title: '',
    intro: '',
    items: []
  })
}

const removeMechanismSubsection = (index) => {
  form.mechanism_subsections.splice(index, 1)
}

const addMechanismItem = (subsectionIndex) => {
  form.mechanism_subsections[subsectionIndex].items.push({
    item: '',
    description: ''
  })
}

const removeMechanismItem = (subsectionIndex, itemIndex) => {
  form.mechanism_subsections[subsectionIndex].items.splice(itemIndex, 1)
}

// Preclinical Research
const addPreclinicalSubsection = () => {
  form.preclinical_subsections.push({
    title: '',
    findings: []
  })
}

const removePreclinicalSubsection = (index) => {
  form.preclinical_subsections.splice(index, 1)
}

const addPreclinicalFinding = (subsectionIndex) => {
  form.preclinical_subsections[subsectionIndex].findings.push({
    title: '',
    content: ''
  })
}

const removePreclinicalFinding = (subsectionIndex, findingIndex) => {
  form.preclinical_subsections[subsectionIndex].findings.splice(findingIndex, 1)
}

// Human Use & Evidence
const addHumanUseSubsection = () => {
  form.human_use_subsections.push({
    title: '',
    entries: []
  })
}

const removeHumanUseSubsection = (index) => {
  form.human_use_subsections.splice(index, 1)
}

const addHumanUseItem = (subsectionIndex) => {
  if (!form.human_use_subsections[subsectionIndex].entries) {
    form.human_use_subsections[subsectionIndex].entries = []
  }
  form.human_use_subsections[subsectionIndex].entries.push({
    type: 'item',
    value: ''
  })
}

const addHumanUseContent = (subsectionIndex) => {
  if (!form.human_use_subsections[subsectionIndex].entries) {
    form.human_use_subsections[subsectionIndex].entries = []
  }
  form.human_use_subsections[subsectionIndex].entries.push({
    type: 'content',
    value: ''
  })
}

const removeHumanUseEntry = (subsectionIndex, entryIndex) => {
  form.human_use_subsections[subsectionIndex].entries.splice(entryIndex, 1)
}

// Regulatory Status
const addRegulatorySubsection = () => {
  form.regulatory_subsections.push({
    title: '',
    entries: []
  })
}

const removeRegulatorySubsection = (index) => {
  form.regulatory_subsections.splice(index, 1)
}

const addRegulatoryItem = (subsectionIndex) => {
  if (!form.regulatory_subsections[subsectionIndex].entries) {
    form.regulatory_subsections[subsectionIndex].entries = []
  }
  form.regulatory_subsections[subsectionIndex].entries.push({
    type: 'item',
    value: ''
  })
}

const addRegulatoryContent = (subsectionIndex) => {
  if (!form.regulatory_subsections[subsectionIndex].entries) {
    form.regulatory_subsections[subsectionIndex].entries = []
  }
  form.regulatory_subsections[subsectionIndex].entries.push({
    type: 'content',
    value: ''
  })
}

const removeRegulatoryEntry = (subsectionIndex, entryIndex) => {
  form.regulatory_subsections[subsectionIndex].entries.splice(entryIndex, 1)
}

// Potential Applications
const addPotentialApplication = () => {
  form.potential_applications.push({
    title: '',
    description: ''
  })
}

const removePotentialApplication = (index) => {
  form.potential_applications.splice(index, 1)
}

// References
const addReference = () => {
  form.references.push({
    authors: '',
    title: '',
    description: '',
    citation: '',
    links: []
  })
}

const removeReference = (index) => {
  form.references.splice(index, 1)
}

const addReferenceLink = (referenceIndex) => {
  form.references[referenceIndex].links.push({
    url: '',
    label: ''
  })
}

const removeReferenceLink = (referenceIndex, linkIndex) => {
  form.references[referenceIndex].links.splice(linkIndex, 1)
}

const submit = () => {
  // Auto-generate title if title is empty
  if (!form.title || form.title.trim() === '') {
    form.title = 'Untitled Entry'
  }
  
  // Filter out empty arrays and objects
  form.tags = form.tags.filter(tag => tag.trim() !== '')
  form.key_points = form.key_points.filter(point => point.trim() !== '')
  form.areas_of_research = form.areas_of_research.filter(area => area.name?.trim() !== '' || area.description?.trim() !== '')
  form.mechanism_subsections = form.mechanism_subsections.filter(sub => sub.title?.trim() !== '' || sub.intro?.trim() !== '' || (sub.items && sub.items.length > 0))
  form.mechanism_subsections.forEach(sub => {
    if (sub.items) {
      sub.items = sub.items.filter(item => item.item?.trim() !== '' || item.description?.trim() !== '')
    }
  })
  form.preclinical_subsections = form.preclinical_subsections.filter(sub => sub.title?.trim() !== '' || (sub.findings && sub.findings.length > 0))
  form.preclinical_subsections.forEach(sub => {
    if (sub.findings) {
      sub.findings = sub.findings.filter(f => f.title?.trim() !== '' || f.content?.trim() !== '')
    }
  })
  // Ensure entries array exists for each subsection
  form.human_use_subsections.forEach(sub => {
    if (!sub.entries || !Array.isArray(sub.entries)) {
      sub.entries = []
    }
  })
  form.human_use_subsections = form.human_use_subsections.filter(sub => sub.title?.trim() !== '' || (sub.entries && sub.entries.length > 0))
  form.human_use_subsections.forEach(sub => {
    if (sub.entries && Array.isArray(sub.entries)) {
      sub.entries = sub.entries.filter(entry => entry && entry.value && entry.value.trim() !== '')
    } else {
      sub.entries = []
    }
  })
  // Ensure entries array exists for each subsection
  form.regulatory_subsections.forEach(sub => {
    if (!sub.entries || !Array.isArray(sub.entries)) {
      sub.entries = []
    }
  })
  form.regulatory_subsections = form.regulatory_subsections.filter(sub => sub.title?.trim() !== '' || (sub.entries && sub.entries.length > 0))
  form.regulatory_subsections.forEach(sub => {
    if (sub.entries && Array.isArray(sub.entries)) {
      sub.entries = sub.entries.filter(entry => entry && entry.value && entry.value.trim() !== '')
    } else {
      sub.entries = []
    }
  })
  form.potential_applications = form.potential_applications.filter(app => app.title?.trim() !== '' || app.description?.trim() !== '')
  form.references = form.references.filter(ref => ref.authors?.trim() !== '' || ref.title?.trim() !== '' || ref.description?.trim() !== '' || ref.citation?.trim() !== '')
  form.references.forEach(ref => {
    if (ref.links) {
      ref.links = ref.links.filter(link => link.url?.trim() !== '' || link.label?.trim() !== '')
    }
  })
  
  // Ensure all subsections have entries arrays (even if empty)
  form.human_use_subsections.forEach(sub => {
    if (!sub.entries || !Array.isArray(sub.entries)) {
      sub.entries = []
    }
    // Ensure each entry has type and value
    sub.entries = sub.entries.map(entry => ({
      type: entry.type || 'item',
      value: entry.value || ''
    }))
  })
  
  form.regulatory_subsections.forEach(sub => {
    if (!sub.entries || !Array.isArray(sub.entries)) {
      sub.entries = []
    }
    // Ensure each entry has type and value
    sub.entries = sub.entries.map(entry => ({
      type: entry.type || 'item',
      value: entry.value || ''
    }))
  })
  
  // Update CSRF token before submission
  form._token = page.props.csrf_token
  
  if (props.entry) {
    form.post(`/admin/encyclopedia-entries/${props.entry.id}`, {
      preserveScroll: true,
    })
  } else {
    form.post('/admin/encyclopedia-entries', {
      preserveScroll: true,
    })
  }
}
</script>
