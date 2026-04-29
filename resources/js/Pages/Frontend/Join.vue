<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Top bar (minimal, no main nav) -->
    <header class="bg-[#0F172A] text-white">
      <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <img :src="'/images/logo.png'" alt="PeptideMap" class="h-7 brightness-0 invert" />
        </div>
        <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.18em] text-white/60">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          Private Invitation
        </div>
      </div>
    </header>

    <!-- Invitation hero -->
    <section class="bg-gradient-to-b from-[#0F172A] via-[#1E293B] to-[#0F172A] text-white relative overflow-hidden">
      <!-- subtle grid texture -->
      <div class="absolute inset-0 opacity-[0.04]" style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 32px 32px;"></div>

      <div class="relative max-w-3xl mx-auto px-6 py-20 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 mb-8 border border-white/15 rounded-full text-[10px] uppercase tracking-[0.18em] text-white/70">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          Private Invitation · Launch Cohort 2026
        </div>

        <p v-if="invitation?.company" class="text-emerald-300/90 text-sm font-medium mb-3">
          Hi {{ invitation.company }},
        </p>

        <h1 class="text-4xl sm:text-5xl font-light leading-[1.05] tracking-tight mb-5">
          You've been invited<br />
          <span class="font-medium bg-gradient-to-r from-white to-emerald-200 bg-clip-text text-transparent">to join PeptideMap.</span>
        </h1>

        <p class="text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed mb-10">
          We're hand-selecting peptide vendors with verifiable quality, transparent COAs, and competitive pricing for our directory's launch cohort. This invitation is extended to your team.
        </p>

        <!-- Three pillars -->
        <div class="grid sm:grid-cols-3 gap-4 max-w-2xl mx-auto mt-12">
          <div class="border border-white/10 bg-white/[0.03] backdrop-blur-sm p-5 text-left">
            <div class="text-emerald-400 mb-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="text-sm font-semibold text-white mb-1">Verified Listing</div>
            <div class="text-xs text-slate-400 leading-relaxed">Featured placement on a directory built around vendor verification, not advertising spend.</div>
          </div>
          <div class="border border-white/10 bg-white/[0.03] backdrop-blur-sm p-5 text-left">
            <div class="text-emerald-400 mb-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </div>
            <div class="text-sm font-semibold text-white mb-1">Compare-Page Placement</div>
            <div class="text-xs text-slate-400 leading-relaxed">Side-by-side comparison surfaces your pricing and quality directly to qualified researchers.</div>
          </div>
          <div class="border border-white/10 bg-white/[0.03] backdrop-blur-sm p-5 text-left">
            <div class="text-emerald-400 mb-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            </div>
            <div class="text-sm font-semibold text-white mb-1">Vendor Analytics</div>
            <div class="text-xs text-slate-400 leading-relaxed">Live visibility into clicks, comparisons, and reviews — direct from your private dashboard.</div>
          </div>
        </div>

        <a href="#accept" class="inline-flex items-center gap-2 mt-12 px-7 py-3 bg-white text-slate-900 text-sm font-semibold rounded-full hover:bg-slate-100 transition-all hover:-translate-y-0.5">
          Accept Your Invitation
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
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
        <!-- Progress -->
        <div class="bg-white border border-slate-200 rounded-t-lg px-8 py-5">
          <div class="flex items-center justify-between">
            <div v-for="(label, i) in stepLabels" :key="i" class="contents">
              <div class="flex flex-col items-center flex-1">
                <div :class="['w-9 h-9 rounded-full flex items-center justify-center transition-all text-sm font-semibold', step >= i + 1 ? 'bg-[#0F172A] text-white' : 'bg-slate-100 text-slate-400']">
                  <svg v-if="step > i + 1" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/></svg>
                  <span v-else>{{ i + 1 }}</span>
                </div>
                <span class="text-[11px] text-slate-500 mt-2 hidden sm:block">{{ label }}</span>
              </div>
              <div v-if="i < stepLabels.length - 1" :class="['h-px flex-1 mx-2 transition-all', step > i + 1 ? 'bg-[#0F172A]' : 'bg-slate-200']"></div>
            </div>
          </div>
        </div>

        <div class="bg-white border-x border-b border-slate-200 rounded-b-lg p-8 sm:p-10">
          <!-- Step 1: Company -->
          <form v-if="step === 1" @submit.prevent="handleStep1Submit" class="space-y-6">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">Company Information</h2>
              <p class="text-sm text-slate-500 mt-1">Tell us about your peptide company</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Company Name <span class="text-rose-500">*</span></label>
              <input v-model="formData.companyName" type="text" required placeholder="Your Peptide Company LLC"
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400" />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Website <span class="text-rose-500">*</span></label>
              <input v-model="formData.website" type="url" required placeholder="https://yourcompany.com"
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400" />
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
                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400" />
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
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password <span class="text-rose-500">*</span></label>
                <input v-model="formData.confirmPassword" type="password" required placeholder="********"
                  :class="['w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2', passwordMismatch ? 'border-rose-300 focus:ring-rose-300' : 'border-slate-300 focus:ring-slate-400']" />
                <p v-if="passwordMismatch" class="text-xs text-rose-600 mt-1">Passwords do not match</p>
              </div>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
              <button type="button" @click="goToStep(1)" class="text-sm text-slate-500 hover:text-slate-700">Back</button>
              <button type="submit" :disabled="passwordMismatch"
                :class="['flex items-center gap-2 px-6 py-2.5 text-white text-sm font-medium rounded-lg transition-colors', passwordMismatch ? 'bg-slate-300 cursor-not-allowed' : 'bg-[#0F172A] hover:bg-slate-800']">
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
              <h2 class="text-xl font-semibold text-slate-900">Connect Your Store</h2>
              <p class="text-sm text-slate-500 mt-1">Add your WooCommerce REST API key so we can sync your product catalog. You can also skip and add this later.</p>
            </div>

            <!-- API Guide -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg overflow-hidden">
              <button type="button" @click="showApiGuide = !showApiGuide"
                class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-100 transition-colors">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-slate-700 text-white flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                  </div>
                  <div>
                    <div class="text-sm font-semibold text-slate-800">How to get your WooCommerce REST API Key</div>
                    <div class="text-xs text-slate-500 mt-0.5">Step-by-step guide</div>
                  </div>
                </div>
                <svg :class="['w-5 h-5 text-slate-400 transition-transform', showApiGuide ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
              </button>
              <div v-if="showApiGuide" class="px-5 pb-5 space-y-5 border-t border-slate-200 pt-5">
                <div class="flex items-start gap-3">
                  <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                  <p class="text-sm text-slate-700">Log in to your WordPress admin panel at <code class="px-1.5 py-0.5 bg-slate-200 rounded">yoursite.com/wp-admin</code></p>
                </div>
                <div class="flex items-start gap-3">
                  <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                  <div class="flex-1">
                    <p class="text-sm text-slate-700">Navigate to <strong>WooCommerce → Settings → Advanced → REST API</strong></p>
                    <div class="mt-2 flex items-center gap-2 text-xs flex-wrap">
                      <span class="px-2 py-1 bg-slate-100 rounded text-slate-700 font-medium">WooCommerce</span>
                      <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                      <span class="px-2 py-1 bg-slate-100 rounded text-slate-700 font-medium">Settings</span>
                      <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                      <span class="px-2 py-1 bg-slate-100 rounded text-slate-700 font-medium">Advanced</span>
                      <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                      <span class="px-2 py-1 bg-emerald-100 rounded text-emerald-700 font-semibold">REST API</span>
                    </div>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                  <p class="text-sm text-slate-700">Click <strong>"Add key"</strong></p>
                </div>
                <div class="flex items-start gap-3">
                  <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                  <div class="flex-1">
                    <p class="text-sm text-slate-700 mb-2">Set Description = <strong>PeptideMap</strong>, Permissions = <strong>Read</strong></p>
                    <p class="text-xs text-slate-500">We only need read access to import your products. We never modify your store.</p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <span class="w-6 h-6 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">5</span>
                  <div class="flex-1">
                    <p class="text-sm text-slate-700">Click <strong>"Generate API key"</strong> and copy both keys below</p>
                    <div class="mt-2 rounded border border-amber-200 bg-amber-50 p-2.5 text-xs text-amber-800 flex items-start gap-2">
                      <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                      <span><strong>Important:</strong> The Consumer Secret is only shown once.</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Consumer Key</label>
                <input v-model="formData.apiConsumerKey" type="text" placeholder="ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-400" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Consumer Secret</label>
                <input v-model="formData.apiConsumerSecret" type="password" placeholder="cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                  class="w-full px-4 py-2.5 border border-slate-300 rounded-lg font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-400" />
              </div>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
              <button type="button" @click="goToStep(3)" class="text-sm text-slate-500 hover:text-slate-700">Back</button>
              <div class="flex items-center gap-3">
                <button type="button" @click="skipAndSubmit" :disabled="isSubmitting"
                  class="px-5 py-2.5 text-sm text-slate-600 hover:text-slate-800 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                  Skip for now
                </button>
                <button type="submit" :disabled="isSubmitting"
                  :class="['flex items-center gap-2 px-6 py-2.5 bg-[#0F172A] hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-colors', isSubmitting ? 'opacity-50 cursor-not-allowed' : '']">
                  <span v-if="isSubmitting">Creating...</span>
                  <span v-else>Complete Registration</span>
                  <svg v-if="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                  <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                </button>
              </div>
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
import { ref, computed, onMounted } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  step: { type: Number, default: 1 },
  locations: { type: Array, default: () => [] },
  invitation: { type: Object, default: () => ({ company: null, ref: null }) },
})

