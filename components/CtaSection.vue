<script setup lang="ts">
import { Calendar, MessageCircle } from 'lucide-vue-next'
import { buildWhatsAppUrl } from '@/lib/utils'

const config = useRuntimeConfig()
const phone1 = config.public.prophetPhone1 as string
const waUrl = buildWhatsAppUrl(phone1, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.')
</script>

<template>
  <section class="cta">
    <div class="cta__bg">
      <div class="cta__bg-glow cta__bg-glow--gold" />
      <div class="cta__bg-lines" />
    </div>

    <div class="cta__inner">
      <div class="cta__ornament">
        <span class="cta__ornament-line" />
        <span class="cta__ornament-cross">✦</span>
        <span class="cta__ornament-line" />
      </div>

      <blockquote class="cta__verse">
        <p>"Appelez-moi, et je vous répondrai ; je vous annoncerai de grandes choses, des choses cachées, que vous ne connaissez pas."</p>
        <cite>— Jérémie 33:3</cite>
      </blockquote>

      <h2 class="cta__title">
        Votre rendez-vous<br>
        <em>avec Dieu vous attend</em>
      </h2>

      <p class="cta__sub">
        Chaque consultation est unique. Chaque parole est précise.<br>
        Prenez votre rendez-vous prophétique aujourd'hui.
      </p>

      <div class="cta__actions">
        <a href="/rdv" class="cta__btn cta__btn--primary">
          <Calendar :size="17" />
          <span>Réserver une consultation</span>
        </a>
        <a :href="waUrl" target="_blank" rel="noopener noreferrer" class="cta__btn cta__btn--wa">
          <MessageCircle :size="17" />
          <span>WhatsApp direct</span>
        </a>
      </div>

      <p class="cta__note">Disponible 7j/7 · Consultation internationale · Réponse en 24h</p>
    </div>
  </section>
</template>

<style scoped>
.cta {
  position: relative;
  background: #0C1528;
  padding: 9rem 2.5rem;
  text-align: center;
  overflow: hidden;
}

.cta__bg { position: absolute; inset: 0; pointer-events: none; }

.cta__bg-glow--gold {
  position: absolute;
  width: 800px; height: 800px;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  border-radius: 50%;
  filter: blur(120px);
  background: radial-gradient(circle, rgba(200,146,28,0.1) 0%, transparent 60%);
}

.cta__bg-lines {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(200,146,28,0.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(200,146,28,0.025) 1px, transparent 1px);
  background-size: 80px 80px;
}

.cta__inner {
  position: relative; z-index: 2;
  max-width: 700px; margin: 0 auto;
}

/* Ornement */
.cta__ornament {
  display: flex; align-items: center; justify-content: center;
  gap: 1rem; margin-bottom: 2rem;
}

.cta__ornament-line {
  display: block; width: 60px; height: 1px;
  background: rgba(200,146,28,0.3);
}

.cta__ornament-cross {
  font-size: 0.85rem; color: rgba(200,146,28,0.55);
}

/* Verset */
.cta__verse {
  display: flex; flex-direction: column;
  align-items: center; gap: 0.6rem;
  margin-bottom: 2.75rem;
}

.cta__verse p {
  font-family: var(--f-serif); font-style: italic;
  font-size: 1.15rem; line-height: 1.7;
  color: rgba(232, 228, 220, 0.45);
  max-width: 520px;
}

.cta__verse cite {
  font-family: var(--f-mono); font-size: 0.65rem;
  letter-spacing: 0.12em; color: rgba(176,122,20,0.5); font-style: normal;
}

/* Titre */
.cta__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(2.4rem, 5.5vw, 4rem);
  line-height: 1.08; letter-spacing: 0.04em;
  color: #F4F0EB; margin-bottom: 1.5rem;
}

.cta__title em {
  font-family: var(--f-serif); font-style: italic; color: #C8921C;
}

.cta__sub {
  font-family: var(--f-serif); font-size: 1.1rem;
  line-height: 1.75; color: rgba(232,228,220,0.4);
  margin-bottom: 2.75rem;
}

/* Actions */
.cta__actions {
  display: flex; align-items: center;
  justify-content: center; gap: 1.1rem;
  margin-bottom: 1.75rem; flex-wrap: wrap;
}

.cta__btn {
  display: inline-flex; align-items: center;
  gap: 0.6rem; font-family: var(--f-mono);
  font-size: 0.7rem; letter-spacing: 0.1em; text-transform: uppercase;
  text-decoration: none; padding: 1rem 2.25rem;
  border-radius: 3px; font-weight: 600;
  transition: opacity var(--t), transform var(--t), box-shadow var(--t);
}

.cta__btn--primary {
  background: linear-gradient(135deg, #F0C040 0%, #C8921C 100%);
  color: #0C1528;
  box-shadow: 0 8px 36px rgba(200,146,28,0.25);
}

.cta__btn--primary:hover {
  opacity: 0.9; transform: translateY(-2px);
  box-shadow: 0 14px 48px rgba(200,146,28,0.35);
}

.cta__btn--wa {
  background: rgba(37,211,102,0.1);
  border: 1px solid rgba(37,211,102,0.28);
  color: #25D366;
}

.cta__btn--wa:hover {
  background: rgba(37,211,102,0.16); transform: translateY(-2px);
}

.cta__note {
  font-family: var(--f-mono); font-size: 0.6rem;
  letter-spacing: 0.12em; text-transform: uppercase;
  color: rgba(232,228,220,0.18);
}

@media (max-width: 640px) { .cta { padding: 7rem 1.75rem; } }
</style>
