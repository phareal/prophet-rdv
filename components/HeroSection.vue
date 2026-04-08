<script setup lang="ts">
import { ChevronDown, MessageCircle, Calendar } from 'lucide-vue-next'
</script>

<template>
  <section id="accueil" class="hero">
    <!-- Arrière-plan animé -->
    <div class="hero__bg">
      <div class="hero__bg-radial hero__bg-radial--gold" />
      <div class="hero__bg-radial hero__bg-radial--green" />
      <div class="hero__bg-grid" />
    </div>

    <!-- Lignes décoratives -->
    <div class="hero__deco hero__deco--tl" />
    <div class="hero__deco hero__deco--br" />

    <!-- Contenu centré -->
    <div class="hero__content">
      <!-- Badge -->
      <div class="hero__badge">
        <span class="hero__badge-dot" />
        <span>Consultation Prophétique Internationale</span>
      </div>

      <!-- Titre principal -->
      <h1 class="hero__title">
        <span class="hero__title-pre">Prophète</span>
        <span class="hero__title-name">Jeremiah<br>Nahoum</span>
      </h1>

      <!-- Sous-titre -->
      <p class="hero__subtitle">"Le Conseiller des Rois"</p>

      <!-- Verset -->
      <blockquote class="hero__verse">
        <p>"Il révèle les choses profondes et cachées ; il connaît ce qui est dans les ténèbres,<br>et la lumière demeure avec lui."</p>
        <cite>— Daniel 2:22</cite>
      </blockquote>

      <!-- Actions -->
      <div class="hero__actions">
        <a href="/rdv" class="hero__btn hero__btn--primary">
          <Calendar :size="16" />
          <span>Prendre Rendez-vous</span>
        </a>
        <a href="#apropos" class="hero__btn hero__btn--ghost">
          <span>Découvrir le ministère</span>
        </a>
      </div>

      <!-- Stats rapides -->
      <div class="hero__quick-stats">
        <div class="hero__qs-item">
          <span class="hero__qs-num">40+</span>
          <span class="hero__qs-label">Nations</span>
        </div>
        <div class="hero__qs-sep" />
        <div class="hero__qs-item">
          <span class="hero__qs-num">15+</span>
          <span class="hero__qs-label">Ans de ministère</span>
        </div>
        <div class="hero__qs-sep" />
        <div class="hero__qs-item">
          <span class="hero__qs-num">10K+</span>
          <span class="hero__qs-label">Consultations</span>
        </div>
      </div>
    </div>

    <!-- Scroll indicator -->
    <a href="#apropos" class="hero__scroll">
      <span class="hero__scroll-label">Défiler</span>
      <ChevronDown :size="16" class="hero__scroll-icon" />
    </a>
  </section>
</template>

<style scoped>
.hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: #080D1A;
}

/* ── Arrière-plan ─────────────────────────────────── */
.hero__bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.hero__bg-radial {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  opacity: 0.12;
}

.hero__bg-radial--gold {
  width: 700px;
  height: 700px;
  top: -200px;
  left: -200px;
  background: radial-gradient(circle, #C8921C 0%, transparent 70%);
  animation: pulse-slow 8s ease-in-out infinite;
}

.hero__bg-radial--green {
  width: 600px;
  height: 600px;
  bottom: -150px;
  right: -150px;
  background: radial-gradient(circle, #0EAA72 0%, transparent 70%);
  animation: pulse-slow 10s ease-in-out infinite 2s;
}

.hero__bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
  background-size: 60px 60px;
}

/* ── Décorations ──────────────────────────────────── */
.hero__deco {
  position: absolute;
  pointer-events: none;
}

.hero__deco--tl {
  top: 6rem;
  left: 4rem;
  width: 120px;
  height: 120px;
  border-top: 1px solid rgba(200, 146, 28, 0.18);
  border-left: 1px solid rgba(200, 146, 28, 0.18);
}

.hero__deco--br {
  bottom: 6rem;
  right: 4rem;
  width: 80px;
  height: 80px;
  border-bottom: 1px solid rgba(14, 170, 114, 0.18);
  border-right: 1px solid rgba(14, 170, 114, 0.18);
}

/* ── Contenu ──────────────────────────────────────── */
.hero__content {
  position: relative;
  z-index: 2;
  text-align: center;
  max-width: 760px;
  padding: 8rem 2rem 4rem;
  animation: fade-up 0.7s var(--ease) both;
}

/* ── Badge ────────────────────────────────────────── */
.hero__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--f-mono);
  font-size: 0.6rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: rgba(200, 146, 28, 0.7);
  border: 1px solid rgba(200, 146, 28, 0.18);
  padding: 0.4rem 0.9rem;
  border-radius: 2px;
  margin-bottom: 2rem;
}

