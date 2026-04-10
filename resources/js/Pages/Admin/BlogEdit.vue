<template>
  <AdminLayout>
    <FormPage
      :title="blog ? blog.title : 'New Blog Post'"
      backLabel="Blog Posts"
      backHref="/admin/blogs"
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

    <!-- Content tab -->
    <template v-if="activeTab === 'content'">
      <FormSection title="Basic Info" :columns="2">
        <FormField label="Title" :required="true" :error="form.errors.title">
          <input v-model="form.title" type="text" class="ui-input" placeholder="Post title" />
        </FormField>
        <FormField label="Blog Type" :error="form.errors.blog_type">
          <select v-model="form.blog_type" class="ui-input">
            <option :value="null">Select type...</option>
            <option value="Regulation">Regulation</option>
            <option value="Research">Research</option>
            <option value="Community">Community</option>
            <option value="Guides">Guides</option>
            <option value="Industry">Industry</option>
          </select>
        </FormField>
      </FormSection>

      <FormSection title="Body">
        <FormField label="Outline" :error="form.errors.outline" hint="Brief outline of the post">
          <textarea v-model="form.outline" class="ui-input" rows="3" placeholder="Brief outline..."></textarea>
        </FormField>
        <FormField label="Description" :error="form.errors.description">
          <textarea v-model="form.description" class="ui-input" rows="3" placeholder="Short description / excerpt..."></textarea>
        </FormField>
        <FormField label="Introduction" :error="form.errors.introduction">
          <textarea v-model="form.introduction" class="ui-input" rows="5" placeholder="Introduction paragraph..."></textarea>
        </FormField>
        <FormField label="Key Points">
          <div class="space-y-2">
            <div v-for="(point, index) in form.key_points" :key="index" class="flex gap-2">
              <input v-model="form.key_points[index]" type="text" class="ui-input flex-1" placeholder="Key point..." />
              <button type="button" @click="form.key_points.splice(index, 1)" class="h-[38px] px-3 text-[12px] font-medium text-[color:var(--color-danger)] bg-[color:var(--color-danger-bg)] hover:bg-red-100 transition-colors">Remove</button>
            </div>
            <button type="button" @click="form.key_points.push('')" class="h-8 px-3 text-[12px] font-medium text-[color:var(--color-ink-muted)] bg-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline)] transition-colors">
              + Add Key Point
            </button>
          </div>
        </FormField>
        <FormField label="Detailed Analysis" :error="form.errors.detailed_analysis">
          <textarea v-model="form.detailed_analysis" class="ui-input" rows="10" placeholder="Main body content..."></textarea>
        </FormField>
        <FormField label="Conclusion" :error="form.errors.conclusion">
          <textarea v-model="form.conclusion" class="ui-input" rows="5" placeholder="Concluding paragraphs..."></textarea>
        </FormField>
      </FormSection>

      <FormSection title="Tags">
        <div class="space-y-2">
          <div v-for="(tag, index) in form.tags" :key="index" class="flex gap-2">
            <input v-model="form.tags[index]" type="text" class="ui-input flex-1" placeholder="Tag..." />
            <button type="button" @click="form.tags.splice(index, 1)" class="h-[38px] px-3 text-[12px] font-medium text-[color:var(--color-danger)] bg-[color:var(--color-danger-bg)] hover:bg-red-100 transition-colors">Remove</button>
          </div>
          <button type="button" @click="form.tags.push('')" class="h-8 px-3 text-[12px] font-medium text-[color:var(--color-ink-muted)] bg-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline)] transition-colors">
            + Add Tag
          </button>
        </div>
      </FormSection>
    </template>

    <!-- Author tab -->
    <template v-if="activeTab === 'author'">
      <FormSection title="Author Details" :columns="2">
        <FormField label="Author Name" :error="form.errors.author_name">
          <input v-model="form.author_name" type="text" class="ui-input" placeholder="Author name" />
        </FormField>
        <FormField label="Author Job Title" :error="form.errors.author_job">
          <input v-model="form.author_job" type="text" class="ui-input" placeholder="e.g. Senior Researcher" />
        </FormField>
      </FormSection>
    </template>

    <!-- Media tab -->
    <template v-if="activeTab === 'media'">
      <FormSection title="Featured Image">
        <div class="flex items-start gap-6">
          <div class="w-64 h-40 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] overflow-hidden">
            <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
            <img v-else-if="blog?.image" :src="blog.image" alt="Current" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex flex-col items-center justify-center text-[color:var(--color-ink-subtle)]">
              <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2zm16 12l-3.09-3.09a2 2 0 00-2.82 0L6 21M9 9a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span class="text-[12px]">No image</span>
            </div>
          </div>
          <div class="flex-1 space-y-3">
            <FormField label="Upload Image" hint="Recommended: 1200x630px. Will be converted to WebP.">
              <input @change="handleImageChange" type="file" accept="image/*" class="ui-input text-sm" />
            </FormField>
          </div>
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
        <FormField label="Read Time" :error="form.errors.read_time" hint="e.g. '5 Min Read'">
          <input v-model="form.read_time" type="text" class="ui-input" placeholder="5 Min Read" />
        </FormField>
      </FormSection>

      <FormSection title="Options">
        <label class="flex items-center gap-2 cursor-pointer">
          <input v-model="form.is_featured" type="checkbox" class="w-4 h-4 text-[color:var(--color-accent-600)] border-[color:var(--color-hairline)] focus:ring-[color:var(--color-accent-500)]" />
          <span class="text-sm text-[color:var(--color-ink)]">Featured Post</span>
        </label>
        <p class="text-[12px] text-[color:var(--color-ink-subtle)] mt-1">Featured posts appear prominently on the homepage.</p>

        <div v-if="blog?.published_at" class="mt-4 pt-4 border-t border-[color:var(--color-hairline)]">
          <span class="text-[12px] text-[color:var(--color-ink-subtle)]">Published:</span>
          <span class="ml-1 text-[13px] text-[color:var(--color-ink)] ui-mono">{{ blog.published_at }}</span>
        </div>
      </FormSection>
    </template>

    <!-- SEO tab -->
    <template v-if="activeTab === 'seo'">
      <FormSection title="Search Engine Optimization" :columns="1">
        <FormField label="Page Title" :error="form.errors.seo_page_title" hint="Override the default page title for search engines">
          <input v-model="form.seo_page_title" type="text" class="ui-input" placeholder="SEO page title..." />
        </FormField>
        <FormField label="Meta Description" :error="form.errors.seo_description" hint="155 characters recommended">
          <textarea v-model="form.seo_description" class="ui-input" rows="3" placeholder="Meta description for search results..."></textarea>
        </FormField>
      </FormSection>

      <FormSection title="Open Graph" :columns="2">
        <FormField label="OG Title" :error="form.errors.seo_og_title">
          <input v-model="form.seo_og_title" type="text" class="ui-input" placeholder="Open Graph title..." />
        </FormField>
        <FormField label="OG Image URL" :error="form.errors.seo_og_image">
          <input v-model="form.seo_og_image" type="url" class="ui-input" placeholder="https://..." />
        </FormField>
        <div class="md:col-span-2">
          <FormField label="OG Description" :error="form.errors.seo_og_description">
            <textarea v-model="form.seo_og_description" class="ui-input" rows="3" placeholder="Open Graph description..."></textarea>
          </FormField>
        </div>
      </FormSection>
    </template>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import FormPage from '@/components/admin/FormPage.vue'
