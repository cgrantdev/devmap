<template>
  <Head :title="seoTitle">
    <meta name="description" :content="seoDescription" />
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article" />
    <meta property="og:url" :content="url" />
    <meta property="og:title" :content="ogTitle" />
    <meta property="og:description" :content="ogDescription" />
    <meta v-if="ogImage" property="og:image" :content="ogImage" />
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" :content="url" />
    <meta name="twitter:title" :content="ogTitle" />
    <meta name="twitter:description" :content="ogDescription" />
    <meta v-if="ogImage" name="twitter:image" :content="ogImage" />
    
    <!-- Canonical URL -->
    <link rel="canonical" :href="canonical" />
  </Head>
  <FrontLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <!-- Back Button -->
          <button
            @click="goBack"
            class="text-gray-600 hover:text-gray-900 flex items-center gap-2 mb-6 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-4 h-4" aria-hidden="true">
              <path d="m12 19-7-7 7-7"></path>
              <path d="M19 12H5"></path>
            </svg>
            <span>Back to Research Library</span>
          </button>
    
          <!-- Tags -->
          <div class="flex items-center gap-2 mb-4">
            <!-- Peptide Tag -->
            <span
              v-if="peptide"
              class="text-sm px-3 py-1 rounded-full bg-blue-100 text-blue-800 border border-blue-200"
            >
              {{ peptide }}
            </span>
            <!-- Evidence Level Tag -->
            <span
              v-if="evidenceLevel"
              :class="[
                'text-sm px-3 py-1 rounded-full border',
                evidenceLevel === 'High Evidence'
                  ? 'bg-green-100 text-green-800 border-green-200'
                  : evidenceLevel === 'Medium Evidence'
                  ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
                  : 'bg-gray-100 text-gray-800 border-gray-200'
              ]"
            >
              {{ evidenceLevel }}
            </span>
          </div>
    
          <!-- Title -->
          <h1 class="text-4xl text-gray-900 mb-6">
            {{ title }}
          </h1>
    
          <!-- Metadata Boxes -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Journal -->
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center gap-2 text-gray-600 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-4 h-4" aria-hidden="true">
                  <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path>
                  <path d="M14 2v5a1 1 0 0 0 1 1h5"></path>
                  <path d="M10 9H8"></path>
                  <path d="M16 13H8"></path>
                  <path d="M16 17H8"></path>
                </svg>
                <span class="text-sm">Journal</span>
              </div>
              <p class="text-gray-900">{{ journalType || 'Not specified' }}</p>
            </div>
    
            <!-- Published -->
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center gap-2 text-gray-600 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-4 h-4" aria-hidden="true">
                  <path d="M8 2v4"></path>
                  <path d="M16 2v4"></path>
                  <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                  <path d="M3 10h18"></path>
                </svg>
                <span class="text-sm">Published</span>
              </div>
              <p class="text-gray-900">{{ date || 'Not published' }}</p>
            </div>
    
            <!-- Study Type -->
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex items-center gap-2 text-gray-600 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-4 h-4" aria-hidden="true">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.128a4 4 0 0 1 0 7.744"></path>
                </svg>
                  <span class="text-sm">Study Type</span>
              </div>
              <p class="text-gray-900">{{ studyType || 'Not specified' }}</p>
            </div>
          </div>
    
          <!-- View on PubMed Button -->
          <a
            :href="pubmedUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link w-4 h-4" aria-hidden="true">
              <path d="M15 3h6v6"></path>
              <path d="M10 14 21 3"></path>
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
            </svg>
            View on PubMed
          </a>
        </div>
      </div>


    
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Study Summary -->
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
          <div class="flex items-center gap-2 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-5 h-5 text-blue-600" aria-hidden="true">
              <path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path>
              <circle cx="12" cy="8" r="6"></circle>
            </svg>
            <h2 class="text-2xl text-gray-900">Study Summary</h2>
          </div>
          <p class="text-lg text-gray-700 leading-relaxed" v-if="studySummary">
            {{ studySummary }}
          </p>
          <p v-else class="text-lg text-gray-500 italic">No study summary available.</p>
        </div>

        <!-- Research Details -->
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
          <h2 class="text-2xl text-gray-900 mb-6">Research Details</h2>
          <div class="space-y-6">
            <div v-if="background">
              <h3 class="text-xl text-gray-900 mb-3">Background</h3>
              <p class="text-gray-700 leading-relaxed">
                {{ background }}
              </p>
            </div>
            <div v-if="keyFindings && keyFindings.length > 0">
              <h3 class="text-xl text-gray-900 mb-3">Key Findings</h3>
              <ul class="list-disc pl-6 space-y-2 text-gray-700">
                <li v-for="(finding, index) in keyFindings" :key="index">{{ finding }}</li>
              </ul>
            </div>
            <div v-if="methodology">
              <h3 class="text-xl text-gray-900 mb-3">Methodology</h3>
              <p class="text-gray-700 leading-relaxed">
                {{ methodology }}
              </p>
            </div>
            <div v-if="clinicalImplications">
              <h3 class="text-xl text-gray-900 mb-3">Clinical Implications</h3>
              <p class="text-gray-700 leading-relaxed">
                {{ clinicalImplications }}
              </p>
            </div>
            <div v-if="limitations">
              <h3 class="text-xl text-gray-900 mb-3">Limitations</h3>
              <p class="text-gray-700 leading-relaxed">
                {{ limitations }}
              </p>
            </div>
          </div>
          <div 
            class="mt-8 rounded-lg p-6 border-2"
            :class="[
              evidenceLevel === 'High Evidence'
                ? 'bg-green-100 text-green-800 border-green-200'
                : evidenceLevel === 'Medium Evidence'
                ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
                : 'bg-gray-100 text-gray-800 border-gray-200'
            ]"
          >
            <div class="flex items-start gap-3">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-5 h-5 mt-1" aria-hidden="true">
                <path d="M16 7h6v6"></path>
                <path d="m22 7-8.5 8.5-5-5L2 17"></path>
              </svg>
              <div>                
                <h4 class="font-semibold mb-2">{{ evidenceLevel || 'Evidence Level' }}</h4>
                <p class="text-sm">
                  <span v-if="evidenceLevel === 'High Evidence'">
                    This study represents high-quality evidence from well-designed clinical trials with robust methodology and statistical significance.
                  </span>
                  <span v-else-if="evidenceLevel === 'Medium Evidence'">
                    This study provides moderate-quality evidence from clinical research with some methodological considerations.
                  </span>
                  <span v-else-if="evidenceLevel === 'Low Evidence'">
                    This study provides preliminary evidence from clinical research. Please review the methodology and findings carefully.
                  </span>
                  <span v-else>
                    This study provides evidence from clinical research. Please review the methodology and findings carefully.
                  </span>
                </p>
              </div>
            </div>
          </div>          
        </div>

        <!-- Research Categories -->
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
          <h3 class="text-gray-900 mb-4">Research Categories</h3>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="tag in tags"
              :key="tag"
              class="text-sm bg-blue-50 text-blue-700 px-3 py-2 rounded-lg border border-blue-200 hover:bg-blue-100 cursor-pointer transition-colors"
            >
              {{ tag }}
            </span>
          </div>
        </div>

        <!-- Research Disclaimer -->
        <div class="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-6">
          <h4 class="text-yellow-900 mb-2">Research Disclaimer</h4>
          <p class="text-sm text-yellow-800">
            This research summary is provided for informational and educational purposes only. It should not be interpreted as medical advice or a recommendation for treatment. 
            Always consult with qualified healthcare professionals before making any decisions regarding peptide therapies or medical treatments. Individual results may vary, and not all findings may be applicable to every patient population.
          </p>
        </div>

        <!-- Related Research -->
        <!-- <div class="mt-12">
          <h2 class="text-2xl text-gray-900 mb-6">Related Research</h2>
          <div class="grid grid-cols-1 gap-4">


          </div>
        </div> -->
      </div>
    </div>
  </FrontLayout>
