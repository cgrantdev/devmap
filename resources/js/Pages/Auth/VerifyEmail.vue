<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Verify Your Email
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          We've sent a verification link to your email address
        </p>
      </div>

      <!-- Success Message -->
      <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        {{ $page.props.flash.success }}
      </div>

      <!-- Error Message -->
      <div v-if="$page.props.flash.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {{ $page.props.flash.error }}
      </div>

      <!-- Info Message -->
      <div v-if="$page.props.flash.info" class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
        {{ $page.props.flash.info }}
      </div>

      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <!-- User Info -->
        <div v-if="$page.props.auth.user" class="mb-6 p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-900">Welcome, {{ $page.props.auth.user.name }}</p>
              <p class="text-sm text-gray-600">{{ $page.props.auth.user.email }}</p>
            </div>
            <button
              @click="logout"
              class="text-sm text-red-600 hover:text-red-800 font-medium"
            >
              Logout
            </button>
          </div>
        </div>

        <div class="text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Check your email</h3>
          <p class="mt-1 text-sm text-gray-500">
            We've sent a verification link to your email address. Please check your inbox and click the link to verify your account.
          </p>
        </div>

        <div class="mt-6">
          <form @submit.prevent="resendVerification">
            <!-- Only show email input if user is not logged in -->
            <div v-if="!$page.props.auth.user">
              <label for="email" class="block text-sm font-medium text-gray-700">
                Email address
              </label>
              <div class="mt-1">
                <input
                  id="email"
                  v-model="form.email"
                  name="email"
                  type="email"
                  autocomplete="email"
                  required
                  class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  placeholder="Enter your email address"
                />
              </div>
            </div>

            <div class="mt-6">
              <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ form.processing ? 'Sending...' : ($page.props.auth.user ? 'Resend Verification Email' : 'Send Verification Email') }}
              </button>
            </div>
          </form>
        </div>

        <div class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">Or</span>
            </div>
          </div>

          <div class="mt-6 space-y-3">
            <!-- Show Login Now button if email was just verified -->
            <Link
              v-if="$page.props.flash.success && $page.props.flash.success.includes('verified successfully')"
              href="/vendor/dashboard"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            >
              Go to Dashboard
            </Link>
            
            <Link
              href="/login"
              class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Back to Login
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const form = useForm({
  email: usePage().props.auth?.user?.email || '',
  _token: usePage().props.csrf_token
})

function resendVerification() {
  form.post('/email/verification-notification', {
    onSuccess: () => {
      // Don't clear the email field since user is logged in
    }
  })
}

function logout() {
  form.post('/logout')
}
</script> 