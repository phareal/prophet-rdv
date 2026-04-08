<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Phone, MessageCircle } from 'lucide-vue-next'
import { buildWhatsAppUrl } from '@/lib/utils'

const config = useRuntimeConfig()
const phone1 = config.public.prophetPhone1 as string
const phone2 = config.public.prophetPhone2 as string

function wa(phone: string) {
  return buildWhatsAppUrl(phone, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.')
}

const temoignages = [
  { initiales: 'MC', nom: 'Marie-Claire D.', pays: 'Côte d\'Ivoire', consultation: 'Mariage',  hue: '#c2185b',
    texte: 'Trois mois après la prophétie, j\'ai rencontré mon époux. Chaque mot s\'est réalisé.' },
  { initiales: 'EK', nom: 'Pastor Emmanuel K.', pays: 'Nigeria', consultation: 'Appel', hue: '#3949ab',
    texte: 'Il a confirmé mon appel avec des détails que seul Dieu pouvait connaître.' },
  { initiales: 'FS', nom: 'Fatou S.', pays: 'Sénégal', consultation: 'Affaires', hue: '#C8921C',
    texte: 'Au bord de la faillite, sa stratégie divine a multiplié mon chiffre d\'affaires par 10.' },
  { initiales: 'JB', nom: 'Jean-Baptiste M.', pays: 'France', consultation: 'Carrière', hue: '#0EAA72',
    texte: 'Élu avec 67% des voix après sa prière. Ce que Dieu planifie ne peut être arrêté.' },
]

const current = ref(0)
let timer: ReturnType<typeof setInterval> | null = null

function goTo(i: number) {
  current.value = i
  restart()
}

function restart() {
  if (timer) clearInterval(timer)
  timer = setInterval(() => {
    current.value = (current.value + 1) % temoignages.length
  }, 4000)
}

onMounted(restart)
onUnmounted(() => { if (timer) clearInterval(timer) })
</script>

<template>
  <div class="hero">
    <div class="hero__accent" />

    <div class="hero__body">

      <!-- Badge -->
      <span class="hero__badge">Consultation Prophétique</span>

      <!-- Titre -->
      <div class="hero__title-block">
        <h1 class="hero__title">
          Prophète
          <span class="hero__title-name">Jeremiah<br>Nahoum</span>
        </h1>
        <p class="hero__subtitle">"Le Conseiller des Rois"</p>
      </div>

      <div class="hero__sep" />

      <!-- Portrait placeholder -->
      <div class="hero__portrait">
        <div class="hero__portrait-avatar">
          <svg viewBox="0 0 56 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="28" cy="20" rx="11" ry="12" fill="rgba(200,146,28,0.15)" stroke="rgba(200,146,28,0.3)" stroke-width="1"/>
            <path d="M9 60 C9 44 18 36 28 36 C38 36 47 44 47 60" fill="rgba(14,170,114,0.1)" stroke="rgba(14,170,114,0.25)" stroke-width="1"/>
          </svg>
        </div>
        <div class="hero__portrait-info">
          <p class="hero__portrait-label">Prophète Jeremiah Nahoum</p>
          <p class="hero__portrait-hint">photo · 400 × 500 px</p>
        </div>
      </div>

      <div class="hero__sep" />

      <!-- Verset -->
      <blockquote class="hero__verse">
        <p>"Il révèle les choses profondes et cachées ; il connaît ce qui est dans les ténèbres, et la lumière demeure avec lui."</p>
        <footer>— Daniel 2:22</footer>
      </blockquote>

      <div class="hero__sep" />

      <!-- Contacts -->
      <div class="hero__contacts">
        <p class="hero__section-label">Contact direct</p>

        <a :href="wa(phone1)" target="_blank" rel="noopener noreferrer" class="hero__wa hero__wa--primary">
          <span class="hero__wa-icon hero__wa-icon--green">
            <MessageCircle :size="15" />
          </span>
          <span class="hero__wa-info">
            <span class="hero__wa-label">WhatsApp principal</span>
            <span class="hero__wa-number">{{ phone1 }}</span>
          </span>
          <span class="hero__wa-arrow">→</span>
        </a>

        <a v-if="phone2" :href="wa(phone2)" target="_blank" rel="noopener noreferrer" class="hero__wa hero__wa--secondary">
          <span class="hero__wa-icon hero__wa-icon--dim">
            <Phone :size="15" />
          </span>
          <span class="hero__wa-info">
            <span class="hero__wa-label">WhatsApp secondaire</span>
            <span class="hero__wa-number hero__wa-number--dim">{{ phone2 }}</span>
          </span>
        </a>
      </div>

      <!-- Carousel témoignages -->
      <div class="hero__carousel">
        <p class="hero__section-label">Témoignages</p>

        <div class="hero__carousel-track">
          <transition name="tcard" mode="out-in">
            <article :key="current" class="hero__tcard">
              <p class="hero__tcard-text">{{ temoignages[current].texte }}</p>
              <div class="hero__tcard-footer">
                <div class="hero__tcard-author">
                  <span class="hero__tcard-avatar" :style="{ background: temoignages[current].hue }">
                    {{ temoignages[current].initiales }}
                  </span>
                  <span class="hero__tcard-meta">
                    <span class="hero__tcard-name">{{ temoignages[current].nom }}</span>
                    <span class="hero__tcard-country">{{ temoignages[current].pays }}</span>
                  </span>
                </div>
                <span class="hero__tcard-tag">{{ temoignages[current].consultation }}</span>
              </div>
            </article>
          </transition>
        </div>

        <div class="hero__carousel-dots">
          <button
            v-for="(_, i) in temoignages"
            :key="i"
            class="hero__dot"
            :class="{ 'hero__dot--active': i === current }"
            @click="goTo(i)"
            :aria-label="`Témoignage ${i + 1}`"
          />
        </div>
      </div>

      <!-- Pied -->
      <div class="hero__footer">
        <p class="hero__footer-copy">© {{ new Date().getFullYear() }} Prophète Jeremiah Nahoum</p>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* ── Conteneur — remplit exactement 100vh ────────────────── */
.hero {
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #0C1528;
  color: #E8E4DC;
  overflow: hidden;
  animation: fade-up 0.5s var(--ease) both;
}

/* ── Barre accent ────────────────────────────────────────── */
.hero__accent {
  position: absolute;
  top: 0;
  left: 2rem;
  width: 40px;
  height: 2px;
  background: var(--gold);
  opacity: 0.6;
  flex-shrink: 0;
}

/* ── Corps — flex column, distribue l'espace ─────────────── */
.hero__body {
  position: relative;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  padding: 1.75rem 2rem 1.25rem;
  min-height: 0;
}

/* ── Badge ───────────────────────────────────────────────── */
.hero__badge {
  display: inline-block;
  align-self: flex-start;
  font-family: var(--f-mono);
  font-size: 0.65rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(200,146,28,0.8);
  padding-bottom: 0.2rem;
  border-bottom: 1px solid rgba(200,146,28,0.3);
  flex-shrink: 0;
}

/* ── Titre ───────────────────────────────────────────────── */
.hero__title-block { flex-shrink: 0; }

.hero__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(1.1rem, 2vw, 1.5rem);
  line-height: 1.2;
  letter-spacing: 0.1em;
  color: rgba(232,228,220,0.62);
  margin-bottom: 0.1rem;
}

