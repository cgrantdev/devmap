<template>
  <footer class="pt-12 relative">
    <div 
            class="max-w-[701px] w-full h-full absolute top-0 right-0 z-0"
            :style="{ backgroundImage: `url(/images/footer/1.png)` }"
          ></div>
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12">   
      
      <!-- Footer Content Grid -->
      <div class="footer-main-content">
        <!-- Left Section -->
        <div class="flex gap-30 z-1">
          <!-- Logo and Sign Up -->
          <div class="footer-logo-group">
            <Link href="/" class="footer-logo">
              <span class="footer-logo-text">PeptideSync</span>
            </Link>
            <Link href="/register" class="footer-signup-btn">
              Sign Up
            </Link>
          </div>
          
          <!-- Legal and Resources Sections -->
          <div class="flex gap-40">
            <!-- Legal Links -->
            <div class="footer-links-section">
              <h3 class="footer-section-title">Legal</h3>
              <ul class="footer-links-list">
                <li>
                  <Link href="/disclaimer" class="footer-link">Disclaimer</Link>
                </li>
                <li>
                  <Link href="/privacy" class="footer-link">Privacy Policy</Link>
                </li>
                <li>
                  <Link href="/brands" class="footer-link">Brands</Link>
                </li>
                <li>
                  <Link href="/calculator" class="footer-link">Calculator</Link>
                </li>
              </ul>
            </div>

            <!-- Resources Section 1 -->
            <div class="footer-links-section">
              <h3 class="footer-section-title">Ressources</h3>
              <ul class="footer-links-list">
                <li>
                  <Link href="/contact" class="footer-link">Contact</Link>
                </li>
                <li>
                  <Link href="/about" class="footer-link">About</Link>
                </li>
                <li>
                  <Link href="/brands" class="footer-link">Brands</Link>
                </li>
                <li>
                  <Link href="/products" class="footer-link">Products</Link>
                </li>
              </ul>
            </div>

          <div class="footer-links-section">
            <h3 class="footer-section-title">Ressources</h3>
            <ul class="footer-links-list">
              <li>
                <Link href="/calculator" class="footer-link">Calculator</Link>
              </li>
              <li>
                <Link href="/education" class="footer-link">Education</Link>
              </li>
              <li>
                <Link href="/disclaimer" class="footer-link">Disclaimer</Link>
              </li>
            </ul>
          </div>

            
          </div>

        </div>

        <div class="footer-right-section z-[-1]"></div>

        <!-- Copyright Left -->
        <div class="footer-copyright-left">
          <p class="footer-copyright-text">© 2024 PeptideSync All Rights Reserved.</p>
        </div>
        <!-- Copyright Right -->
        <div class="footer-copyright-right">
          <p class="footer-copyright-right-text">PeptideSync All Rights Reserved</p>
        </div>

      </div>
    </div>
  </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>

