<template>
  <FrontLayout>
    <section class="py-16 bg-white">
      <div class="max-w-[800px] mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Contact Us</h1>
        <p class="text-gray-600 text-lg mb-8">Get in touch with us for questions, support, partnership opportunities, or general inquiries.</p>
        
        <div class="grid md:grid-cols-2 gap-8 mb-12">
          <div class="bg-gray-50 p-6 rounded-lg">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">General Inquiries</h3>
            <p class="text-gray-600 mb-2">For general questions about our platform, services, or how to get started:</p>
            <p class="text-gray-800 font-medium">info@peptidesync.com</p>
          </div>
          
          <div class="bg-gray-50 p-6 rounded-lg">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Supplier Partnerships</h3>
            <p class="text-gray-600 mb-2">Interested in becoming a featured supplier on our platform?</p>
            <p class="text-gray-800 font-medium">suppliers@peptidesync.com</p>
          </div>
          
          <div class="bg-gray-50 p-6 rounded-lg">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Technical Support</h3>
            <p class="text-gray-600 mb-2">Need help with your account or experiencing technical issues?</p>
            <p class="text-gray-800 font-medium">support@peptidesync.com</p>
          </div>
          
          <div class="bg-gray-50 p-6 rounded-lg">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Research Collaboration</h3>
            <p class="text-gray-600 mb-2">For research partnerships or educational content inquiries:</p>
            <p class="text-gray-800 font-medium">research@peptidesync.com</p>
          </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-8">
          <h2 class="text-2xl font-semibold text-gray-800 mb-6">Send Us a Message</h2>
          
          <form @submit.prevent="submitForm" class="space-y-6">
            <div v-if="formErrors" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
              <p class="font-medium">Please fix the following errors:</p>
              <ul class="list-disc list-inside mt-2">
                <li v-for="error in formErrors" :key="error">{{ error }}</li>
              </ul>
            </div>
            
            <div v-if="formSuccess" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
              {{ formSuccess }}
            </div>

            <div>
              <label class="block mb-2 font-semibold text-gray-800">Name *</label>
              <input 
                v-model="contactForm.name" 
                type="text" 
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Your full name"
              />
            </div>

            <div>
              <label class="block mb-2 font-semibold text-gray-800">Email *</label>
              <input 
                v-model="contactForm.email" 
                type="email" 
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="your.email@example.com"
              />
            </div>

            <div>
              <label class="block mb-2 font-semibold text-gray-800">Subject *</label>
              <select 
                v-model="contactForm.subject" 
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select a subject</option>
                <option value="general">General Inquiry</option>
                <option value="supplier">Supplier Partnership</option>
                <option value="support">Technical Support</option>
                <option value="research">Research Collaboration</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label class="block mb-2 font-semibold text-gray-800">Message *</label>
              <textarea 
                v-model="contactForm.message" 
                required
                rows="6"
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Please provide details about your inquiry..."
              ></textarea>
            </div>

            <button 
              type="submit" 
              :disabled="isSubmitting"
              class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ isSubmitting ? 'Sending...' : 'Send Message' }}
            </button>
          </form>
        </div>

        <div class="mt-12 bg-blue-50 border-l-4 border-blue-400 p-6 rounded">
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Response Time</h3>
          <p class="text-gray-600">
            We aim to respond to all inquiries within 24-48 hours during business days. For urgent matters, please indicate this in your message subject line.
          </p>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'

const contactForm = ref({
  name: '',
  email: '',
  subject: '',
  message: ''
})

const isSubmitting = ref(false)
const formErrors = ref(null)
const formSuccess = ref(null)

const submitForm = async () => {
  isSubmitting.value = true
  formErrors.value = null
  formSuccess.value = null

  try {
    // In a real application, this would send to a backend endpoint
    // For now, we'll simulate a submission
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    formSuccess.value = 'Thank you for your message! We will get back to you soon.'
    contactForm.value = {
      name: '',
      email: '',
      subject: '',
      message: ''
    }
  } catch (error) {
    formErrors.value = ['Failed to send message. Please try again or email us directly.']
  } finally {
    isSubmitting.value = false
  }
}
</script>
