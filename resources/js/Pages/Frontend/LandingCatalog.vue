<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
      <link rel="canonical" :href="seo.canonical" />
    </Head>

    <!-- Hero -->
    <section class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1100px] mx-auto px-6 lg:px-10 py-10">
        <div class="text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-accent-600)] mb-2">
          Vendor Comparison
        </div>
        <h1 class="ui-display text-3xl md:text-4xl font-semibold text-[color:var(--color-ink)] mb-3">
          {{ h1 }}
        </h1>
        <p class="text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl text-[15px]">
          {{ subtitle }}
        </p>
        <div class="mt-4 flex items-center gap-4 text-[13px] text-[color:var(--color-ink-muted)]">
          <span><strong class="text-[color:var(--color-ink)] ui-mono">{{ product_count }}</strong> products</span>
          <span class="text-[color:var(--color-ink-subtle)]">·</span>
          <span><strong class="text-[color:var(--color-ink)] ui-mono">{{ vendor_count }}</strong> vendors</span>
          <span class="text-[color:var(--color-ink-subtle)]">·</span>
          <span>Prices updated hourly</span>
        </div>
      </div>
    </section>

    <!-- Intro copy — long-form SEO body -->
    <section class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
      <div class="max-w-[900px] mx-auto px-6 lg:px-10 py-8">
        <p class="text-[15px] text-[color:var(--color-ink-muted)] leading-relaxed">{{ intro }}</p>
      </div>
    </section>

    <!-- Product table -->
    <section>
      <div class="max-w-[1100px] mx-auto px-6 lg:px-10 py-10">
        <div v-if="!products.length" class="text-center py-16 text-[color:var(--color-ink-subtle)]">
          {{ empty_message }}
        </div>

        <div v-else class="overflow-x-auto rounded-[12px] border border-[color:var(--color-hairline)] bg-white">
          <table class="w-full text-[13px]">
            <thead class="bg-[color:var(--color-bg)] border-b border-[color:var(--color-hairline)]">
              <tr>
                <th class="px-5 py-3 text-left text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)]">Product</th>
                <th class="px-5 py-3 text-left text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)] hidden md:table-cell">Compound</th>
                <th class="px-5 py-3 text-left text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
                <th class="px-5 py-3 text-right text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)]">Price</th>
                <th class="px-5 py-3 text-right text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-ink-subtle)]">Buy</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in products" :key="p.id" class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-accent-50)]/40 transition-colors">
                <td class="px-5 py-3">
                  <a :href="p.product_url" class="text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] font-medium">
                    {{ p.name }}
                  </a>
                  <div v-if="p.size_mg" class="text-[11px] text-[color:var(--color-ink-subtle)] mt-0.5">{{ p.size_mg }}</div>
                </td>
                <td class="px-5 py-3 hidden md:table-cell text-[color:var(--color-ink-muted)]">{{ p.category_name || '—' }}</td>
                <td class="px-5 py-3">
                  <a :href="`/brand/${p.brand_slug}`" class="text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)]">
                    {{ p.brand_name }}
                  </a>
                  <div v-if="p.brand_location" class="text-[11px] text-[color:var(--color-ink-subtle)] mt-0.5">{{ p.brand_location }}</div>
                </td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                  <template v-if="p.pmap_price">
                    <div class="ui-mono text-[14px] font-bold text-emerald-700">{{ p.currency_symbol }}{{ p.pmap_price.toFixed(2) }}</div>
                    <div class="ui-mono text-[10px] text-[color:var(--color-ink-subtle)] line-through">{{ p.currency_symbol }}{{ (p.discount_price || p.price).toFixed(2) }}</div>
                  </template>
                  <template v-else>
                    <div class="ui-mono text-[14px] font-bold text-[color:var(--color-ink)]">{{ p.currency_symbol }}{{ (p.discount_price || p.price).toFixed(2) }}</div>
                  </template>
                </td>
                <td class="px-5 py-3 text-right">
                  <a
                    :href="withSrc(p.go_url)"
                    target="_blank"
                    rel="noopener noreferrer nofollow sponsored"
                    class="ui-focus inline-flex items-center gap-1 h-8 px-3 rounded-[8px] text-[12px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] hover:-translate-y-[0.5px] transition-all"
                  >
                    Buy →
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import { withSrc } from '@/composables/useOutbound'

defineProps({
  slug: String,
  h1: String,
  subtitle: String,
  intro: String,
  empty_message: { type: String, default: 'No products found.' },
  products: { type: Array, default: () => [] },
  vendor_count: { type: Number, default: 0 },
  product_count: { type: Number, default: 0 },
  seo: { type: Object, default: () => ({}) },
})
</script>
