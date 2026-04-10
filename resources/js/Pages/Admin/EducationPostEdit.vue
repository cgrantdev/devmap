<template>
  <AdminLayout>
    <FormPage
      :title="post ? post.title : 'New Education Post'"
      backLabel="Education Posts"
      backHref="/admin/education-posts"
      :tabs="tabs"
      :activeTab="activeTab"
      @update:activeTab="activeTab = $event"
      :saving="form.processing"
      :saved="justSaved"
      @save="submit"
    />

    <!-- Flash -->
    <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 bg-[color:var(--color-verified-bg)] border border-[#A7F3D0] text-[#065F46] text-sm">
      {{ $page.props.flash.success }}
    </div>

    <!-- Errors -->
    <div v-if="Object.keys(form.errors).length > 0" class="mb-4 px-4 py-3 bg-[color:var(--color-danger-bg)] border border-red-200 text-[#991B1B] text-sm">
      <p class="font-medium mb-1">Please fix the following errors:</p>
      <ul class="list-disc list-inside">
        <li v-for="(error, field) in form.errors" :key="field">{{ Array.isArray(error) ? error[0] : error }}</li>
      </ul>
    </div>

    <!-- General tab -->
    <template v-if="activeTab === 'general'">
      <FormSection title="Basic Info" :columns="2">
        <FormField label="Title" :required="true" :error="form.errors.title">
          <input v-model="form.title" type="text" class="ui-input" placeholder="Peptide name" />
        </FormField>
        <FormField label="Education Tag" :error="form.errors.education_tag">
          <select v-model="form.education_tag" class="ui-input">
            <option :value="null">Select tag...</option>
            <option value="Healing & Recovery">Healing & Recovery</option>
            <option value="Growth & Recovery">Growth & Recovery</option>
            <option value="Performance">Performance</option>
            <option value="Anti-Aging">Anti-Aging</option>
          </select>
        </FormField>
        <FormField label="Peptide Full Name" :error="form.errors.peptide_full_name">
          <input v-model="form.peptide_full_name" type="text" class="ui-input" placeholder="Full scientific name" />
        </FormField>
        <FormField label="Category" :error="form.errors.product_category_id" hint="Each category can only have one education post">
          <select v-model="form.product_category_id" class="ui-input">
            <option :value="null">Select category...</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="category.id"
              :disabled="category.has_education_post"
            >
              {{ category.name }}{{ category.has_education_post ? ' (taken)' : '' }}
            </option>
          </select>
        </FormField>
      </FormSection>

      <FormSection title="Description">
        <FormField label="Description" :error="form.errors.description">
          <textarea v-model="form.description" class="ui-input" rows="4" placeholder="Brief description of this peptide..."></textarea>
        </FormField>
        <FormField label="How It Works" :error="form.errors.how_it_works">
          <textarea v-model="form.how_it_works" class="ui-input" rows="6" placeholder="Mechanism of action..."></textarea>
        </FormField>
      </FormSection>
    </template>

    <!-- Research tab -->
    <template v-if="activeTab === 'research'">
      <FormSection title="Quick Facts" :columns="2">
        <FormField label="Half-Life" :error="form.errors.half_life" hint="e.g. 2-4 hours">
          <input v-model="form.half_life" type="text" class="ui-input" placeholder="2-4 hours" />
        </FormField>
        <FormField label="Bioavailability" :error="form.errors.bioavailability">
          <input v-model="form.bioavailability" type="text" class="ui-input" placeholder="Bioavailability info" />
        </FormField>
        <FormField label="Storage" :error="form.errors.storage" hint="e.g. Store at 2-8 C">
          <input v-model="form.storage" type="text" class="ui-input" placeholder="Store at 2-8 C" />
        </FormField>
      </FormSection>

      <FormSection title="Research Applications">
        <FormField label="Key Effects">
          <div class="space-y-2">
            <div v-for="(effect, index) in form.key_effects" :key="index" class="flex gap-2">
              <input v-model="form.key_effects[index]" type="text" class="ui-input flex-1" placeholder="Research application..." />
              <button type="button" @click="form.key_effects.splice(index, 1)" class="h-[38px] px-3 text-[12px] font-medium text-[color:var(--color-danger)] bg-[color:var(--color-danger-bg)] hover:bg-red-100 transition-colors">Remove</button>
            </div>
            <button type="button" @click="form.key_effects.push('')" class="h-8 px-3 text-[12px] font-medium text-[color:var(--color-ink-muted)] bg-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline)] transition-colors">+ Add Effect</button>
          </div>
        </FormField>
        <FormField label="Common Use Cases" class="mt-4">
          <div class="space-y-2">
            <div v-for="(uc, index) in form.common_use_cases" :key="index" class="flex gap-2">
              <input v-model="form.common_use_cases[index]" type="text" class="ui-input flex-1" placeholder="Use case..." />
              <button type="button" @click="form.common_use_cases.splice(index, 1)" class="h-[38px] px-3 text-[12px] font-medium text-[color:var(--color-danger)] bg-[color:var(--color-danger-bg)] hover:bg-red-100 transition-colors">Remove</button>
            </div>
            <button type="button" @click="form.common_use_cases.push('')" class="h-8 px-3 text-[12px] font-medium text-[color:var(--color-ink-muted)] bg-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline)] transition-colors">+ Add Use Case</button>
          </div>
        </FormField>
      </FormSection>
    </template>

    <!-- Dosage tab -->
    <template v-if="activeTab === 'dosage'">
      <FormSection title="Dosage & Administration" :columns="2">
        <FormField label="Typical Dosage" :error="form.errors.typical_dosage" hint="e.g. 200-300 mcg">
          <input v-model="form.typical_dosage" type="text" class="ui-input" placeholder="200-300 mcg" />
        </FormField>
        <FormField label="Frequency" :error="form.errors.frequency" hint="e.g. Once daily">
          <input v-model="form.frequency" type="text" class="ui-input" placeholder="Once daily" />
        </FormField>
        <FormField label="Administration" :error="form.errors.administration" hint="e.g. Subcutaneous injection">
          <input v-model="form.administration" type="text" class="ui-input" placeholder="Subcutaneous injection" />
        </FormField>
        <FormField label="Cycle Duration" :error="form.errors.cycle_duration" hint="e.g. 8-12 weeks">
          <input v-model="form.cycle_duration" type="text" class="ui-input" placeholder="8-12 weeks" />
        </FormField>
      </FormSection>

      <FormSection title="Safety Information">
        <FormField label="Possible Side Effects">
          <div class="space-y-2">
            <div v-for="(effect, index) in form.possible_side_effects" :key="index" class="flex gap-2">
              <input v-model="form.possible_side_effects[index]" type="text" class="ui-input flex-1" placeholder="Side effect..." />
              <button type="button" @click="form.possible_side_effects.splice(index, 1)" class="h-[38px] px-3 text-[12px] font-medium text-[color:var(--color-danger)] bg-[color:var(--color-danger-bg)] hover:bg-red-100 transition-colors">Remove</button>
            </div>
            <button type="button" @click="form.possible_side_effects.push('')" class="h-8 px-3 text-[12px] font-medium text-[color:var(--color-ink-muted)] bg-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline)] transition-colors">+ Add Side Effect</button>
          </div>
        </FormField>
        <FormField label="Contraindications" class="mt-4">
          <div class="space-y-2">
            <div v-for="(ci, index) in form.contraindications" :key="index" class="flex gap-2">
              <input v-model="form.contraindications[index]" type="text" class="ui-input flex-1" placeholder="Contraindication..." />
              <button type="button" @click="form.contraindications.splice(index, 1)" class="h-[38px] px-3 text-[12px] font-medium text-[color:var(--color-danger)] bg-[color:var(--color-danger-bg)] hover:bg-red-100 transition-colors">Remove</button>
            </div>
            <button type="button" @click="form.contraindications.push('')" class="h-8 px-3 text-[12px] font-medium text-[color:var(--color-ink-muted)] bg-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline)] transition-colors">+ Add Contraindication</button>
          </div>
        </FormField>
      </FormSection>

      <FormSection title="Stacking Recommendations" description="Select categories that work well when stacked with this peptide">
        <div class="space-y-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" :checked="form.stacking_recommendations.includes(null)" @change="handleStackingNoneChange" class="w-4 h-4 border-[color:var(--color-hairline)] text-[color:var(--color-accent-600)]" />
            <span class="text-sm text-[color:var(--color-ink)]">None</span>
          </label>
          <select
            v-model="selectedStackingCategories"
            multiple
            class="ui-input min-h-[120px]"
            :disabled="form.stacking_recommendations.includes(null)"
          >
            <option v-for="cat in availableStackingCategories" :key="cat.id" :value="Number(cat.id)">{{ cat.name }}</option>
          </select>
          <div v-if="selectedStackingCategories.length > 0" class="flex flex-wrap gap-1.5 mt-2">
            <span v-for="catId in selectedStackingCategories" :key="catId" class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-600)]">
              {{ getCategoryName(catId) }}
              <button type="button" @click="removeStackingCategory(catId)" class="hover:text-[color:var(--color-accent-800)]">&times;</button>
            </span>
          </div>
        </div>
      </FormSection>
    </template>

    <!-- FAQs tab -->
    <template v-if="activeTab === 'faqs'">
      <FormSection title="Frequently Asked Questions">
        <div class="space-y-4">
          <div v-for="(faq, index) in form.faqs" :key="index" class="bg-[color:var(--color-hairline-soft)] p-4 border border-[color:var(--color-hairline)]">
            <div class="flex items-center justify-between mb-3">
              <span class="text-[12px] font-semibold text-[color:var(--color-ink-muted)]">FAQ {{ index + 1 }}</span>
              <button type="button" @click="form.faqs.splice(index, 1)" class="text-[12px] font-medium text-[color:var(--color-danger)] hover:underline">Remove</button>
            </div>
            <FormField label="Question" class="mb-3">
              <input v-model="form.faqs[index].question" type="text" class="ui-input" placeholder="Question..." />
            </FormField>
            <FormField label="Answer">
              <textarea v-model="form.faqs[index].answer" class="ui-input" rows="3" placeholder="Answer..."></textarea>
            </FormField>
          </div>
          <button type="button" @click="form.faqs.push({ question: '', answer: '' })" class="h-8 px-3 text-[12px] font-medium text-[color:var(--color-ink-muted)] bg-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline)] transition-colors">+ Add FAQ</button>
        </div>
      </FormSection>
    </template>

    <!-- Publishing tab -->
    <template v-if="activeTab === 'publishing'">
      <FormSection title="Status & Visibility" :columns="2">
        <FormField label="Status" :required="true" :error="form.errors.status">
          <select v-model="form.status" class="ui-input">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
          </select>
        </FormField>
        <FormField label="Rating" :error="form.errors.rating">
          <input v-model.number="form.rating" type="number" step="0.01" min="0" max="5" class="ui-input" />
        </FormField>
        <FormField label="Rating Count" :error="form.errors.rating_count">
          <input v-model.number="form.rating_count" type="number" min="0" class="ui-input" />
        </FormField>
      </FormSection>

      <FormSection title="Featured Image">
        <div class="flex items-start gap-6">
          <div class="w-48 h-32 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] overflow-hidden">
            <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
            <img v-else-if="post?.image" :src="post.image" alt="Current" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex flex-col items-center justify-center text-[color:var(--color-ink-subtle)]">
              <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2zm16 12l-3.09-3.09a2 2 0 00-2.82 0L6 21M9 9a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span class="text-[12px]">No image</span>
            </div>
          </div>
          <FormField label="Upload Image" hint="Will be converted to WebP.">
            <input @change="handleImageChange" type="file" accept="image/*" class="ui-input text-sm" />
          </FormField>
        </div>
      </FormSection>

      <div v-if="post?.published_at" class="text-[12px] text-[color:var(--color-ink-subtle)] mt-2">
        Published: <span class="ui-mono text-[color:var(--color-ink)]">{{ post.published_at }}</span>
      </div>
    </template>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import FormPage from '@/components/admin/FormPage.vue'
