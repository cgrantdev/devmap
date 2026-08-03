<template>
  <AdminLayout>
    <!-- Sticky top bar (breaks out of AdminLayout padding) -->
    <div class="-mx-6 lg:-mx-8 -mt-6 lg:-mt-8 mb-6 sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-[color:var(--color-hairline)]">
      <div class="px-6 lg:px-8 py-3 flex items-center gap-4">
        <a href="/admin/encyclopedia-entries" class="flex items-center gap-1 text-[12px] text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink)]">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
          Entries
        </a>
        <div class="flex-1 min-w-0">
          <input
            v-model="form.title"
            type="text"
            placeholder="Untitled Entry"
            class="w-full text-lg font-semibold text-[color:var(--color-ink)] bg-transparent border-0 border-b border-transparent hover:border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none px-0 py-1 truncate"
          />
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
          <span v-if="form.processing" class="text-[12px] text-[color:var(--color-ink-muted)]">Saving...</span>
          <span v-else-if="justSaved" class="text-[12px] text-[color:var(--color-verified)] font-medium flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Saved
          </span>
          <span v-else-if="isDirty" class="text-[12px] text-amber-600 font-medium flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            Unsaved changes
          </span>
          <button
            type="button"
            @click="saveAndPreview"
            :disabled="form.processing"
            class="h-9 px-3 text-[13px] font-medium text-[color:var(--color-ink-muted)] border border-[color:var(--color-hairline)] hover:text-[color:var(--color-ink)] hover:border-[color:var(--color-ink-subtle)] transition-colors disabled:opacity-50"
          >
            Save & Preview
          </button>
          <button
            type="button"
            @click="submit"
            :disabled="form.processing"
            class="h-9 px-5 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : (entry ? 'Save' : 'Create') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Flash / errors -->
    <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 bg-[color:var(--color-verified-bg)] border border-[#A7F3D0] text-[#065F46] text-sm">
      {{ $page.props.flash.success }}
    </div>
    <div v-if="$page.props.flash?.error" class="mb-4 px-4 py-3 bg-[color:var(--color-danger-bg)] border border-red-200 text-[#991B1B] text-sm">
      {{ $page.props.flash.error }}
    </div>
    <div v-if="Object.keys(form.errors).length > 0" class="mb-4 px-4 py-3 bg-[color:var(--color-danger-bg)] border border-red-200 text-[#991B1B] text-sm">
      <p class="font-medium mb-1">Please fix the following errors:</p>
      <ul class="list-disc list-inside">
        <li v-for="(error, field) in form.errors" :key="field">{{ Array.isArray(error) ? error[0] : error }}</li>
      </ul>
    </div>

    <!-- Mobile section jump -->
    <div class="lg:hidden mb-4">
      <select
        v-model="activeSection"
        @change="scrollToSection(activeSection)"
        class="ui-input"
      >
        <option v-for="s in sections" :key="s.id" :value="s.id">
          {{ isComplete(s.id) ? '● ' : '○ ' }} {{ s.label }}
        </option>
      </select>
    </div>

    <!-- Two-column layout -->
    <div class="flex gap-6">
      <!-- Left rail -->
      <aside class="hidden lg:block w-[220px] flex-shrink-0">
        <nav class="sticky top-[76px] bg-white border border-[color:var(--color-hairline)] py-2 max-h-[calc(100vh-100px)] overflow-y-auto">
          <div class="px-4 py-2 text-[10px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">Sections</div>
          <button
            v-for="s in sections"
            :key="s.id"
            type="button"
            @click="scrollToSection(s.id)"
            :class="[
              'w-full flex items-center gap-2.5 px-4 py-2 text-left text-[13px] transition-colors',
              activeSection === s.id
                ? 'bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] font-medium border-l-2 border-[color:var(--color-accent-600)] pl-[14px]'
                : 'text-[color:var(--color-ink-muted)] hover:bg-[color:var(--color-hairline-soft)] hover:text-[color:var(--color-ink)]',
            ]"
          >
            <span
              class="flex-shrink-0 w-2 h-2 rounded-full"
              :class="isComplete(s.id) ? 'bg-[color:var(--color-verified)]' : 'border border-[color:var(--color-hairline)] bg-white'"
            />
            <span class="flex-1 truncate">{{ s.label }}</span>
          </button>
        </nav>
      </aside>

      <!-- Main content -->
      <form @submit.prevent="submit" class="flex-1 min-w-0 space-y-4">

        <!-- 1. Basic Information -->
        <SectionCard section-id="basic" title="Basic Information" :open="isOpen('basic')" :complete="isComplete('basic')" @toggle="toggle('basic')">
          <FormField label="Encyclopedia Tag" :required="true">
            <select v-model="form.education_tag" class="ui-input">
              <option :value="null">-- Select Tag --</option>
              <option value="Healing & Recovery">Healing & Recovery</option>
              <option value="Growth & Recovery">Growth & Recovery</option>
              <option value="Performance">Performance</option>
              <option value="Anti-Aging">Anti-Aging</option>
            </select>
          </FormField>
          <FormField label="Research URL" hint="External link to research, e.g. a PubMed search URL. Used for the 'View Research' link.">
            <input v-model="form.research_url" type="url" class="ui-input" placeholder="https://pubmed.ncbi.nlm.nih.gov/..." />
          </FormField>
          <FormField label="Peptide Full Name">
            <input v-model="form.peptide_full_name" type="text" class="ui-input" placeholder="Enter full peptide name" />
          </FormField>
          <FormField label="Entry Tags">
            <RepeaterStringList v-model="form.tags" item-label="Tag" placeholder="Enter tag" />
          </FormField>
        </SectionCard>

        <!-- 2. Molecular Information -->
        <SectionCard section-id="molecular" title="Molecular Information" :open="isOpen('molecular')" :complete="isComplete('molecular')" @toggle="toggle('molecular')">
          <FormField label="Formula">
            <input v-model="form.molecular_formula" type="text" class="ui-input" placeholder="e.g. C₆₂H₉₈N₁₆O₂₂" />
          </FormField>
          <FormField label="Molecular Weight">
            <input v-model="form.molecular_weight" type="text" class="ui-input" placeholder="e.g. 1,419.53 g/mol" />
          </FormField>
          <FormField label="CAS Registry Number">
            <input v-model="form.cas_registry_number" type="text" class="ui-input" placeholder="e.g. 137525-51-0" />
          </FormField>
        </SectionCard>

        <!-- 3. Amino Acid Sequence -->
        <SectionCard section-id="amino" title="Amino Acid Sequence" :open="isOpen('amino')" :complete="isComplete('amino')" @toggle="toggle('amino')">
          <FormField label="Sequence">
            <textarea v-model="form.amino_acid_sequence" rows="3" class="ui-input" placeholder="e.g. Gly-Glu-Pro-Pro-Pro-Gly-Lys-Pro-Ala-Asp-Asp-Ala-Gly-Leu-Val"></textarea>
          </FormField>
          <FormField label="Net Charge">
            <input v-model="form.amino_acid_net_charge" type="text" class="ui-input" placeholder="e.g. -2 (at pH 7)" />
          </FormField>
          <FormField label="Hydrophobic">
            <input v-model="form.amino_acid_hydrophobic" type="text" class="ui-input" placeholder="e.g. 40%" />
          </FormField>
          <FormField label="Stability">
            <input v-model="form.amino_acid_stability" type="text" class="ui-input" placeholder="e.g. High" />
          </FormField>
          <FormField label="Solubility">
            <input v-model="form.amino_acid_solubility" type="text" class="ui-input" placeholder="e.g. Water soluble" />
          </FormField>
        </SectionCard>

        <!-- 4. Key Points -->
        <SectionCard section-id="key_points" title="Key Points" :open="isOpen('key_points')" :complete="isComplete('key_points')" @toggle="toggle('key_points')">
          <RepeaterStringList v-model="form.key_points" item-label="Key Point" placeholder="Enter key point" />
        </SectionCard>

        <!-- 5. Overview -->
        <SectionCard section-id="overview" title="Overview" :open="isOpen('overview')" :complete="isComplete('overview')" @toggle="toggle('overview')">
          <textarea v-model="form.overview" rows="8" class="ui-input" placeholder="Enter overview information"></textarea>
        </SectionCard>

        <!-- 6. Areas of Research -->
        <SectionCard section-id="areas" title="Areas of Research" :open="isOpen('areas')" :complete="isComplete('areas')" @toggle="toggle('areas')">
          <FormField label="Introduction">
            <textarea v-model="form.areas_of_research_intro" rows="4" class="ui-input" placeholder="Enter introduction to areas of research"></textarea>
          </FormField>
          <FormField label="Areas">
            <RepeaterObjectList
              v-model="form.areas_of_research"
              item-label="Area"
              title-field="name"
              :new-item="() => ({ name: '', description: '' })"
            >
              <template #item="{ item }">
                <div class="space-y-3">
                  <FormField label="Area Name">
                    <input v-model="item.name" type="text" class="ui-input" placeholder="Enter area name" />
                  </FormField>
                  <FormField label="Description">
                    <textarea v-model="item.description" rows="3" class="ui-input" placeholder="Enter description"></textarea>
                  </FormField>
                </div>
              </template>
            </RepeaterObjectList>
          </FormField>
        </SectionCard>

        <!-- 7. Background -->
        <SectionCard section-id="background" title="Background" :open="isOpen('background')" :complete="isComplete('background')" @toggle="toggle('background')">
          <textarea v-model="form.background" rows="8" class="ui-input" placeholder="Enter background information"></textarea>
        </SectionCard>

        <!-- 8. Mechanism of Action -->
        <SectionCard section-id="mechanism" title="Mechanism of Action" :open="isOpen('mechanism')" :complete="isComplete('mechanism')" @toggle="toggle('mechanism')">
          <FormField label="Introduction">
            <textarea v-model="form.mechanism_of_action_intro" rows="4" class="ui-input" placeholder="Enter introduction to mechanism of action"></textarea>
          </FormField>
          <FormField label="Subsections">
            <RepeaterObjectList
              v-model="form.mechanism_subsections"
              item-label="Subsection"
              title-field="title"
              :new-item="() => ({ title: '', intro: '', items: [] })"
            >
              <template #item="{ item }">
                <div class="space-y-3">
                  <FormField label="Title">
                    <input v-model="item.title" type="text" class="ui-input" placeholder="Enter subsection title" />
                  </FormField>
                  <FormField label="Introduction">
                    <textarea v-model="item.intro" rows="2" class="ui-input" placeholder="Enter subsection introduction"></textarea>
                  </FormField>
                  <FormField label="Items">
                    <RepeaterObjectList
                      v-model="item.items"
                      item-label="Item"
                      title-field="item"
                      :new-item="() => ({ item: '', description: '' })"
                    >
                      <template #item="{ item: sub }">
                        <div class="space-y-3">
                          <FormField label="Item">
                            <input v-model="sub.item" type="text" class="ui-input" placeholder="Enter item" />
                          </FormField>
                          <FormField label="Description">
                            <textarea v-model="sub.description" rows="2" class="ui-input" placeholder="Enter description"></textarea>
                          </FormField>
                        </div>
                      </template>
                    </RepeaterObjectList>
                  </FormField>
                </div>
              </template>
            </RepeaterObjectList>
          </FormField>
        </SectionCard>

        <!-- 9. Preclinical -->
        <SectionCard section-id="preclinical" title="Preclinical Research and Findings" :open="isOpen('preclinical')" :complete="isComplete('preclinical')" @toggle="toggle('preclinical')">
          <FormField label="Introduction">
            <textarea v-model="form.preclinical_intro" rows="4" class="ui-input" placeholder="Enter introduction to preclinical research"></textarea>
          </FormField>
          <FormField label="Subsections">
            <RepeaterObjectList
              v-model="form.preclinical_subsections"
              item-label="Subsection"
              title-field="title"
              :new-item="() => ({ title: '', findings: [] })"
            >
              <template #item="{ item }">
                <div class="space-y-3">
                  <FormField label="Title">
                    <input v-model="item.title" type="text" class="ui-input" placeholder="Enter subsection title" />
                  </FormField>
                  <FormField label="Findings">
                    <RepeaterObjectList
                      v-model="item.findings"
                      item-label="Finding"
                      title-field="title"
                      :new-item="() => ({ title: '', content: '' })"
                    >
                      <template #item="{ item: f }">
                        <div class="space-y-3">
                          <FormField label="Title">
                            <input v-model="f.title" type="text" class="ui-input" placeholder="Enter finding title" />
                          </FormField>
                          <FormField label="Content">
                            <textarea v-model="f.content" rows="3" class="ui-input" placeholder="Enter finding content"></textarea>
                          </FormField>
                        </div>
                      </template>
                    </RepeaterObjectList>
                  </FormField>
                </div>
              </template>
            </RepeaterObjectList>
          </FormField>
          <FormField label="Disclaimer">
            <textarea v-model="form.preclinical_disclaimer" rows="3" class="ui-input" placeholder="Enter disclaimer text"></textarea>
          </FormField>
        </SectionCard>

        <!-- 10. Human Use -->
        <SectionCard section-id="human_use" title="Human Use and Evidence" :open="isOpen('human_use')" :complete="isComplete('human_use')" @toggle="toggle('human_use')">
          <FormField label="Introduction">
            <textarea v-model="form.human_use_intro" rows="4" class="ui-input" placeholder="Enter introduction"></textarea>
          </FormField>
          <FormField label="Subsections">
            <RepeaterObjectList
              v-model="form.human_use_subsections"
              item-label="Subsection"
              title-field="title"
              :new-item="() => ({ title: '', entries: [] })"
            >
              <template #item="{ item }">
                <div class="space-y-3">
                  <FormField label="Title">
                    <input v-model="item.title" type="text" class="ui-input" placeholder="Enter subsection title" />
                  </FormField>
                  <FormField label="Entries">
                    <EntriesEditor v-model="item.entries" />
                  </FormField>
                </div>
              </template>
            </RepeaterObjectList>
          </FormField>
        </SectionCard>

        <!-- 11. Regulatory -->
        <SectionCard section-id="regulatory" title="Regulatory Status and Considerations" :open="isOpen('regulatory')" :complete="isComplete('regulatory')" @toggle="toggle('regulatory')">
          <FormField label="Subsections">
            <RepeaterObjectList
              v-model="form.regulatory_subsections"
              item-label="Subsection"
              title-field="title"
              :new-item="() => ({ title: '', entries: [] })"
            >
              <template #item="{ item }">
                <div class="space-y-3">
                  <FormField label="Title">
                    <input v-model="item.title" type="text" class="ui-input" placeholder="Enter subsection title" />
                  </FormField>
                  <FormField label="Entries">
                    <EntriesEditor v-model="item.entries" />
                  </FormField>
                </div>
              </template>
            </RepeaterObjectList>
          </FormField>
          <FormField label="Important Note">
            <textarea v-model="form.regulatory_important_note" rows="3" class="ui-input" placeholder="Enter important note"></textarea>
          </FormField>
        </SectionCard>

        <!-- 12. Potential Applications -->
        <SectionCard section-id="applications" title="Potential Therapeutic Applications" :open="isOpen('applications')" :complete="isComplete('applications')" @toggle="toggle('applications')">
          <FormField label="Introduction">
            <textarea v-model="form.potential_applications_intro" rows="4" class="ui-input" placeholder="Enter introduction"></textarea>
          </FormField>
          <FormField label="Applications">
            <RepeaterObjectList
              v-model="form.potential_applications"
              item-label="Application"
              title-field="title"
              :new-item="() => ({ title: '', description: '' })"
            >
              <template #item="{ item }">
                <div class="space-y-3">
                  <FormField label="Title">
                    <input v-model="item.title" type="text" class="ui-input" placeholder="Enter application title" />
                  </FormField>
                  <FormField label="Description">
                    <textarea v-model="item.description" rows="3" class="ui-input" placeholder="Enter description"></textarea>
                  </FormField>
                </div>
              </template>
            </RepeaterObjectList>
          </FormField>
          <FormField label="Important Context">
            <textarea v-model="form.potential_applications_important_context" rows="4" class="ui-input" placeholder="Enter important context information"></textarea>
          </FormField>
        </SectionCard>

        <!-- 13. Conclusion -->
        <SectionCard section-id="conclusion" title="Conclusion" :open="isOpen('conclusion')" :complete="isComplete('conclusion')" @toggle="toggle('conclusion')">
          <textarea v-model="form.conclusion" rows="8" class="ui-input" placeholder="Enter conclusion"></textarea>
        </SectionCard>

        <!-- 14. References -->
        <SectionCard section-id="references" title="References" :open="isOpen('references')" :complete="isComplete('references')" @toggle="toggle('references')">
          <RepeaterObjectList
            v-model="form.references"
            item-label="Reference"
            title-field="title"
            :new-item="() => ({ authors: '', title: '', description: '', citation: '', links: [] })"
          >
            <template #item="{ item }">
              <div class="space-y-3">
                <FormField label="Authors">
                  <input v-model="item.authors" type="text" class="ui-input" placeholder="Enter authors" />
                </FormField>
                <FormField label="Title">
                  <input v-model="item.title" type="text" class="ui-input" placeholder="Enter title" />
                </FormField>
                <FormField label="Description">
                  <textarea v-model="item.description" rows="2" class="ui-input" placeholder="Enter description"></textarea>
                </FormField>
                <FormField label="Citation">
                  <input v-model="item.citation" type="text" class="ui-input" placeholder="Enter citation" />
                </FormField>
                <FormField label="Links">
                  <RepeaterObjectList
                    v-model="item.links"
                    item-label="Link"
                    title-field="label"
                    :new-item="() => ({ url: '', label: '' })"
                  >
                    <template #item="{ item: link }">
                      <div class="space-y-3">
                        <FormField label="URL">
                          <input v-model="link.url" type="url" class="ui-input" placeholder="https://..." />
                        </FormField>
                        <FormField label="Label">
                          <input v-model="link.label" type="text" class="ui-input" placeholder="e.g. PubMed" />
                        </FormField>
                      </div>
                    </template>
                  </RepeaterObjectList>
                </FormField>
              </div>
            </template>
          </RepeaterObjectList>
        </SectionCard>

        <!-- 15. Publishing -->
        <SectionCard section-id="publishing" title="Publishing" :open="isOpen('publishing')" :complete="isComplete('publishing')" @toggle="toggle('publishing')">
          <FormField label="Category" :required="true">
            <select v-model="form.product_category_id" class="ui-input">
              <option :value="null">-- Select Category --</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </FormField>
          <FormField label="Status" :required="true">
            <select v-model="form.status" class="ui-input">
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
          </FormField>
          <div v-if="entry?.published_at" class="text-[12px] text-[color:var(--color-ink-muted)]">
            Published: <span class="font-mono">{{ entry.published_at }}</span>
          </div>
        </SectionCard>

        <!-- 16. SEO -->
        <SectionCard section-id="seo" title="SEO Data" :open="isOpen('seo')" :complete="isComplete('seo')" @toggle="toggle('seo')">
          <FormField label="Page Title">
            <input v-model="form.seo_page_title" type="text" class="ui-input" placeholder="Enter page title for SEO" />
          </FormField>
          <FormField label="Description">
            <textarea v-model="form.seo_description" rows="3" class="ui-input" placeholder="Enter meta description for SEO"></textarea>
          </FormField>
          <FormField label="OG:Title">
            <input v-model="form.seo_og_title" type="text" class="ui-input" placeholder="Enter Open Graph title" />
          </FormField>
          <FormField label="OG:Image">
            <input v-model="form.seo_og_image" type="url" class="ui-input" placeholder="Enter Open Graph image URL" />
          </FormField>
          <FormField label="OG:Description">
            <textarea v-model="form.seo_og_description" rows="3" class="ui-input" placeholder="Enter Open Graph description"></textarea>
          </FormField>
        </SectionCard>

        <div class="pt-2 pb-8 flex items-center justify-end gap-3">
          <a href="/admin/encyclopedia-entries" class="h-9 px-4 inline-flex items-center text-[13px] font-medium text-[color:var(--color-ink-muted)] border border-[color:var(--color-hairline)] hover:text-[color:var(--color-ink)]">Cancel</a>
          <button type="submit" :disabled="form.processing" class="h-9 px-5 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm disabled:opacity-50">
            {{ form.processing ? 'Saving...' : (entry ? 'Save' : 'Create') }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, onMounted, onBeforeUnmount, reactive, ref, watch, h, nextTick } from 'vue'
