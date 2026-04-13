<template>
  <AdminLayout>
    <FormPage
      :title="category ? category.name : 'New Category'"
      backLabel="Categories"
      backHref="/admin/categories"
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
      <ul class="list-disc list-inside">
        <li v-for="(error, field) in form.errors" :key="field">{{ Array.isArray(error) ? error[0] : error }}</li>
      </ul>
    </div>

    <!-- General tab -->
    <template v-if="activeTab === 'general'">
      <FormSection title="Category Info" :columns="2">
        <FormField label="Name" :required="true" :error="form.errors.name">
          <input v-model="form.name" type="text" class="ui-input" placeholder="e.g. BPC-157" />
        </FormField>
        <FormField label="Slug" hint="Auto-generated from name if empty">
          <input v-model="form.slug" type="text" class="ui-input" placeholder="bpc-157" />
        </FormField>
      </FormSection>

      <FormSection title="Details">
        <FormField label="Description">
          <textarea v-model="form.description" class="ui-input" rows="3" placeholder="Brief description..."></textarea>
        </FormField>
        <FormField label="Research Area" hint="e.g. Tissue Repair, Anti-Aging, Performance">
          <input v-model="form.research_area" type="text" class="ui-input" placeholder="Tissue Repair" />
        </FormField>
      </FormSection>

      <FormSection title="Status">
        <label class="flex items-center gap-2 cursor-pointer">
          <input v-model="form.is_active" type="checkbox" class="w-4 h-4 text-[color:var(--color-accent-600)]" />
          <span class="text-sm text-[color:var(--color-ink)]">Active</span>
        </label>
      </FormSection>
    </template>

    <!-- Aliases tab -->
    <template v-if="activeTab === 'aliases'">
      <FormSection title="Keyword Aliases" description="Products containing these keywords will be auto-categorized into this category. Longer keywords match first.">
        <!-- Existing aliases -->
        <div class="space-y-2 mb-4">
          <div v-for="alias in aliases" :key="alias.id" class="flex items-center gap-2">
            <code class="flex-1 h-9 px-3 flex items-center text-sm bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] ui-mono text-[color:var(--color-ink)]">{{ alias.keyword }}</code>
            <button @click="removeAlias(alias.id)" class="h-9 px-3 text-[12px] font-medium text-[color:var(--color-danger)] bg-[color:var(--color-danger-bg)] hover:bg-red-100 transition-colors">Remove</button>
          </div>
          <div v-if="!aliases.length" class="text-[13px] text-[color:var(--color-ink-subtle)] py-2">No aliases yet. Add keywords below.</div>
        </div>

        <!-- Add new alias -->
        <div class="flex gap-2">
          <input
            v-model="newAlias"
            @keydown.enter.prevent="addAlias"
            type="text"
            class="ui-input flex-1"
            placeholder="Add keyword (e.g. bpc157, body protection compound)..."
          />
          <button @click="addAlias" class="h-[38px] px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all">Add</button>
        </div>
        <p class="text-[11px] text-[color:var(--color-ink-subtle)] mt-2">Keywords are case-insensitive. Add variations like abbreviations, brand names, alternate spellings.</p>
      </FormSection>
    </template>

    <!-- SEO tab -->
    <template v-if="activeTab === 'seo'">
      <FormSection title="Search Engine Optimization" :columns="2">
        <FormField label="Meta Title">
          <input v-model="form.meta_title" type="text" class="ui-input" placeholder="SEO title..." />
        </FormField>
        <FormField label="Meta Description">
          <textarea v-model="form.meta_description" class="ui-input" rows="3" placeholder="SEO description..."></textarea>
        </FormField>
      </FormSection>
    </template>

    <!-- Products tab -->
    <template v-if="activeTab === 'products'">
      <FormSection :title="`${products.length} products in this category`">
        <div v-if="products.length" class="space-y-1">
          <div
            v-for="p in products"
            :key="p.id"
            class="flex items-center gap-3 py-2 border-b border-[color:var(--color-hairline-soft)] last:border-0"
          >
            <div class="w-8 h-8 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] overflow-hidden">
              <img v-if="p.image_url" :src="p.image_url" :alt="p.name" class="w-full h-full object-cover" loading="lazy" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-[13px] font-medium text-[color:var(--color-ink)] truncate">{{ p.name }}</div>
              <div class="text-[11px] text-[color:var(--color-ink-subtle)]">{{ p.brand_name }}</div>
            </div>
            <span class="ui-mono text-[13px] text-[color:var(--color-ink)]">${{ p.price || '0.00' }}</span>
          </div>
        </div>
        <p v-else class="text-[13px] text-[color:var(--color-ink-subtle)] py-4">No products in this category.</p>
      </FormSection>
    </template>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, usePage, Link, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import FormPage from '@/components/admin/FormPage.vue'
import FormSection from '@/components/admin/FormSection.vue'
import FormField from '@/components/admin/FormField.vue'

const props = defineProps({
  category: Object,
  products: { type: Array, default: () => [] },
  aliases: { type: Array, default: () => [] },
})

const page = usePage()
const activeTab = ref('general')
const justSaved = ref(false)
const newAlias = ref('')

const tabs = [
  { id: 'general', label: 'General' },
  { id: 'aliases', label: `Aliases (${(props.aliases || []).length})` },
  { id: 'seo', label: 'SEO' },
  { id: 'products', label: `Products (${(props.products || []).length})` },
]

const form = useForm({
  name: props.category?.name || '',
  slug: props.category?.slug || '',
  description: props.category?.description || '',
  research_area: props.category?.research_area || '',
  meta_title: props.category?.meta_title || '',
  meta_description: props.category?.meta_description || '',
  is_active: props.category?.is_active ?? true,
  _token: page.props.csrf_token,
})

const aliases = ref(props.aliases || [])

function submit() {
  form._token = page.props.csrf_token
  if (props.category) {
    form.post(`/admin/categories/${props.category.id}`, {
      preserveScroll: true,
      preserveState: true,
      forceFormData: true,
      onSuccess: () => {
        justSaved.value = true
        setTimeout(() => justSaved.value = false, 3000)
      },
    })
  } else {
    form.post('/admin/categories', {
      preserveScroll: true,
      forceFormData: true,
    })
  }
}

function addAlias() {
  const keyword = newAlias.value.trim().toLowerCase()
  if (!keyword || !props.category) return
  if (aliases.value.some(a => a.keyword === keyword)) { newAlias.value = ''; return }

  router.post(`/admin/categories/${props.category.id}/aliases`, {
    _token: page.props.csrf_token,
    keyword,
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: (page) => {
      aliases.value = page.props.aliases || aliases.value
      newAlias.value = ''
    },
  })
}

function removeAlias(aliasId) {
  router.delete(`/admin/categories/aliases/${aliasId}`, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: (page) => {
      aliases.value = aliases.value.filter(a => a.id !== aliasId)
    },
  })
}
</script>
