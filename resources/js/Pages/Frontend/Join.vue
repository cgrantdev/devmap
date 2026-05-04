<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Top bar (minimal, no main nav) -->
    <header class="bg-white border-b border-slate-200">
      <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <img :src="'/images/logo.png'" alt="PeptideMap" class="h-7" />
        </div>
        <a href="https://demo.peptidemap.com" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-900 transition-colors">
          Preview the directory
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M7 7h10v10"/></svg>
        </a>
      </div>
    </header>

    <!-- Invitation hero -->
    <section class="bg-white border-b border-slate-200">
      <div class="max-w-3xl mx-auto px-6 py-12 sm:py-16 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1 mb-6 bg-slate-100 rounded-full text-[10px] uppercase tracking-[0.18em] text-slate-600 font-medium">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          Private Invitation
        </div>

        <p v-if="invitation?.company" class="text-slate-500 text-sm mb-3">
          Hi {{ invitation.company }},
        </p>

        <h1 class="text-3xl sm:text-4xl font-semibold text-slate-900 leading-tight tracking-tight mb-5">
          You've been invited to join PeptideMap.
        </h1>

        <p class="text-base sm:text-lg text-slate-600 max-w-xl mx-auto leading-relaxed mb-8">
          We're hand-selecting peptide vendors with verifiable quality, transparent COAs, and competitive pricing for our launch cohort.
        </p>

        <a href="https://demo.peptidemap.com" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-700 hover:text-slate-900 transition-colors">
          Preview the directory at demo.peptidemap.com
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M7 7h10v10"/></svg>
        </a>
      </div>
    </section>

    <!-- Success state -->
    <div v-if="showSuccessMessage || $page.props.flash?.success" id="accept" class="max-w-3xl mx-auto px-6 py-16">
      <div class="bg-emerald-50 border border-emerald-200 p-10 flex flex-col items-center text-center rounded-lg">
        <svg class="w-16 h-16 text-emerald-600 mb-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="m9 12 2 2 4-4"></path>
        </svg>
        <h2 class="text-emerald-900 font-semibold text-2xl mb-2">Invitation accepted.</h2>
        <p class="text-emerald-800 text-base max-w-xl">
          Your application has been received. Our team will review it and follow up at the email you provided. Welcome to PeptideMap.
        </p>
      </div>
    </div>

    <!-- Form section -->
    <section v-if="!showSuccessMessage && !$page.props.flash?.success" id="accept" class="py-16 px-6">
      <div class="max-w-3xl mx-auto">
        <!-- Resumed-draft notice -->
        <div v-if="draftRestored" class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-3 text-sm text-emerald-800">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          <span class="flex-1">Welcome back — we picked up where you left off.</span>
          <button @click="discardDraft" class="text-xs font-medium text-emerald-700 hover:text-emerald-900 underline underline-offset-2">Start over</button>
        </div>

        <!-- Progress -->
        <div class="bg-white border border-slate-200 rounded-t-lg px-8 py-5">
          <div class="flex items-center justify-between">
            <div v-for="(label, i) in stepLabels" :key="i" class="contents">
              <button
                type="button"
                @click="jumpToStep(i + 1)"
                :disabled="i + 1 > maxStepReached"
                :class="['flex flex-col items-center flex-1 transition-opacity', (i + 1 <= maxStepReached) ? 'cursor-pointer hover:opacity-75' : 'cursor-not-allowed opacity-100']"
                :title="(i + 1 <= maxStepReached) ? `Jump to ${label}` : 'Complete the current step first'"
              >
                <div :class="['w-9 h-9 rounded-full flex items-center justify-center transition-all text-sm font-semibold', step >= i + 1 ? 'bg-[#0F172A] text-white' : 'bg-slate-100 text-slate-400']">
                  <svg v-if="step > i + 1" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/></svg>
                  <span v-else>{{ i + 1 }}</span>
                </div>
                <span class="text-[11px] text-slate-500 mt-2 hidden sm:block">{{ label }}</span>
              </button>
              <div v-if="i < stepLabels.length - 1" :class="['h-px flex-1 mx-2 transition-all', step > i + 1 ? 'bg-[#0F172A]' : 'bg-slate-200']"></div>
            </div>
          </div>
        </div>

        <!-- Submission error banner -->
        <div v-if="submissionError" class="mt-4 px-4 py-3 bg-rose-50 border border-rose-200 rounded-lg flex items-start gap-3 text-sm text-rose-800">
          <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span class="flex-1">{{ submissionError }}</span>
          <button @click="submissionError = ''" class="text-rose-600 hover:text-rose-900 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="bg-white border-x border-b border-slate-200 rounded-b-lg p-8 sm:p-10 mt-0" :class="submissionError ? 'mt-4' : ''">
          <!-- Step 1: Company -->
          <form v-if="step === 1" @submit.prevent="handleStep1Submit" class="space-y-6">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">Company Information</h2>
              <p class="text-sm text-slate-500 mt-1">Tell us about your peptide company</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Company Name <span class="text-rose-500">*</span></label>
              <input v-model="formData.companyName" type="text" required placeholder="Your Peptide Company LLC"
                :class="['w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', fieldErrors.companyName ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']" />
              <p v-if="fieldErrors.companyName" class="text-xs text-rose-600 mt-1">{{ fieldErrors.companyName }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Website <span class="text-rose-500">*</span></label>
              <input v-model="formData.website" @blur="normalizeWebsite" type="url" required placeholder="https://yourcompany.com"
                :class="['w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', fieldErrors.website ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']" />
              <p v-if="fieldErrors.website" class="text-xs text-rose-600 mt-1">{{ fieldErrors.website }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Year Established</label>
                <input v-model="formData.yearEstablished" type="text" placeholder="2020"
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Country <span class="text-rose-500">*</span></label>
                <select v-model="formData.country" required
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400">
                  <option value="">Select...</option>
                  <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                </select>
              </div>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
              <span class="text-xs text-slate-400">Step 1 of 4</span>
              <button type="submit" class="flex items-center gap-2 px-6 py-2.5 bg-[#0F172A] hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-colors">
                Continue
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </button>
            </div>
          </form>

          <!-- Step 2: Contact -->
          <form v-if="step === 2" @submit.prevent="handleStep2Submit" class="space-y-6">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">Contact Details</h2>
              <p class="text-sm text-slate-500 mt-1">Primary contact for your account</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Full Name <span class="text-rose-500">*</span></label>
              <input v-model="formData.fullName" type="text" required placeholder="John Smith"
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400" />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
              <input v-model="formData.email" type="email" required placeholder="john@yourcompany.com"
                :class="['w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', fieldErrors.email ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']" />
              <p v-if="fieldErrors.email" class="text-xs text-rose-600 mt-1">{{ fieldErrors.email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
              <input v-model="formData.phone" type="tel" placeholder="+1 (555) 123-4567"
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Password <span class="text-rose-500">*</span></label>
                <input v-model="formData.password" type="password" required placeholder="********"
                  :class="['w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', passwordWeak ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']" />
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
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password <span class="text-rose-500">*</span></label>
                <input v-model="formData.confirmPassword" type="password" required placeholder="********"
                  :class="['w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', passwordMismatch ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']" />
                <p v-if="passwordMismatch" class="text-xs text-rose-600 mt-1">Passwords do not match</p>
              </div>
            </div>

            <p v-if="step2ErrorMessage" class="text-sm text-rose-600">{{ step2ErrorMessage }}</p>

            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
              <button type="button" @click="goToStep(1)" class="text-sm text-slate-500 hover:text-slate-700">Back</button>
              <button type="submit" :disabled="step2Invalid"
                :class="['flex items-center gap-2 px-6 py-2.5 text-white text-sm font-medium rounded-lg transition-colors', step2Invalid ? 'bg-slate-300 cursor-not-allowed' : 'bg-[#0F172A] hover:bg-slate-800']">
                Continue
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </button>
            </div>
          </form>

          <!-- Step 3: Business Info -->
          <form v-if="step === 3" @submit.prevent="handleStep3Submit" class="space-y-6">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">Business Information</h2>
              <p class="text-sm text-slate-500 mt-1">Help us understand your business</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Number of Products</label>
              <select v-model="formData.productCount" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400">
                <option value="">Select range...</option>
                <option value="1-10">1 - 10 products</option>
                <option value="11-25">11 - 25 products</option>
                <option value="26-50">26 - 50 products</option>
                <option value="51-100">51 - 100 products</option>
                <option value="100+">100+ products</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Company Description</label>
              <textarea v-model="formData.companyDescription" rows="4" placeholder="Tell us about your company..."
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Payment Methods Accepted</label>
              <div class="grid grid-cols-2 gap-2">
                <label v-for="method in ['Credit Card', 'PayPal', 'Cryptocurrency', 'Bank Transfer']" :key="method"
                  class="flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                  <input type="checkbox" :value="method" v-model="formData.paymentMethods" class="w-4 h-4 accent-slate-700" />
                  <span class="text-sm text-slate-700">{{ method }}</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Shipping Information</label>
              <textarea v-model="formData.shippingInformation" rows="3" placeholder="Shipping policies, methods, delivery times..."
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Return Policy</label>
              <textarea v-model="formData.returnPolicy" rows="3" placeholder="Return and refund policies..."
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Unique Selling Points</label>
              <textarea v-model="formData.uniqueSellingPoints" rows="3" placeholder="What makes your brand stand out?"
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Logo (PNG, 1000×1000px transparent)</label>
              <input type="file" accept="image/png" @change="handleLogoUpload"
                class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer" />
              <p v-if="formData.logoFile" class="text-xs text-slate-500 mt-1">Selected: {{ formData.logoFile.name }}</p>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
              <button type="button" @click="goToStep(2)" class="text-sm text-slate-500 hover:text-slate-700">Back</button>
              <button type="submit" class="flex items-center gap-2 px-6 py-2.5 bg-[#0F172A] hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-colors">
                Continue
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </button>
            </div>
          </form>

          <!-- Step 4: REST API Key -->
          <form v-if="step === 4" @submit.prevent="handleStep4Submit" class="space-y-6">
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

              <!-- Plugin shortcut callout -->
              <div class="mt-4 rounded-lg border border-indigo-200 bg-indigo-50/50 p-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                  <div class="flex-1">
                    <div class="text-sm font-semibold text-slate-900 mb-1">Skip the manual setup with our WordPress plugin</div>
                    <p class="text-sm text-slate-600 leading-relaxed mb-3">
                      Once registered, you'll get a unique connection token. Install the plugin in <strong>your</strong> WordPress site (<em>WooCommerce → PeptideMap</em>), paste the token there, and we'll auto-generate the read-only API key for you. No copying keys back and forth.
                    </p>
                    <a
                      href="/downloads/peptidemap-connect.zip"
                      class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-700 hover:text-indigo-900"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                      Download plugin (3.8 KB)
                    </a>
                  </div>
                </div>
              </div>
            </div>

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

            <div class="space-y-4" :class="{ 'opacity-50 pointer-events-none': formData.refuseApiAccess }">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Consumer Key <span v-if="!formData.refuseApiAccess" class="text-rose-500">*</span></label>
                <input v-model="formData.apiConsumerKey" type="text" :required="!formData.refuseApiAccess" :disabled="formData.refuseApiAccess" placeholder="ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                  :class="['w-full px-4 py-2.5 border rounded-lg font-mono text-sm focus:outline-none focus:ring-2', (consumerKeyInvalid || fieldErrors.apiConsumerKey) ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']" />
                <p v-if="fieldErrors.apiConsumerKey" class="text-xs text-rose-600 mt-1">{{ fieldErrors.apiConsumerKey }}</p>
                <p v-else-if="consumerKeyInvalid" class="text-xs text-rose-600 mt-1">Consumer Key must start with <code>ck_</code></p>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Consumer Secret <span v-if="!formData.refuseApiAccess" class="text-rose-500">*</span></label>
                <input v-model="formData.apiConsumerSecret" type="password" :required="!formData.refuseApiAccess" :disabled="formData.refuseApiAccess" placeholder="cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                  :class="['w-full px-4 py-2.5 border rounded-lg font-mono text-sm focus:outline-none focus:ring-2', (consumerSecretInvalid || fieldErrors.apiConsumerSecret) ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']" />
                <p v-if="fieldErrors.apiConsumerSecret" class="text-xs text-rose-600 mt-1">{{ fieldErrors.apiConsumerSecret }}</p>
                <p v-else-if="consumerSecretInvalid" class="text-xs text-rose-600 mt-1">Consumer Secret must start with <code>cs_</code></p>
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

            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
              <button type="button" @click="goToStep(3)" class="text-sm text-slate-500 hover:text-slate-700">Back</button>
              <button type="submit" :disabled="isSubmitting || step4Invalid"
                :class="['flex items-center gap-2 px-6 py-2.5 text-white text-sm font-medium rounded-lg transition-colors', (isSubmitting || step4Invalid) ? 'bg-slate-300 cursor-not-allowed' : 'bg-[#0F172A] hover:bg-slate-800']">
                <span v-if="isSubmitting">Creating...</span>
                <span v-else>Complete Registration</span>
                <svg v-if="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
              </button>
            </div>
          </form>
        </div>

        <p class="mt-8 text-center text-xs text-slate-400">
          Already a vendor partner?
          <a href="/vendor/login" class="text-slate-600 hover:text-slate-900 underline underline-offset-2">Sign in</a>
        </p>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-8 px-6">
      <div class="max-w-5xl mx-auto text-center text-xs text-slate-400 space-y-2">
        <p class="uppercase tracking-[0.18em] text-slate-500 font-medium">For Research Use Only</p>
        <p>The peptides listed on PeptideMap are intended for laboratory research applications only. Not for human consumption.</p>
        <p class="pt-2">© {{ new Date().getFullYear() }} PeptideMap. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'

// --- Draft persistence -----------------------------------------------------
// Form state survives page refreshes via localStorage. Sensitive fields
// (password, confirmPassword) and non-serializable fields (logoFile) are
// never persisted. Drafts auto-expire after 24 hours.
const DRAFT_KEY = 'pmap_join_signup_v1'
const DRAFT_TTL_MS = 24 * 60 * 60 * 1000
const DRAFT_SKIP_FIELDS = ['password', 'confirmPassword', 'logoFile']

function loadDraft() {
  try {
    const raw = localStorage.getItem(DRAFT_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    if (!parsed?.savedAt || Date.now() - parsed.savedAt > DRAFT_TTL_MS) {
      localStorage.removeItem(DRAFT_KEY)
      return null
    }
    return parsed
  } catch {
    return null
  }
}

function saveDraft(data, step) {
  try {
    const persistable = {}
    for (const key in data) {
      if (!DRAFT_SKIP_FIELDS.includes(key)) {
        persistable[key] = data[key]
      }
    }
    localStorage.setItem(DRAFT_KEY, JSON.stringify({
      savedAt: Date.now(),
      step,
      data: persistable,
    }))
  } catch {
    // Storage full / disabled — silently skip
  }
}

function clearDraft() {
  try { localStorage.removeItem(DRAFT_KEY) } catch {}
}

const props = defineProps({
  step: { type: Number, default: 1 },
  locations: { type: Array, default: () => [] },
  invitation: { type: Object, default: () => ({ company: null, ref: null }) },
})

const stepLabels = ['Company', 'Contact', 'Business', 'Connect']

const formData = ref({
  companyName: props.invitation?.company || '',
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
  logoFile: null,
})

const step = ref(props.step || 1)
// Tracks the highest step the user has progressed to. Step circles can be
// clicked to jump back to any step they've already visited; jumping forward
// past unfilled steps is blocked.
const maxStepReached = ref(props.step || 1)
const showApiVideo = ref(false)
const apiVideoUrl = '/videos/woocommerce-rest-api-guide.mp4'
const isSubmitting = ref(false)
const showSuccessMessage = ref(false)

// --- Submission error state ---
// Field-level errors keyed by backend field name (e.g. errors.email)
const fieldErrors = ref({})
// Top-of-form error message shown when submission fails
const submissionError = ref('')

// Maps each form field to the step that owns it, so we can jump the
// user to the right step when a field-level error comes back.
const fieldStepMap = {
  companyName: 1, website: 1, yearEstablished: 1, country: 1,
  fullName: 2, email: 2, phone: 2, password: 2, password_confirmation: 2,
  productCount: 3, companyDescription: 3, paymentMethods: 3,
  shippingInformation: 3, returnPolicy: 3, businessHours: 3,
  uniqueSellingPoints: 3, logoFile: 3,
  apiConsumerKey: 4, apiConsumerSecret: 4, connectionMethod: 4,
}

const passwordMismatch = computed(() => {
  return formData.value.password && formData.value.confirmPassword &&
         formData.value.password !== formData.value.confirmPassword
})

// Password strength
const passwordChecks = computed(() => ({
  length: (formData.value.password || '').length >= 8,
  upper: /[A-Z]/.test(formData.value.password || ''),
  lower: /[a-z]/.test(formData.value.password || ''),
  number: /[0-9]/.test(formData.value.password || ''),
}))
const passwordScore = computed(() => Object.values(passwordChecks.value).filter(Boolean).length)
const passwordStrong = computed(() => passwordScore.value === 4)
const passwordWeak = computed(() => formData.value.password && !passwordStrong.value)

const step2ErrorMessage = ref('')
const step2Invalid = computed(() => passwordMismatch.value || !passwordStrong.value)

// API key format
const consumerKeyInvalid = computed(() => {
  const v = (formData.value.apiConsumerKey || '').trim()
  return v.length > 0 && !v.startsWith('ck_')
})
const consumerSecretInvalid = computed(() => {
  const v = (formData.value.apiConsumerSecret || '').trim()
  return v.length > 0 && !v.startsWith('cs_')
})
const step4Invalid = computed(() => {
  // If the vendor opted out of API access, the keys aren't required.
  if (formData.value.refuseApiAccess) return false
  const k = (formData.value.apiConsumerKey || '').trim()
  const s = (formData.value.apiConsumerSecret || '').trim()
  return !k || !s || consumerKeyInvalid.value || consumerSecretInvalid.value
})

const draftRestored = ref(false)

onMounted(() => {
  step.value = props.step || 1

  // Restore from a saved draft, if any
  const draft = loadDraft()
  if (draft?.data) {
    Object.assign(formData.value, draft.data)
    if (draft.step >= 1 && draft.step <= 4) {
      step.value = draft.step
      maxStepReached.value = Math.max(maxStepReached.value, draft.step)
    }
    draftRestored.value = true
  }

  // Safety: ensure website has https:// prefix even after a draft restore
  if (!formData.value.website || formData.value.website === '') {
    formData.value.website = 'https://'
  }
})

// Persist on every change (deep watch) and on step change
watch(formData, (val) => saveDraft(val, step.value), { deep: true })
watch(step, (newStep) => saveDraft(formData.value, newStep))

function discardDraft() {
  clearDraft()
  // Reset all form fields to their initial values
  formData.value = {
    companyName: props.invitation?.company || '',
    website: 'https://', yearEstablished: '', country: '',
    fullName: '', email: '', phone: '', password: '', confirmPassword: '',
    connectionMethod: 'api_key', apiConsumerKey: '', apiConsumerSecret: '', refuseApiAccess: false,
    productCount: '', companyDescription: '',
    paymentMethods: [], shippingInformation: '', returnPolicy: '',
    businessHours: '', uniqueSellingPoints: '', logoFile: null,
  }
  step.value = 1
  maxStepReached.value = 1
  draftRestored.value = false
}

const goToStep = (newStep) => {
  step.value = newStep
  if (newStep > maxStepReached.value) maxStepReached.value = newStep
  // Scroll to form
  setTimeout(() => {
    const el = document.getElementById('accept')
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 50)
}

/**
 * Jump to a step from the progress indicator. Only allows navigation to
 * previously-reached steps so users can't skip ahead with unfilled fields.
 */
const jumpToStep = (targetStep) => {
  if (targetStep < 1 || targetStep > 4) return
  if (targetStep > maxStepReached.value) return
  step.value = targetStep
  setTimeout(() => {
    const el = document.getElementById('accept')
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 50)
}

/**
 * Normalize a website URL — auto-prepend https:// if missing, strip
 * accidental duplicate protocols like "https://https://example.com",
 * and trim whitespace. Runs on blur so it cleans up paste mistakes
 * before submission.
 */
function normalizeWebsite() {
  let v = (formData.value.website || '').trim()
  // Strip duplicate protocols (e.g. "https://http://x.com" or "https://https://x.com")
  v = v.replace(/^(https?:\/\/)+/i, '')
  // Anything left? prepend https://
  if (v.length > 0) {
    formData.value.website = 'https://' + v
  } else {
    formData.value.website = 'https://'
  }
}

const handleStep1Submit = () => {
  normalizeWebsite()
  // Reject if website is empty or just "https://" (no domain)
  const hasDomain = (formData.value.website || '').replace(/^https?:\/\//i, '').trim().length > 0
  if (!formData.value.companyName || !hasDomain || !formData.value.country) return
  goToStep(2)
}

const handleStep2Submit = () => {
  step2ErrorMessage.value = ''
  if (!formData.value.fullName || !formData.value.email || !formData.value.password || !formData.value.confirmPassword) {
    step2ErrorMessage.value = 'Please fill in all required fields.'
    return
  }
  if (!passwordStrong.value) {
    step2ErrorMessage.value = 'Password must include uppercase, lowercase, a number, and be at least 8 characters.'
    return
  }
  if (formData.value.password !== formData.value.confirmPassword) {
    step2ErrorMessage.value = 'Passwords do not match.'
    return
  }
  goToStep(3)
}

const handleStep3Submit = () => {
  goToStep(4)
}

const handleLogoUpload = (event) => {
  const file = event.target.files[0]
  if (!file) return
  if (file.type !== 'image/png') {
    alert('Please upload a PNG file only.')
    event.target.value = ''
    formData.value.logoFile = null
    return
  }
  formData.value.logoFile = file
}

const page = usePage()

const submitRegistration = () => {
  if (!formData.value.companyName || !formData.value.website || !formData.value.country ||
      !formData.value.fullName || !formData.value.email ||
      !formData.value.password || !formData.value.confirmPassword) return

  if (formData.value.password !== formData.value.confirmPassword) return

  isSubmitting.value = true

  // Append invitation ref to companyDescription so it's tracked alongside the vendor record
  let description = formData.value.companyDescription || ''
  if (props.invitation?.ref) {
    description = description ? `${description}\n\n[Invitation ref: ${props.invitation.ref}]` : `[Invitation ref: ${props.invitation.ref}]`
  }

  const submitForm = useForm({
    companyName: formData.value.companyName,
    website: formData.value.website,
    yearEstablished: formData.value.yearEstablished || null,
    country: formData.value.country,
    fullName: formData.value.fullName,
    email: formData.value.email,
    phone: formData.value.phone || null,
    password: formData.value.password,
    password_confirmation: formData.value.confirmPassword,
    // If the vendor refused API access, send manual mode and drop any keys
    // they may have typed before checking the box.
    connectionMethod: formData.value.refuseApiAccess
      ? 'manual'
      : (formData.value.connectionMethod || 'auto_scrape'),
    apiConsumerKey: formData.value.refuseApiAccess ? null : (formData.value.apiConsumerKey || null),
    apiConsumerSecret: formData.value.refuseApiAccess ? null : (formData.value.apiConsumerSecret || null),
    refuseApiAccess: formData.value.refuseApiAccess,
    productCount: formData.value.productCount || null,
    companyDescription: description || null,
    paymentMethods: formData.value.paymentMethods || [],
    shippingInformation: formData.value.shippingInformation || null,
    returnPolicy: formData.value.returnPolicy || null,
    businessHours: formData.value.businessHours || null,
    uniqueSellingPoints: formData.value.uniqueSellingPoints || null,
    logoFile: formData.value.logoFile || null,
    _token: page.props.csrf_token,
  })

  // Reset error state before submitting
  fieldErrors.value = {}
  submissionError.value = ''

  submitForm.post('/become-a-vendor', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      isSubmitting.value = false
      showSuccessMessage.value = true
      clearDraft()
      fieldErrors.value = {}
      submissionError.value = ''
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
    onError: (errors) => {
      isSubmitting.value = false
      console.error('Registration errors:', errors)

      // 419 CSRF expired — refresh to get new token
      if (errors.message && errors.message.includes('419')) {
        window.location.reload()
        return
      }

      // No structured errors? Treat as a generic failure.
      if (!errors || Object.keys(errors).length === 0) {
        submissionError.value = 'Something went wrong on our end. Please try again in a moment.'
        return
      }

      fieldErrors.value = { ...errors }

      // Find the earliest step that has an error and jump to it
      let earliestStep = 4
      for (const field in errors) {
        const fieldStep = fieldStepMap[field]
        if (fieldStep && fieldStep < earliestStep) earliestStep = fieldStep
      }

      if (earliestStep < step.value) {
        // Jump back to the step with the problem
        step.value = earliestStep
        submissionError.value = "Some details need fixing — we've taken you back to the relevant step."
      } else {
        // Errors on current step — show a banner summary
        const errorMessages = Object.values(errors).flat()
        submissionError.value = errorMessages[0] || 'Please review the errors below and try again.'
      }

      setTimeout(() => {
        const el = document.getElementById('accept')
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }, 50)
    },
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}

const handleStep4Submit = () => {
  if (step4Invalid.value) return
  submitRegistration()
}
</script>
