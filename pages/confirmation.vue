<script setup lang="ts">
import { computed } from 'vue'
import { CheckCircle2, MessageCircle, Home, CalendarDays, Clock, User } from 'lucide-vue-next'
import { formatDateFr } from '@/lib/utils'

useSeoMeta({
  title: 'Demande reçue — Prophète Jeremiah Nahoum',
  description: 'Votre demande de rendez-vous a bien été reçue.',
})

const route = useRoute()
const nom         = computed(() => route.query.nom as string || '')
const prenom      = computed(() => route.query.prenom as string || '')
const consultation= computed(() => route.query.consultation as string || '')
const heure       = computed(() => route.query.heure as string || '')
const waUrl       = computed(() => route.query.waUrl as string || '')
const date        = computed(() => {
  const raw = route.query.date as string
  return raw ? formatDateFr(new Date(raw)) : ''
})
</script>

<template>
  <div class="conf">
    <div class="conf__card">

      <!-- En-tête -->
      <div class="conf__head">
        <div class="conf__check-ring">
          <CheckCircle2 :size="36" class="conf__check-icon" />
        </div>
        <h1 class="conf__head-title">Demande envoyée</h1>
        <p class="conf__head-sub">Votre demande de rendez-vous a bien été reçue.</p>
      </div>

      <!-- Corps -->
      <div class="conf__body">

        <!-- Résumé -->
        <div class="conf__summary">
          <div v-if="prenom || nom" class="conf__row">
            <User :size="14" class="conf__row-icon" />
            <span>{{ prenom }} {{ nom }}</span>
          </div>
          <div v-if="consultation" class="conf__row">
            <span class="conf__row-dot" />
            <span>Consultation : <strong>{{ consultation }}</strong></span>
          </div>
          <div v-if="date" class="conf__row">
            <CalendarDays :size="14" class="conf__row-icon" />
            <span>{{ date }}</span>
          </div>
          <div v-if="heure" class="conf__row">
            <Clock :size="14" class="conf__row-icon" />
            <span>{{ heure }}</span>
          </div>
        </div>

        <!-- Infos -->
        <div class="conf__info">
          <p>Un email de confirmation vous a été envoyé avec tous les détails.</p>
          <p>Votre demande sera traitée dans les <strong>24–48h</strong>. Le Prophète vous contactera directement.</p>
        </div>

        <!-- Verset -->
        <blockquote class="conf__verse">
          <p>"Invoque-moi, et je te répondrai ; je t'annoncerai de grandes choses, des choses cachées, que tu ne connais pas."</p>
          <footer>— Jérémie 33:3</footer>
        </blockquote>

        <!-- Actions -->
        <div class="conf__actions">
          <a v-if="waUrl" :href="waUrl" target="_blank" rel="noopener noreferrer" class="conf__btn conf__btn--wa">
            <MessageCircle :size="17" />
            <span>Confirmer sur WhatsApp</span>
          </a>
          <NuxtLink to="/" class="conf__btn conf__btn--home">
            <Home :size="17" />
            <span>Retour à l'accueil</span>
          </NuxtLink>
        </div>

      </div>
    </div>

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
  background: #FAFAF9;
}

.conf__card {
  width: 100%;
  max-width: 440px;
  border-radius: var(--r3);
  overflow: hidden;
  background: white;
  border: 1px solid #DDD9D0;
  box-shadow: var(--sh-xl);
}

/* Header */
.conf__head {
  padding: 2.5rem 2rem 2rem;
  text-align: center;
  background: var(--emerald);
}
.conf__check-ring {
  width: 64px; height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  background: rgba(255,255,255,0.15);
}
.conf__check-icon { color: white; }
.conf__head-title {
  font-family: var(--f-display);
  font-size: 1.4rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: white;
  margin-bottom: 0.4rem;
}
.conf__head-sub {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.95rem;
  color: rgba(255,255,255,0.7);
}

/* Body */
.conf__body {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Summary */
.conf__summary {
  background: #F6F4EF;
  border-radius: var(--r2);
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  border: 1px solid #DDD9D0;
}
.conf__row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-family: var(--f-serif);
  font-size: 0.95rem;
  color: var(--text);
}
.conf__row-icon { color: var(--emerald); flex-shrink: 0; }
.conf__row-dot {
  width: 12px; height: 12px;
  border-radius: 50%;
  background: var(--emerald);
  flex-shrink: 0;
}
.conf__row strong { font-weight: 600; }

/* Info */
.conf__info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.conf__info p {
  font-family: var(--f-serif);
  font-size: 0.92rem;
  color: var(--text-soft);
  line-height: 1.55;
}
.conf__info strong { color: var(--ink); font-weight: 600; }

/* Verse */
.conf__verse {
  padding: 0.85rem 1rem;
  border-left: 1px solid rgba(200,146,28,0.28);
  background: #FAF7F0;
  border-radius: 0 var(--r2) var(--r2) 0;
}
.conf__verse p {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.9rem;
  line-height: 1.6;
  color: rgba(120,90,16,0.75);
}
.conf__verse footer {
  font-family: var(--f-mono);
  font-size: 0.65rem;
  font-weight: 600;
  color: rgba(200,146,28,0.6);
  margin-top: 0.5rem;
}

/* Actions */
.conf__actions { display: flex; flex-direction: column; gap: 0.65rem; }
.conf__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 0.8rem 1.5rem;
  border-radius: var(--r2);
  font-family: var(--f-mono);
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  transition: background var(--t), transform var(--tf);
}
.conf__btn--wa {
  background: #25D366;
  color: white;
}
.conf__btn--wa:hover {
  background: #1aad4d;
  transform: translateY(-1px);
}
.conf__btn--home {
  background: white;
  color: rgba(26,23,20,0.6);
  border: 1px solid #DDD9D0;
}
.conf__btn--home:hover {
  border-color: var(--emerald);
  color: var(--emerald);
}

/* Footer */
.conf__footer {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  color: rgba(26,23,20,0.25);
  text-align: center;
  margin-top: 1.5rem;
}
</style>
