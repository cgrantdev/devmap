<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">{{ guide ? 'Edit Educational Guide' : 'Create New Educational Guide' }}</h1>
      <p class="text-slate-500 mt-2">{{ guide ? 'Update educational guide details' : 'Add a new educational guide' }}</p>
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
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Title *</label>
              <input v-model="form.title" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" required />
              <p class="text-sm text-slate-500 mt-1">Slug will be automatically generated from title</p>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Guide Type *</label>
              <select v-model="form.guide_type" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" required>
                <option :value="null">-- Select Guide Type --</option>
                <option value="Beginner">Beginner</option>
                <option value="Stacking">Stacking</option>
                <option value="Dosage">Dosage</option>
                <option value="Advanced">Advanced</option>
              </select>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Outline</label>
              <textarea v-model="form.outline" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" rows="3" placeholder="Enter guide outline"></textarea>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Introduction</label>
              <textarea v-model="form.introduction" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Enter introduction"></textarea>
            </div>
            
            <!-- Overview & Benefits Section -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Overview & Benefits</h2>
              <div v-for="(benefit, index) in form.overview_benefits" :key="index" class="mb-4 p-4 border border-slate-200 rounded-xl">
                <div class="flex gap-2 mb-2">
                  <input v-model="benefit.title" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter benefit title" />
                  <button type="button" @click="removeOverviewBenefit(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <textarea v-model="benefit.description" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" rows="3" placeholder="Enter benefit description"></textarea>
              </div>
              <button type="button" @click="addOverviewBenefit" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Benefit</button>
            </div>
            
            <!-- Usage Guidelines Section -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Usage Guidelines</h2>
              
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Important Note</label>
                <textarea v-model="form.usage_guidelines_important_note" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" rows="4" placeholder="Enter important note"></textarea>
              </div>
              
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Dosage Recommendation</label>
                <div v-for="(recommendation, index) in form.dosage_recommendation" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.dosage_recommendation[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter dosage recommendation" />
                  <button type="button" @click="removeDosageRecommendation(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addDosageRecommendation" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Dosage Recommendation</button>
              </div>
              
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Administration Methods</label>
                <div v-for="(method, index) in form.administration_methods" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.administration_methods[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter administration method" />
                  <button type="button" @click="removeAdministrationMethod(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addAdministrationMethod" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Administration Method</button>
              </div>
              
              <div>
                <label class="block mb-1.5 font-semibold text-slate-800">Timing & Frequency</label>
                <textarea v-model="form.timing_frequency" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" rows="4" placeholder="Enter timing and frequency information"></textarea>
              </div>
            </div>
            
            <!-- Safety & Precautions Section -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Safety & Precautions</h2>
              
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Safety First</label>
                <textarea v-model="form.safety_first" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" rows="4" placeholder="Enter safety information"></textarea>
              </div>
              
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Common Side Effects</label>
                <div v-for="(effect, index) in form.common_side_effects" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.common_side_effects[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter side effect" />
                  <button type="button" @click="removeCommonSideEffect(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addCommonSideEffect" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Side Effect</button>
              </div>
              
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Contraindications</label>
                <div v-for="(contraindication, index) in form.contraindications" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.contraindications[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter contraindication" />
                  <button type="button" @click="removeContraindication(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addContraindication" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Contraindication</button>
              </div>
              
              <div>
                <label class="block mb-1.5 font-semibold text-slate-800">Storage & Handling</label>
                <div v-for="(item, index) in form.storage_handling" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.storage_handling[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter storage/handling instruction" />
                  <button type="button" @click="removeStorageHandling(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addStorageHandling" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Storage/Handling Instruction</button>
              </div>
            </div>
            
            <!-- Best Practices Section -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Best Practices</h2>
              
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Do's</label>
                <div v-for="(doItem, index) in form.dos" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.dos[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter do's item" />
                  <button type="button" @click="removeDo(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addDo" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Do's Item</button>
              </div>
              
              <div>
                <label class="block mb-1.5 font-semibold text-slate-800">Don'ts</label>
                <div v-for="(dontItem, index) in form.donts" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.donts[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter don'ts item" />
                  <button type="button" @click="removeDont(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addDont" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Don'ts Item</button>
              </div>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Conclusion</label>
              <textarea v-model="form.conclusion" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Enter conclusion"></textarea>
            </div>
            
            <!-- Covered Peptides Section -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Covered Peptides</h2>
              <div v-for="(peptide, index) in form.peptides" :key="index" class="flex gap-2 mb-2">
                <input v-model="form.peptides[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter peptide name" />
                <button type="button" @click="removePeptide(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
              </div>
              <button type="button" @click="addPeptide" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Peptide</button>
            </div>
          </div>
        </form>
      </div>
      
      <!-- Right Sidebar -->
      <div class="w-80 flex-shrink-0 space-y-6">
        <!-- Save Button -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex gap-3">
            <Link href="/admin/educational-guides" class="flex-1 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-center transition-all duration-200 font-medium">Cancel</Link>
            <button 
              @click="submit" 
              :disabled="form.processing" 
              class="flex-1 px-4 py-2.5 rounded-xl bg-orange-500 text-white hover:bg-orange-600 font-medium transition-all duration-200 shadow-sm"
            >
              {{ form.processing ? 'Saving...' : (guide ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
        
        <!-- Sidebar Content -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-6">
          <div>
            <label class="block mb-2 font-semibold text-slate-800">Status *</label>
            <select v-model="form.status" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" required>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
            <p class="text-sm text-slate-500 mt-1" v-if="form.status === 'published'">
              Published date will be set automatically when you save
            </p>
          </div>
          
          <div>
            <label class="flex items-center cursor-pointer">
              <input v-model="form.is_featured" type="checkbox" class="mr-2 w-4 h-4 text-orange-500 border-slate-300 rounded focus:ring-orange-500 focus:ring-2" />
              <span class="text-slate-700">Featured Guide</span>
            </label>
          </div>
          
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">Reading Time</label>
            <input v-model="form.reading_time" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base" placeholder="e.g., 19 Min Read" />
          </div>
          
          <!-- SEO Data Section -->
          <div class="border-t border-slate-200 pt-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">SEO Data</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm text-slate-700 mb-2">Page Title</label>
                <input
                  v-model="form.seo_page_title"
                  type="text"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter page title for SEO"
                />
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">Description</label>
                <textarea
                  v-model="form.seo_description"
                  rows="3"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter meta description for SEO"
                ></textarea>
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">OG:Title</label>
                <input
                  v-model="form.seo_og_title"
                  type="text"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter Open Graph title"
                />
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">OG:Image</label>
                <input
                  v-model="form.seo_og_image"
                  type="url"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter Open Graph image URL"
                />
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">OG:Description</label>
                <textarea
                  v-model="form.seo_og_description"
                  rows="3"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter Open Graph description"
                ></textarea>
              </div>
            </div>
          </div>
          
          <div v-if="guide?.published_at" class="pt-4 border-t border-slate-100">
            <label class="block mb-1 font-medium text-sm text-slate-600">Published Date</label>
            <p class="text-sm text-slate-700">{{ guide.published_at }}</p>
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
  guide: Object,
})

const page = usePage()

// Initialize form with default values
const form = useForm({
  title: props.guide?.title || '',
  guide_type: props.guide?.guide_type || null,
  outline: props.guide?.outline || '',
  introduction: props.guide?.introduction || '',
  overview_benefits: props.guide?.overview_benefits || [],
  usage_guidelines_important_note: props.guide?.usage_guidelines_important_note || '',
  dosage_recommendation: props.guide?.dosage_recommendation || [],
  administration_methods: props.guide?.administration_methods || [],
  timing_frequency: props.guide?.timing_frequency || '',
  safety_first: props.guide?.safety_first || '',
  common_side_effects: props.guide?.common_side_effects || [],
  contraindications: props.guide?.contraindications || [],
  storage_handling: props.guide?.storage_handling || [],
  dos: props.guide?.dos || [],
  donts: props.guide?.donts || [],
  conclusion: props.guide?.conclusion || '',
  reading_time: props.guide?.reading_time || '',
  peptides: props.guide?.peptides || [],
  status: props.guide?.status || 'draft',
  is_featured: props.guide?.is_featured || false,
  seo_page_title: props.guide?.seo_page_title || '',
  seo_description: props.guide?.seo_description || '',
  seo_og_title: props.guide?.seo_og_title || '',
  seo_og_description: props.guide?.seo_og_description || '',
  seo_og_image: props.guide?.seo_og_image || '',
  _token: page.props.csrf_token,
})

// Overview & Benefits functions
const addOverviewBenefit = () => {
  form.overview_benefits.push({ title: '', description: '' })
}

const removeOverviewBenefit = (index) => {
  form.overview_benefits.splice(index, 1)
}

// Dosage Recommendation functions
const addDosageRecommendation = () => {
  form.dosage_recommendation.push('')
}

const removeDosageRecommendation = (index) => {
  form.dosage_recommendation.splice(index, 1)
}

// Administration Methods functions
const addAdministrationMethod = () => {
  form.administration_methods.push('')
}

const removeAdministrationMethod = (index) => {
  form.administration_methods.splice(index, 1)
}

// Common Side Effects functions
const addCommonSideEffect = () => {
  form.common_side_effects.push('')
}

const removeCommonSideEffect = (index) => {
  form.common_side_effects.splice(index, 1)
}

// Contraindications functions
const addContraindication = () => {
  form.contraindications.push('')
}

const removeContraindication = (index) => {
  form.contraindications.splice(index, 1)
}

// Storage & Handling functions
const addStorageHandling = () => {
  form.storage_handling.push('')
}

const removeStorageHandling = (index) => {
  form.storage_handling.splice(index, 1)
}

// Do's functions
const addDo = () => {
  form.dos.push('')
}

const removeDo = (index) => {
  form.dos.splice(index, 1)
}

// Don'ts functions
const addDont = () => {
  form.donts.push('')
}

const removeDont = (index) => {
  form.donts.splice(index, 1)
}

// Peptides functions
const addPeptide = () => {
  form.peptides.push('')
}

const removePeptide = (index) => {
  form.peptides.splice(index, 1)
}

const submit = () => {
  // Filter out empty items from arrays
  form.overview_benefits = form.overview_benefits.filter(benefit => benefit.title?.trim() || benefit.description?.trim())
  form.dosage_recommendation = form.dosage_recommendation.filter(item => item.trim() !== '')
  form.administration_methods = form.administration_methods.filter(item => item.trim() !== '')
  form.common_side_effects = form.common_side_effects.filter(item => item.trim() !== '')
  form.contraindications = form.contraindications.filter(item => item.trim() !== '')
  form.storage_handling = form.storage_handling.filter(item => item.trim() !== '')
  form.dos = form.dos.filter(item => item.trim() !== '')
  form.donts = form.donts.filter(item => item.trim() !== '')
  form.peptides = form.peptides.filter(item => item.trim() !== '')
  
  // Update CSRF token before submission
  form._token = page.props.csrf_token
  
  if (props.guide) {
    form.post(`/admin/educational-guides/${props.guide.id}`, {
      preserveScroll: true,
    })
  } else {
    form.post('/admin/educational-guides', {
      preserveScroll: true,
    })
  }
}
</script>
