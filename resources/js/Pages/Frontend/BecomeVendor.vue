<template>
  <ModernLayout>
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

      <!-- Success Message -->
      <div v-if="showSuccessMessage || $page.props.flash?.success" class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-green-50 border border-green-200 rounded-lg p-8 flex flex-col items-center text-center">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-16 h-16 text-green-600 mb-4">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="m9 12 2 2 4-4"></path>
          </svg>
          <h2 class="text-green-800 font-semibold text-2xl mb-2">
            {{ $page.props.flash?.success || 'Registration completed successfully.' }}
          </h2>
          <p class="text-green-700 text-base mt-2 max-w-2xl">
            Your registration has been submitted. We will review your application and contact you soon.
          </p>
        </div>
      </div>

      <!-- Progress Indicator -->
      <div class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-6">
          <div class="flex items-center justify-between">
            <template v-for="(label, i) in stepLabels" :key="i">
              <button
                type="button"
                @click="jumpToStep(i + 1)"
                :disabled="i + 1 > maxStepReached"
                :class="['flex flex-col items-center flex-1 transition-opacity', (i + 1 <= maxStepReached) ? 'cursor-pointer hover:opacity-75' : 'cursor-not-allowed']"
                :title="(i + 1 <= maxStepReached) ? `Jump to ${label}` : 'Complete the current step first'"
              >
                <div :class="['w-10 h-10 rounded-full flex items-center justify-center transition-all text-sm font-semibold', step >= i + 1 ? 'bg-slate-700 text-white' : 'bg-slate-200 text-gray-600']">
                  <svg v-if="step > i + 1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="m9 12 2 2 4-4"></path>
                  </svg>
                  <span v-else>{{ i + 1 }}</span>
                </div>
                <span class="text-xs text-slate-600 mt-2 hidden sm:block">{{ label }}</span>
              </button>
              <div v-if="i < stepLabels.length - 1" :class="['h-0.5 flex-1 mx-2 transition-all', step > i + 1 ? 'bg-slate-700' : 'bg-slate-200']"></div>
            </template>
          </div>
        </div>
      </div>

      <!-- Form Card -->
      <div v-if="!showSuccessMessage && !$page.props.flash?.success" :class="[step === 3 ? 'max-w-[1400px]' : 'max-w-2xl', 'mx-auto px-4 py-12']">
        <!-- Resumed-draft notice -->
        <div v-if="draftRestored" class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-3 text-sm text-emerald-800">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <span class="flex-1">Welcome back — we picked up where you left off.</span>
          <button @click="discardDraft" class="text-xs font-medium text-emerald-700 hover:text-emerald-900 underline underline-offset-2">Start over</button>
        </div>

        <!-- Submission error banner -->
        <div v-if="submissionError" class="mb-4 px-4 py-3 bg-rose-50 border border-rose-200 rounded-lg flex items-start gap-3 text-sm text-rose-800">
          <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span class="flex-1">{{ submissionError }}</span>
          <button @click="submissionError = ''" class="text-rose-600 hover:text-rose-900 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Step 3 skips the white card + padding so the two-column
             layout (form + preview) can breathe on the page background.
             Every other step keeps the traditional centered card. -->
        <div :class="step === 3 ? '' : 'bg-white rounded-lg shadow-sm border border-slate-200 p-8'">
          <!-- Step 1: Company Information -->
          <div v-if="step === 1" class="space-y-6">
            <div>
              <h2 class="text-slate-900 mb-2">Company Information</h2>
              <p class="text-slate-600 text-sm">Tell us about your peptide company</p>
            </div>

            <form @submit.prevent="handleStep1Submit" class="space-y-6">
              <!-- Company Name -->
              <div>
                <label for="company_name" class="block text-sm text-slate-700 mb-2">
                  Company Name <span class="text-slate-500">*</span>
                </label>
                <div class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building2 lucide-building-2 absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400">
                    <path d="M10 12h4"></path>
                    <path d="M10 8h4"></path>
                    <path d="M14 21v-3a2 2 0 0 0-4 0v3"></path>
                    <path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2"></path>
                    <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"></path>
                  </svg>
                  <input
                    id="company_name"
                    v-model="formData.companyName"
                    type="text"
                    placeholder="Your Peptide Company LLC"
                    required
                    :class="['w-full pl-11 pr-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', fieldErrors.companyName ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']"
                  />
                </div>
                <p v-if="fieldErrors.companyName" class="text-xs text-rose-600 mt-1">{{ fieldErrors.companyName }}</p>
              </div>

              <!-- Website -->
              <div>
                <label for="website" class="block text-sm text-slate-700 mb-2">
                  Website *
                </label>
                <div class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-globe absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                    <path d="M2 12h20"></path>
                  </svg>
                  <input
                    id="website"
                    v-model="formData.website"
                    @blur="normalizeWebsite"
                    type="url"
                    placeholder="https://yourcompany.com"
                    required
                    :class="['w-full pl-11 pr-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', fieldErrors.website ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']"
                  />
                </div>
                <p v-if="fieldErrors.website" class="text-xs text-rose-600 mt-1">{{ fieldErrors.website }}</p>
              </div>

              <!-- Year Established, Country -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="year_established" class="block text-sm text-slate-700 mb-2">
                    Year Established
                  </label>
                  <input
                    id="year_established"
                    v-model="formData.yearEstablished"
                    type="text"
                    min="1800"
                    :max="new Date().getFullYear()"
                    placeholder="2020"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                  />
                </div>

                <div>
                  <label for="country" class="block text-sm text-slate-700 mb-2">
                    Country *
                  </label>
                  <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400">
                      <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <select
                      id="country"
                      v-model="formData.country"
                      required
                      class="w-full pl-11 pr-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 appearance-none"
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
                  </div>
                </div>
              </div>

              <!-- Navigation Buttons -->
              <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-200">
                <button
                  type="button"
                  @click="goBack"
                  class="px-6 py-2 text-slate-600 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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
          <div v-if="step === 2" class="space-y-6">
            <div>
              <h2 class="text-slate-900 mb-2">Contact Details</h2>
              <p class="text-slate-600 text-sm">Primary contact for your account</p>
            </div>

            <form @submit.prevent="handleStep2Submit" class="space-y-6">
              <!-- Full Name -->
              <div>
                <label for="full_name" class="block text-sm text-slate-700 mb-2">
                  Full Name *
                </label>
                <div class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                  <input
                    id="full_name"
                    v-model="formData.fullName"
                    type="text"
                    placeholder="John Smith"
                    required
                    class="w-full pl-11 pr-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                  />
                </div>
              </div>

              <!-- Email Address -->
              <div>
                <label for="email" class="block text-sm text-slate-700 mb-2">
                  Email Address *
                </label>
                <div class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400">
                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                    <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path>
                  </svg>
                  <input
                    id="email"
                    v-model="formData.email"
                    type="email"
                    placeholder="john@yourcompany.com"
                    required
                    :class="['w-full pl-11 pr-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', fieldErrors.email ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']"
                  />
                </div>
                <p v-if="fieldErrors.email" class="text-xs text-rose-600 mt-1">{{ fieldErrors.email }}</p>
              </div>

              <!-- Phone Number -->
              <div>
                <label for="phone" class="block text-sm text-slate-700 mb-2">
                  Phone Number
                </label>
                <div class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400">
                    <path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path>
                  </svg>
                  <input
                    id="phone"
                    v-model="formData.phone"
                    type="tel"
                    placeholder="+1 (555) 123-4567"
                    class="w-full pl-11 pr-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                  />
                </div>
              </div>

              <!-- Password -->
              <div>
                <label for="password" class="block text-sm text-slate-700 mb-2">
                  Password *
                </label>
                <div class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                  </svg>
                  <input
                    id="password"
                    v-model="formData.password"
                    type="password"
                    placeholder="********"
                    required
                    :class="['w-full pl-11 pr-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', passwordWeak ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']"
                  />
                </div>
                <!-- Strength meter -->
                <div v-if="formData.password" class="mt-2 space-y-1.5">
                  <div class="flex gap-1">
                    <div :class="['h-1 flex-1 rounded-full', passwordScore >= 1 ? 'bg-rose-500' : 'bg-slate-200']"></div>
                    <div :class="['h-1 flex-1 rounded-full', passwordScore >= 2 ? 'bg-amber-500' : 'bg-slate-200']"></div>
                    <div :class="['h-1 flex-1 rounded-full', passwordScore >= 3 ? 'bg-yellow-500' : 'bg-slate-200']"></div>
                    <div :class="['h-1 flex-1 rounded-full', passwordScore >= 4 ? 'bg-emerald-500' : 'bg-slate-200']"></div>
                  </div>
                  <ul class="text-[11px] text-slate-500 space-y-0.5 leading-tight">
                    <li :class="passwordChecks.length ? 'text-emerald-600' : ''">
                      <span v-if="passwordChecks.length">✓</span><span v-else>○</span> At least 8 characters
                    </li>
                    <li :class="passwordChecks.upper ? 'text-emerald-600' : ''">
                      <span v-if="passwordChecks.upper">✓</span><span v-else>○</span> One uppercase letter
                    </li>
                    <li :class="passwordChecks.lower ? 'text-emerald-600' : ''">
                      <span v-if="passwordChecks.lower">✓</span><span v-else>○</span> One lowercase letter
                    </li>
                    <li :class="passwordChecks.number ? 'text-emerald-600' : ''">
                      <span v-if="passwordChecks.number">✓</span><span v-else>○</span> One number
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Confirm Password -->
              <div>
                <label for="confirm_password" class="block text-sm text-slate-700 mb-2">
                  Confirm Password *
                </label>
                <div class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                  </svg>
                  <input
                    id="confirm_password"
                    v-model="formData.confirmPassword"
                    type="password"
                    placeholder="********"
                    required
                    :class="[
                      'w-full pl-11 pr-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400',
                      passwordMismatch ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500'
                    ]"
                  />
                </div>
                <p v-if="passwordMismatch" class="mt-1 text-sm text-red-600">
                  Passwords do not match
                </p>
              </div>

              <p v-if="step2ErrorMessage" class="text-sm text-rose-600">{{ step2ErrorMessage }}</p>

              <!-- Navigation Buttons -->
              <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-200">
                <button
                  type="button"
                  @click="goToStep(1)"
                  class="px-6 py-2 text-slate-600 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Back
                </button>
                <button
                  type="submit"
                  :disabled="step2Invalid"
                  :class="['flex items-center gap-2 px-6 py-2 text-white rounded-lg transition-colors', step2Invalid ? 'bg-slate-300 cursor-not-allowed' : 'bg-slate-700 hover:bg-slate-600']"
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

          <!-- Step 3: Business Info. Parent container widens to
               max-w-[1400px] at step 3 so the two-column grid has room.
               Form column stays reader-friendly (~480px), preview takes
               the rest and sticks as the applicant scrolls. -->
          <div v-if="step === 3">
            <div class="mb-6">
              <h2 class="text-slate-900 mb-2 text-xl">Business Information</h2>
              <p class="text-slate-600 text-sm">Help us understand your business — the preview updates as you type.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,480px)_minmax(0,1fr)] gap-8">
              <form @submit.prevent="handleStep3Submit" class="space-y-6 min-w-0">
              <!-- Number of Products -->
              <div>
                <label for="product_count" class="block text-sm text-slate-700 mb-2">
                  Number of Products
                </label>
                <div class="relative">
                  <select
                    id="product_count"
                    v-model="formData.productCount"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                  >
                    <option value="">Select range...</option>
                    <option value="1-10">1 - 10 products</option>
                    <option value="11-25">11 - 25 products</option>
                    <option value="26-50">26 - 50 products</option>
                    <option value="51-100">51 - 100 products</option>
                    <option value="100+">100+ products</option>
                  </select>                  
                </div>
              </div>

              <!-- Tagline — short one-liner shown under the brand name on
                   your storefront (e.g. "USA-manufactured research peptides,
                   3rd-party tested, ships same-day"). Max 120 chars. -->
              <div>
                <div class="flex items-baseline justify-between mb-2">
                  <label for="tagline" class="block text-sm text-slate-700">
                    Tagline <span class="text-xs text-slate-500 font-normal">— one line under your name</span>
                  </label>
                  <span class="text-[11px] ui-mono" :class="charCountColor(formData.tagline, LIMITS.tagline)">
                    {{ (formData.tagline || '').length }} / {{ LIMITS.tagline }}
                  </span>
                </div>
                <input
                  id="tagline"
                  v-model="formData.tagline"
                  type="text"
                  :maxlength="LIMITS.tagline"
                  placeholder="USA-manufactured research peptides, 3rd-party tested, ships same-day"
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                />
              </div>

              <!-- Company Description -->
              <div>
                <div class="flex items-baseline justify-between mb-2">
                  <label for="company_description" class="block text-sm text-slate-700">
                    Company Description <span class="text-xs text-slate-500 font-normal">— longer 'about us'</span>
                  </label>
                  <span class="text-[11px] ui-mono" :class="charCountColor(formData.companyDescription, LIMITS.description)">
                    {{ (formData.companyDescription || '').length }} / {{ LIMITS.description }}
                  </span>
                </div>
                <textarea
                  id="company_description"
                  v-model="formData.companyDescription"
                  rows="4"
                  :maxlength="LIMITS.description"
                  placeholder="Tell us about your company..."
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                ></textarea>
              </div>

              <!-- Reviews & Trust — optional URLs for third-party review
                   platforms. Each one appears as a badge on your storefront
                   with live rating pulled weekly. All optional. -->
              <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                <div class="flex items-baseline justify-between mb-3">
                  <label class="block text-sm font-semibold text-slate-800">Reviews & Trust badges <span class="text-xs font-normal text-slate-500">— all optional</span></label>
                </div>
                <p class="text-xs text-slate-600 mb-4">
                  Paste any review-platform URLs where customers can find you. We'll pull your live rating + count and display them as trust badges on your Peptidemap storefront. Aggregated across sources.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label for="trustpilot_url" class="block text-xs text-slate-700 mb-1">Trustpilot URL</label>
                    <input id="trustpilot_url" v-model="formData.trustpilotUrl" type="url" placeholder="https://www.trustpilot.com/review/your-domain.com" class="w-full px-3 py-2 text-sm border border-slate-300 rounded focus:border-slate-500 focus:outline-none" />
                  </div>
                  <div>
                    <label for="google_reviews_url" class="block text-xs text-slate-700 mb-1">Google Reviews URL</label>
                    <input id="google_reviews_url" v-model="formData.googleReviewsUrl" type="url" placeholder="https://www.google.com/storepages?q=…" class="w-full px-3 py-2 text-sm border border-slate-300 rounded focus:border-slate-500 focus:outline-none" />
                  </div>
                  <div>
                    <label for="reviews_io_url" class="block text-xs text-slate-700 mb-1">Reviews.io URL</label>
                    <input id="reviews_io_url" v-model="formData.reviewsIoUrl" type="url" placeholder="https://www.reviews.io/company-reviews/store/…" class="w-full px-3 py-2 text-sm border border-slate-300 rounded focus:border-slate-500 focus:outline-none" />
                  </div>
                  <div>
                    <label for="pepreviewpro_url" class="block text-xs text-slate-700 mb-1">PepReviewPro URL</label>
                    <input id="pepreviewpro_url" v-model="formData.pepreviewproUrl" type="url" placeholder="https://your-brand-reviews.com" class="w-full px-3 py-2 text-sm border border-slate-300 rounded focus:border-slate-500 focus:outline-none" />
                  </div>
                </div>

                <!-- Live preview — shows the exact trust panel that'll appear
                     on the vendor's storefront based on what URLs they've entered.
                     Numbers only appear after we scrape (post-approval) but the
                     layout preview lands immediately. -->
                <div v-if="reviewsPreviewList.length" class="mt-4 p-4 rounded-lg bg-white border border-slate-200">
                  <div class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mb-2">Preview on your storefront</div>
                  <div class="space-y-2">
                    <div
                      v-for="p in reviewsPreviewList"
                      :key="p.key"
                      class="flex items-center justify-between gap-3 p-2 rounded border border-slate-200 bg-slate-50"
                    >
                      <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded text-[10px] font-bold" :class="p.badgeClasses">{{ p.badgeText }}</span>
                        <div>
                          <div class="text-[12px] font-semibold text-slate-800">{{ p.platform }}</div>
                          <div class="text-[10px] text-slate-500 truncate max-w-[220px]">{{ p.url }}</div>
                        </div>
                      </div>
                      <span class="text-[10px] text-slate-400">rating fetches after approval</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Payment Methods Accepted -->
              <div>
                <label class="block text-sm text-slate-700 mb-2">
                  Payment Methods Accepted
                </label>
                <div class="space-y-2">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      value="Credit Card"
                      v-model="formData.paymentMethods"
                      class="w-4 h-4 text-slate-700 border-slate-300 rounded focus:ring-slate-400"
                    />
                    <span class="text-sm text-slate-700">Credit Card</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      value="PayPal"
                      v-model="formData.paymentMethods"
                      class="w-4 h-4 text-slate-700 border-slate-300 rounded focus:ring-slate-400"
                    />
                    <span class="text-sm text-slate-700">PayPal</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      value="Cryptocurrency"
                      v-model="formData.paymentMethods"
                      class="w-4 h-4 text-slate-700 border-slate-300 rounded focus:ring-slate-400"
                    />
                    <span class="text-sm text-slate-700">Cryptocurrency</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      value="Bank Transfer"
                      v-model="formData.paymentMethods"
                      class="w-4 h-4 text-slate-700 border-slate-300 rounded focus:ring-slate-400"
                    />
                    <span class="text-sm text-slate-700">Bank Transfer</span>
                  </label>
                </div>
              </div>

              <!-- Shipping Information -->
              <div>
                <div class="flex items-baseline justify-between mb-2">
                  <label for="shipping_information" class="block text-sm text-slate-700">Shipping Information</label>
                  <span class="text-[11px] ui-mono" :class="charCountColor(formData.shippingInformation, LIMITS.shipping_info)">
                    {{ (formData.shippingInformation || '').length }} / {{ LIMITS.shipping_info }}
                  </span>
                </div>
                <textarea
                  id="shipping_information"
                  v-model="formData.shippingInformation"
                  rows="4"
                  :maxlength="LIMITS.shipping_info"
                  placeholder="Describe your shipping policies, methods, and estimated delivery times..."
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                ></textarea>
              </div>

              <!-- Return Policy -->
              <div>
                <div class="flex items-baseline justify-between mb-2">
                  <label for="return_policy" class="block text-sm text-slate-700">Return Policy</label>
                  <span class="text-[11px] ui-mono" :class="charCountColor(formData.returnPolicy, LIMITS.return_policy)">
                    {{ (formData.returnPolicy || '').length }} / {{ LIMITS.return_policy }}
                  </span>
                </div>
                <textarea
                  id="return_policy"
                  v-model="formData.returnPolicy"
                  rows="4"
                  :maxlength="LIMITS.return_policy"
                  placeholder="Describe your return and refund policies..."
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                ></textarea>
              </div>

              <!-- Business Hours -->
              <div>
                <div class="flex items-baseline justify-between mb-2">
                  <label for="business_hours" class="block text-sm text-slate-700">Business Hours</label>
                  <span class="text-[11px] ui-mono" :class="charCountColor(formData.businessHours, LIMITS.business_hours)">
                    {{ (formData.businessHours || '').length }} / {{ LIMITS.business_hours }}
                  </span>
                </div>
                <textarea
                  id="business_hours"
                  v-model="formData.businessHours"
                  rows="3"
                  :maxlength="LIMITS.business_hours"
                  placeholder="e.g., Monday-Friday: 9 AM - 5 PM EST"
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                ></textarea>
              </div>

              <!-- Unique Selling Points -->
              <div>
                <label for="unique_selling_points" class="block text-sm text-slate-700 mb-2">
                  Unique selling points of your brand?
                </label>
                <textarea
                  id="unique_selling_points"
                  v-model="formData.uniqueSellingPoints"
                  rows="4"
                  placeholder="What makes your brand stand out? List key differentiators..."
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"
                ></textarea>
              </div>

              <!-- Logo Upload -->
              <div>
                <label for="logo_file" class="block text-sm text-slate-700 mb-2">
                  Upload your logo
                </label>
                <div class="mt-2">
                  <input
                    id="logo_file"
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    @change="handleLogoUpload"
                    class="block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-600 file:cursor-pointer cursor-pointer"
                  />
                  <p class="mt-1 text-xs text-slate-500">
                    PNG, JPG, WebP, or SVG · up to 8 MB · square (e.g. 1000×1000) transparent preferred.
                  </p>
                  <div v-if="formData.logoFile" class="mt-2 text-sm text-slate-600">
                    Selected: {{ formData.logoFile.name }}
                  </div>
                  <p v-if="fieldErrors.logoFile" class="mt-1 text-xs text-rose-600">{{ fieldErrors.logoFile }}</p>
                </div>
              </div>

              <!-- What you'll get Section -->
              <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <h3 class="text-slate-900 text-sm mb-2">What you'll get:</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                  <li class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg>
                    <span>Dedicated vendor profile page with your products</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg>
                    <span>Featured placement in search results and listings</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg>
                    <span>Customer review management and analytics</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="m9 12 2 2 4-4"></path>
                    </svg>
                    <span>Access to promotional deals and discount features</span>
                  </li>
                </ul>
              </div>

              <!-- Navigation Buttons -->
              <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-200">
                <button type="button" @click="goToStep(2)" class="px-6 py-2 text-slate-600 hover:text-slate-700 transition-colors">Back</button>
                <button type="submit" class="flex items-center gap-2 px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                  Continue
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
              </div>
            </form>

              <!-- Preview column — right side of the two-col grid.
                   Sticky on lg+ so it stays in view as they scroll the
                   form. Falls below the form on mobile. -->
              <aside>
                <div class="lg:sticky lg:top-4">
                  <div class="mb-3 flex items-baseline justify-between gap-2 flex-wrap">
                    <div class="text-[11px] uppercase tracking-wider font-semibold text-indigo-600">Live preview</div>
                    <div class="text-[11px] text-slate-500">Updates as you type · Products import after approval</div>
                  </div>
                  <VendorStorefrontPreview :data="storefrontPreviewData" :logo="formData.logoFile" />
                </div>
              </aside>
            </div>
          </div>

          <!-- Step 4: Connect Your Store (REST API Key) -->
          <div v-if="step === 4" class="space-y-6">
            <div>
              <div class="inline-flex items-center gap-1.5 px-2.5 py-1 mb-3 bg-emerald-50 text-emerald-700 rounded-full text-[10px] uppercase tracking-[0.08em] font-semibold">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Final step
              </div>
              <h2 class="text-2xl font-semibold text-slate-900 tracking-tight">Connect your store</h2>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Almost there. We use a WooCommerce REST API key to:
              </p>
              <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                <li class="flex items-start gap-2">
                  <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  Automatically import your product catalog (no manual entry)
                </li>
                <li class="flex items-start gap-2">
                  <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  Keep prices and stock in sync daily
                </li>
                <li class="flex items-start gap-2">
                  <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  Surface new products as you launch them
                </li>
              </ul>
              <p class="mt-3 text-sm text-slate-600 leading-relaxed">
                We only request <strong class="text-slate-900">read-only</strong> access — your store data stays unchanged.
              </p>
            </div>

            <form @submit.prevent="handleStep4Submit" class="space-y-6">
              <!-- Written guide (always visible, primary content) -->
              <div class="bg-slate-50 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-sm font-semibold text-slate-800">How to get your REST API key</h3>
                  <span class="text-[11px] text-slate-500">5 steps · ~2 minutes</span>
                </div>
                <ol class="space-y-4">
                  <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                    <p class="text-sm text-slate-700 leading-relaxed">Log in to your WordPress admin panel at <code class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[12px]">yoursite.com/wp-admin</code></p>
                  </li>
                  <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                    <div class="flex-1">
                      <p class="text-sm text-slate-700 leading-relaxed">Navigate to <strong>WooCommerce → Settings → Advanced → REST API</strong></p>
                      <div class="mt-2 flex items-center gap-1.5 text-xs flex-wrap">
                        <span class="px-2 py-0.5 bg-white border border-slate-200 rounded text-slate-700 font-medium">WooCommerce</span>
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                        <span class="px-2 py-0.5 bg-white border border-slate-200 rounded text-slate-700 font-medium">Settings</span>
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                        <span class="px-2 py-0.5 bg-white border border-slate-200 rounded text-slate-700 font-medium">Advanced</span>
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                        <span class="px-2 py-0.5 bg-emerald-100 rounded text-emerald-700 font-semibold">REST API</span>
                      </div>
                    </div>
                  </li>
                  <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                    <p class="text-sm text-slate-700 leading-relaxed">Click <strong>"Add key"</strong> at the top of the page</p>
                  </li>
                  <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                    <div class="flex-1">
                      <p class="text-sm text-slate-700 leading-relaxed mb-2">Fill in the key details:</p>
                      <div class="rounded border border-slate-200 bg-white p-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                          <span class="text-slate-500">Description</span>
                          <span class="px-2 py-0.5 bg-slate-100 rounded font-mono text-slate-800 text-xs">PeptideMap</span>
                        </div>
                        <div class="border-t border-slate-100"></div>
                        <div class="flex items-center justify-between">
                          <span class="text-slate-500">Permissions</span>
                          <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded font-semibold text-xs">Read</span>
                        </div>
                      </div>
                    </div>
                  </li>
                  <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">5</span>
                    <div class="flex-1">
                      <p class="text-sm text-slate-700 leading-relaxed">Click <strong>"Generate API key"</strong>, then copy both keys into the fields below</p>
                      <div class="mt-2 rounded border border-amber-200 bg-amber-50 p-2.5 text-xs text-amber-800 flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span><strong>Important:</strong> The Consumer Secret is only shown once. Copy it before leaving the page.</span>
                      </div>
                    </div>
                  </li>
                </ol>

                <!-- Need help? Video toggle -->
                <div class="mt-5 pt-4 border-t border-slate-200">
                  <button
                    v-if="!showApiVideo"
                    type="button"
                    @click="showApiVideo = true"
                    class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                    Need help? Watch the 60-second walkthrough
                  </button>
                  <div v-else>
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Walkthrough video</span>
                      <button type="button" @click="showApiVideo = false" class="text-xs text-slate-500 hover:text-slate-700">Hide video</button>
                    </div>
                    <video
                      :src="apiVideoUrl"
                      controls
                      autoplay
                      preload="metadata"
                      class="w-full block rounded border border-slate-200"
                    >
                      Your browser doesn't support video playback.
                    </video>
                  </div>
                </div>
              </div>

              <!-- API Key input fields -->
              <div class="space-y-4" :class="{ 'opacity-50 pointer-events-none': formData.refuseApiAccess }">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1.5">Consumer Key <span v-if="!formData.refuseApiAccess" class="text-rose-500">*</span></label>
                  <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                    <input
                      v-model="formData.apiConsumerKey"
                      type="text"
                      :required="!formData.refuseApiAccess"
                      :disabled="formData.refuseApiAccess"
                      placeholder="ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                      :class="['w-full pl-11 pr-4 py-2.5 border rounded-lg font-mono text-sm focus:outline-none focus:ring-2', (consumerKeyInvalid || fieldErrors.apiConsumerKey) ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']"
                    />
                  </div>
                  <p v-if="fieldErrors.apiConsumerKey" class="text-xs text-rose-600 mt-1">{{ fieldErrors.apiConsumerKey }}</p>
                  <p v-else-if="consumerKeyInvalid" class="text-xs text-rose-600 mt-1">Consumer Key must start with <code>ck_</code></p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1.5">Consumer Secret <span v-if="!formData.refuseApiAccess" class="text-rose-500">*</span></label>
                  <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input
                      v-model="formData.apiConsumerSecret"
                      type="password"
                      :required="!formData.refuseApiAccess"
                      :disabled="formData.refuseApiAccess"
                      placeholder="cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                      :class="['w-full pl-11 pr-4 py-2.5 border rounded-lg font-mono text-sm focus:outline-none focus:ring-2', (consumerSecretInvalid || fieldErrors.apiConsumerSecret) ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']"
                    />
                  </div>
                  <p v-if="fieldErrors.apiConsumerSecret" class="text-xs text-rose-600 mt-1">{{ fieldErrors.apiConsumerSecret }}</p>
                  <p v-else-if="consumerSecretInvalid" class="text-xs text-rose-600 mt-1">Consumer Secret must start with <code>cs_</code></p>
                </div>
              </div>

              <!-- Info note -->
              <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-slate-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                  <div class="text-sm text-slate-600">
                    <p>Your API keys are encrypted and stored securely. We use <strong>read-only</strong> access to import your product catalog and sync prices daily. You can also add or update these later from your <strong>Vendor Dashboard → Integrations</strong>.</p>
                  </div>
                </div>
              </div>

              <!-- Opt-out: refuse API access -->
              <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                  <input
                    v-model="formData.refuseApiAccess"
                    type="checkbox"
                    class="mt-0.5 w-4 h-4 accent-slate-700 cursor-pointer"
                  />
                  <div class="flex-1">
                    <span class="text-sm text-slate-800 leading-relaxed">
                      I refuse to provide API access to allow PeptideMap to keep our products and pricing up to date.
                    </span>
                    <p v-if="formData.refuseApiAccess" class="mt-2 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded p-2.5 leading-relaxed">
                      <strong>Heads up:</strong> Without API access, your listing won't auto-sync. You'll need to manually update product prices, stock, and availability through the vendor dashboard, and changes on your storefront won't appear on PeptideMap until you submit them yourself.
                    </p>
                  </div>
                </label>
              </div>

              <!-- Navigation Buttons -->
              <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-200">
                <button type="button" @click="goToStep(3)" class="px-6 py-2 text-slate-600 hover:text-slate-700 transition-colors">Back</button>
                <button
                  type="submit"
                  :disabled="isSubmitting || step4Invalid"
                  :class="['flex items-center gap-2 px-6 py-2.5 text-white rounded-lg transition-colors', (isSubmitting || step4Invalid) ? 'bg-slate-300 cursor-not-allowed' : 'bg-slate-700 hover:bg-slate-600']"
                >
                  <span v-if="isSubmitting">Creating account...</span>
                  <span v-else>Complete Registration</span>
                  <svg v-if="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                  <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                </button>
              </div>
            </form>
          </div>

          <!-- OLD Step 4 placeholder (removed)
          <div v-if="false" class="space-y-6">
            <div @click="formData.selectedPlan = 'basic'"
                  :class="[
                    'border-2 rounded-lg p-6 cursor-pointer transition-all border-slate-700 bg-slate-50',
                    formData.selectedPlan === 'basic'
                      ? 'border-blue-700 bg-blue-50'
                      : 'border-slate-200 hover:border-slate-300'
                  ]"
                >
                  <div class="flex items-start justify-between mb-3">
                    <div>
                      <h3 class="text-slate-900">Basic</h3>
                      <p class="text-sm text-slate-600">Perfect for getting started</p>
                    </div>
                    <div class="text-right">
                      <div class="text-slate-900">$99</div>
                      <div class="text-sm text-slate-600">/month</div>
                    </div>
                  </div>
                  <ul class="space-y-2 text-sm text-slate-600">
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Basic vendor profile</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Up to 25 products</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Customer reviews</span>
                    </li>
                  </ul>
                </div>

                // Professional Plan
                <div
                  @click="formData.selectedPlan = 'professional'"
                  :class="[
                    'border-2 rounded-lg p-6 cursor-pointer transition-all border-slate-200 hover:border-slate-300',
                    formData.selectedPlan === 'professional'
                      ? 'border-blue-700 bg-blue-50'
                      : 'border-slate-200 hover:border-slate-300'
                  ]"
                >
                  <div class="flex items-start justify-between mb-3">
                    <div>
                      <div class="flex items-center gap-2">
                        <h3 class="text-slate-900">Professional</h3>
                        <span class="px-2 py-0.5 bg-slate-700 text-white text-xs rounded">Popular</span>
                      </div>
                      <p class="text-sm text-slate-600">For growing businesses</p>
                    </div>
                    <div class="text-right">
                      <div class="text-slate-900">$249</div>
                      <div class="text-sm text-slate-500">/month</div>
                    </div>
                  </div>
                  <ul class="space-y-2 text-sm text-slate-600">
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Enhanced vendor profile</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Unlimited products</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Featured placement</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Analytics dashboard</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Priority support</span>
                    </li>
                  </ul>
                </div>

                // Enterprise Plan
                <div
                  @click="formData.selectedPlan = 'enterprise'"
                  :class="[
                    'border-2 rounded-lg p-6 cursor-pointer transition-all border-slate-200 hover:border-slate-300',
                    formData.selectedPlan === 'enterprise'
                      ? 'border-blue-700 bg-blue-50'
                      : 'border-slate-200 hover:border-slate-300'
                  ]"
                >
                  <div class="flex items-start justify-between mb-3">
                    <div>
                      <h3 class="text-slate-900">Enterprise</h3>
                      <p class="text-sm text-slate-600">Custom solutions</p>
                    </div>
                    <div class="text-right">
                      <div class="text-slate-900">Custom</div>
                      <div class="text-sm text-slate-500">pricing</div>
                    </div>
                  </div>
                  <ul class="space-y-2 text-sm text-slate-600">
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Everything in Professional</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Dedicated account manager</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>Custom integrations</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4 text-slate-700 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                      </svg>
                      <span>White-label options</span>
                    </li>
                  </ul>
                </div>
              </div>

              // Note Section
              <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <p class="text-sm text-slate-600">
                  <strong>Note:</strong> You can change or cancel your plan at any time. All plans include a 14-day free trial.
                </p>
              </div>

              // Navigation Buttons
              <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-200">
                <button
                  type="button"
                  @click="goToStep(3)"
                  class="px-6 py-2 text-slate-600 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Back
                </button>
                <button
                  type="submit"
                  class="flex items-center gap-2 px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors"
                >
                  Complete Registration
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-4 h-4" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="m9 12 2 2 4-4"></path>
                  </svg>
                </button>
              </div>
            </form>
          </div> -->
        </div>

        <!-- Footer Link -->
        <div class="mt-8 text-center">
          <p class="text-sm text-slate-500">
            Already have an account?
            <a href="/vendor/login" class="text-slate-700 hover:underline">Sign in</a>
          </p>
        </div>
      </div>

      
    </div>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import VendorStorefrontPreview from '@/components/VendorStorefrontPreview.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue';

