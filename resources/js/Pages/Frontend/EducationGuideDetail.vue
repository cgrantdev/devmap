<template>
    <FrontLayout>
      <!-- Header Section with Gradient -->
      <div class="min-h-screen bg-gray-50">
        <div class="border-b-4 bg-gradient-to-br from-purple-600 to-blue-600 text-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Back Button -->
                <button 
                    @click="router.visit('/news')"
                    class="flex items-center gap-2 mb-6 transition-colors text-purple-100 hover:text-white"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-4 h-4" aria-hidden="true">
                        <path d="m12 19-7-7 7-7"></path>
                        <path d="M19 12H5"></path>
                    </svg>
                    Back to Educational Guides
                </button>
                <div class="flex items-center gap-2 mb-4">
                    <span v-if="tag" class="text-sm px-3 py-1 rounded-full border bg-white/20 text-white border-white/30">{{ tag }} Guide</span>
                    <span class="flex items-center gap-1 text-sm text-purple-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-4 h-4" aria-hidden="true">
                            <path d="M12 6v6l4 2"></path>
                            <circle cx="12" cy="12" r="10"></circle> 
                        </svg>
                        {{ readingTime }} read
                    </span>
                    <span class="text-sm px-3 py-1 rounded-full bg-yellow-400 text-yellow-900 border border-yellow-500 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#facc15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" aria-hidden="true">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                        Featured Guide
                    </span>
                </div>
                <h1 class="text-4xl mb-4 text-white">{{ title }}</h1>
                <p class="text-xl mb-6 text-purple-100">{{ description }}</p>
                <div>
                    <p class="text-sm mb-2 text-purple-200">Peptides Covered:</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="peptide in peptides" :key="peptide" class="text-sm px-3 py-1 rounded-lg border bg-white/10 text-white border-white/30">
                            {{ peptide }}
                        </span>
                    </div>
                </div>
            </div>            
        </div>


        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-5 h-5 text-purple-600" aria-hidden="true">
                        <path d="M12 7v14"></path>
                        <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
                    </svg>
                    <h2 class="text-xl text-gray-900">Table of Contents</h2>
                </div>
                <ul class="space-y-2">
                    <li>
                        <a href="#introduction" class="text-blue-600 hover:text-blue-700 hover:underline">Introduction</a>
                    </li>
                    <li>
                        <a href="#overview" class="text-blue-600 hover:text-blue-700 hover:underline">Overview & Benefits</a>
                    </li>
                    <li>
                        <a href="#guidelines" class="text-blue-600 hover:text-blue-700 hover:underline">Usage Guidelines</a>
                    </li>
                    <li>
                        <a href="#safety" class="text-blue-600 hover:text-blue-700 hover:underline">Safety & Precautions</a>
                    </li>
                    <li>
                        <a href="#best-practices" class="text-blue-600 hover:text-blue-700 hover:underline">Best Practices</a>
                    </li>
                    <li>
                        <a href="#conclusion" class="text-blue-600 hover:text-blue-700 hover:underline">Conclusion</a>
                    </li>
                </ul>
            </div>

            <div id="introduction" class="bg-white rounded-lg shadow-sm p-8 mb-8">
                <h2 class="text-2xl text-gray-900 mb-4">Introduction</h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Welcome to this comprehensive guide on {{ title.toLowerCase() }}. 
                    This educational resource has been created to provide you with in-depth knowledge, 
                    practical insights, and evidence-based information to help you make informed decisions 
                    about peptide therapy. 
                </p>
                <p>
                    Whether you're new to peptides or looking to optimize your current protocol, 
                    this guide covers everything you need to know, from basic principles to advanced strategies. 
                    All information is based on current scientific research and expert recommendations.
                </p>
            </div>

            <div id="overview" class="bg-white rounded-lg shadow-sm p-8 mb-8">
                <h2 class="text-2xl text-gray-900 mb-6">Overview & Benefits</h2>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big w-5 h-5 text-green-600 mt-1 flex-shrink-0" aria-hidden="true">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <div>
                            <h3 class="text-gray-900 mb-1">Understanding the Basics</h3>
                            <p class="text-gray-700">Learn the fundamental concepts and mechanisms behind BPC-157, TB-500, Ipamorelin, including how they work at the molecular level and what outcomes you can expect.</p>
                        </div>

                    </div>

                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big w-5 h-5 text-green-600 mt-1 flex-shrink-0" aria-hidden="true">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <div>
                            <h3 class="text-gray-900 mb-1">Therapeutic Benefits</h3>
                            <p class="text-gray-700">
                                Discover the wide range of potential benefits, from improved recovery and healing to enhanced performance and overall wellness. Each benefit is backed by scientific research and clinical evidence.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big w-5 h-5 text-green-600 mt-1 flex-shrink-0" aria-hidden="true">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <div>
                            <h3 class="text-gray-900 mb-1">Evidence-Based Information</h3>
                            <p class="text-gray-700">
                                All recommendations in this guide are based on peer-reviewed research, clinical trials, and expert consensus from the medical and scientific community.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="guidelines" class="bg-white rounded-lg shadow-sm p-8 mb-8">
                <h2 class="text-2xl text-gray-900 mb-6">Usage Guidelines</h2>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lightbulb w-5 h-5 text-blue-600 mt-1" aria-hidden="true">
                            <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"></path>
                            <path d="M9 18h6"></path>
                            <path d="M10 22h4"></path>
                        </svg>
                        <div>
                            <h4 class="text-blue-900 mb-2">Important Note</h4>
                            <p class="text-sm text-blue-800">
                                These guidelines are for educational purposes only. Always consult with a qualified healthcare professional before starting any new supplement or peptide protocol.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-xl text-gray-900 mb-3">Dosage Recommendations</h3>
                        <p class="text-gray-700 mb-3">
                            Proper dosing is crucial for both safety and effectiveness. Recommended dosages typically range based on individual factors including body weight, goals, and experience level.
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-700">
                            <li>Start with lower doses to assess individual tolerance</li>
                            <li>Gradually increase as needed under professional guidance</li>
                            <li>Follow recommended injection protocols and timing</li>
                            <li>Maintain consistent scheduling for optimal results</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-xl text-gray-900 mb-3">Administration Methods</h3>
                        <p class="text-gray-700 mb-3">
                            Understanding proper administration techniques ensures safety and maximizes therapeutic benefits:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-700">
                            <li>Subcutaneous injection is the most common method</li>
                            <li>Proper reconstitution with bacteriostatic water</li>
                            <li>Sterile technique and safe disposal practices</li>
                            <li>Rotation of injection sites to prevent irritation</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xl text-gray-900 mb-3">Timing & Frequency</h3>
                        <p class="text-gray-700 mb-3">
                            Optimal timing and frequency vary by peptide and individual goals. 
                            Most protocols involve daily or several-times-weekly administration, with specific timing recommendations based on the peptide's mechanism of action and half-life.
                        </p>
                    </div>
                </div>
            </div>

            <div id="safety" class="bg-white rounded-lg shadow-sm p-8 mb-8">
                <h2 class="text-2xl text-gray-900 mb-6">Safety & Precautions</h2>
                <div class="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-6 mb-6">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert w-5 h-5 text-yellow-600 mt-1 flex-shrink-0" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" x2="12" y1="8" y2="12"></line>
                            <line x1="12" x2="12.01" y1="16" y2="16"></line>
                        </svg>
                        <div>
                            <h4 class="text-yellow-900 mb-2">Safety First</h4>
                            <p class="text-sm text-yellow-800">
                                While peptides are generally well-tolerated, it's essential to understand potential side effects, contraindications, and safety considerations before beginning any protocol.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <h3 class="text-xl text-gray-900 mb-3">Common Side Effects</h3>
                        <ul class="list-disc pl-6 space-y-2 text-gray-700">
                            <li>Mild injection site reactions (redness, swelling)</li>
                            <li>Temporary flushing or warmth</li>
                            <li>Headaches in some individuals</li>
                            <li>Water retention (peptide-dependent)</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xl text-gray-900 mb-3">Contraindications</h3>
                        <ul class="list-disc pl-6 space-y-2 text-gray-700">
                            <li>Mild injection site reactions (redness, swelling)</li>
                            <li>Temporary flushing or warmth</li>
                            <li>Headaches in some individuals</li>
                            <li>Water retention (peptide-dependent)</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xl text-gray-900 mb-3">Storage & Handling</h3>
                        <ul class="list-disc pl-6 space-y-2 text-gray-700">
                            <li>Mild injection site reactions (redness, swelling)</li>
                            <li>Temporary flushing or warmth</li>
                            <li>Headaches in some individuals</li>
                            <li>Water retention (peptide-dependent)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="best-practices" class="bg-white rounded-lg shadow-sm p-8 mb-8">
                <h2 class="text-2xl text-gray-900 mb-6">Best Practices</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-lg p-6 border border-blue-200">
                        <h3 class="text-gray-900 mb-3">✓ Do's</h3>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li>• Work with qualified healthcare professionals</li>
                            <li>• Source peptides from reputable vendors</li>
                            <li>• Keep detailed logs of dosing and results</li>
                            <li>• Follow proper storage protocols</li>
                            <li>• Monitor your progress and adjust as needed</li>
                            <li>• Get regular health checkups</li>
                        </ul>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-lg p-6 border border-red-200">
                        <h3 class="text-gray-900 mb-3">✗ Don'ts</h3>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li>• Don't exceed recommended dosages</li>
                            <li>• Don't share needles or supplies</li>
                            <li>• Don't use expired or improperly stored peptides</li>
                            <li>• Don't ignore adverse reactions</li>
                            <li>• Don't purchase from unverified sources</li>
                            <li>• Don't self-diagnose medical conditions</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="conclusion" class="bg-white rounded-lg shadow-sm p-8 mb-8">
                <h2 class="text-2xl text-gray-900 mb-6">Conclusion</h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    This guide has provided you with a comprehensive overview of {{ title.toLowerCase() }}. By following these evidence-based recommendations and working with qualified healthcare professionals, you can make informed decisions about incorporating peptides into your wellness protocol.
                    Remember that individual responses to peptide therapy can vary, and what works for one person may not be ideal for another. Patience, proper monitoring, and adherence to safety guidelines are key to achieving the best possible outcomes.
                    Continue your education by exploring our research library, staying updated with the latest scientific findings, and connecting with the peptide community. Knowledge is your most powerful tool for successful peptide therapy.
                </p>
            </div>

            <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg p-8 text-white text-center mb-8">
                <h3 class="text-2x1 mb-3">Save This Guide</h3>
                <p class="text-purple-100 mb-6">
                    Bookmark this page or download it for offline reference
                </p>
                <button class="inline-flex items-center gap-2 px-6 py-3 bg-white text-purple-600 rounded-lg hover:bg-purple-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download w-4 h-4" aria-hidden="true">
                        <path d="M12 15V3"></path>
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <path d="m7 10 5 5 5-5"></path>
                    </svg>
                    Download PDF
                </button>
            </div>

            <div class="mt-12">
                <h2 class="text-2xl text-gray-900 mb-6">Related Guides</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>
            </div>
        </div>
      </div>
    </FrontLayout>
</template>
  
<script setup>
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'   

defineProps({
    id: {
        type: [Number, String],
        required: true
    },
    tag: {
        type: String,
        default: null
    },
    readingTime: {
        type: String,
        required: true
    },
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        required: true
    },
    peptides: {
        type: Array,
        default: () => []
    },
    guideUrl: {
        type: String,
        default: '#'
    }
})

</script>