import FormSection from '@/components/admin/FormSection.vue'
import FormField from '@/components/admin/FormField.vue'

const props = defineProps({
  post: Object,
  categories: { type: Array, default: () => [] },
})

const page = usePage()
const activeTab = ref('general')
const justSaved = ref(false)
const imagePreview = ref(null)

const tabs = [
  { id: 'general', label: 'General' },
  { id: 'research', label: 'Research' },
  { id: 'dosage', label: 'Dosage & Safety' },
  { id: 'faqs', label: 'FAQs' },
  { id: 'publishing', label: 'Publishing' },
]

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

const availableStackingCategories = computed(() => {
  if (!props.categories) return []
  return props.categories.filter(c => !(props.post && c.id === props.post.product_category_id))
})

const selectedStackingCategories = ref(
  (form.stacking_recommendations || []).filter(id => id !== null).map(id => Number(id))
)

watch(selectedStackingCategories, (newVal) => {
  if (Array.isArray(newVal)) {
    const ids = newVal.map(id => Number(id))
    form.stacking_recommendations = ids.length > 0 ? ids : []
  }
}, { deep: true })

function handleStackingNoneChange(event) {
  if (event.target.checked) {
    form.stacking_recommendations = [null]
    selectedStackingCategories.value = []
  } else {
    form.stacking_recommendations = selectedStackingCategories.value.map(id => Number(id))
  }
}