// --- Draft persistence -----------------------------------------------------
// Form state survives page refreshes via localStorage. Sensitive fields
// (password, confirmPassword) and non-serializable fields (logoFile) are
// never persisted. Drafts auto-expire after 24 hours.
const DRAFT_KEY = 'pmap_become_vendor_signup_v1';
const DRAFT_TTL_MS = 24 * 60 * 60 * 1000;
const DRAFT_SKIP_FIELDS = ['password', 'confirmPassword', 'logoFile'];

function loadDraft() {
  try {
    const raw = localStorage.getItem(DRAFT_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (!parsed?.savedAt || Date.now() - parsed.savedAt > DRAFT_TTL_MS) {
      localStorage.removeItem(DRAFT_KEY);
      return null;
    }
    return parsed;
  } catch {
    return null;
  }
}

function saveDraft(data, step) {
  try {
    const persistable = {};
    for (const key in data) {
      if (!DRAFT_SKIP_FIELDS.includes(key)) {
        persistable[key] = data[key];
      }
    }
    localStorage.setItem(DRAFT_KEY, JSON.stringify({
      savedAt: Date.now(),
      step,
      data: persistable,
    }));
  } catch {
    // Storage full / disabled — silently skip
  }
}

function clearDraft() {
  try { localStorage.removeItem(DRAFT_KEY); } catch {}
}

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
  website: 'https://',
  yearEstablished: '',
  country: '',
  fullName: '',
  email: '',
  phone: '',
  password: '',
  confirmPassword: '',
  connectionMethod: 'api_key',
  apiConsumerKey: '',
  apiConsumerSecret: '',
  refuseApiAccess: false,
  productCount: '',
  companyDescription: '',
  paymentMethods: [],
  shippingInformation: '',
  returnPolicy: '',
  businessHours: '',
  uniqueSellingPoints: '',
  tagline: '',
  logoFile: null,
  // External review platform URLs — all optional. Aggregated into the
  // trust panel on the vendor's storefront by ExternalReviewFetcher.
  trustpilotUrl: '',
  googleReviewsUrl: '',
  reviewsIoUrl: '',
  pepreviewproUrl: '',
  selectedPlan: 'basic', // Default to Basic plan
});

