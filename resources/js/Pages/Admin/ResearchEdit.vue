<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">{{ research ? 'Edit Research Paper' : 'Create New Research Paper' }}</h1>
      <p class="text-slate-500 mt-2">{{ research ? 'Update research paper details' : 'Add a new research paper' }}</p>
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
            <!-- Title Section -->
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Title *</label>
              <input v-model="form.title" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" required />
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Journal Type *</label>
              <select v-model="form.journal_type" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" required>
                <option :value="null">-- Select Journal Type --</option>
                <option value="Journal of Orthopedic Research">Journal of Orthopedic Research</option>
                <option value="Journal of Clinical Endocrinology">Journal of Clinical Endocrinology</option>
                <option value="Sexual Medicine Reviews">Sexual Medicine Reviews</option>
              </select>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Study Type *</label>
              <select v-model="form.study_type" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" required>
                <option :value="null">-- Select Study Type --</option>
                <option value="Clinical Research">Clinical Research</option>
              </select>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Study Summary *</label>
              <textarea v-model="form.study_summary" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Enter study summary" required></textarea>
            </div>
            
            <!-- Research Details Section -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Research Details</h2>
              
              <!-- Background Sub-section -->
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Background</label>
                <textarea v-model="form.background" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Enter background information"></textarea>
              </div>
              
              <!-- Key Findings Sub-section -->
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Key Findings</label>
                <div v-for="(finding, index) in form.key_findings" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.key_findings[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter key finding" />
                  <button type="button" @click="removeKeyFinding(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addKeyFinding" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Key Finding</button>
              </div>
              
              <!-- Methodology Sub-section -->
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Methodology</label>
                <textarea v-model="form.methodology" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Enter methodology"></textarea>
              </div>
              
              <!-- Clinical Implications Sub-section -->
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Clinical Implications</label>
                <textarea v-model="form.clinical_implications" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Enter clinical implications"></textarea>
              </div>
              
              <!-- Limitations Sub-section -->
              <div class="mb-6">
                <label class="block mb-1.5 font-semibold text-slate-800">Limitations</label>
                <textarea v-model="form.limitations" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Enter limitations"></textarea>
              </div>
            </div>
            
            <!-- Research Categories Section (Tags) -->
            <div class="border-t border-slate-200 pt-6">
              <h2 class="text-xl font-semibold text-slate-800 mb-4">Research Categories</h2>
              <div v-for="(tag, index) in form.tags" :key="index" class="flex gap-2 mb-2">
                <input v-model="form.tags[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter category/tag" />
                <button type="button" @click="removeTag(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
              </div>
              <button type="button" @click="addTag" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Category</button>
            </div>
          </div>
        </form>
      </div>
      
      <!-- Right Sidebar -->
      <div class="w-80 flex-shrink-0 space-y-6">
        <!-- Save Button -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex gap-3">
            <Link href="/admin/research" class="flex-1 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-center transition-all duration-200 font-medium">Cancel</Link>
            <button 
              @click="submit" 
              :disabled="form.processing" 
              class="flex-1 px-4 py-2.5 rounded-xl bg-yellow-500 text-white hover:bg-yellow-600 font-medium transition-all duration-200 shadow-sm"
            >
              {{ form.processing ? 'Saving...' : (research ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
        
        <!-- Sidebar Content -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-6">
          <div>
            <label class="block mb-2 font-semibold text-slate-800">Status *</label>
            <select v-model="form.status" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" required>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
            <p class="text-sm text-slate-500 mt-1" v-if="form.status === 'published'">
              Published date will be set automatically when you save
            </p>
          </div>
          
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">Peptide</label>
            <select v-model="form.peptide" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base">
              <option :value="null">-- Select Peptide --</option>
              <option v-for="category in peptides" :key="category.id" :value="category.name">{{ category.name }}</option>
            </select>
          </div>
          
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">Evidence Level</label>
            <select v-model="form.evidence_level" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base">
              <option :value="null">-- Select Evidence Level --</option>
              <option value="High">High</option>
              <option value="Medium">Medium</option>
              <option value="Low">Low</option>
            </select>
          </div>
          
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">PubMed URL</label>
            <input v-model="form.pubmed_url" type="url" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base" placeholder="https://pubmed.ncbi.nlm.nih.gov/..." />
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
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter page title for SEO"
                />
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">Description</label>
                <textarea
                  v-model="form.seo_description"
                  rows="3"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter meta description for SEO"
                ></textarea>
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">OG:Title</label>
                <input
                  v-model="form.seo_og_title"
                  type="text"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter Open Graph title"
                />
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">OG:Image</label>
                <input
                  v-model="form.seo_og_image"
                  type="url"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter Open Graph image URL"
                />
              </div>
              <div>
                <label class="block text-sm text-slate-700 mb-2">OG:Description</label>
                <textarea
                  v-model="form.seo_og_description"
                  rows="3"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-sans text-base"
                  placeholder="Enter Open Graph description"
                ></textarea>
              </div>
            </div>
          </div>
          
          <div v-if="research?.published_at" class="pt-4 border-t border-slate-100">
            <label class="block mb-1 font-medium text-sm text-slate-600">Published Date</label>
            <p class="text-sm text-slate-700">{{ research.published_at }}</p>
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
  research: Object,
  peptides: Array,
})

const page = usePage()

const form = useForm({
  title: props.research?.title || '',
  peptide: props.research?.peptide || null,
  evidence_level: props.research?.evidence_level || null,
  journal_type: props.research?.journal_type || null,
  study_type: props.research?.study_type || null,
  study_summary: props.research?.study_summary || '',
  background: props.research?.background || '',
  key_findings: props.research?.key_findings || [],
  methodology: props.research?.methodology || '',
  clinical_implications: props.research?.clinical_implications || '',
  limitations: props.research?.limitations || '',
  pubmed_url: props.research?.pubmed_url || '',
  tags: props.research?.tags || [],
  status: props.research?.status || 'draft',
  seo_page_title: props.research?.seo_page_title || '',
  seo_description: props.research?.seo_description || '',
  seo_og_title: props.research?.seo_og_title || '',
  seo_og_description: props.research?.seo_og_description || '',
  seo_og_image: props.research?.seo_og_image || '',
  _token: page.props.csrf_token,
})

const addKeyFinding = () => {
  form.key_findings.push('')
}

const removeKeyFinding = (index) => {
  form.key_findings.splice(index, 1)
}

const addTag = () => {
  form.tags.push('')
}

const removeTag = (index) => {
  form.tags.splice(index, 1)
}

const submit = () => {
  // Filter out empty Key Findings
  form.key_findings = form.key_findings.filter(finding => finding.trim() !== '')
  
  // Filter out empty Tags
  form.tags = form.tags.filter(tag => tag.trim() !== '')
  
  // Update CSRF token before submission
  form._token = page.props.csrf_token
  
  if (props.research) {
    form.post(`/admin/research/${props.research.id}`, {
      preserveScroll: true,
    })
  } else {
    form.post('/admin/research', {
      preserveScroll: true,
    })
  }
}
</script>
