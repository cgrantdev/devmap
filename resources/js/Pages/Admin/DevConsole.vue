<template>
  <AdminLayout>
    <div class="p-8 max-w-5xl mx-auto">
      <h1 class="text-2xl font-semibold mb-2">Dev Console</h1>
      <p class="text-sm text-[color:var(--color-ink-muted)] mb-8">
        Whitelisted artisan commands. Triggered via Admin API so no SSH is needed.
      </p>

      <div class="space-y-3">
        <div
          v-for="cmd in commands"
          :key="cmd.signature"
          class="border border-[color:var(--color-hairline)] rounded-lg p-4 bg-white"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="ui-mono text-[13px] text-[color:var(--color-ink)] font-semibold">
                php artisan {{ cmd.signature }}
              </div>
              <div class="text-[13px] text-[color:var(--color-ink-muted)] mt-1">
                {{ cmd.label }}
              </div>

              <div v-if="cmd.args.length" class="mt-3 flex flex-wrap gap-2 items-center">
                <label
                  v-for="arg in cmd.args"
                  :key="arg"
                  class="inline-flex items-center gap-1.5 text-[12px]"
                >
                  <template v-if="arg === '--dry-run' || arg === '--force'">
                    <input
                      type="checkbox"
                      v-model="argValues[cmd.signature][arg]"
                      class="w-4 h-4"
                    />
                    <span class="ui-mono">{{ arg }}</span>
                  </template>
                  <template v-else>
                    <span class="ui-mono">{{ arg }}</span>
                    <input
                      type="text"
                      v-model="argValues[cmd.signature][arg]"
                      :placeholder="arg === '--limit' ? '20' : 'slug'"
                      class="h-7 px-2 w-24 text-[12px] border border-[color:var(--color-hairline)] rounded ui-mono"
                    />
                  </template>
                </label>
              </div>
            </div>

            <button
              @click="run(cmd)"
              :disabled="running[cmd.signature]"
              class="h-9 px-4 text-[13px] font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded disabled:opacity-50"
            >
              {{ running[cmd.signature] ? 'Running…' : 'Run' }}
            </button>
          </div>

          <div
            v-if="results[cmd.signature]"
            class="mt-4 border-t border-[color:var(--color-hairline-soft)] pt-3"
          >
            <div class="flex items-center gap-3 text-[12px] mb-2">
              <span
                :class="[
                  'px-2 py-0.5 rounded-full font-semibold',
                  results[cmd.signature].ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700',
                ]"
              >
                {{ results[cmd.signature].ok ? '✓ ok' : '✗ failed' }}
              </span>
              <span class="text-[color:var(--color-ink-muted)]">
                exit {{ results[cmd.signature].exit_code ?? '-' }} ·
                {{ results[cmd.signature].duration_ms }}ms
              </span>
            </div>
            <pre class="text-[11.5px] ui-mono bg-slate-900 text-slate-100 p-3 rounded max-h-[400px] overflow-auto whitespace-pre-wrap">{{ results[cmd.signature].output || results[cmd.signature].error }}</pre>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ commands: { type: Array, required: true } })

// Initialize per-command arg storage so v-model binds cleanly.
const argValues = reactive(Object.fromEntries(
  props.commands.map(c => [c.signature, Object.fromEntries(c.args.map(a => [a, '']))])
))
const running = reactive({})
const results = reactive({})

async function run(cmd) {
  running[cmd.signature] = true
  const payload = {
    command: cmd.signature,
    args: argValues[cmd.signature],
  }
  try {
    const resp = await fetch('/admin/dev/console/run', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
          || document.querySelector('input[name="_token"]')?.value,
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    })
    results[cmd.signature] = await resp.json()
  } catch (e) {
    results[cmd.signature] = { ok: false, error: String(e) }
  } finally {
    running[cmd.signature] = false
  }
}
</script>
