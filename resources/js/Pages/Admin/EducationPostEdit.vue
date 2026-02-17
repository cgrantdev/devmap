<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">{{ post ? 'Edit Education Post' : 'Create New Education Post' }}</h1>
      <p class="text-slate-500 mt-2">{{ post ? 'Update education post details' : 'Add a new education post' }}</p>
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
              <input v-model="form.title" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required />
              <p class="text-sm text-slate-500 mt-1">Slug will be automatically generated from title</p>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Education Tag *</label>
              <select v-model="form.education_tag" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required>
                <option :value="null">-- Select Education Tag --</option>
                <option value="Healing & Recovery">Healing & Recovery</option>
                <option value="Growth & Recovery">Growth & Recovery</option>
                <option value="Performance">Performance</option>
                <option value="Anti-Aging">Anti-Aging</option>
              </select>
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Peptide Full Name</label>
              <input v-model="form.peptide_full_name" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter full peptide name" />
            </div>
            
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Description</label>
              <textarea v-model="form.description" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="3"></textarea>
            </div>
            
            <!-- Quick Facts -->
            <div>
              <label class="block mb-3 font-semibold text-slate-800">Quick Facts</label>
              <div class="space-y-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Half-Life:</label>
                  <input v-model="form.half_life" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., 2-4 hours" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Bioavailability:</label>
                  <input v-model="form.bioavailability" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter bioavailability information" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Storage:</label>
                  <input v-model="form.storage" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., Store at 2-8°C" />
                </div>
              </div>
            </div>
            
            <!-- Research Applications -->
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Research Applications</label>
              <div v-for="(effect, index) in form.key_effects" :key="index" class="flex gap-2 mb-2">
                <input v-model="form.key_effects[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter benefit" />
                <button type="button" @click="removeKeyEffect(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
              </div>
              <button type="button" @click="addKeyEffect" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Application</button>
            </div>
            
            <!-- Common Use Cases -->
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Common Use Cases</label>
              <div v-for="(useCase, index) in form.common_use_cases" :key="index" class="flex gap-2 mb-2">
                <input v-model="form.common_use_cases[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter use case" />
                <button type="button" @click="removeCommonUseCase(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
              </div>
              <button type="button" @click="addCommonUseCase" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Use Case</button>
            </div>
            
            <!-- How it works -->
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">How it works</label>
              <textarea v-model="form.how_it_works" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="5" placeholder="Describe how this peptide works..."></textarea>
            </div>
            
            <!-- Dosage & Administration -->
            <div>
              <label class="block mb-3 font-semibold text-slate-800">Dosage & Administration</label>
              <div class="space-y-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Typical Dosage:</label>
                  <input v-model="form.typical_dosage" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., 200-300 mcg" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Frequency:</label>
                  <input v-model="form.frequency" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., Once daily" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Administration:</label>
                  <input v-model="form.administration" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., Subcutaneous injection" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Cycle Duration:</label>
                  <input v-model="form.cycle_duration" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="e.g., 8-12 weeks" />
                </div>
              </div>
            </div>
            
            <!-- Safety Information -->
            <div>
              <label class="block mb-3 font-semibold text-slate-800">Safety Information</label>
              
              <!-- Possible Side Effects -->
              <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-slate-700">Possible Side Effects</label>
                <div v-for="(effect, index) in form.possible_side_effects" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.possible_side_effects[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter possible side effect" />
                  <button type="button" @click="removePossibleSideEffect(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addPossibleSideEffect" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Side Effect</button>
              </div>
              
              <!-- Contraindications -->
              <div>
                <label class="block mb-2 text-sm font-medium text-slate-700">Contraindications</label>
                <div v-for="(contraindication, index) in form.contraindications" :key="index" class="flex gap-2 mb-2">
                  <input v-model="form.contraindications[index]" type="text" class="flex-1 border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" placeholder="Enter contraindication" />
                  <button type="button" @click="removeContraindication(index)" class="px-3 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 font-medium">Remove</button>
                </div>
                <button type="button" @click="addContraindication" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add Contraindication</button>
              </div>
            </div>
            
            <!-- Stacking Recommendations -->
            <div>
              <label class="block mb-1.5 font-semibold text-slate-800">Stacking Recommendations</label>
              <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input 
                    type="checkbox" 
                    :checked="form.stacking_recommendations.includes(null)"
                    @change="handleStackingNoneChange"
                    class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                  />
                  <span class="text-sm font-medium text-slate-700">None</span>
                </label>
                <select 
                  v-model="selectedStackingCategories" 
                  multiple
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base min-h-[120px]"
                  :disabled="form.stacking_recommendations.includes(null)"
                >
                  <option 
                    v-for="category in availableStackingCategories" 
                    :key="category.id" 
                    :value="Number(category.id)"
                  >
                    {{ category.name }}
                  </option>
                </select>
                <div v-if="selectedStackingCategories.length > 0" class="flex flex-wrap gap-2 mt-2">
                  <span 
                    v-for="categoryId in selectedStackingCategories" 
                    :key="categoryId"
                    class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-1 rounded text-sm border border-blue-200"
                  >
                    {{ getCategoryName(categoryId) }}
                    <button 
                      type="button"
                      @click="removeStackingCategory(categoryId)"
                      class="text-blue-600 hover:text-blue-800"
                    >
                    </button>
                  </span>
                </div>
              </div>
              <p class="text-sm text-slate-500 mt-1">Select categories that work well when stacked with this peptide (hold Ctrl/Cmd to select multiple)</p>
            </div>
            
            <!-- Frequently Asked Questions -->
            <div>
              <label class="block mb-3 font-semibold text-slate-800">Frequently Asked Questions</label>
              <div v-for="(faq, index) in form.faqs" :key="index" class="mb-4 p-4 border border-slate-100 rounded-xl bg-slate-50">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="font-medium text-slate-700">FAQ {{ index + 1 }}</h4>
                  <button type="button" @click="removeFAQ(index)" class="px-3 py-1 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 text-sm font-medium">Remove</button>
                </div>
                <div class="mb-3">
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Question:</label>
                  <input v-model="form.faqs[index].question" type="text" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base bg-white" placeholder="Enter question" />
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-slate-700">Answer:</label>
                  <textarea v-model="form.faqs[index].answer" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base bg-white" rows="3" placeholder="Enter answer"></textarea>
                </div>
              </div>
              <button type="button" @click="addFAQ" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all duration-200 font-medium">+ Add FAQ</button>
            </div>
          </div>
        </form>
      </div>
      
      <!-- Right Sidebar -->
      <div class="w-80 flex-shrink-0 space-y-6">
        <!-- Save Button -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex gap-3">
            <Link href="/admin/education-posts" class="flex-1 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-center transition-all duration-200 font-medium">Cancel</Link>
            <button 
              @click="submit" 
              :disabled="form.processing" 
              class="flex-1 px-4 py-2.5 rounded-xl bg-blue-500 text-white hover:bg-blue-600 font-medium transition-all duration-200 shadow-sm"
            >
              {{ form.processing ? 'Saving...' : (post ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
        
        <!-- Sidebar Content -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-6">
          <div>
            <label class="block mb-2 font-semibold text-slate-800">Category *</label>
            <select v-model="form.product_category_id" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required>
              <option :value="null">-- Select Category --</option>
              <option 
                v-for="category in availableCategories" 
                :key="category.id" 
                :value="category.id"
                :disabled="category.has_education_post"
              >
                {{ category.name }}{{ category.has_education_post ? ' (Already has education post)' : '' }}
              </option>
            </select>
            <p class="text-sm text-slate-500 mt-1">Each category can only have one education post</p>
          </div>
          
          <div>
            <label class="block mb-2 font-semibold text-slate-800">Status *</label>
            <select v-model="form.status" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
            <p class="text-sm text-slate-500 mt-1" v-if="form.status === 'published'">
              Published date will be set automatically when you save
            </p>
          </div>
          
          <div>
            <label class="block mb-2 font-semibold text-slate-800">Featured Image</label>
            <div v-if="imagePreview" class="mb-4">
              <img :src="imagePreview" alt="Preview" class="w-full h-48 object-cover rounded-xl border border-slate-100 shadow-sm" />
            </div>
            <div v-else-if="post?.image" class="mb-4">
              <img :src="post.image" alt="Current" class="w-full h-48 object-cover rounded-xl border border-slate-100 shadow-sm" />
            </div>
            <div v-else class="mb-4 w-full h-48 bg-slate-100 rounded-xl border border-slate-100 flex items-center justify-center text-slate-400">
              <span>No Image</span>
            </div>
            <input
              @change="handleImageChange"
              type="file"
              accept="image/*"
              class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
            />
            <p class="text-sm text-slate-500 mt-1">Upload featured image</p>
          </div>
          
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">Rating</label>
            <input v-model.number="form.rating" type="number" step="0.01" min="0" max="5" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
          </div>
          
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">Rating Count</label>
            <input v-model.number="form.rating_count" type="number" min="0" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
          </div>
          
          <div v-if="post?.published_at" class="pt-4 border-t border-slate-100">
            <label class="block mb-1 font-medium text-sm text-slate-600">Published Date</label>
            <p class="text-sm text-slate-700">{{ post.published_at }}</p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, computed, watch } from 'vue'

const props = defineProps({
  post: Object,
  categories: {
    type: Array,
    default: () => []
  }
})

const page = usePage()
const imagePreview = ref(null)

const form = useForm({
  title: props.post?.title || '',
  education_tag: props.post?.education_tag || null,
  peptide_full_name: props.post?.peptide_full_name || '',
  description: props.post?.description || '',
  half_life: props.post?.half_life || '',
  bioavailability: props.post?.bioavailability || '',
  storage: props.post?.storage || '',
  how_it_works: props.post?.how_it_works || '',
  typical_dosage: props.post?.typical_dosage || '',
  frequency: props.post?.frequency || '',
  administration: props.post?.administration || '',
  cycle_duration: props.post?.cycle_duration || '',
  possible_side_effects: props.post?.possible_side_effects || [],
  contraindications: props.post?.contraindications || [],
  stacking_recommendations: props.post?.stacking_recommendations || [],
  faqs: props.post?.faqs || [],
  image: null,
  rating: props.post?.rating || 0,
  rating_count: props.post?.rating_count || 0,
  key_effects: props.post?.key_effects || [],
  common_use_cases: props.post?.common_use_cases || [],
  status: props.post?.status || 'draft',
  product_category_id: props.post?.product_category_id || null,
  _token: page.props.csrf_token,
})

// Computed property to filter available categories
const availableCategories = computed(() => {
  return props.categories || []
})

// Computed property for stacking recommendations (exclude current category)
const availableStackingCategories = computed(() => {
  if (!props.categories) return []
  return props.categories.filter(category => {
    // Exclude the current category if editing
    if (props.post && category.id === props.post.product_category_id) {
      return false
    }
    return true
  })
})

// Ref for selected stacking categories (array of category IDs, excluding null)
const selectedStackingCategories = ref([])

// Initialize selectedStackingCategories from form
if (form.stacking_recommendations && form.stacking_recommendations.length > 0) {
  selectedStackingCategories.value = form.stacking_recommendations.filter(id => id !== null).map(id => Number(id))
}

// Watch selectedStackingCategories and update form (only when user selects/deselects in dropdown)
watch(selectedStackingCategories, (newValue) => {
  if (Array.isArray(newValue)) {
    // Update form with selected IDs, ensuring they're numbers
    const ids = newValue.map(id => Number(id))
    if (ids.length > 0) {
      form.stacking_recommendations = ids
    } else {
      // If no selections and "None" is not checked, set to empty array
      if (!form.stacking_recommendations || !form.stacking_recommendations.includes(null)) {
        form.stacking_recommendations = []
      }
    }
  }
}, { deep: true })

// Handle "None" checkbox change
const handleStackingNoneChange = (event) => {
  if (event.target.checked) {
    // If "None" is selected, clear all other selections
    form.stacking_recommendations = [null]
    selectedStackingCategories.value = []
  } else {
    // If "None" is unchecked, remove it from the array
    const filtered = form.stacking_recommendations.filter(item => item !== null)
    form.stacking_recommendations = filtered
    selectedStackingCategories.value = filtered.map(id => Number(id))
  }
}

// Remove a specific category from stacking recommendations
const removeStackingCategory = (categoryId) => {
  form.stacking_recommendations = form.stacking_recommendations.filter(id => id !== categoryId)
}

// Get category name by ID
const getCategoryName = (categoryId) => {
  const category = availableStackingCategories.value.find(cat => cat.id === categoryId)
  return category ? category.name : ''
}

const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.image = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const addKeyEffect = () => {
  form.key_effects.push('')
}

const removeKeyEffect = (index) => {
  form.key_effects.splice(index, 1)
}

const addCommonUseCase = () => {
  form.common_use_cases.push('')
}

const removeCommonUseCase = (index) => {
  form.common_use_cases.splice(index, 1)
}

const addPossibleSideEffect = () => {
  form.possible_side_effects.push('')
}

const removePossibleSideEffect = (index) => {
  form.possible_side_effects.splice(index, 1)
}

const addContraindication = () => {
  form.contraindications.push('')
}

const removeContraindication = (index) => {
  form.contraindications.splice(index, 1)
}

const addFAQ = () => {
  form.faqs.push({
    question: '',
    answer: ''
  })
}

const removeFAQ = (index) => {
  form.faqs.splice(index, 1)
}

const submit = () => {
  // Filter out empty Research Applications
  form.key_effects = form.key_effects.filter(effect => effect.trim() !== '')
  
  // Filter out empty Common Use Cases
  form.common_use_cases = form.common_use_cases.filter(useCase => useCase.trim() !== '')
  
  // Filter out empty Possible Side Effects
  form.possible_side_effects = form.possible_side_effects.filter(effect => effect.trim() !== '')
  
  // Filter out empty Contraindications
  form.contraindications = form.contraindications.filter(contraindication => contraindication.trim() !== '')
  
  // Filter out empty FAQs (both question and answer must be filled)
  form.faqs = form.faqs.filter(faq => faq.question?.trim() !== '' && faq.answer?.trim() !== '')
  
  // Stacking recommendations: [null] for None, or [categoryId1, categoryId2, ...] for multiple categories
  // Only set to [null] if array is empty and no categories are selected
  if (!form.stacking_recommendations || form.stacking_recommendations.length === 0) {
    form.stacking_recommendations = [null]
  } else {
    // Ensure null is not in the array if we have actual category selections
    form.stacking_recommendations = form.stacking_recommendations.filter(id => id !== null)
    // If after filtering we have nothing, set to [null]
    if (form.stacking_recommendations.length === 0) {
      form.stacking_recommendations = [null]
    }
  }
  
  // Update CSRF token before submission
  form._token = page.props.csrf_token
  
  if (props.post) {
    form.post(`/admin/education-posts/${props.post.id}`, {
      preserveScroll: true,
      forceFormData: true,
    })
  } else {
    form.post('/admin/education-posts', {
      preserveScroll: true,
      forceFormData: true,
    })
  }
}
</script>
