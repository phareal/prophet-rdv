<script setup lang="ts">
import { Calendar, MessageCircle } from 'lucide-vue-next'
import { buildWhatsAppUrl } from '@/lib/utils'

const config = useRuntimeConfig()
const phone1 = config.public.prophetPhone1 as string
const waUrl = buildWhatsAppUrl(phone1, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.')
</script>

<template>
  <section class="cta">
    <div class="cta__inner">
      <blockquote class="cta__verse">
        <span class="cta__verse-cross">✦</span>
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
          <Calendar :size="16" />
          <span>Réserver une consultation</span>
        </a>
        <a :href="waUrl" target="_blank" rel="noopener noreferrer" class="cta__btn cta__btn--wa">
          <MessageCircle :size="16" />
          <span>WhatsApp direct</span>
        </a>
      </div>

      <p class="cta__note">Disponible 7j/7 · Consultation internationale · Réponse en 24h</p>
    </div>
  </section>
</template>

<style scoped>
/* CTA garde un fond sombre pour le contraste final avant le footer */
.cta {
  background: #0C1528;
  padding: 8rem 2rem;
  text-align: center;
}

.cta__inner {
  max-width: 640px;
  margin: 0 auto;
}

.cta__verse {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 2.5rem;
}

.cta__verse-cross { font-size: 1rem; color: rgba(200, 146, 28, 0.5); }

.cta__verse p {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 1rem;
  line-height: 1.65;
  color: rgba(232, 228, 220, 0.42);
  max-width: 480px;
}

.cta__verse cite {
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.12em;
  color: rgba(176, 122, 20, 0.5);
  font-style: normal;
}

.cta__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(2rem, 5vw, 3.5rem);
  line-height: 1.1;
  letter-spacing: 0.04em;
  color: #F4F0EB;
  margin-bottom: 1.25rem;
}

.cta__title em {
  font-family: var(--f-serif);
  font-style: italic;
  color: #C8921C;
}

.cta__sub {
  font-family: var(--f-serif);
  font-size: 1rem;
  line-height: 1.7;
  color: rgba(232, 228, 220, 0.4);
  margin-bottom: 2.5rem;
}

.cta__actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.cta__btn {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  font-family: var(--f-mono);
  font-size: 0.65rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  text-decoration: none;
  padding: 0.9rem 2rem;
  border-radius: 2px;
  font-weight: 600;
  transition: opacity var(--t), transform var(--t);
}

.cta__btn--primary {
  background: linear-gradient(135deg, #F0C040 0%, #C8921C 100%);
  color: #080D1A;
  box-shadow: 0 8px 32px rgba(200, 146, 28, 0.22);
}

.cta__btn--primary:hover {
  opacity: 0.9;
  transform: translateY(-2px);
}

.cta__btn--wa {
  background: rgba(37, 211, 102, 0.1);
  border: 1px solid rgba(37, 211, 102, 0.25);
  color: #25D366;
}

.cta__btn--wa:hover {
  background: rgba(37, 211, 102, 0.16);
  transform: translateY(-2px);
}

.cta__note {
  font-family: var(--f-mono);
  font-size: 0.56rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(232, 228, 220, 0.18);
}

@media (max-width: 640px) { .cta { padding: 6rem 1.5rem; } }
</style>
