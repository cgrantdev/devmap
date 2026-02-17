<template>
  <FrontLayout>
    <div class="min-h-screen bg-gray-50">
      <!-- Header Section -->
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <!-- Back Button -->
          <button 
            @click="router.visit('/encyclopedia')"
            class="flex items-center gap-2 text-slate-600 hover:text-slate-900 mb-6 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-4 h-4" aria-hidden="true">
              <path d="m12 19-7-7 7-7"></path>
              <path d="M19 12H5"></path>
            </svg>
            Back to Education Hub
          </button>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
            <!-- Left Column: Content -->
            <div>
              <!-- Category Tag -->
              <div class="inline-block bg-slate-100 text-slate-700 px-3 py-1 rounded-full text-sm mb-4">
                  {{ categoryTag }}
              </div>

              <!-- Title and Subtitle -->
              <h1 class="text-5xl text-gray-900 mb-3">{{ name }}</h1>
              <p v-if="subtitle" class="text-xl text-slate-600 mb-6">{{ subtitle }}</p>

              <!-- Description -->
              <p class="text-lg text-gray-700 leading-relaxed">
                {{ description }}
              </p>
            </div>

            <!-- Right Column: Image -->
            <div class="rounded-lg overflow-hidden border border-gray-200">
              <img 
                v-if="image" 
                :src="image" 
                :alt="name"
                class="w-full h-64 lg:h-96 object-cover"
              />
              <div v-else class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                  <path d="M12 7v14"></path>
                  <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Left Column: Main Content -->
          <div class="lg:col-span-2 space-y-8">
            <!-- Research Applications -->
            <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap w-6 h-6 text-blue-600" aria-hidden="true">
                  <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                </svg>
                Research Applications
              </h2>
              <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <li 
                  v-for="(benefit, index) in keyBenefits" 
                  :key="index"
                  class="flex items-start gap-2"
                >
                  <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <div class="w-2 h-2 rounded-full bg-green-600"></div>
                  </div>
                  <span class="text-gray-700">{{ benefit }}</span>
                </li>
              </ul>
            </section>

            <!-- Common Use Cases -->
            <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-6 h-6 text-purple-600" aria-hidden="true">
                  <path d="M12 7v14"></path>
                  <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
                </svg>
                Common Use Cases
              </h2>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="(useCase, index) in commonUseCases" 
                  :key="index"
                  class="bg-purple-50 text-purple-700 px-3 py-2 rounded-lg text-sm border border-purple-200"
                >
                  {{ useCase }}
                </span>
              </div>
            </section>


            <!-- How It Works -->
            <section v-if="howItWorks" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-6 h-6 text-blue-600" aria-hidden="true">
                  <path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path>
                  <circle cx="12" cy="8" r="6"></circle>
                </svg>
                How It Works
              </h2>
              <div class="text-gray-700 whitespace-pre-line leading-relaxed">
                {{ howItWorks }}
              </div>
              <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center gap-2 text-blue-900">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield w-5 h-5" aria-hidden="true">
                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                  </svg>
                  <span class="font-medium">{{ researchStudies }}+ research studies available</span>
                </div>
              </div>
            </section>

            <!-- Dosage & Administration -->
            <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-syringe w-6 h-6 text-green-600" aria-hidden="true">
                  <path d="m18 2 4 4"></path>
                  <path d="m17 7 3-3"></path>
                  <path d="M19 9 8.7 19.3c-1 1-2.5 1-3.4 0l-.6-.6c-1-1-1-2.5 0-3.4L15 5"></path>
                  <path d="m9 11 4 4"></path>
                  <path d="m5 19-3 3"></path>
                  <path d="m14 4 6 6"></path>
                </svg>
                Dosage & Administration
              </h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-sm text-gray-600 mb-1">Typical Dosage:</div>
                  <div class="text-lg text-gray-900">{{ dosage.typicalDosage }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-sm text-gray-600 mb-1">Frequency:</div>
                  <div class="text-lg text-gray-900">{{ dosage.frequency }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-sm text-gray-600 mb-1">Administration:</div>
                  <div class="text-lg text-gray-900">{{ dosage.administration }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-sm text-gray-600 mb-1">Cycle Duration:</div>
                  <div class="text-sm text-gray-900">{{ dosage.cycleDuration }}</div>
                </div>
              </div>
            </section>

            <!-- Safety Information -->
            <section class="bg-white rounded-lg shadow-sm p-6">
              <h2 class="text-2xl text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-triangle-alert w-6 h-6 text-yellow-600" aria-hidden="true">
                  <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                  <path d="M12 9v4"></path>
                  <path d="M12 17h.01"></path>
                </svg>
                Safety Information
              </h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Side Effects -->
                <div>
                  <h3 class="text-lg text-gray-900 mb-2">Possible Side Effects</h3>
                  <ul class="space-y-2">
                    <li 
                      v-for="(effect, index) in safetyInfo.sideEffects" 
                      :key="index"
                      class="flex items-start gap-2 text-sm text-gray-700"
                    >
                      <div class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0 mt-1.5"></div>
                      {{ effect }}
                    </li>
                  </ul>
                </div>

                <!-- Contraindications -->
                <div>
                  <h3 class="text-lg text-gray-900 mb-2">Contraindications</h3>
                  <ul class="space-y-2">
                    <li 
                      v-for="(contra, index) in safetyInfo.contraindications" 
                      :key="index"
                      class="flex items-start gap-2 text-sm text-gray-700"
                    >
                      <div class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0 mt-1.5"></div>
                      {{ contra }}
                    </li>
                  </ul>
                </div>
              </div>
            </section>

            <!-- Stacking Recommendations -->
            <section v-if="stackingRecommendations && stackingRecommendations.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl text-gray-900 mb-4">Stacking Recommendations</h2>
              <p class="text-gray-700 mb-4">
                Stacking {{ name }} with other peptides can enhance effects.
              </p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div 
                  v-for="(stack, index) in stackingRecommendations" 
                  :key="index"
                  class="bg-gradient-to-br from-purple-50 to-blue-50 border border-purple-200 rounded-lg p-4 hover:shadow-lg transition-all text-left group"
                  @click="router.visit(`/encyclopedia/${stack.slug}`)"
                >
                  <h3 class="text-lg text-gray-900 mb-1 group-hover:text-purple-600 transition-colors">{{ stack.name }}</h3>
                  <p class="text-sm text-gray-600 mb-2">{{ stack.subtitle }}</p>
                  <!-- <p class="text-sm text-gray-700 mb-3">{{ stack.description }}</p> -->
                  <a class="flex items-center text-purple-600 text-sm">
                    Learn more
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" aria-hidden="true">
                      <path d="m9 18 6-6-6-6"></path>
                    </svg>
                  </a>
                </div>
              </div>
            </section>

            <!-- Frequently Asked Questions -->
            <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl text-gray-900 mb-4">Frequently Asked Questions</h2>
              <div class="space-y-4">
                <div 
                  v-for="(faq, index) in faqs" 
                  :key="index"
                  class="border-b border-gray-200 last:border-b-0 pb-4 last:pb-0"
                >
                  <h3 class="text-lg text-gray-900 mb-2">{{ faq.question }}</h3>
                  <p class="text-gray-700">{{ faq.answer }}</p>
                </div>
              </div>
            </section>

            <!-- User Experiences -->
            <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl text-gray-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-6 h-6 text-blue-600" aria-hidden="true">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                  <path d="M16 3.128a4 4 0 0 1 0 7.744"></path>
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                </svg>                
                User Experiences
              </h2>
              <div class="space-y-4">
                <div 
                  v-for="(experience, index) in userExperiences" 
                  :key="index"
                  class="border-b border-gray-200 last:border-b-0 pb-4 last:pb-0"
                >
                  <div class="flex items-center gap-2 mb-2">
                    <div class="flex items-center">
                      <svg 
                        v-for="i in 5" 
                        :key="i"
                        xmlns="http://www.w3.org/2000/svg" 
                        width="16" 
                        height="16" 
                        viewBox="0 0 24 24" 
                        fill="currentColor" 
                        :class="i <= experience.rating ? 'text-yellow-400' : 'text-gray-300'"
                      >
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                      </svg>
                    </div>
                    <span class="text-sm text-gray-600">{{ experience.author }}</span>
                    <span v-if="experience.verified" class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded">
                      Verified Purchase
                    </span>
                  </div>
                  <p class="text-gray-700">{{ experience.review }}</p>
                  <div class="text-xs text-gray-500 mt-2">12/9/2024</div>
                </div>
              </div>
            </section>
          </div>

          <!-- Right Column: Sidebar -->
          <div class="space-y-6">
            <!-- Quick Facts -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 class="text-lg text-gray-900 mb-4">Quick Facts</h2>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">Half-Life:</span>
                  <span class="text-gray-900">{{ quickFacts.halfLife }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Bioavailability: </span>
                  <span class="text-gray-900">{{ quickFacts.bioavailability }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Storage: </span>
                  <span class="text-gray-900 text-right">{{ quickFacts.storage }}</span>
                </div>
                <div class="pt-3 border-t border-gray-200">
                  <span class="text-xs px-2 py-1 rounded bg-purple-100 text-purple-800">
                    {{ quickFacts.researchLevel }}
                  </span>
                </div>                
              </div>
            </div>


            <!-- Legal Status -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <h3 class="text-sm text-yellow-900 mb-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-triangle-alert w-4 h-4" aria-hidden="true">
                  <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                  <path d="M12 9v4"></path>
                  <path d="M12 17h.01"></path>
                </svg> 
                Legal Status
              </h3>
              <p class="text-sm text-yellow-800">
                Legal for research purposes. Not FDA approved for human use.
              </p>
            </div>

            <!-- Shop -->
            <div class="bg-gradient-to-br from-blue-600 to-purple-700 text-white rounded-lg p-6">
              <h3 class="text-lg mb-2">Shop {{ name }}</h3>
              <p class="text-sm text-blue-100 mb-4">
                Compare prices from {{ products.length }} verified vendors
              </p>
              <button 
                @click="router.visit(`/products?category=${slug}`)"
                class="w-full bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors flex items-center justify-center gap-2"
              >
                View Products >
              </button>
            </div>
          </div>
        </div>

        <!-- Available Products -->
        <section class="mt-12">
          <h2 class="text-2xl text-gray-900 mb-6">Available Products</h2>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <ProductCard
              v-for="product in products.slice(0, 4)"
              :key="product.id"
              :name="product.name"
              :image-url="product.image_url"
              :price="product.price"
              :discount-price="product.discount_price"
              :brand-name="product.brand?.name"
              :rating-average="product.rating_average"
              :rating-count="product.rating_count"
              :category-name="product.category?.name || categoryTag"
              :size-mg="product.size_mg"
              :availability="product.availability"
              :to="`/product/${product.slug}/${product.id}`"
            />
          </div>
          <div class="text-center mt-6">
            <button 
              v-if="products.length > 4"
              @click="router.visit(`/products?category=${slug}`)"
              class="text-blue-600 hover:text-blue-700 flex items-center gap-2 mx-auto"
            >
              View all {{ products.length }} products >
            </button>
          </div>
        </section>
      </div>
    </div>
  </FrontLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import ProductCard from '@/components/ProductCard.vue'

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
  description: {
    type: String,
    default: ''
  },
  image: {
    type: String,
    default: null
  },
  categoryTag: {
    type: String,
    default: ''
  },
  keyBenefits: {
    type: Array,
    default: () => []
  },
  quickFacts: {
    type: Object,
    default: () => ({
      halfLife: '',
      bioavailability: '',
      storage: '',
      researchLevel: ''
    })
  },
  commonUseCases: {
    type: Array,
    default: () => []
  },
  howItWorks: {
    type: String,
    default: ''
  },
  dosage: {
    type: Object,
    default: () => ({
      typicalDosage: '',
      frequency: '',
      administration: '',
      cycleDuration: ''
    })
  },
  safetyInfo: {
    type: Object,
    default: () => ({
      sideEffects: [],
      contraindications: []
    })
  },
  stackingRecommendations: {
    type: Array,
    default: () => []
  },
  faqs: {
    type: Array,
    default: () => []
  },
  userExperiences: {
    type: Array,
    default: () => []
  },
  products: {
    type: Array,
    default: () => []
  },
  researchStudies: {
    type: [Number, String],
    default: 0
  }
})
</script>
