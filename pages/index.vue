<script setup lang="ts">
// Nuxt auto-imports all components — préfixe Lazy = chargement différé
// Above-the-fold : TheNavbar, HeroSection, StatsBar, AboutSection, ServicesSection
// Below-the-fold : Lazy* (chargés après l'hydration initiale)

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
      <LazyVideoSection />
      <LazyEventsSection />
      <LazyTestimonialsSection />
      <LazyGallerySection />
      <LazyCtaSection />
    </main>
    <AppFooter />
    <LazyFloatingWhatsApp />
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
