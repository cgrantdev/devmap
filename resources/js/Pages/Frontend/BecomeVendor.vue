<template>
  <FrontLayout>
    <div class="min-h-screen bg-slate-50">
      <!-- Header Section -->
      <div class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-12 text-center">
          <h1 class="text-slate-900 mb-4">
            Become a Vendor on Peptidemaps
          </h1>
          <p class="text-slate-600 max-w-2xl mx-auto">
            Join the leading peptide marketplace and connect with thousands of customers actively searching for quality peptide products.
          </p>
        </div>
      </div>

      <!-- Progress Indicator -->
      <div class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-6">
          <div class="flex items-center justify-between">
            <!-- Step 1: Company Info -->
            <div class="flex flex-col items-center flex-1">
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center transition-all bg-slate-200 text-slate-400',
                  step > 1
                    ? 'bg-slate-700 text-white'
                    : step === 1
                    ? 'bg-slate-700 text-white'
                    : 'bg-slate-200 text-gray-600'
                ]"
              >
                <svg v-if="step > 1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-5 h-5" aria-hidden="true">
                  <circle cx="12" cy="12" r="10"></circle>
                  <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span v-else>1</span>
              </div>
              <span class="text-xs text-slate-600 mt-2 hidden sm:block">Company Info</span>
            </div>

            <!-- Connector Line -->
            <div
              :class="[
                'h-0.5 flex-1 transition-all bg-slate-200',
                step > 1 ? 'bg-slate-700' : 'bg-slate-200'
              ]"
            ></div>

            <!-- Step 2: Contact Details -->
            <div class="flex flex-col items-center flex-1">
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center transition-all bg-slate-200 text-slate-400',
                  step === 2
                    ? 'bg-slate-700 text-white'
                    : step > 2
                    ? 'bg-slate-700 text-white'
                    : 'bg-slate-200 text-gray-600'
                ]"
              >
              <svg v-if="step > 2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-5 h-5" aria-hidden="true">
                  <circle cx="12" cy="12" r="10"></circle>
                  <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span v-else>2</span>
              </div>
              <span class="text-xs text-slate-600 mt-2 hidden sm:block">Contact Details</span>
            </div>

            <!-- Connector Line -->
            <div
              :class="[
                'h-0.5 flex-1 mx-2',
                step > 2 ? 'bg-slate-700' : 'bg-slate-200'
              ]"
            ></div>

            <!-- Step 3: Business Info -->
            <div class="flex flex-col items-center flex-1">
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center transition-all bg-slate-200 text-slate-400',
                  step > 3
                    ? 'bg-slate-700 text-white'
                    : step === 3
                    ? 'bg-slate-700 text-white'
                    : 'bg-slate-200 text-gray-600'
                ]"
              >
                <svg v-if="step > 3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-5 h-5" aria-hidden="true">
                  <circle cx="12" cy="12" r="10"></circle>
                  <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span v-else>3</span>
              </div>
              <span class="text-xs text-slate-600 mt-2 hidden sm:block">Business Info</span>
            </div>

            <!-- Connector Line -->
            <div
              :class="[
                'h-0.5 flex-1 mx-2',
                step > 3 ? 'bg-slate-700' : 'bg-slate-200'
              ]"
            ></div>

            <!-- Step 4: Plan & Payment -->
            <div class="flex flex-col items-center flex-1">
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center transition-all bg-slate-200 text-slate-400',
                  step === 4
                    ? 'bg-slate-700 text-white'
                    : 'bg-slate-200 text-gray-600'
                ]"
              >
                4
              </div>
              <span class="text-xs text-slate-600 mt-2 hidden sm:block">Plan & Payment</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Card -->
      <div class="max-w-2xl mx-auto px-4 py-12">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
          <!-- Step 1: Company Information -->
          <div v-if="step === 1" class="space-y-6">
            <h2 class="text-slate-900 mb-2">Company Information</h2>
            <p class="text-slate-600 text-sm">Tell us about your peptide company</p>

            <form @submit.prevent="handleStep1Submit" class="space-y-6">
              <!-- Company Name -->
              <div>
                <label for="company_name" class="block text-sm text-slate-700 mb-2">
                  Company Name <span class="text-slate-500">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                      <circle cx="9" cy="7" r="4"></circle>
                      <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                      <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                  </div>
                  <input
                    id="company_name"
                    v-model="formData.companyName"
                    type="text"
                    placeholder="Your Peptide Company LLC"
                    required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- Website -->
              <div>
                <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                  Website <span class="text-slate-500">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="2" y1="12" x2="22" y2="12"></line>
                      <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                  </div>
                  <input
                    id="website"
                    v-model="formData.website"
                    type="url"
                    placeholder="https://yourcompany.com"
                    required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- Year Established -->
              <div>
                <label for="year_established" class="block text-sm font-medium text-gray-700 mb-2">
                  Year Established
                </label>
                <input
                  id="year_established"
                  v-model="formData.yearEstablished"
                  type="number"
                  min="1800"
                  :max="new Date().getFullYear()"
                  placeholder="2020"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <!-- Country -->
              <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                  Country <span class="text-slate-500">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                  </div>
                  <select
                    id="country"
                    v-model="formData.country"
                    required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white"
                  >
                    <option value="">Select...</option>
                    <option
                      v-for="location in locations"
                      :key="location.id"
                      :value="location.id"
                    >
                      {{ location.name }}
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <path d="m6 9 6 6 6-6"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Navigation Buttons -->
              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <button
                  type="button"
                  @click="goBack"
                  class="text-gray-600 hover:text-gray-900 font-medium"
                >
                  Back
                </button>
                <button
                  type="submit"
                  class="flex items-center gap-2 px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors"
                >
                  Continue
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-4 h-4">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                  </svg>
                </button>
              </div>
            </form>
          </div>

          <!-- Step 2: Contact Details -->
          <div v-if="step === 2">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Contact Details</h2>
            <p class="text-gray-600 mb-6">Primary contact for your account</p>

            <form @submit.prevent="handleStep2Submit" class="space-y-6">
              <!-- Full Name -->
              <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                  Full Name <span class="text-slate-500">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                  </div>
                  <input
                    id="full_name"
                    v-model="formData.fullName"
                    type="text"
                    placeholder="John Smith"
                    required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- Email Address -->
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                  Email Address <span class="text-slate-500">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                      <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                    </svg>
                  </div>
                  <input
                    id="email"
                    v-model="formData.email"
                    type="email"
                    placeholder="john@yourcompany.com"
                    required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- Phone Number -->
              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                  Phone Number <span class="text-slate-500">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                  </div>
                  <input
                    id="phone"
                    v-model="formData.phone"
                    type="tel"
                    placeholder="+1 (555) 123-4567"
                    required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- Password -->
              <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                  Password <span class="text-slate-500">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                  </div>
                  <input
                    id="password"
                    v-model="formData.password"
                    type="password"
                    placeholder="********"
                    required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- Confirm Password -->
              <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                  Confirm Password <span class="text-slate-500">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                  </div>
                  <input
                    id="confirm_password"
                    v-model="formData.confirmPassword"
                    type="password"
                    placeholder="********"
                    required
                    :class="[
                      'w-full pl-10 pr-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent',
                      passwordMismatch ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500'
                    ]"
                  />
                </div>
                <p v-if="passwordMismatch" class="mt-1 text-sm text-red-600">
                  Passwords do not match
                </p>
              </div>

              <!-- Navigation Buttons -->
              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <button
                  type="button"
                  @click="goToStep(1)"
                  class="text-gray-600 hover:text-gray-900 font-medium"
                >
                  Back
                </button>
                <button
                  type="submit"
                  :disabled="passwordMismatch"
                  :class="[
                    'px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2',
                    passwordMismatch
                      ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                      : 'bg-blue-700 text-white hover:bg-blue-800'
                  ]"
                >
                  Continue
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-4 h-4">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                  </svg>
                </button>
              </div>
            </form>
          </div>

          <!-- Step 3: Business Info -->
          <div v-if="step === 3">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Business Information</h2>
            <p class="text-gray-600 mb-6">Help us understand your business</p>

            <form @submit.prevent="handleStep3Submit" class="space-y-6">
              <!-- Estimated Monthly Sales Volume -->
              <div>
                <label for="sales_volume" class="block text-sm font-medium text-gray-700 mb-2">
                  Estimated Monthly Sales Volume
                </label>
                <div class="relative">
                  <select
                    id="sales_volume"
                    v-model="formData.salesVolume"
                    class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white"
                  >
                    <option value="">Select range...</option>
                    <option value="0-1000">$0 - $1,000</option>
                    <option value="1000-5000">$1,000 - $5,000</option>
                    <option value="5000-10000">$5,000 - $10,000</option>
                    <option value="10000-25000">$10,000 - $25,000</option>
                    <option value="25000-50000">$25,000 - $50,000</option>
                    <option value="50000-100000">$50,000 - $100,000</option>
                    <option value="100000+">$100,000+</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <path d="m6 9 6 6 6-6"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Number of Products -->
              <div>
                <label for="product_count" class="block text-sm font-medium text-gray-700 mb-2">
                  Number of Products
                </label>
                <div class="relative">
                  <select
                    id="product_count"
                    v-model="formData.productCount"
                    class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white"
                  >
                    <option value="">Select range...</option>
                    <option value="1-10">1 - 10 products</option>
                    <option value="11-50">11 - 50 products</option>
                    <option value="51-100">51 - 100 products</option>
                    <option value="101-500">101 - 500 products</option>
                    <option value="500+">500+ products</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-400">
                      <path d="m6 9 6 6 6-6"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <!-- What you'll get Section -->
              <div class="pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">What you'll get:</h3>
                <ul class="space-y-3">
                  <li class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-blue-700">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                    </div>
                    <span class="text-gray-700">Dedicated vendor profile page with your products</span>
                  </li>
                  <li class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-blue-700">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                    </div>
                    <span class="text-gray-700">Featured placement in search results and listings</span>
                  </li>
                  <li class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-blue-700">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                    </div>
                    <span class="text-gray-700">Customer review management and analytics</span>
                  </li>
                  <li class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-blue-700">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                    </div>
                    <span class="text-gray-700">Access to promotional deals and discount features</span>
                  </li>
                </ul>
              </div>

              <!-- Navigation Buttons -->
              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <button
                  type="button"
                  @click="goToStep(2)"
                  class="text-gray-600 hover:text-gray-900 font-medium"
                >
                  Back
                </button>
                <button
                  type="submit"
                  class="bg-blue-700 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-800 transition-colors flex items-center gap-2"
                >
                  Continue
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-4 h-4">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                  </svg>
                </button>
              </div>
            </form>
          </div>

          <!-- Step 4: Plan & Payment -->
          <div v-if="step === 4">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Choose Your Plan</h2>
            <p class="text-gray-600 mb-6">Select the plan that works for your business</p>

            <form @submit.prevent="handleStep4Submit" class="space-y-6">
              <!-- Plan Selection Cards -->
              <div class="space-y-4">
                <!-- Basic Plan -->
                <div
                  @click="formData.selectedPlan = 'basic'"
                  :class="[
                    'border-2 rounded-lg p-6 cursor-pointer transition-all',
                    formData.selectedPlan === 'basic'
                      ? 'border-blue-700 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <div class="flex items-start justify-between mb-4">
                    <div>
                      <h3 class="text-xl font-bold text-gray-900 mb-1">Basic</h3>
                      <p class="text-sm text-gray-600">Perfect for getting started</p>
                    </div>
                    <div class="text-right">
                      <div class="text-2xl font-bold text-gray-900">$99</div>
                      <div class="text-sm text-gray-600">/month</div>
                    </div>
                  </div>
                  <ul class="space-y-2">
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Basic vendor profile</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Up to 25 products</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Customer reviews</span>
                    </li>
                  </ul>
                </div>

                <!-- Professional Plan -->
                <div
                  @click="formData.selectedPlan = 'professional'"
                  :class="[
                    'border-2 rounded-lg p-6 cursor-pointer transition-all relative',
                    formData.selectedPlan === 'professional'
                      ? 'border-blue-700 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <div class="absolute top-4 right-4">
                    <span class="bg-gray-700 text-white text-xs font-medium px-3 py-1 rounded-full">Popular</span>
                  </div>
                  <div class="flex items-start justify-between mb-4">
                    <div>
                      <h3 class="text-xl font-bold text-gray-900 mb-1">Professional</h3>
                      <p class="text-sm text-gray-600">For growing businesses</p>
                    </div>
                    <div class="text-right">
                      <div class="text-2xl font-bold text-gray-900">$249</div>
                      <div class="text-sm text-gray-600">/month</div>
                    </div>
                  </div>
                  <ul class="space-y-2">
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Enhanced vendor profile</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Unlimited products</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Featured placement</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Analytics dashboard</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Priority support</span>
                    </li>
                  </ul>
                </div>

                <!-- Enterprise Plan -->
                <div
                  @click="formData.selectedPlan = 'enterprise'"
                  :class="[
                    'border-2 rounded-lg p-6 cursor-pointer transition-all',
                    formData.selectedPlan === 'enterprise'
                      ? 'border-blue-700 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <div class="flex items-start justify-between mb-4">
                    <div>
                      <h3 class="text-xl font-bold text-gray-900 mb-1">Enterprise</h3>
                      <p class="text-sm text-gray-600">Custom solutions</p>
                    </div>
                    <div class="text-right">
                      <div class="text-2xl font-bold text-gray-900">Custom</div>
                      <div class="text-sm text-gray-600">pricing</div>
                    </div>
                  </div>
                  <ul class="space-y-2">
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Everything in Professional</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Dedicated account manager</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">Custom integrations</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                        <path d="M20 6 9 17l-5-5"></path>
                      </svg>
                      <span class="text-gray-700">White-label options</span>
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Note Section -->
              <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                <p class="text-sm text-gray-700">
                  <span class="font-semibold">Note:</span> You can change or cancel your plan at any time. All plans include a 14-day free trial.
                </p>
              </div>

              <!-- Navigation Buttons -->
              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <button
                  type="button"
                  @click="goToStep(3)"
                  class="text-gray-600 hover:text-gray-900 font-medium"
                >
                  Back
                </button>
                <button
                  type="submit"
                  class="bg-blue-700 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-800 transition-colors flex items-center gap-2"
                >
                  Complete Registration
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                  </svg>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Footer Link -->
      <div class="text-center mt-8">
        <p class="text-gray-600">
          Already have an account?
          <a href="/login" class="text-gray-900 font-medium hover:underline">Sign in</a>
        </p>
      </div>
      
    </div>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import FrontLayout from '@/Pages/Layouts/FrontLayout.vue';