const step = ref(props.step);
const stepLabels = ['Company Info', 'Contact Details', 'Business Info', 'Connect Store'];
// Tracks the highest step the user has progressed to. Step circles can be
// clicked to jump back to any visited step; jumping forward past unfilled
// fields is blocked.
const maxStepReached = ref(props.step || 1);

const passwordMismatch = computed(() => {
  return formData.value.password && formData.value.confirmPassword &&
         formData.value.password !== formData.value.confirmPassword;
});

// Character limits shown as live counters. Mirrored server-side in
// BecomeVendorController / StorefrontEditController. Adjust here + there
// together if you tweak.
const LIMITS = {
  tagline: 120,
  description: 1200,
  shipping_info: 800,
  return_policy: 800,
  business_hours: 200,
  unique_selling_points: 800,
};

// Character-counter color: gray under 80%, amber 80-99%, rose at max.
function charCountColor(value, limit) {
  const len = (value || '').length;
  if (len >= limit) return 'text-rose-600 font-semibold';
  if (len >= limit * 0.8) return 'text-amber-600';
  return 'text-slate-400';
}

// Shape the form's flat fields into the object <VendorStorefrontPreview>
// expects. Location name comes from the selected country id — resolves
// against props.locations. Falls back to id string on failure so the
// preview never breaks mid-typing.
const storefrontPreviewData = computed(() => {
  const country = props.locations?.find(l => String(l.id) === String(formData.value.country))
  return {
    name: formData.value.companyName,
    tagline: formData.value.tagline,
    description: formData.value.companyDescription,
    location: country?.name || null,
    shipping_info: formData.value.shippingInformation,
    return_policy: formData.value.returnPolicy,
    business_hours: formData.value.businessHours,
    payment_methods: formData.value.paymentMethods,
    contact_email: formData.value.email,
    phone: formData.value.phone,
    trustpilot_url: formData.value.trustpilotUrl,
    google_reviews_url: formData.value.googleReviewsUrl,
    reviews_io_url: formData.value.reviewsIoUrl,
    pepreviewpro_url: formData.value.pepreviewproUrl,
  }
})

