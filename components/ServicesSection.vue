<script setup lang="ts">
import {
  Heart, Sparkles, Mic2, Landmark, Briefcase, Flame,
  Star, HeartPulse, Plane, Building2, ShoppingBag, CalendarDays
} from 'lucide-vue-next'

const services = [
  { icon: Heart,        title: 'Mariage',    desc: 'Guidance prophétique pour trouver votre partenaire de vie selon la volonté de Dieu.',          color: '#c2185b' },
  { icon: Sparkles,     title: 'Anges',      desc: 'Révélation sur les anges assignés à votre vie et votre destinée divine.',                       color: '#B07A14' },
  { icon: Mic2,         title: 'Appel',      desc: 'Confirmation et activation de votre appel prophétique ou ministériel.',                         color: '#7B5EA7' },
  { icon: Landmark,     title: 'Politique',  desc: 'Stratégie divine pour les élections, les nominations et la gouvernance.',                       color: '#1565C0' },
  { icon: Briefcase,    title: 'Affaires',   desc: 'Direction spirituelle pour vos investissements, partenariats et décisions clés.',               color: '#0A9060' },
  { icon: Flame,        title: 'Autels',     desc: 'Identification et bris des autels familiaux qui bloquent votre progression.',                   color: '#E64A19' },
  { icon: Star,         title: 'Étoiles',    desc: 'Lecture prophétique de votre étoile et de votre glorieux destin céleste.',                      color: '#B07A14' },
  { icon: HeartPulse,   title: 'Santé',      desc: 'Prière prophétique et révélation pour les guérisons physiques et mentales.',                    color: '#D32F2F' },
  { icon: Plane,        title: 'Voyage',     desc: 'Confirmation divine pour vos déplacements, immigrations et nouvelles résidences.',              color: '#0277BD' },
  { icon: Building2,    title: 'Entreprise', desc: 'Vision prophétique pour le lancement et la croissance de votre entreprise.',                    color: '#00897B' },
  { icon: ShoppingBag,  title: 'Commerce',   desc: 'Stratégies divines pour la prospérité dans le négoce et le commerce international.',            color: '#6D4C41' },
  { icon: CalendarDays, title: 'Année 2026', desc: 'Prophétie personnelle sur ce que Dieu prépare pour vous cette année.',                          color: '#B07A14' },
]
</script>

<template>
  <section id="services" class="services">
    <div class="services__inner">

      <div class="services__header">
        <div class="section-ornament"><span>✦</span></div>
        <span class="services__label">Domaines prophétiques</span>
        <h2 class="services__title">
          Consultations<br>
          <em>disponibles</em>
        </h2>
        <p class="services__desc">
          Chaque consultation est une rencontre avec Dieu à travers son prophète.
          Choisissez le domaine pour lequel vous recherchez une parole divine.
        </p>
      </div>

      <div class="services__grid">
        <article v-for="(s, i) in services" :key="i" class="svc-card">
          <div class="svc-card__icon" :style="{ '--svc-color': s.color }">
            <component :is="s.icon" :size="22" />
          </div>
          <h3 class="svc-card__title">{{ s.title }}</h3>
          <p class="svc-card__desc">{{ s.desc }}</p>
          <a href="/rdv" class="svc-card__link">Réserver →</a>
        </article>
      </div>

      <div class="services__footer">
        <a href="/rdv" class="services__cta">Prendre rendez-vous maintenant</a>
      </div>

    </div>
  </section>
</template>

<style scoped>
.services {
  background: #F6F4EF;
  padding: 8rem 2.5rem;
  position: relative;
}

.services::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent 0%, rgba(176,122,20,0.35) 30%, rgba(176,122,20,0.35) 70%, transparent 100%);
}

.services__inner { max-width: 1200px; margin: 0 auto; }

.services__header {
  text-align: center;
  max-width: 600px;
  margin: 0 auto 4.5rem;
}

.services__label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.68rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: #B07A14;
  margin-bottom: 1rem;
}

.services__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(2.2rem, 4vw, 3.2rem);
  line-height: 1.1;
  letter-spacing: 0.04em;
  color: #0C1528;
  margin-bottom: 1.25rem;
}

.services__title em {
  font-family: var(--f-serif);
  font-style: italic;
  color: #B07A14;
}

.services__desc {
  font-family: var(--f-serif);
  font-size: 1.1rem;
  line-height: 1.75;
  color: #4A4540;
}

/* Grid */
.services__grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  background: #E0DDD6;
  border: 1px solid #E0DDD6;
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 3.5rem;
  box-shadow: 0 4px 24px rgba(0,0,0,0.05);
}

.svc-card {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 2rem 1.75rem;
  background: #fff;
  transition: background var(--t), box-shadow var(--t);
  cursor: pointer;
  position: relative;
}

.svc-card::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 2px;
  background: var(--svc-color);
  opacity: 0;
  transition: opacity 0.25s var(--ease);
}

.svc-card:hover { background: #FDFCF9; }
.svc-card:hover::after { opacity: 0.5; }

.svc-card__icon {
  width: 48px; height: 48px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: color-mix(in srgb, var(--svc-color) 10%, white);
  color: var(--svc-color);
  border: 1px solid color-mix(in srgb, var(--svc-color) 18%, transparent);
  flex-shrink: 0;
  transition: box-shadow var(--t), transform var(--t);
}

.svc-card:hover .svc-card__icon {
  box-shadow: 0 4px 16px color-mix(in srgb, var(--svc-color) 22%, transparent);
  transform: translateY(-2px);
}

.svc-card__title {
  font-family: var(--f-display);
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #1A1714;
}

.svc-card__desc {
  font-family: var(--f-serif);
  font-size: 0.95rem;
  line-height: 1.65;
  color: #5C5752;
  flex: 1;
}

.svc-card__link {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #B07A14;
  text-decoration: none;
  opacity: 0;
  transition: opacity var(--t);
}

.svc-card:hover .svc-card__link { opacity: 1; }

/* Footer */
.services__footer { display: flex; justify-content: center; }

.services__cta {
  font-family: var(--f-mono);
  font-size: 0.7rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #fff;
  background: #0C1528;
  padding: 1rem 2.75rem;
  border-radius: 3px;
  text-decoration: none;
  font-weight: 600;
  box-shadow: 0 4px 24px rgba(12, 21, 40, 0.18);
  transition: opacity var(--t), transform var(--t), box-shadow var(--t);
}

.services__cta:hover {
  opacity: 0.87;
  transform: translateY(-2px);
  box-shadow: 0 8px 36px rgba(12, 21, 40, 0.24);
}

@media (max-width: 1024px) { .services__grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .services__grid { grid-template-columns: repeat(2, 1fr); } .services { padding: 6rem 1.75rem; } }
@media (max-width: 480px) { .services__grid { grid-template-columns: 1fr; } }
</style>
