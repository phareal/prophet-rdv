<script setup lang="ts">
import { computed } from 'vue'
import { CheckCircle2, MessageCircle, Home, CalendarDays, Clock, User, ArrowLeft } from 'lucide-vue-next'
import { formatDateFr } from '@/lib/utils'

useSeoMeta({
  title: 'Demande reçue — Prophète Jeremiah Nahoum',
  description: 'Votre demande de rendez-vous a bien été reçue.',
})

const route = useRoute()
const nom          = computed(() => route.query.nom as string || '')
const prenom       = computed(() => route.query.prenom as string || '')
const consultation = computed(() => route.query.consultation as string || '')
const heure        = computed(() => route.query.heure as string || '')
const waUrl        = computed(() => route.query.waUrl as string || '')
const date         = computed(() => {
  const raw = route.query.date as string
  return raw ? formatDateFr(new Date(raw)) : ''
})
</script>

<template>
  <div class="conf">
    <!-- Fond animé -->
    <div class="conf__bg">
      <div class="conf__bg-glow conf__bg-glow--gold" />
      <div class="conf__bg-glow conf__bg-glow--green" />
      <div class="conf__bg-grid" />
    </div>

    <!-- Carte -->
    <div class="conf__card">

      <!-- Header de la carte -->
      <div class="conf__head">
        <div class="conf__check-ring">
          <CheckCircle2 :size="32" class="conf__check-icon" />
        </div>
        <div class="conf__head-text">
          <span class="conf__head-eyebrow">Demande reçue</span>
          <h1 class="conf__head-title">Rendez-vous<br><em>enregistré</em></h1>
          <p class="conf__head-sub">Votre demande a bien été transmise au Prophète.</p>
        </div>
      </div>

      <!-- Corps -->
      <div class="conf__body">

        <!-- Résumé RDV -->
        <div class="conf__summary">
          <p class="conf__summary-label">Récapitulatif</p>
          <div class="conf__rows">
            <div v-if="prenom || nom" class="conf__row">
              <User :size="13" class="conf__row-icon" />
              <span>{{ prenom }} {{ nom }}</span>
            </div>
            <div v-if="consultation" class="conf__row">
              <span class="conf__row-dot" />
              <span>{{ consultation }}</span>
            </div>
            <div v-if="date" class="conf__row">
              <CalendarDays :size="13" class="conf__row-icon" />
              <span>{{ date }}</span>
            </div>
            <div v-if="heure" class="conf__row">
              <Clock :size="13" class="conf__row-icon" />
              <span>{{ heure }}</span>
            </div>
          </div>
        </div>

        <!-- Message d'info -->
        <div class="conf__info">
          <p>Un email de confirmation vous a été envoyé avec tous les détails de votre rendez-vous.</p>
          <p>Votre demande sera traitée dans les <strong>24–48h</strong>. Le Prophète Jeremiah Nahoum vous contactera directement.</p>
        </div>

        <!-- Verset -->
        <blockquote class="conf__verse">
          <span class="conf__verse-cross">✦</span>
          <p>"Invoque-moi, et je te répondrai ; je t'annoncerai de grandes choses, des choses cachées, que tu ne connais pas."</p>
          <cite>— Jérémie 33:3</cite>
        </blockquote>

        <!-- Actions -->
        <div class="conf__actions">
          <a
            v-if="waUrl"
            :href="waUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="conf__btn conf__btn--wa"
          >
            <MessageCircle :size="16" />
            <span>Confirmer sur WhatsApp</span>
          </a>
          <NuxtLink to="/" class="conf__btn conf__btn--ghost">
            <Home :size="16" />
            <span>Retour à l'accueil</span>
          </NuxtLink>
        </div>

      </div>
    </div>

    <!-- Footer -->
    <p class="conf__footer">
      © {{ new Date().getFullYear() }} Prophète Jeremiah Nahoum · Le Conseiller des Rois
    </p>
  </div>
</template>

<style scoped>
.conf {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.25rem;
  background: #080D1A;
  position: relative;
  overflow: hidden;
}

/* Fond */
.conf__bg { position: absolute; inset: 0; pointer-events: none; }

.conf__bg-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
}

.conf__bg-glow--gold {
  width: 500px;
  height: 500px;
  top: -150px;
  left: -150px;
  background: radial-gradient(circle, rgba(200, 146, 28, 0.08) 0%, transparent 70%);
}