// Live preview of the trust panel — mirrors the shape rendered on
// /brand/{slug} so vendors can see what their badges will look like as
// they fill in the URLs. Rating numbers come after approval + first scrape.
const REVIEW_PLATFORM_META = {
  reviews_io:   { platform: 'Reviews.io',    badgeText: 'Rio', badgeClasses: 'bg-blue-50 text-blue-700 border border-blue-200' },
  trustpilot:   { platform: 'Trustpilot',    badgeText: 'TP',  badgeClasses: 'bg-emerald-50 text-emerald-700 border border-emerald-200' },
  google:       { platform: 'Google Reviews', badgeText: 'G',  badgeClasses: 'bg-red-50 text-red-700 border border-red-200' },
  pepreviewpro: { platform: 'PepReviewPro',  badgeText: 'PRP', badgeClasses: 'bg-amber-50 text-amber-700 border border-amber-200' },
}
const reviewsPreviewList = computed(() => {
  const map = {
    reviews_io: formData.value.reviewsIoUrl,
    trustpilot: formData.value.trustpilotUrl,
    google: formData.value.googleReviewsUrl,
    pepreviewpro: formData.value.pepreviewproUrl,
  }
  return Object.keys(map)
    .filter(k => map[k] && map[k].trim())
    .map(k => ({ key: k, url: map[k], ...REVIEW_PLATFORM_META[k] }))
})