function removeStackingCategory(catId) {
  selectedStackingCategories.value = selectedStackingCategories.value.filter(id => id !== catId)
}

function getCategoryName(catId) {
  const cat = availableStackingCategories.value.find(c => c.id === catId)
  return cat ? cat.name : ''
}

function handleImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    form.image = file
    const reader = new FileReader()
    reader.onload = (e) => { imagePreview.value = e.target.result }
    reader.readAsDataURL(file)
  }
}

function submit() {
  form.key_effects = form.key_effects.filter(e => e.trim() !== '')
  form.common_use_cases = form.common_use_cases.filter(u => u.trim() !== '')
  form.possible_side_effects = form.possible_side_effects.filter(e => e.trim() !== '')
  form.contraindications = form.contraindications.filter(c => c.trim() !== '')
  form.faqs = form.faqs.filter(f => f.question?.trim() !== '' && f.answer?.trim() !== '')

  if (!form.stacking_recommendations || form.stacking_recommendations.length === 0) {
    form.stacking_recommendations = [null]
  } else {
    form.stacking_recommendations = form.stacking_recommendations.filter(id => id !== null)
    if (form.stacking_recommendations.length === 0) form.stacking_recommendations = [null]
  }

  form._token = page.props.csrf_token

  const url = props.post ? `/admin/education-posts/${props.post.id}` : '/admin/education-posts'

  form.post(url, {
    preserveScroll: true,
    preserveState: true,
    forceFormData: true,
    onSuccess: () => {
      justSaved.value = true
      setTimeout(() => { justSaved.value = false }, 3000)
    },
  })
}
</script>
