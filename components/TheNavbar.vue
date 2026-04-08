<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Menu, X } from 'lucide-vue-next'

const scrolled = ref(false)
const menuOpen = ref(false)

function onScroll() {
  scrolled.value = window.scrollY > 40
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))

const links = [
  { label: 'Accueil', href: '#accueil' },
  { label: 'À propos', href: '#apropos' },
  { label: 'Services', href: '#services' },
  { label: 'Témoignages', href: '#temoignages' },
  { label: 'Galerie', href: '#galerie' },
]
</script>

<template>
  <header :class="['navbar', { 'navbar--scrolled': scrolled }]">
    <div class="navbar__inner">
      <!-- Logo -->
      <a href="#accueil" class="navbar__logo">
        <span class="navbar__logo-cross">✦</span>
        <div class="navbar__logo-text">
          <span class="navbar__logo-title">Prophète Jeremiah</span>
          <span class="navbar__logo-sub">Le Conseiller des Rois</span>
        </div>
      </a>

      <!-- Desktop nav -->
      <nav class="navbar__nav">
        <a v-for="link in links" :key="link.href" :href="link.href" class="navbar__link">
          {{ link.label }}
        </a>
      </nav>

      <!-- CTA -->
      <div class="navbar__actions">
        <a href="/rdv" class="navbar__cta">Prendre RDV</a>
        <button class="navbar__burger" @click="menuOpen = !menuOpen" :aria-label="menuOpen ? 'Fermer' : 'Menu'">
          <X v-if="menuOpen" :size="22" />
          <Menu v-else :size="22" />
        </button>
      </div>
    </div>

    <!-- Mobile menu -->
    <transition name="mobile-menu">
      <div v-if="menuOpen" class="navbar__mobile">
        <a
          v-for="link in links"
          :key="link.href"
          :href="link.href"
          class="navbar__mobile-link"
          @click="menuOpen = false"
        >
          {{ link.label }}
        </a>
        <a href="/rdv" class="navbar__mobile-cta" @click="menuOpen = false">Prendre RDV →</a>
      </div>
    </transition>
  </header>
</template>

<style scoped>
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  transition: background 0.35s var(--ease), backdrop-filter 0.35s var(--ease), box-shadow 0.35s var(--ease);
}

.navbar--scrolled {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  box-shadow: 0 1px 0 rgba(0, 0, 0, 0.06), 0 4px 24px rgba(0, 0, 0, 0.05);
}

.navbar__inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.1rem 2rem;
  gap: 2rem;
}

/* Logo */
.navbar__logo {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  text-decoration: none;
  flex-shrink: 0;
}

.navbar__logo-cross {
  font-size: 1.1rem;
  color: #B07A14;
  line-height: 1;
}

.navbar__logo-text {
  display: flex;
  flex-direction: column;
  gap: 0.05rem;
}

.navbar__logo-title {
  font-family: var(--f-display);
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #1A1714;
  line-height: 1;
}

.navbar__logo-sub {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.62rem;
  color: #B07A14;
  line-height: 1;
}

/* Nav links */
.navbar__nav {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.navbar__link {
  font-family: var(--f-mono);
  font-size: 0.62rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #6B6560;
  text-decoration: none;
  transition: color var(--t);
}

.navbar__link:hover { color: #1A1714; }

/* Actions */
.navbar__actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.navbar__cta {
  font-family: var(--f-mono);
  font-size: 0.62rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #fff;
  background: #0C1528;
  padding: 0.55rem 1.2rem;
  border-radius: 2px;
  text-decoration: none;
  transition: opacity var(--t), transform var(--t);
  flex-shrink: 0;
}

.navbar__cta:hover {
  opacity: 0.85;
  transform: translateY(-1px);
}

.navbar__burger {
  display: none;
  background: none;
  border: none;
  color: #4B4540;
  cursor: pointer;
  padding: 0.25rem;
}

/* Mobile menu */
.navbar__mobile {
  display: flex;
  flex-direction: column;
  padding: 1rem 2rem 1.5rem;
  border-top: 1px solid #EAE7E0;
  background: #fff;
}

.navbar__mobile-link {
  font-family: var(--f-mono);
  font-size: 0.72rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #6B6560;
  text-decoration: none;
  padding: 0.85rem 0;
  border-bottom: 1px solid #F0EDE6;
  transition: color var(--t);
}

.navbar__mobile-link:hover { color: #1A1714; }

.navbar__mobile-cta {
  font-family: var(--f-display);
  font-size: 0.8rem;
  letter-spacing: 0.08em;
  color: #B07A14;
  text-decoration: none;
  padding: 1rem 0 0;
}

/* Transitions */
.mobile-menu-enter-active,
.mobile-menu-leave-active {
  transition: opacity 0.25s var(--ease), transform 0.25s var(--ease);
}
.mobile-menu-enter-from,
.mobile-menu-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@media (max-width: 768px) {
  .navbar__nav { display: none; }
  .navbar__cta { display: none; }
  .navbar__burger { display: flex; }
  .navbar__inner { padding: 1rem 1.25rem; }
}
</style>
