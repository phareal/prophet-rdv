<script setup lang="ts">
import { computed } from 'vue'
import { CheckCircle2, MessageCircle, Home, CalendarDays, Clock, User } from 'lucide-vue-next'
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
    <!-- Fond décoratif léger -->
    <div class="conf__bg">
      <div class="conf__bg-radial conf__bg-radial--gold" />
      <div class="conf__bg-radial conf__bg-radial--green" />
    </div>

    <!-- Carte -->
    <div class="conf__card">

      <!-- Header -->
      <div class="conf__head">
        <div class="conf__check-ring">
          <CheckCircle2 :size="28" class="conf__check-icon" />
        </div>
        <div class="conf__head-text">
          <span class="conf__head-eyebrow">Demande reçue</span>
          <h1 class="conf__head-title">Rendez-vous <em>enregistré</em></h1>
          <p class="conf__head-sub">Votre demande a bien été transmise au Prophète.</p>
        </div>
      </div>

      <!-- Corps -->
      <div class="conf__body">

        <!-- Résumé -->
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

        <!-- Info -->
        <div class="conf__info">
          <p>Un email de confirmation vous a été envoyé avec tous les détails de votre rendez-vous.</p>
          <p>Votre demande sera traitée dans les <strong>24–48h</strong>. Le Prophète vous contactera directement.</p>
        </div>

        <!-- Verset -->
        <blockquote class="conf__verse">
          <span class="conf__verse-cross">✦</span>
          <p>"Invoque-moi, et je te répondrai ; je t'annoncerai de grandes choses, des choses cachées, que tu ne connais pas."</p>
          <cite>— Jérémie 33:3</cite>
        </blockquote>

        <!-- Actions -->
        <div class="conf__actions">
          <a v-if="waUrl" :href="waUrl" target="_blank" rel="noopener noreferrer" class="conf__btn conf__btn--wa">
            <MessageCircle :size="16" />
            <span>Confirmer sur WhatsApp</span>
          </a>
          <NuxtLink to="/" class="conf__btn conf__btn--outline">
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
  background: #FDFCF9;
  position: relative;
  overflow: hidden;
}

/* Fond */
.conf__bg { position: absolute; inset: 0; pointer-events: none; }

.conf__bg-radial {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
}

.conf__bg-radial--gold {
  width: 500px; height: 500px;
  top: -200px; left: -200px;
  background: radial-gradient(circle, rgba(176, 122, 20, 0.07) 0%, transparent 70%);
}

.conf__bg-radial--green {
  width: 400px; height: 400px;
  bottom: -150px; right: -150px;
  background: radial-gradient(circle, rgba(10, 144, 96, 0.06) 0%, transparent 70%);
}

/* Carte */
.conf__card {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 460px;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  border: 1px solid #EAE7E0;
  box-shadow: 0 16px 60px rgba(0, 0, 0, 0.08);
  animation: fade-up 0.5s var(--ease) both;
}

/* Header */
.conf__head {
  display: flex;
  align-items: center;
  gap: 1.1rem;
  padding: 2rem 2rem 1.75rem;
  background: #F6FBF8;
  border-bottom: 1px solid #D4EDE3;
}

.conf__check-ring {
  width: 56px; height: 56px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: #E8F7F1;
  border: 1px solid #A8D8C4;
  flex-shrink: 0;
}

.conf__check-icon { color: #0A9060; }

.conf__head-eyebrow {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.55rem; letter-spacing: 0.14em;
  text-transform: uppercase; color: #0A9060;
  margin-bottom: 0.25rem;
}

.conf__head-title {
  font-family: var(--f-display);
  font-size: 1.4rem; font-weight: 400;
  letter-spacing: 0.04em; color: #0C1528;
  line-height: 1.15; margin-bottom: 0.3rem;
}

.conf__head-title em {
  font-family: var(--f-serif);
  font-style: italic; color: #0A9060;
}

.conf__head-sub {
  font-family: var(--f-serif);
  font-style: italic; font-size: 0.82rem; color: #6B6560;
}

/* Body */
.conf__body {
  padding: 1.75rem 2rem;
  display: flex; flex-direction: column; gap: 1.4rem;
}

/* Summary */
.conf__summary {
  background: #FDFCF9;
  border: 1px solid #EAE7E0;
  border-radius: 4px;
  padding: 1rem;
}

.conf__summary-label {
  font-family: var(--f-mono);
  font-size: 0.55rem; letter-spacing: 0.12em;
  text-transform: uppercase; color: #9B9590; margin-bottom: 0.75rem;
}

.conf__rows { display: flex; flex-direction: column; gap: 0.5rem; }

.conf__row {
  display: flex; align-items: center; gap: 0.65rem;
  font-family: var(--f-serif); font-size: 0.92rem; color: #4B4540;
}

.conf__row-icon { color: #0A9060; flex-shrink: 0; }

.conf__row-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #B07A14; flex-shrink: 0;
}

/* Info */
.conf__info { display: flex; flex-direction: column; gap: 0.5rem; }

.conf__info p {
  font-family: var(--f-serif);
  font-size: 0.88rem; line-height: 1.6; color: #6B6560;
}

.conf__info strong { color: #1A1714; font-weight: 600; }

/* Verse */
.conf__verse {
  display: flex; flex-direction: column; gap: 0.4rem;
  padding: 0.9rem 1rem;
  border-left: 2px solid rgba(176, 122, 20, 0.3);
  background: rgba(176, 122, 20, 0.04);
  border-radius: 0 4px 4px 0;
}

.conf__verse-cross { font-size: 0.7rem; color: rgba(176, 122, 20, 0.5); }

.conf__verse p {
  font-family: var(--f-serif); font-style: italic;
  font-size: 0.82rem; line-height: 1.6; color: #6B6560;
}

.conf__verse cite {
  font-family: var(--f-mono); font-size: 0.56rem;
  letter-spacing: 0.1em; color: #B07A14; font-style: normal;
}

/* Actions */
.conf__actions { display: flex; flex-direction: column; gap: 0.65rem; }

.conf__btn {
  display: flex; align-items: center; justify-content: center;
  gap: 0.6rem;
  padding: 0.85rem 1.5rem;
  border-radius: 3px;
  font-family: var(--f-mono);
  font-size: 0.65rem; font-weight: 600;
  letter-spacing: 0.1em; text-transform: uppercase;
  text-decoration: none;
  transition: background var(--t), transform var(--t), border-color var(--t);
}

.conf__btn--wa {
  background: #25D366; color: #fff;
}
.conf__btn--wa:hover { background: #1aad4d; transform: translateY(-1px); }

.conf__btn--outline {
  background: #fff; color: #4B4540;
  border: 1px solid #D4CFC6;
}
.conf__btn--outline:hover { border-color: #0A9060; color: #0A9060; }

/* Footer */
.conf__footer {
  position: relative; z-index: 2;
  font-family: var(--f-mono);
  font-size: 0.56rem; letter-spacing: 0.08em;
  color: #C4BAA0; text-align: center; margin-top: 1.5rem;
}
</style>