import FormSection from '@/components/admin/FormSection.vue'
import FormField from '@/components/admin/FormField.vue'

const props = defineProps({
  blog: Object,
})

const page = usePage()
const activeTab = ref('content')
const justSaved = ref(false)
const imagePreview = ref(null)

const tabs = [
  { id: 'content', label: 'Content' },
  { id: 'author', label: 'Author' },
  { id: 'media', label: 'Media' },
  { id: 'publishing', label: 'Publishing' },
  { id: 'seo', label: 'SEO' },
]

const form = useForm({
  title: props.blog?.title || '',
  blog_type: props.blog?.blog_type || null,
  author_name: props.blog?.author_name || '',
  author_job: props.blog?.author_job || '',
  outline: props.blog?.outline || '',
  description: props.blog?.description || '',
  introduction: props.blog?.introduction || '',
  key_points: props.blog?.key_points || [],
  detailed_analysis: props.blog?.detailed_analysis || '',
  conclusion: props.blog?.conclusion || '',
  tags: props.blog?.tags || [],
  image: null,
  read_time: props.blog?.read_time || '',
  status: props.blog?.status || 'draft',
  is_featured: props.blog?.is_featured || false,
  seo_page_title: props.blog?.seo_page_title || '',
  seo_description: props.blog?.seo_description || '',
  seo_og_title: props.blog?.seo_og_title || '',
  seo_og_description: props.blog?.seo_og_description || '',
  seo_og_image: props.blog?.seo_og_image || '',
  _token: page.props.csrf_token,
})

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
  // Clean up empty values
  form.key_points = form.key_points.filter(p => p.trim() !== '')
  form.tags = form.tags.filter(t => t.trim() !== '')
  form._token = page.props.csrf_token

  const url = props.blog ? `/admin/blogs/${props.blog.id}` : '/admin/blogs'

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