.hero__title-name {
  display: block;
  font-weight: 700;
  font-size: clamp(1.8rem, 3.2vw, 2.6rem);
  letter-spacing: 0.06em;
  line-height: 0.92;
  color: #F4F0EB;
  margin-top: 0.1rem;
}

.hero__subtitle {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.95rem;
  color: rgba(200,146,28,0.82);
  margin-top: 0.5rem;
}

/* ── Séparateurs ─────────────────────────────────────────── */
.hero__sep {
  height: 1px;
  background: rgba(255,255,255,0.06);
  flex-shrink: 0;
}

/* ── Portrait ────────────────────────────────────────────── */
.hero__portrait {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.65rem 0.85rem;
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: var(--r3);
  background: rgba(255,255,255,0.02);
  flex-shrink: 0;
}
.hero__portrait-avatar {
  width: 44px; height: 44px;
  border-radius: 50%;
  border: 1px solid rgba(200,146,28,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,0.03);
  flex-shrink: 0;
}
.hero__portrait-avatar svg { width: 28px; height: 28px; }
.hero__portrait-label {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.78rem;
  color: rgba(232,228,220,0.75);
}
.hero__portrait-hint {
  font-family: var(--f-mono);
  font-size: 0.57rem;
  color: rgba(255,255,255,0.32);
  margin-top: 0.15rem;
}