// Password strength
const passwordChecks = computed(() => ({
  length: (formData.value.password || '').length >= 8,
  upper: /[A-Z]/.test(formData.value.password || ''),
  lower: /[a-z]/.test(formData.value.password || ''),
  number: /[0-9]/.test(formData.value.password || ''),
}));
const passwordScore = computed(() => Object.values(passwordChecks.value).filter(Boolean).length);
const passwordStrong = computed(() => passwordScore.value === 4);
const passwordWeak = computed(() => formData.value.password && !passwordStrong.value);

const step2ErrorMessage = ref('');
const step2Invalid = computed(() => passwordMismatch.value || !passwordStrong.value);

// API key format (Step 4)
const consumerKeyInvalid = computed(() => {
  const v = (formData.value.apiConsumerKey || '').trim();
  return v.length > 0 && !v.startsWith('ck_');
});
const consumerSecretInvalid = computed(() => {
  const v = (formData.value.apiConsumerSecret || '').trim();
  return v.length > 0 && !v.startsWith('cs_');
});
const step4Invalid = computed(() => {
  // If the vendor opted out of API access, the keys aren't required.
  if (formData.value.refuseApiAccess) return false;
  const k = (formData.value.apiConsumerKey || '').trim();
  const s = (formData.value.apiConsumerSecret || '').trim();
  return !k || !s || consumerKeyInvalid.value || consumerSecretInvalid.value;
});