</template>

<script setup>
import { computed, watchEffect } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'

const props = defineProps({
  id: {
    type: [Number, String],
    required: true
  },
  peptide: {
    type: String,
    default: null
  },
  evidenceLevel: {
    type: String,
    default: null
  },
  title: {
    type: String,
    required: true
  },
  studySummary: {
    type: String,
    default: null
  },
  journalType: {
    type: String,
    default: null
  },
  date: {
    type: String,
    default: null
  },
  studyType: {
    type: String,
    default: null
  },
  background: {
    type: String,
    default: null
  },
  keyFindings: {
    type: Array,
    default: () => []
  },
  methodology: {
    type: String,
    default: null
  },
  clinicalImplications: {
    type: String,
    default: null
  },
  limitations: {
    type: String,
    default: null
  },
  tags: {
    type: Array,
    default: () => []
  },
  pubmedUrl: {
    type: String,
    default: '#'
  },
  seo: {
    type: Object,
    default: () => ({
      title: null,
      description: null,
      og_title: null,
      og_description: null,
      og_image: null,
      image: null,
      url: null,
      canonical: null,
    })
  }
})

const page = usePage()

// Computed values for reactive SEO updates (separate from displayed title/description)
const seoTitle = computed(() => {
  if (props.seo?.title) {
    return props.seo.title
  }
  const siteName = page.props.site_name || 'Peptidemap'
  return `${props.title || 'Research'} - ${siteName}`
})

const seoDescription = computed(() => {
  if (props.seo?.description) {
    return props.seo.description
  }
  if (props.studySummary) {
    const desc = props.studySummary.replace(/\s+/g, ' ').trim()
    return desc.length > 160 ? desc.substring(0, 160) + '...' : desc
  }
  return `Research study: ${props.title || 'this research'} on Peptidemap.`
})

const url = computed(() => {
  return props.seo?.url || page.url
})

const ogTitle = computed(() => {
  return props.seo?.og_title || seoTitle.value
})

const ogDescription = computed(() => {
  return props.seo?.og_description || seoDescription.value
})

const ogImage = computed(() => {
  return props.seo?.og_image || props.seo?.image || null
})

const canonical = computed(() => {
  return props.seo?.canonical || url.value
})

// Watch for SEO changes and update document meta tags
watchEffect(() => {
  document.title = seoTitle.value
  let metaDescription = document.querySelector('meta[name="description"]')
  if (!metaDescription) {
    metaDescription = document.createElement('meta')
    metaDescription.setAttribute('name', 'description')
    document.head.appendChild(metaDescription)
  }
  metaDescription.setAttribute('content', seoDescription.value)

  let canonicalLink = document.querySelector('link[rel="canonical"]')
  if (!canonicalLink) {
    canonicalLink = document.createElement('link')
    canonicalLink.setAttribute('rel', 'canonical')
    document.head.appendChild(canonicalLink)
  }
  canonicalLink.setAttribute('href', canonical.value)

  const updateMetaTag = (property, content) => {
    if (!content) return
    let meta = document.querySelector(`meta[property="${property}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('property', property)
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', content)
  }

  updateMetaTag('og:title', ogTitle.value)
  updateMetaTag('og:description', ogDescription.value)
  updateMetaTag('og:url', url.value)
  if (ogImage.value) {
    updateMetaTag('og:image', ogImage.value)
  }
})

const goBack = () => {
  router.visit('/news?nav=research')
}
</script>