const props = defineProps({
  step: {
    type: Number,
    default: 1,
  },
  locations: {
    type: Array,
    default: () => [],
  },
});

const formData = ref({
  companyName: '',
  website: '',
  yearEstablished: '2020',
  country: '',
  fullName: '',
  email: '',
  phone: '',
  password: '',
  confirmPassword: '',
  salesVolume: '',
  productCount: '',
  selectedPlan: 'basic', // Default to Basic plan
});

const step = ref(props.step);

const passwordMismatch = computed(() => {
  return formData.value.password && formData.value.confirmPassword && 
         formData.value.password !== formData.value.confirmPassword;
});

onMounted(() => {
  step.value = props.step;
});

const goToStep = (newStep) => {
  step.value = newStep;
  router.get('/become-a-vendor', { step: newStep }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const goBack = () => {
  if (step.value > 1) {
    goToStep(step.value - 1);
  } else {
    router.visit('/');
  }
};

const handleStep1Submit = () => {
  // Validate form
  if (!formData.value.companyName || !formData.value.website || !formData.value.country) {
    return;
  }

  // Move to next step
  goToStep(2);
};

const handleStep2Submit = () => {
  // Validate form
  if (!formData.value.fullName || !formData.value.email || !formData.value.phone || 
      !formData.value.password || !formData.value.confirmPassword) {
    return;
  }

  // Validate password match
  if (formData.value.password !== formData.value.confirmPassword) {
    return;
  }

  // Move to next step
  goToStep(3);
};

const handleStep3Submit = () => {
  // Move to next step (fields are optional)
  goToStep(4);
};

const handleStep4Submit = () => {
  // Validate that a plan is selected
  if (!formData.value.selectedPlan) {
    return;
  }

  // Here you would typically submit the form to the backend
  // For now, we'll just log the data
  console.log('Form Data:', formData.value);
  
  // TODO: Submit form data to backend API
  // router.post('/become-a-vendor/submit', formData.value);
  
  // After successful submission, redirect to success page or vendor dashboard
  // router.visit('/become-a-vendor/success');
};
</script>