.conf__bg-glow--green {
  width: 400px;
  height: 400px;
  bottom: -100px;
  right: -100px;
  background: radial-gradient(circle, rgba(14, 170, 114, 0.07) 0%, transparent 70%);
}

.conf__bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
  background-size: 60px 60px;
}

/* Carte */
.conf__card {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 460px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  overflow: hidden;
  background: rgba(12, 21, 40, 0.9);
  backdrop-filter: blur(20px);
  box-shadow: 0 24px 80px rgba(0, 0, 0, 0.5);
  animation: fade-up 0.5s var(--ease) both;
}

/* Header */
.conf__head {
  padding: 2.5rem 2rem;
  background: linear-gradient(135deg, rgba(14, 170, 114, 0.15) 0%, rgba(14, 170, 114, 0.05) 100%);
  border-bottom: 1px solid rgba(14, 170, 114, 0.15);
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.conf__check-ring {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(14, 170, 114, 0.15);
  border: 1px solid rgba(14, 170, 114, 0.3);
  flex-shrink: 0;
}

.conf__check-icon { color: #0EAA72; }

.conf__head-text { flex: 1; }

.conf__head-eyebrow {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.55rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: rgba(14, 170, 114, 0.7);
  margin-bottom: 0.3rem;
}

.conf__head-title {
  font-family: var(--f-display);
  font-size: 1.5rem;
  font-weight: 400;
  letter-spacing: 0.06em;
  color: #F4F0EB;
  line-height: 1.1;
  margin-bottom: 0.4rem;
}

.conf__head-title em {
  font-family: var(--f-serif);
  font-style: italic;
  color: #0EAA72;
}

.conf__head-sub {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.82rem;
  color: rgba(232, 228, 220, 0.45);
}

/* Body */
.conf__body {
  padding: 1.75rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.4rem;
}

/* Summary */
.conf__summary {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 4px;
  padding: 1rem;
}

.conf__summary-label {
  font-family: var(--f-mono);
  font-size: 0.55rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.22);
  margin-bottom: 0.75rem;
}

.conf__rows { display: flex; flex-direction: column; gap: 0.5rem; }

.conf__row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-family: var(--f-serif);
  font-size: 0.9rem;
  color: rgba(232, 228, 220, 0.65);
}

.conf__row-icon {
  color: #0EAA72;
  flex-shrink: 0;
  opacity: 0.7;
}

.conf__row-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(200, 146, 28, 0.5);
  flex-shrink: 0;
}

/* Info */
.conf__info { display: flex; flex-direction: column; gap: 0.5rem; }

.conf__info p {
  font-family: var(--f-serif);
  font-size: 0.88rem;
  line-height: 1.6;
  color: rgba(232, 228, 220, 0.42);
}

.conf__info strong { color: rgba(232, 228, 220, 0.75); font-weight: 600; }

/* Verse */
.conf__verse {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.4rem;
  padding: 0.9rem 1rem;
  border-left: 2px solid rgba(200, 146, 28, 0.25);
  background: rgba(200, 146, 28, 0.04);
  border-radius: 0 4px 4px 0;
}

.conf__verse-cross {
  font-size: 0.7rem;
  color: rgba(200, 146, 28, 0.4);
}

.conf__verse p {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.82rem;
  line-height: 1.6;
  color: rgba(232, 228, 220, 0.38);
}

.conf__verse cite {
  font-family: var(--f-mono);
  font-size: 0.56rem;
  letter-spacing: 0.1em;
  color: rgba(200, 146, 28, 0.4);
  font-style: normal;
}

/* Actions */
.conf__actions { display: flex; flex-direction: column; gap: 0.65rem; }

.conf__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 0.85rem 1.5rem;
  border-radius: 3px;
  font-family: var(--f-mono);
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  text-decoration: none;
  transition: background var(--t), transform var(--t), opacity var(--t);
}

.conf__btn--wa {
  background: rgba(37, 211, 102, 0.12);
  border: 1px solid rgba(37, 211, 102, 0.3);
  color: #25D366;
}

.conf__btn--wa:hover {
  background: rgba(37, 211, 102, 0.18);
  transform: translateY(-1px);
}

.conf__btn--ghost {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(232, 228, 220, 0.45);
}

.conf__btn--ghost:hover {
  border-color: rgba(255, 255, 255, 0.2);
  color: rgba(232, 228, 220, 0.75);
}

/* Footer */
.conf__footer {
  position: relative;
  z-index: 2;
  font-family: var(--f-mono);
  font-size: 0.56rem;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.12);
  text-align: center;
  margin-top: 1.5rem;
}
</style>