const stepLabels = ['Company', 'Contact', 'Business', 'Connect']

const formData = ref({
  companyName: props.invitation?.company || '',
  website: '',
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
const showApiGuide = ref(false)
const isSubmitting = ref(false)
const showSuccessMessage = ref(false)

const passwordMismatch = computed(() => {
  return formData.value.password && formData.value.confirmPassword &&
         formData.value.password !== formData.value.confirmPassword
})

onMounted(() => {
  step.value = props.step || 1
})

const goToStep = (newStep) => {
  step.value = newStep
  // Scroll to form
  setTimeout(() => {
    const el = document.getElementById('accept')
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 50)
}

const handleStep1Submit = () => {
  if (!formData.value.companyName || !formData.value.website || !formData.value.country) return
  goToStep(2)
}

const handleStep2Submit = () => {
  if (!formData.value.fullName || !formData.value.email || !formData.value.password || !formData.value.confirmPassword) return
  if (formData.value.password !== formData.value.confirmPassword) return
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
    connectionMethod: formData.value.connectionMethod || 'auto_scrape',
    apiConsumerKey: formData.value.apiConsumerKey || null,
    apiConsumerSecret: formData.value.apiConsumerSecret || null,
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

  submitForm.post('/become-a-vendor', {
    preserveScroll: false,
    forceFormData: true,
    onSuccess: () => {
      isSubmitting.value = false
      showSuccessMessage.value = true
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
    onError: (errors) => {
      isSubmitting.value = false
      console.error('Registration errors:', errors)
      if (errors.message && errors.message.includes('419')) {
        window.location.reload()
      }
    },
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}

const handleStep4Submit = () => {
  submitRegistration()
}

const skipAndSubmit = () => {
  formData.value.apiConsumerKey = ''
  formData.value.apiConsumerSecret = ''
  formData.value.connectionMethod = 'auto_scrape'
  submitRegistration()
}
</script>
