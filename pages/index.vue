<script setup lang="ts">
import TheNavbar from '@/components/TheNavbar.vue'
import HeroSection from '@/components/HeroSection.vue'
import StatsBar from '@/components/StatsBar.vue'
import AboutSection from '@/components/AboutSection.vue'
import ServicesSection from '@/components/ServicesSection.vue'
import VideoSection from '@/components/VideoSection.vue'
import EventsSection from '@/components/EventsSection.vue'
import TestimonialsSection from '@/components/TestimonialsSection.vue'
import GallerySection from '@/components/GallerySection.vue'
import CtaSection from '@/components/CtaSection.vue'
import AppFooter from '@/components/AppFooter.vue'
import FloatingWhatsApp from '@/components/FloatingWhatsApp.vue'

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
      <VideoSection />
      <EventsSection />
      <TestimonialsSection />
      <GallerySection />
      <CtaSection />
    </main>
    <AppFooter />
    <FloatingWhatsApp />
  </div>
</template>

<style>
html {
  scroll-behavior: smooth;
}

.site {
  background: #FDFCF9;
  color: #1A1714;
  min-height: 100vh;
}
</style>
