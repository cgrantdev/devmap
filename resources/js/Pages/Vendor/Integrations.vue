<template>
  <Layout>
    <div class="p-8 max-w-3xl">
      <h1 class="text-3xl font-bold text-slate-900 mb-2">Integrations</h1>
      <p class="text-slate-600 mb-8">
        Connect your WooCommerce store so PeptideMap automatically imports and keeps your products in sync.
      </p>

      <!-- Connected state -->
      <div v-if="config && config.has_credentials" class="bg-white border border-green-200 rounded-lg p-6 mb-6">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500"></span>
              <span class="font-semibold text-green-700">Connected</span>
            </div>
            <div class="text-sm text-slate-700 mb-1">Store: <span class="font-mono">{{ config.store_url }}</span></div>
            <div class="text-sm text-slate-600">Sync frequency: {{ config.frequency }}</div>
            <div class="text-sm text-slate-500 mt-2">
              Last sync: {{ config.last_run_at || 'never' }} · Next: {{ config.next_run_at || '—' }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
              {{ config.success_count }} successful syncs, {{ config.error_count }} failures
            </div>
            <div v-if="config.last_error" class="mt-2 text-xs text-red-600 bg-red-50 border border-red-200 rounded px-2 py-1">
              Last error: {{ config.last_error }}
            </div>
          </div>
          <div class="flex flex-col gap-2">
            <button @click="syncNow" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
              Sync now
            </button>
            <button @click="disconnect" class="px-4 py-2 border border-red-300 text-red-600 text-sm rounded hover:bg-red-50">
              Disconnect
            </button>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="bg-white border border-slate-200 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">
          {{ config && config.has_credentials ? 'Update connection' : 'Connect WooCommerce store' }}
        </h2>

        <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-6 text-sm text-slate-700">
          <p class="font-semibold mb-2">How to get your API keys:</p>
          <ol class="list-decimal list-inside space-y-1 text-slate-600">
            <li>Log into your WordPress admin</li>
            <li>Go to <b>WooCommerce → Settings → Advanced → REST API</b></li>
            <li>Click <b>Add key</b></li>
            <li>Description: "PeptideMap"</li>
            <li>Permissions: <b>Read</b> (we never modify your store)</li>
            <li>Click <b>Generate API key</b> and paste them below</li>
          </ol>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Store URL *</label>
            <input
              v-model="form.store_url"
              type="url"
              required
              placeholder="https://yourstore.com"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="errors.store_url" class="text-xs text-red-600 mt-1">{{ errors.store_url }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Consumer Key *</label>
            <input
              v-model="form.consumer_key"
              type="text"
              required
              placeholder="ck_..."
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
            />
            <p v-if="errors.consumer_key" class="text-xs text-red-600 mt-1">{{ errors.consumer_key }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Consumer Secret *</label>
            <input
              v-model="form.consumer_secret"
              type="password"
              required
              placeholder="cs_..."
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
            />
            <p v-if="errors.consumer_secret" class="text-xs text-red-600 mt-1">{{ errors.consumer_secret }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Sync frequency *</label>
            <select
              v-model="form.frequency"
              required
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="hourly">Hourly</option>
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
            </select>
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ submitting ? 'Testing connection…' : 'Save & test connection' }}
          </button>
        </form>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Layout from './Layout.vue'

const props = defineProps({
  config: Object,
})

const form = useForm({
  store_url: props.config?.store_url || '',
  consumer_key: '',
  consumer_secret: '',
  frequency: props.config?.frequency || 'daily',
})

const submitting = ref(false)
const errors = ref({})

function submit() {
  submitting.value = true
  errors.value = {}
  form.post('/vendor/integrations/woo', {
    preserveScroll: true,
    onError: (errs) => { errors.value = errs },
    onFinish: () => { submitting.value = false },
    onSuccess: () => {
      form.consumer_key = ''
      form.consumer_secret = ''
    },
  })
}

function syncNow() {
  router.post('/vendor/integrations/woo/sync', {}, { preserveScroll: true })
}

function disconnect() {
  if (!confirm('Disconnect WooCommerce? Existing imported products will remain, but sync will stop.')) return
  router.delete('/vendor/integrations/woo', { preserveScroll: true })
}
</script>