.hero__badge-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #C8921C;
  animation: blink 2.5s ease-in-out infinite;
}

/* ── Titre ────────────────────────────────────────── */
.hero__title {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0;
  margin-bottom: 1.1rem;
}

.hero__title-pre {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(0.9rem, 2vw, 1.15rem);
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: rgba(232, 228, 220, 0.35);
  line-height: 1;
}

.hero__title-name {
  font-family: var(--f-display);
  font-weight: 700;
  font-size: clamp(3.2rem, 8vw, 6.5rem);
  letter-spacing: 0.04em;
  line-height: 0.9;
  color: #F4F0EB;
  text-shadow: 0 0 80px rgba(200, 146, 28, 0.12);
}

/* ── Sous-titre ───────────────────────────────────── */
.hero__subtitle {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: clamp(1.1rem, 2.5vw, 1.5rem);
  color: rgba(200, 146, 28, 0.65);
  margin-bottom: 2rem;
}

/* ── Verset ───────────────────────────────────────── */
.hero__verse {
  max-width: 540px;
  margin: 0 auto 2.5rem;
  padding: 1rem 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-left: 2px solid rgba(200, 146, 28, 0.3);
  background: rgba(255, 255, 255, 0.02);
  border-radius: 0 4px 4px 0;
  text-align: left;
}

.hero__verse p {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.9rem;
  line-height: 1.65;
  color: rgba(232, 228, 220, 0.5);
  margin-bottom: 0.5rem;
}

.hero__verse cite {
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.1em;
  color: rgba(200, 146, 28, 0.45);
  font-style: normal;
}

/* ── Actions ──────────────────────────────────────── */
.hero__actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 3rem;
  flex-wrap: wrap;
}

.hero__btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--f-mono);
  font-size: 0.65rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  text-decoration: none;
  padding: 0.85rem 1.8rem;
  border-radius: 2px;
  transition: opacity var(--t), transform var(--t), background var(--t);
}

.hero__btn--primary {
  background: linear-gradient(135deg, #F0C040 0%, #C8921C 100%);
  color: #080D1A;
  font-weight: 600;
  box-shadow: 0 8px 32px rgba(200, 146, 28, 0.22);
}

.hero__btn--primary:hover {
  opacity: 0.9;
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(200, 146, 28, 0.3);
}

.hero__btn--ghost {
  border: 1px solid rgba(255, 255, 255, 0.14);
  color: rgba(232, 228, 220, 0.6);
  background: rgba(255, 255, 255, 0.03);
}

.hero__btn--ghost:hover {
  border-color: rgba(255, 255, 255, 0.25);
  color: rgba(232, 228, 220, 0.9);
  background: rgba(255, 255, 255, 0.06);
}

/* ── Stats rapides ────────────────────────────────── */
.hero__quick-stats {
  display: inline-flex;
  align-items: center;
  gap: 2rem;
  padding: 1rem 2rem;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.025);
}

.hero__qs-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
}

.hero__qs-num {
  font-family: var(--f-display);
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--gold);
  line-height: 1;
}

.hero__qs-label {
  font-family: var(--f-mono);
  font-size: 0.55rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(232, 228, 220, 0.3);
}

.hero__qs-sep {
  width: 1px;
  height: 32px;
  background: rgba(255, 255, 255, 0.08);
}

/* ── Scroll ───────────────────────────────────────── */
.hero__scroll {
  position: absolute;
  bottom: 2.5rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
  text-decoration: none;
  color: rgba(232, 228, 220, 0.25);
  transition: color var(--t);
  z-index: 2;
}

.hero__scroll:hover { color: rgba(232, 228, 220, 0.5); }

.hero__scroll-label {
  font-family: var(--f-mono);
  font-size: 0.52rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.hero__scroll-icon { animation: bounce 2s ease-in-out infinite; }

/* ── Keyframes ────────────────────────────────────── */
@keyframes pulse-slow {
  0%, 100% { opacity: 0.10; transform: scale(1); }
  50% { opacity: 0.16; transform: scale(1.08); }
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(5px); }
}

/* ── Responsive ───────────────────────────────────── */
@media (max-width: 640px) {
  .hero__content { padding: 7rem 1.5rem 3rem; }
  .hero__quick-stats { gap: 1.2rem; padding: 0.85rem 1.25rem; }
  .hero__verse { text-align: center; }
  .hero__deco--tl, .hero__deco--br { display: none; }
}
</style>