import AdminLayout from './Layout.vue'
import FormField from '@/components/admin/FormField.vue'
import SectionCard from '@/components/admin/SectionCard.vue'
import RepeaterStringList from '@/components/admin/RepeaterStringList.vue'
import RepeaterObjectList from '@/components/admin/RepeaterObjectList.vue'

const props = defineProps({
  entry: Object,
  categories: { type: Array, default: () => [] },
})
const page = usePage()

// --- Small inline component: entries editor for Human Use / Regulatory ---
const EntriesEditor = {
  props: { modelValue: { type: Array, required: true } },
  emits: ['update:modelValue'],
  setup(p, { emit }) {
    const list = computed({
      get: () => p.modelValue || [],
      set: (v) => emit('update:modelValue', v),
    })
    const add = (type) => emit('update:modelValue', [...list.value, { type, value: '' }])
    const remove = (i) => {
      const next = [...list.value]
      next.splice(i, 1)
      emit('update:modelValue', next)
    }
    const move = (i, dir) => {
      const j = i + dir
      if (j < 0 || j >= list.value.length) return
      const next = [...list.value]
      ;[next[i], next[j]] = [next[j], next[i]]
      emit('update:modelValue', next)
    }
    return () =>
      h('div', { class: 'space-y-2' }, [
        ...list.value.map((entry, i) =>
          h('div', { key: i, class: 'group flex items-start gap-2 border border-[color:var(--color-hairline)] bg-white p-2' }, [
            h('div', { class: 'flex flex-col text-[color:var(--color-ink-subtle)]' }, [
              h('button', {
                type: 'button',
                onClick: () => move(i, -1),
                class: 'px-1 hover:text-[color:var(--color-ink-muted)] disabled:opacity-30',
                disabled: i === 0,
                title: 'Move up',
              }, '▲'),
              h('button', {
                type: 'button',
                onClick: () => move(i, 1),
                class: 'px-1 hover:text-[color:var(--color-ink-muted)] disabled:opacity-30',
                disabled: i === list.value.length - 1,
                title: 'Move down',
              }, '▼'),
            ]),
            h('span', { class: 'flex-shrink-0 text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 mt-1.5 rounded ' + (entry.type === 'item' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600') }, entry.type),
            entry.type === 'item'
              ? h('input', {
                  type: 'text',
                  value: entry.value,
                  onInput: (e) => { entry.value = e.target.value },
                  class: 'ui-input flex-1',
                  placeholder: 'Enter item',
                })
              : h('textarea', {
                  value: entry.value,
                  onInput: (e) => { entry.value = e.target.value },
                  rows: 2,
                  class: 'ui-input flex-1',
                  placeholder: 'Enter content',
                }),
            h('button', {
              type: 'button',
              onClick: () => remove(i),
              class: 'flex-shrink-0 p-1 mt-1 text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-danger)]',
              title: 'Remove',
            }, [
              h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [
                h('polyline', { points: '3 6 5 6 21 6' }),
                h('path', { d: 'M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6' }),
              ]),
            ]),
          ])
        ),
        h('div', { class: 'flex gap-2' }, [
          h('button', {
            type: 'button',
            onClick: () => add('item'),
            class: 'flex-1 py-2 text-[12px] font-medium text-[color:var(--color-ink-muted)] border border-dashed border-[color:var(--color-hairline)] hover:border-[color:var(--color-accent-500)] hover:text-[color:var(--color-accent-600)]',
          }, '+ Add Item'),
          h('button', {
            type: 'button',
            onClick: () => add('content'),
            class: 'flex-1 py-2 text-[12px] font-medium text-[color:var(--color-ink-muted)] border border-dashed border-[color:var(--color-hairline)] hover:border-[color:var(--color-accent-500)] hover:text-[color:var(--color-accent-600)]',
          }, '+ Add Content'),
        ]),
      ])
  },
}

// --- Data normalization (kept from original) ---
const normalizeMechanismItems = (items) => {
  if (!items || !Array.isArray(items)) return []
  return items.map((item) => {
    if (typeof item === 'string') return { item, description: '' }
    return { item: item.item || '', description: item.description || '' }
  })
}
const normalizeMechanismSubsections = (subs) => {
  if (!subs || !Array.isArray(subs)) return []
  return subs.map((s) => ({ ...s, items: normalizeMechanismItems(s.items) }))
}
const normalizeSubsections = (subs) => {
  if (!subs || !Array.isArray(subs)) return []
  return subs.map((sub) => {
    const entries = []
    if (sub.entries && Array.isArray(sub.entries)) {
      sub.entries.forEach((e) => {
        if (e && (e.value || e.value === '')) entries.push({ type: e.type || 'item', value: e.value || '' })
      })
    }
    if (sub.items && Array.isArray(sub.items)) {
      sub.items.forEach((it) => {
        if (it && (typeof it === 'string' ? it.trim() !== '' : true))
          entries.push({ type: 'item', value: typeof it === 'string' ? it : (it.value || '') })
      })
    }
    if (sub.contents && Array.isArray(sub.contents)) {
      sub.contents.forEach((c) => {
        if (c && (typeof c === 'string' ? c.trim() !== '' : true))
          entries.push({ type: 'content', value: typeof c === 'string' ? c : (c.value || '') })
      })
    }
    if (sub.content && typeof sub.content === 'string' && sub.content.trim() !== '') {
      entries.push({ type: 'content', value: sub.content })
    }
    return { title: sub.title || '', entries }
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
  seo_page_title: props.entry?.seo_page_title || '',
  seo_description: props.entry?.seo_description || '',
  seo_og_title: props.entry?.seo_og_title || '',
  seo_og_description: props.entry?.seo_og_description || '',
  seo_og_image: props.entry?.seo_og_image || '',
  _token: page.props.csrf_token,
})

// --- Section metadata + completion ---
const sections = [
  { id: 'basic',        label: 'Basic Information' },
  { id: 'molecular',    label: 'Molecular Info' },
  { id: 'amino',        label: 'Amino Acid Sequence' },
  { id: 'key_points',   label: 'Key Points' },
  { id: 'overview',     label: 'Overview' },
  { id: 'areas',        label: 'Areas of Research' },
  { id: 'background',   label: 'Background' },
  { id: 'mechanism',    label: 'Mechanism of Action' },
  { id: 'preclinical',  label: 'Preclinical Research' },
  { id: 'human_use',    label: 'Human Use & Evidence' },
  { id: 'regulatory',   label: 'Regulatory' },
  { id: 'applications', label: 'Potential Applications' },
  { id: 'conclusion',   label: 'Conclusion' },
  { id: 'references',   label: 'References' },
  { id: 'publishing',   label: 'Publishing' },
  { id: 'seo',          label: 'SEO Data' },
]

const hasText = (v) => typeof v === 'string' && v.trim() !== ''
const arrayHasContent = (arr, isFilled) => Array.isArray(arr) && arr.some(isFilled)

const isComplete = (id) => {
  switch (id) {
    case 'basic':        return !!form.education_tag || hasText(form.peptide_full_name) || hasText(form.research_url) || arrayHasContent(form.tags, hasText)
    case 'molecular':    return hasText(form.molecular_formula) || hasText(form.molecular_weight) || hasText(form.cas_registry_number)
    case 'amino':        return hasText(form.amino_acid_sequence) || hasText(form.amino_acid_net_charge) || hasText(form.amino_acid_hydrophobic) || hasText(form.amino_acid_stability) || hasText(form.amino_acid_solubility)
    case 'key_points':   return arrayHasContent(form.key_points, hasText)
    case 'overview':     return hasText(form.overview)
    case 'areas':        return hasText(form.areas_of_research_intro) || arrayHasContent(form.areas_of_research, (a) => hasText(a?.name) || hasText(a?.description))
    case 'background':   return hasText(form.background)
    case 'mechanism':    return hasText(form.mechanism_of_action_intro) || arrayHasContent(form.mechanism_subsections, (s) => hasText(s?.title) || hasText(s?.intro) || arrayHasContent(s?.items, (it) => hasText(it?.item) || hasText(it?.description)))
    case 'preclinical':  return hasText(form.preclinical_intro) || hasText(form.preclinical_disclaimer) || arrayHasContent(form.preclinical_subsections, (s) => hasText(s?.title) || arrayHasContent(s?.findings, (f) => hasText(f?.title) || hasText(f?.content)))
    case 'human_use':    return hasText(form.human_use_intro) || arrayHasContent(form.human_use_subsections, (s) => hasText(s?.title) || arrayHasContent(s?.entries, (e) => hasText(e?.value)))
    case 'regulatory':   return hasText(form.regulatory_important_note) || arrayHasContent(form.regulatory_subsections, (s) => hasText(s?.title) || arrayHasContent(s?.entries, (e) => hasText(e?.value)))
    case 'applications': return hasText(form.potential_applications_intro) || hasText(form.potential_applications_important_context) || arrayHasContent(form.potential_applications, (a) => hasText(a?.title) || hasText(a?.description))
    case 'conclusion':   return hasText(form.conclusion)
    case 'references':   return arrayHasContent(form.references, (r) => hasText(r?.title) || hasText(r?.authors) || hasText(r?.citation) || hasText(r?.description))
    case 'publishing':   return !!form.product_category_id
    case 'seo':          return hasText(form.seo_page_title) || hasText(form.seo_description) || hasText(form.seo_og_title) || hasText(form.seo_og_description) || hasText(form.seo_og_image)
  }
  return false
}

// --- Collapse state ---
const openMap = reactive(Object.fromEntries(sections.map((s) => [s.id, true])))
const isOpen = (id) => openMap[id]
const toggle = (id) => { openMap[id] = !openMap[id] }

// --- Active section + scroll ---
const activeSection = ref(sections[0].id)

const scrollToSection = async (id) => {
  if (!openMap[id]) openMap[id] = true
  await nextTick()
  const el = document.getElementById(id)
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  activeSection.value = id
}

let observer = null
onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((e) => e.isIntersecting)
        .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)
      if (visible[0]) activeSection.value = visible[0].target.id
    },
    { rootMargin: '-100px 0px -60% 0px', threshold: 0 }
  )
  sections.forEach((s) => {
    const el = document.getElementById(s.id)
    if (el) observer.observe(el)
  })

  window.addEventListener('beforeunload', beforeUnloadHandler)
})
onBeforeUnmount(() => {
  if (observer) observer.disconnect()
  window.removeEventListener('beforeunload', beforeUnloadHandler)
})

