<template>
  <div class="relative overflow-hidden">
    <!-- Gradient fade on sides so logos ease in/out -->
    <div class="pointer-events-none absolute inset-y-0 left-0 w-20 z-10 bg-gradient-to-r from-white to-transparent" />
    <div class="pointer-events-none absolute inset-y-0 right-0 w-20 z-10 bg-gradient-to-l from-white to-transparent" />

    <div class="flex ui-marquee whitespace-nowrap py-6 gap-14">
      <!-- Duplicate list for seamless loop -->
      <template v-for="loop in 2" :key="loop">
        <a
          v-for="brand in brands"
          :key="`${loop}-${brand.id}`"
          :href="brand.url || '#'"
          :title="brand.name"
          class="ui-focus flex items-center gap-3 opacity-60 hover:opacity-100 transition-opacity duration-[200ms] flex-shrink-0"
        >
          <VendorLogo
            :src="brand.logo"
            :name="brand.name"
            size="sm"
            radius="md"
          />
          <span class="ui-display text-[15px] font-semibold text-[color:var(--color-ink)] tracking-tight">
            {{ brand.name }}
          </span>
          <VerifiedShield v-if="brand.verified" size="xs" :animate="false" />
        </a>
      </template>
    </div>
  </div>
</template>

<script setup>
import VendorLogo from './VendorLogo.vue'
import VerifiedShield from './VerifiedShield.vue'

defineProps({
  brands: {
    type: Array,
    required: true,
    /* each: { id, name, logo, url, verified } */
  },
})
</script>
