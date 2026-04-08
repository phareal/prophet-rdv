<script setup lang="ts">
import HeroLeft from '@/components/HeroLeft.vue'
import RdvForm from '@/components/RdvForm.vue'
import { Toaster } from '@/components/ui/toast'
import { ArrowLeft } from 'lucide-vue-next'

useSeoMeta({
  title: 'Prendre Rendez-vous — Prophète Jeremiah Nahoum',
  description: 'Réservez votre consultation prophétique avec le Prophète Jeremiah Nahoum. Formulaire de rendez-vous en ligne.',
})
</script>

<template>
  <div class="rdv-page">
    <!-- Bouton retour discret -->
    <a href="/" class="rdv-back">
      <ArrowLeft :size="14" />
      <span>Retour au site</span>
    </a>

    <section id="rdv" class="rdv-layout">
      <div class="rdv-layout__left">
        <HeroLeft />
      </div>
      <div class="rdv-layout__right">
        <RdvForm />
      </div>
    </section>

    <Toaster />
  </div>
</template>

<style>
.rdv-page {
  position: relative;
  background: #080D1A;
}

.rdv-back {
  position: fixed;
  top: 1.25rem;
  left: 1.25rem;
  z-index: 200;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(232, 228, 220, 0.28);
  text-decoration: none;
  padding: 0.4rem 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 2px;
  background: rgba(8, 13, 26, 0.6);
  backdrop-filter: blur(8px);
  transition: color var(--t), border-color var(--t), background var(--t);
}

.rdv-back:hover {
  color: rgba(232, 228, 220, 0.65);
  border-color: rgba(255, 255, 255, 0.14);
  background: rgba(8, 13, 26, 0.85);
}

.rdv-layout {
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

.rdv-layout__left {
  height: 100vh;
  overflow: hidden;
}

.rdv-layout__right {
  height: 100vh;
  overflow: hidden;
  background: #FAFAF9;
}

@media (max-width: 1023px) {
  .rdv-layout {
    position: static;
    grid-template-columns: 1fr;
    height: auto;
    max-height: none;
    width: 100%;
    overflow: visible;
  }

  .rdv-layout__left,
  .rdv-layout__right {
    height: auto;
    overflow: visible;
  }

  .rdv-back {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
  }
}
</style>