const draftRestored = ref(false);

onMounted(() => {
  step.value = props.step;

  // Restore from a saved draft, if any
  const draft = loadDraft();
  if (draft?.data) {
    Object.assign(formData.value, draft.data);
    if (draft.step >= 1 && draft.step <= 4) {
      // Password fields are intentionally NOT persisted to localStorage.
      // If the draft put us past step 2 but the password is empty, we'd end up
      // at step 4 with a silently un-clickable "Complete Registration" button —
      // exactly the failure Max from Hydro Research reported. Bounce back to
      // step 2 in that case so they can re-enter the password.
      const passwordMissing = !formData.value.password || !formData.value.confirmPassword;
      const targetStep = (draft.step >= 3 && passwordMissing) ? 2 : draft.step;

      step.value = targetStep;
      maxStepReached.value = Math.max(maxStepReached.value, targetStep);

      if (targetStep !== draft.step && passwordMissing) {
        submissionError.value = 'For your security, please re-enter your password to continue.';
      }

      // Sync URL to match the restored step (only if it differs)
      if (props.step !== targetStep) {
        router.get('/become-a-vendor', { step: targetStep }, {
          preserveState: true,
          preserveScroll: true,
          replace: true,
        });
      }
    }
    draftRestored.value = true;
  }

  // Safety: ensure website has https:// prefix even after a draft restore
  if (!formData.value.website || formData.value.website === '') {
    formData.value.website = 'https://';
  }
});

