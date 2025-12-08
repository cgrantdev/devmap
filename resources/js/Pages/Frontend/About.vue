<template>
  <FrontLayout>
    <section class="py-16 bg-white">
      <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-8">About PeptideSync</h1>
        
        <div class="prose prose-lg max-w-none text-gray-700 space-y-6">
          <div>
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">Our Mission</h2>
            <p class="text-gray-600 leading-relaxed">
              PeptideSync is dedicated to advancing peptide research by connecting scientists, researchers, and laboratory professionals with trusted peptide suppliers. Our platform serves as a comprehensive resource hub, providing access to high-quality peptides, educational content, and research tools to support the scientific community.
            </p>
          </div>

          <div>
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">What We Do</h2>
            <p class="text-gray-600 leading-relaxed mb-4">
              PeptideSync operates as an aggregator platform that brings together multiple verified peptide suppliers, making it easier for researchers to:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-600 leading-relaxed ml-4">
              <li>Compare products and prices from various suppliers</li>
              <li>Access comprehensive educational resources about peptide research</li>
              <li>Find detailed information about different peptide compounds</li>
              <li>Connect with reputable vendors and suppliers</li>
              <li>Stay informed about the latest research and developments</li>
            </ul>
          </div>

          <div>
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">Our Commitment to Research</h2>
            <p class="text-gray-600 leading-relaxed">
              All products listed on PeptideSync are intended exclusively for research purposes in laboratory settings. We are committed to supporting legitimate scientific research and do not promote or endorse the use of peptides for human consumption or therapeutic purposes. Our platform strictly adheres to research-only guidelines and works with suppliers who share this commitment.
            </p>
          </div>

          <div>
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">Quality and Verification</h2>
            <p class="text-gray-600 leading-relaxed">
              We work diligently to ensure that all suppliers featured on our platform meet high standards of quality and reliability. While we provide a platform for comparison and information, we encourage researchers to conduct their own due diligence when selecting suppliers and to verify product specifications, purity, and documentation before making purchases.
            </p>
          </div>

          <div>
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">Educational Resources</h2>
            <p class="text-gray-600 leading-relaxed">
              Beyond product listings, PeptideSync provides extensive educational content including research articles, dosage guidelines, storage protocols, and safety information. Our goal is to empower researchers with the knowledge they need to conduct safe and effective peptide research.
            </p>
          </div>

          <div>
            <h2 class="text-2xl font-semibold text-gray-800 mt-8 mb-4">Contact Us</h2>
            <p class="text-gray-600 leading-relaxed">
              If you have questions about our platform, need assistance, or are interested in becoming a supplier partner, please visit our <Link href="/contact" class="text-blue-600 hover:underline">Contact</Link> page. We're here to support the research community and welcome your feedback.
            </p>
          </div>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
</script>
