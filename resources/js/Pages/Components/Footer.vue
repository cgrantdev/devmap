<template>
  <footer class="pt-12 relative">
    <div 
            ref="footerBgRef"
            class="max-w-[701px] w-full h-full absolute top-0 right-0 z-0"
            data-bg-image="/images/footer/1.png"
            :style="{ backgroundImage: footerBgLoaded ? `url(/images/footer/1.png)` : 'none' }"
          ></div>
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12">   
      
      <!-- Footer Content Grid -->
      <div class="footer-main-content">
        <!-- Left Section -->
        <div class="flex gap-30 z-10">
          <!-- Logo and Sign Up -->
          <div class="flex flex-col gap-4 mb-8">
            <Link href="/" class="inline-block mb-0">
              <span class="font-hv-muse font-normal text-3xl leading-tight tracking-normal text-gray-800">PeptideSync</span>
            </Link>
            <Link href="/register" class="inline-block py-[10px] px-6 rounded-[500px] bg-gray-800 font-roboto font-medium text-base leading-none tracking-normal text-white text-center transition-colors duration-300 w-fit hover:bg-gray-700">
              Sign Up
            </Link>
          </div>
          
          <!-- Legal and Resources Sections -->
          <div class="flex gap-40">
            <!-- Legal Links -->
            <div class="mt-0">
              <h3 class="font-roboto font-semibold text-lg leading-relaxed text-gray-800 mb-4">Legal</h3>
              <ul class="list-none p-0 m-0 flex flex-col gap-3">
                <li>
                  <Link href="/disclaimer" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Disclaimer</Link>
                </li>
                <li>
                  <Link href="/privacy" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Privacy Policy</Link>
                </li>
                <li>
                  <Link href="/brands" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Brands</Link>
                </li>
                <li>
                  <Link href="/calculator" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Calculator</Link>
                </li>
              </ul>
            </div>

            <!-- Resources Section 1 -->
            <div class="mt-0">
              <h3 class="font-roboto font-semibold text-lg leading-relaxed text-gray-800 mb-4">Ressources</h3>
              <ul class="list-none p-0 m-0 flex flex-col gap-3">
                <li>
                  <Link href="/contact" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Contact</Link>
                </li>
                <li>
                  <Link href="/about" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">About</Link>
                </li>
                <li>
                  <Link href="/brands" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Brands</Link>
                </li>
                <li>
                  <Link href="/products" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Products</Link>
                </li>
              </ul>
            </div>

          <div class="mt-0">
            <h3 class="font-roboto font-semibold text-lg leading-relaxed text-gray-800 mb-4">Ressources</h3>
            <ul class="list-none p-0 m-0 flex flex-col gap-3">
              <li>
                <Link href="/calculator" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Calculator</Link>
              </li>
              <li>
                <Link href="/education" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Education</Link>
              </li>
              <li>
                <Link href="/disclaimer" class="font-roboto font-normal text-sm leading-relaxed text-gray-500 no-underline transition-colors duration-300 hover:text-gray-800">Disclaimer</Link>
              </li>
            </ul>
          </div>

            
          </div>

        </div>

        <div class="footer-right-section z-[-1]"></div>

        <!-- Copyright Left -->
        <div class="pt-6 border-t border-gray-200 mt-auto pb-0">
          <p class="font-roboto font-normal text-xs leading-relaxed text-gray-400 m-0">© 2024 PeptideSync All Rights Reserved.</p>
        </div>
        <!-- Copyright Right -->
        <div class="pt-6 mt-auto">
          <p class="font-roboto font-normal text-xs leading-relaxed text-gray-400 m-0 text-right self-end">PeptideSync All Rights Reserved</p>
        </div>

      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'

const footerBgRef = ref(null)
const footerBgLoaded = ref(false)

// Lazy load footer background image
onMounted(() => {
  nextTick(() => {
    if (footerBgRef.value) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const bgImage = entry.target.getAttribute('data-bg-image')
            if (bgImage) {
              const img = new Image()
              img.onload = () => {
                footerBgLoaded.value = true
              }
              img.src = bgImage
            }
            observer.unobserve(entry.target)
          }
        })
      }, {
        rootMargin: '50px'
      })
      observer.observe(footerBgRef.value)
    }
  })
})
</script>