// Persist on every change (deep watch) and on step change
watch(formData, (val) => saveDraft(val, step.value), { deep: true });
watch(step, (newStep) => saveDraft(formData.value, newStep));

function discardDraft() {
  clearDraft();
  formData.value = {
    companyName: '', website: 'https://', yearEstablished: '', country: '',
    fullName: '', email: '', phone: '', password: '', confirmPassword: '',
    connectionMethod: 'api_key', apiConsumerKey: '', apiConsumerSecret: '', refuseApiAccess: false,
    productCount: '', companyDescription: '',
    paymentMethods: [], shippingInformation: '', returnPolicy: '',
    businessHours: '', uniqueSellingPoints: '', logoFile: null,
    selectedPlan: 'basic',
  };
  step.value = 1;
  maxStepReached.value = 1;
  draftRestored.value = false;
  router.get('/become-a-vendor', { step: 1 }, { preserveState: true, replace: true });
}

const goToStep = (newStep) => {
  step.value = newStep;
  if (newStep > maxStepReached.value) maxStepReached.value = newStep;
  router.get('/become-a-vendor', { step: newStep }, {
    preserveState: true,
    preserveScroll: true,
  });
};

/**
 * Jump to a step from the progress indicator. Only allows navigation to
 * previously-reached steps so users can't skip ahead with unfilled fields.
 */
