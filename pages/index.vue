<script setup lang="ts">
import TheNavbar from '@/components/TheNavbar.vue'
import HeroSection from '@/components/HeroSection.vue'
import StatsBar from '@/components/StatsBar.vue'
import AboutSection from '@/components/AboutSection.vue'
import ServicesSection from '@/components/ServicesSection.vue'
import TestimonialsSection from '@/components/TestimonialsSection.vue'
import GallerySection from '@/components/GallerySection.vue'
import CtaSection from '@/components/CtaSection.vue'
import AppFooter from '@/components/AppFooter.vue'

useSeoMeta({
  title: 'Prophète Jeremiah Nahoum — Le Conseiller des Rois',
  description: 'Ministère prophétique international. Consultations prophétiques pour les nations, les leaders et les familles. Plus de 40 nations, 15 ans de ministère.',
  ogTitle: 'Prophète Jeremiah Nahoum — Le Conseiller des Rois',
  ogDescription: 'Consultation prophétique internationale de précision. Mariages, affaires, appels, politique — réservez votre rendez-vous.',
})
</script>

<template>
  <div class="site">
    <TheNavbar />
    <main>
      <HeroSection />
      <StatsBar />
      <AboutSection />
      <ServicesSection />
      <TestimonialsSection />
      <GallerySection />
      <CtaSection />
    </main>
    <AppFooter />
  </div>
</template>

<style>
html {
  scroll-behavior: smooth;
}

.site {
  background: #080D1A;
  color: #E8E4DC;
  min-height: 100vh;
}
</style>
