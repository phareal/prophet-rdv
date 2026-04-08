<script setup lang="ts">
import {
  Heart, Sparkles, Mic2, Landmark, Briefcase, Flame,
  Star, HeartPulse, Plane, Building2, ShoppingBag, CalendarDays
} from 'lucide-vue-next'

const services = [
  {
    icon: Heart,
    title: 'Mariage',
    desc: 'Guidance prophétique pour trouver votre partenaire de vie selon la volonté de Dieu.',
    color: '#c2185b',
  },
  {
    icon: Sparkles,
    title: 'Anges',
    desc: 'Révélation sur les anges assignés à votre vie et votre destinée divine.',
    color: '#C8921C',
  },
  {
    icon: Mic2,
    title: 'Appel',
    desc: 'Confirmation et activation de votre appel prophétique ou ministériel.',
    color: '#7B5EA7',
  },
  {
    icon: Landmark,
    title: 'Politique',
    desc: 'Stratégie divine pour les élections, les nominations et la gouvernance.',
    color: '#1565C0',
  },
  {
    icon: Briefcase,
    title: 'Affaires',
    desc: 'Direction spirituelle pour vos investissements, partenariats et décisions clés.',
    color: '#0EAA72',
  },
  {
    icon: Flame,
    title: 'Autels',
    desc: 'Identification et bris des autels familiaux qui bloquent votre progression.',
    color: '#E64A19',
  },
  {
    icon: Star,
    title: 'Étoiles',
    desc: 'Lecture prophétique de votre étoile et de votre glorieux destin céleste.',
    color: '#F0C040',
  },
  {
    icon: HeartPulse,
    title: 'Santé',
    desc: 'Prière prophétique et révélation pour les guérisons physiques et mentales.',
    color: '#D32F2F',
  },
  {
    icon: Plane,
    title: 'Voyage',
    desc: 'Confirmation divine pour vos déplacements, immigrations et nouvelles résidences.',
    color: '#0277BD',
  },
  {
    icon: Building2,
    title: 'Entreprise',
    desc: 'Vision prophétique pour le lancement et la croissance de votre entreprise.',
    color: '#00897B',
  },
  {
    icon: ShoppingBag,
    title: 'Commerce',
    desc: 'Stratégies divines pour la prospérité dans le négoce et le commerce international.',
    color: '#6D4C41',
  },
  {
    icon: CalendarDays,
    title: 'Année 2026',
    desc: 'Prophétie personnelle sur ce que Dieu prépare pour vous cette année.',
    color: '#C8921C',
  },
]
</script>

<template>
  <section id="services" class="services">
    <div class="services__inner">

      <!-- Header -->
      <div class="services__header">
        <span class="services__label">Domaines prophétiques</span>
        <h2 class="services__title">
          Consultations<br>
          <em>disponibles</em>
        </h2>
        <p class="services__desc">
          Chaque consultation est une rencontre avec Dieu à travers son prophète.<br>
          Choisissez le domaine pour lequel vous recherchez une parole divine.
        </p>
      </div>

      <!-- Grille -->
      <div class="services__grid">
        <article
          v-for="(s, i) in services"
          :key="i"
          class="svc-card"
        >
          <div class="svc-card__icon" :style="{ '--svc-color': s.color }">
            <component :is="s.icon" :size="22" />
          </div>
          <h3 class="svc-card__title">{{ s.title }}</h3>
          <p class="svc-card__desc">{{ s.desc }}</p>
          <a href="/rdv" class="svc-card__link">Réserver →</a>
        </article>
      </div>

      <!-- CTA -->
      <div class="services__footer">
        <a href="/rdv" class="services__cta">
          Prendre rendez-vous maintenant
        </a>
      </div>

    </div>
  </section>
</template>

<style scoped>
.services {
  background: #060B16;
  padding: 7rem 2rem;
}

.services__inner {
  max-width: 1200px;
  margin: 0 auto;
}

/* ── Header ────────────────────────────────────────── */
.services__header {
  text-align: center;
  max-width: 560px;
  margin: 0 auto 4rem;
}

.services__label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--gold);
  opacity: 0.65;
  margin-bottom: 0.85rem;
}

.services__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(1.9rem, 4vw, 2.8rem);
  line-height: 1.1;
  letter-spacing: 0.04em;
  color: #F4F0EB;
  margin-bottom: 1rem;
}

.services__title em {
  font-family: var(--f-serif);
  font-style: italic;
  color: rgba(200, 146, 28, 0.65);
}

.services__desc {
  font-family: var(--f-serif);
  font-size: 0.95rem;
  line-height: 1.7;
  color: rgba(232, 228, 220, 0.4);
}

/* ── Grille ────────────────────────────────────────── */
.services__grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 3rem;
}

/* ── Carte ─────────────────────────────────────────── */
.svc-card {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  padding: 1.75rem 1.5rem;
  background: rgba(255, 255, 255, 0.02);
  border-right: 1px solid rgba(255, 255, 255, 0.05);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  transition: background var(--t);
  cursor: pointer;
}

.svc-card:hover {
  background: rgba(255, 255, 255, 0.04);
}

.svc-card:hover .svc-card__icon {
  box-shadow: 0 0 20px color-mix(in srgb, var(--svc-color) 20%, transparent);
}

.svc-card__icon {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, var(--svc-color) 10%, transparent);
  color: var(--svc-color);
  border: 1px solid color-mix(in srgb, var(--svc-color) 20%, transparent);
  transition: box-shadow var(--t);
  flex-shrink: 0;
}

.svc-card__title {
  font-family: var(--f-display);
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(232, 228, 220, 0.82);
}

.svc-card__desc {
  font-family: var(--f-serif);
  font-size: 0.82rem;
  line-height: 1.6;
  color: rgba(232, 228, 220, 0.35);
  flex: 1;
}

.svc-card__link {
  font-family: var(--f-mono);
  font-size: 0.55rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(200, 146, 28, 0.5);
  text-decoration: none;
  transition: color var(--t);
}

.svc-card:hover .svc-card__link { color: rgba(200, 146, 28, 0.85); }

/* ── Footer ────────────────────────────────────────── */
.services__footer {
  display: flex;
  justify-content: center;
}

.services__cta {
  font-family: var(--f-mono);
  font-size: 0.65rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #080D1A;
  background: linear-gradient(135deg, #F0C040 0%, #C8921C 100%);
  padding: 0.9rem 2.5rem;
  border-radius: 2px;
  text-decoration: none;
  font-weight: 600;
  box-shadow: 0 8px 32px rgba(200, 146, 28, 0.2);
  transition: opacity var(--t), transform var(--t);
}

.services__cta:hover {
  opacity: 0.9;
  transform: translateY(-2px);
}

/* ── Responsive ────────────────────────────────────── */
@media (max-width: 1024px) {
  .services__grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
  .services__grid { grid-template-columns: repeat(2, 1fr); }
  .services { padding: 5rem 1.5rem; }
}

@media (max-width: 480px) {
  .services__grid { grid-template-columns: 1fr; }
}
</style>