const jumpToStep = (targetStep) => {
  if (targetStep < 1 || targetStep > 4) return;
  if (targetStep > maxStepReached.value) return;
  step.value = targetStep;
  router.get('/become-a-vendor', { step: targetStep }, {
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

/**
 * Normalize the website URL — auto-prepend https:// if missing,
 * strip duplicate protocols, trim whitespace.
 */
function normalizeWebsite() {
  let v = (formData.value.website || '').trim();
  v = v.replace(/^(https?:\/\/)+/i, '');
  formData.value.website = v.length > 0 ? 'https://' + v : 'https://';
}

const handleStep1Submit = () => {
  normalizeWebsite();
  const hasDomain = (formData.value.website || '').replace(/^https?:\/\//i, '').trim().length > 0;
  if (!formData.value.companyName || !hasDomain || !formData.value.country) {
    return;
  }
  goToStep(2);
};

const handleStep2Submit = () => {
  step2ErrorMessage.value = '';
  if (!formData.value.fullName || !formData.value.email || !formData.value.password || !formData.value.confirmPassword) {
    step2ErrorMessage.value = 'Please fill in all required fields.';
    return;
  }
  if (!passwordStrong.value) {
    step2ErrorMessage.value = 'Password must include uppercase, lowercase, a number, and be at least 8 characters.';
    return;
  }
  if (formData.value.password !== formData.value.confirmPassword) {
    step2ErrorMessage.value = 'Passwords do not match.';
    return;
  }
  goToStep(3);
};

// Downscale + compress a logo before upload. Large PNGs from vendor asset
// packs (2000+ px, DSLR exports) previously blew past nginx's 1MB body limit
// and returned 413 with no user feedback. This resizes to max 1024px (plenty
// for a header logo) and re-encodes, keeping transparency. Robert @ Flawless
// hit this in Aug 2026 with the S1 Labs submission — three of his four
// brands went through fine, only the one with the oversized logo hung.
const compressLogo = (file) => new Promise((resolve, reject) => {
  const MAX_DIM = 1024;
  const url = URL.createObjectURL(file);
  const img = new Image();
  img.onload = () => {
    try {
      let { width, height } = img;
      if (width <= MAX_DIM && height <= MAX_DIM && file.size < 900_000) {
        URL.revokeObjectURL(url);
        return resolve(file); // already small enough
      }
      const scale = Math.min(MAX_DIM / width, MAX_DIM / height, 1);
      width = Math.round(width * scale);
      height = Math.round(height * scale);
      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, width, height);
      canvas.toBlob((blob) => {
        URL.revokeObjectURL(url);
        if (!blob) return reject(new Error('Could not compress image.'));
        // Wrap in a File so backend validation still sees a filename + type.
        const compressed = new File([blob], file.name, { type: 'image/png', lastModified: Date.now() });
        resolve(compressed);
      }, 'image/png');
    } catch (err) {
      URL.revokeObjectURL(url);
      reject(err);
    }
  };
  img.onerror = () => {
    URL.revokeObjectURL(url);
    reject(new Error('Could not read image.'));
  };
  img.src = url;
});

const handleLogoUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.type !== 'image/png') {
    alert('Please upload a PNG file only.');
    event.target.value = '';
    formData.value.logoFile = null;
    return;
  }

  try {
    const optimized = await compressLogo(file);
    if (optimized.size > 3_500_000) {
      alert('Logo is still too large after compression. Please use a simpler PNG under 3.5 MB.');
      event.target.value = '';
      formData.value.logoFile = null;
      return;
    }
    formData.value.logoFile = optimized;
  } catch (err) {
    console.error('Logo compression failed:', err);
    // Fall back to raw file — server-side validation is the safety net.
    formData.value.logoFile = file;
  }
};

const page = usePage();
const isSubmitting = ref(false);
const showSuccessMessage = ref(false);
const showApiVideo = ref(false);
const apiVideoUrl = '/videos/woocommerce-rest-api-guide.mp4';

// --- Submission error state ---
const fieldErrors = ref({});
const submissionError = ref('');
const fieldStepMap = {
  companyName: 1, website: 1, yearEstablished: 1, country: 1,
  fullName: 2, email: 2, phone: 2, password: 2, password_confirmation: 2,
  productCount: 3, companyDescription: 3, paymentMethods: 3,
  shippingInformation: 3, returnPolicy: 3, businessHours: 3,
  uniqueSellingPoints: 3, logoFile: 3,
  apiConsumerKey: 4, apiConsumerSecret: 4, connectionMethod: 4,
};

const handleStep3Submit = () => {
  step.value = 4;
}

const handleStep4Submit = () => {
  if (step4Invalid.value) return;

  // Defensive validation: anything missing here means we're in a recovered-draft
  // state where password fields couldn't be persisted. Bounce the user back to
  // the relevant step instead of silently doing nothing.
  const missingStep1 = !formData.value.companyName || !formData.value.website || !formData.value.country;
  const missingStep2 = !formData.value.fullName || !formData.value.email ||
                       !formData.value.password || !formData.value.confirmPassword;

  if (missingStep1 || missingStep2) {
    const target = missingStep1 ? 1 : 2;
    step.value = target;
    submissionError.value = target === 2
      ? 'For your security, please re-enter your password and confirm it to complete registration.'
      : 'Some required details are missing — please review the earlier steps.';
    router.get('/become-a-vendor', { step: target }, { preserveState: true, replace: true });
    window.scrollTo({ top: 0, behavior: 'smooth' });
    return;
  }

  // Validate password match
  if (formData.value.password !== formData.value.confirmPassword) {
    step.value = 2;
    submissionError.value = 'Passwords do not match. Please re-enter them.';
    router.get('/become-a-vendor', { step: 2 }, { preserveState: true, replace: true });
    window.scrollTo({ top: 0, behavior: 'smooth' });
    return;
  }

  isSubmitting.value = true;

  // Create form with Inertia's useForm for proper file handling
  const submitForm = useForm({
    // Step 1
    companyName: formData.value.companyName,
    website: formData.value.website,
    yearEstablished: formData.value.yearEstablished || null,
    country: formData.value.country,
    
    // Step 2
    fullName: formData.value.fullName,
    email: formData.value.email,
    phone: formData.value.phone || null,
    password: formData.value.password,
    password_confirmation: formData.value.confirmPassword,
    
    // Step 3
    // If the vendor refused API access, send manual mode and drop any keys
    // they may have typed before checking the box.
    connectionMethod: formData.value.refuseApiAccess
      ? 'manual'
      : (formData.value.connectionMethod || 'auto_scrape'),
    apiConsumerKey: formData.value.refuseApiAccess ? null : (formData.value.apiConsumerKey || null),
    apiConsumerSecret: formData.value.refuseApiAccess ? null : (formData.value.apiConsumerSecret || null),
    refuseApiAccess: formData.value.refuseApiAccess,
    productCount: formData.value.productCount || null,
    companyDescription: formData.value.companyDescription || null,
    paymentMethods: formData.value.paymentMethods || [],
    shippingInformation: formData.value.shippingInformation || null,
    returnPolicy: formData.value.returnPolicy || null,
    businessHours: formData.value.businessHours || null,
    uniqueSellingPoints: formData.value.uniqueSellingPoints || null,
    tagline: formData.value.tagline || null,
    logoFile: formData.value.logoFile || null,
    trustpilotUrl: formData.value.trustpilotUrl || null,
    googleReviewsUrl: formData.value.googleReviewsUrl || null,
    reviewsIoUrl: formData.value.reviewsIoUrl || null,
    pepreviewproUrl: formData.value.pepreviewproUrl || null,
    _token: page.props.csrf_token,
  });

  // Ensure CSRF token is up to date before submission
  const csrfToken = page.props.csrf_token || '';
  submitForm._token = csrfToken;

  // Reset error state before submitting
  fieldErrors.value = {};
  submissionError.value = '';

  submitForm.post('/become-a-vendor', {
    preserveScroll: true,
    forceFormData: true, // Force FormData for file uploads
    onSuccess: (page) => {
      isSubmitting.value = false;
      showSuccessMessage.value = true;
      clearDraft();
      fieldErrors.value = {};
      submissionError.value = '';
      // Scroll to top to show success message
      window.scrollTo({ top: 0, behavior: 'smooth' });
      router.reload({ only: ['pending_vendors_count'] });
    },
    onError: (errors) => {
      isSubmitting.value = false;
      console.error('Registration errors:', errors);

      // 419 CSRF expired — refresh to get new token
      if (errors.message && errors.message.includes('419')) {
        window.location.reload();
        return;
      }

      // No structured errors? Treat as a generic failure.
      if (!errors || Object.keys(errors).length === 0) {
        submissionError.value = 'Something went wrong on our end. Please try again in a moment.';
        return;
      }

      fieldErrors.value = { ...errors };

      // Find the earliest step with a problem and jump back to it
      let earliestStep = 4;
      for (const field in errors) {
        const fs = fieldStepMap[field];
        if (fs && fs < earliestStep) earliestStep = fs;
      }

      // Build a readable summary so the user sees the actual problem
      // (e.g. "Logo file is too large") instead of a generic message.
      const errorList = Object.values(errors).flat().filter(Boolean);
      const summary = errorList.slice(0, 3).join(' ')
        + (errorList.length > 3 ? ` (+${errorList.length - 3} more — see fields below)` : '');

      if (earliestStep < step.value) {
        step.value = earliestStep;
        router.get('/become-a-vendor', { step: earliestStep }, { preserveState: true, replace: true });
        submissionError.value = summary || "Some details need fixing — we've taken you back to the relevant step.";
      } else {
        submissionError.value = summary || 'Please review the errors below and try again.';
      }

      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};

// Old step 4 handler removed — now handled above
</script>
