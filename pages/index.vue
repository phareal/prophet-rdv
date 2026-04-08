<script setup lang="ts">
import HeroLeft from '@/components/HeroLeft.vue'
import RdvForm from '@/components/RdvForm.vue'
import { Toaster } from '@/components/ui/toast'

useSeoMeta({
  title: 'Prophète Jeremiah Nahoum — Prendre Rendez-vous',
  description: 'Prenez rendez-vous avec le Prophète Jeremiah Nahoum, Le Conseiller des Rois. Consultation prophétique, spirituelle et stratégique pour les nations.',
  ogTitle: 'Prophète Jeremiah Nahoum — Le Conseiller des Rois',
  ogDescription: 'Consultation prophétique internationale. Prenez rendez-vous maintenant.',
})
</script>

<template>
  <section id="rdv" class="hero-layout">
    <div class="hero-layout__left">
      <HeroLeft />
    </div>
    <div class="hero-layout__right">
      <RdvForm />
    </div>
    <Toaster />
  </section>
</template>

<style>
.hero-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  width: 100vw;
  height: 100vh;
  max-height: 100vh;
  overflow: hidden;
  position: fixed;
  top: 0;
  left: 0;
}
.hero-layout__left {
  height: 100vh;
  overflow: hidden;
}
.hero-layout__right {
  height: 100vh;
  overflow: hidden;
  background: #FAFAF9;
}

@media (max-width: 1023px) {
  .hero-layout {
    position: static;
    grid-template-columns: 1fr;
    height: auto;
    max-height: none;
    width: 100%;
    overflow: visible;
  }
  .hero-layout__left,
  .hero-layout__right {
    height: auto;
    overflow: visible;
  }
}
</style>