// --- Dirty tracking ---
const isDirty = computed(() => form.isDirty)
const justSaved = ref(false)
const beforeUnloadHandler = (e) => {
  if (isDirty.value) {
    e.preventDefault()
    e.returnValue = ''
    return ''
  }
}

// --- Submit ---
const submit = (opts = {}) => {
  if (!form.title || form.title.trim() === '') form.title = 'Untitled Entry'

  form.tags = form.tags.filter((t) => (t || '').trim() !== '')
  form.key_points = form.key_points.filter((p) => (p || '').trim() !== '')
  form.areas_of_research = form.areas_of_research.filter((a) => (a.name || '').trim() !== '' || (a.description || '').trim() !== '')

  form.mechanism_subsections = form.mechanism_subsections.filter((s) => (s.title || '').trim() !== '' || (s.intro || '').trim() !== '' || (s.items && s.items.length > 0))
  form.mechanism_subsections.forEach((s) => {
    if (s.items) s.items = s.items.filter((i) => (i.item || '').trim() !== '' || (i.description || '').trim() !== '')
  })

  form.preclinical_subsections = form.preclinical_subsections.filter((s) => (s.title || '').trim() !== '' || (s.findings && s.findings.length > 0))
  form.preclinical_subsections.forEach((s) => {
    if (s.findings) s.findings = s.findings.filter((f) => (f.title || '').trim() !== '' || (f.content || '').trim() !== '')
  })

  const scrubEntries = (arr) => {
    arr.forEach((s) => {
      if (!s.entries || !Array.isArray(s.entries)) s.entries = []
      s.entries = s.entries
        .filter((e) => e && e.value && e.value.trim() !== '')
        .map((e) => ({ type: e.type || 'item', value: e.value || '' }))
    })
  }
  scrubEntries(form.human_use_subsections)
  form.human_use_subsections = form.human_use_subsections.filter((s) => (s.title || '').trim() !== '' || (s.entries && s.entries.length > 0))
  scrubEntries(form.regulatory_subsections)
  form.regulatory_subsections = form.regulatory_subsections.filter((s) => (s.title || '').trim() !== '' || (s.entries && s.entries.length > 0))

  form.potential_applications = form.potential_applications.filter((a) => (a.title || '').trim() !== '' || (a.description || '').trim() !== '')
  form.references = form.references.filter((r) => (r.authors || '').trim() !== '' || (r.title || '').trim() !== '' || (r.description || '').trim() !== '' || (r.citation || '').trim() !== '')
  form.references.forEach((r) => {
    if (r.links) r.links = r.links.filter((l) => (l.url || '').trim() !== '' || (l.label || '').trim() !== '')
  })

  form.seo_page_title = form.seo_page_title?.trim() || null
  form.seo_description = form.seo_description?.trim() || null
  form.seo_og_title = form.seo_og_title?.trim() || null
  form.seo_og_description = form.seo_og_description?.trim() || null
  form.seo_og_image = form.seo_og_image?.trim() || null

  form._token = page.props.csrf_token

  const url = props.entry ? `/admin/encyclopedia-entries/${props.entry.id}` : '/admin/encyclopedia-entries'
  form.post(url, {
    preserveScroll: true,
    onSuccess: () => {
      justSaved.value = true
      setTimeout(() => { justSaved.value = false }, 2500)
      if (opts.thenPreview && props.entry) {
        const slug = props.entry.slug || props.entry.id
        window.open(`/encyclopedia/${slug}`, '_blank')
      }
    },
  })
}

const saveAndPreview = () => submit({ thenPreview: true })
</script>