/* ── Section label ───────────────────────────────────────── */
.hero__section-label {
  font-family: var(--f-mono);
  font-size: 0.62rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: rgba(255,255,255,0.48);
  margin-bottom: 0.35rem;
}

/* ── Verset ──────────────────────────────────────────────── */
.hero__verse {
  padding-left: 0.85rem;
  border-left: 1px solid rgba(200,146,28,0.28);
  flex-shrink: 0;
}
.hero__verse p {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.9rem;
  line-height: 1.55;
  color: rgba(232,228,220,0.75);
}
.hero__verse footer {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  color: rgba(200,146,28,0.7);
  margin-top: 0.35rem;
}

/* ── Contacts ────────────────────────────────────────────── */
.hero__contacts {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

.hero__wa {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.55rem 0;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  transition: opacity var(--t);
}
.hero__wa:last-child { border-bottom: none; }
.hero__wa:hover { opacity: 0.7; }

.hero__wa-icon {
  width: 28px; height: 28px;
  border-radius: 50%;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
.hero__wa-icon--green { background: rgba(37,211,102,0.1); color: #25D366; }
.hero__wa-icon--dim   { background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.25); }

.hero__wa-info {
  display: flex;
  flex-direction: column;
  gap: 0.05rem;
  flex: 1;
  min-width: 0;
}
.hero__wa-label {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(255,255,255,0.5);
}
.hero__wa-number {
  font-family: var(--f-mono);
  font-size: 0.82rem;
  font-weight: 500;
  color: #E8E4DC;
}
.hero__wa-number--dim { color: rgba(232,228,220,0.72); }
.hero__wa-arrow { color: rgba(255,255,255,0.38); font-size: 0.75rem; }

/* ── Carousel — prend l'espace restant ───────────────────── */
.hero__carousel {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-height: 0;
}

.hero__carousel-track {
  flex: 1;
  position: relative;
  min-height: 0;
}

.hero__tcard {
  position: absolute;
  inset: 0;
  padding: 0.85rem;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: var(--r3);
  background: rgba(255,255,255,0.03);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.hero__tcard-text {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.9rem;
  line-height: 1.55;
  color: rgba(232,228,220,0.85);
  flex: 1;
}

.hero__tcard-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.65rem;
}
.hero__tcard-author { display: flex; align-items: center; gap: 0.55rem; }
.hero__tcard-avatar {
  width: 26px; height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--f-display);
  font-size: 0.52rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}
.hero__tcard-meta { display: flex; flex-direction: column; gap: 0.03rem; }
.hero__tcard-name {
  font-family: var(--f-display);
  font-size: 0.62rem;
  font-weight: 600;
  color: rgba(232,228,220,0.9);
  letter-spacing: 0.04em;
}
.hero__tcard-country {
  font-family: var(--f-mono);
  font-size: 0.57rem;
  color: rgba(255,255,255,0.48);
}
.hero__tcard-tag {
  font-family: var(--f-mono);
  font-size: 0.57rem;
  padding: 0.15rem 0.5rem;
  border-radius: 2px;
  border: 1px solid rgba(14,170,114,0.35);
  color: rgba(14,170,114,0.85);
  white-space: nowrap;
}

/* Dots */
.hero__carousel-dots {
  display: flex;
  align-items: center;
  gap: 0.38rem;
  flex-shrink: 0;
}
.hero__dot {
  width: 5px; height: 5px;
  border-radius: 50%;
  background: rgba(255,255,255,0.16);
  transition: background var(--t), transform var(--t);
  flex-shrink: 0;
}
.hero__dot--active { background: var(--gold); transform: scale(1.4); }
.hero__dot:hover   { background: rgba(255,255,255,0.38); }

/* Transition slide */
.tcard-enter-active,
.tcard-leave-active {
  transition: opacity 0.3s var(--ease), transform 0.3s var(--ease);
}
.tcard-enter-from { opacity: 0; transform: translateX(14px); }
.tcard-leave-to   { opacity: 0; transform: translateX(-14px); }
.tcard-enter-to,
.tcard-leave-from { opacity: 1; transform: translateX(0); }

/* ── Pied ────────────────────────────────────────────────── */
.hero__footer { flex-shrink: 0; }
.hero__footer-copy {
  font-family: var(--f-mono);
  font-size: 0.57rem;
  color: rgba(255,255,255,0.28);
}
</style>
