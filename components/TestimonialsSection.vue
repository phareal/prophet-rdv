<script setup lang="ts">
import { Quote } from 'lucide-vue-next'

const testimonials = [
  {
    initiales: 'MC',
    nom: 'Marie-Claire D.',
    pays: 'Côte d\'Ivoire',
    consultation: 'Mariage',
    hue: '#c2185b',
    texte: 'Trois mois après la prophétie, j\'ai rencontré mon époux. Chaque mot s\'est réalisé avec une précision qui m\'a laissée sans voix. Seul Dieu pouvait connaître ces détails.',
    etoiles: 5,
  },
  {
    initiales: 'EK',
    nom: 'Pastor Emmanuel K.',
    pays: 'Nigeria',
    consultation: 'Appel',
    hue: '#3949ab',
    texte: 'Il a confirmé mon appel au ministère avec des détails que seul Dieu pouvait connaître — le nom de ma ville d\'origine, l\'année de ma conversion, et la nation vers laquelle Dieu m\'envoyait.',
    etoiles: 5,
  },
  {
    initiales: 'FS',
    nom: 'Fatou S.',
    pays: 'Sénégal',
    consultation: 'Affaires',
    hue: '#C8921C',
    texte: 'Au bord de la faillite, sa stratégie divine a multiplié mon chiffre d\'affaires par 10 en 8 mois. Ce n\'est pas de la magie — c\'est la direction de l\'Esprit Saint.',
    etoiles: 5,
  },
  {
    initiales: 'JB',
    nom: 'Jean-Baptiste M.',
    pays: 'France',
    consultation: 'Politique',
    hue: '#0EAA72',
    texte: 'Élu avec 67% des voix après sa prière et sa prophétie sur ma candidature. Il m\'avait annoncé la date exacte de ma victoire deux mois à l\'avance.',
    etoiles: 5,
  },
  {
    initiales: 'AB',
    nom: 'Amara B.',
    pays: 'Guinée',
    consultation: 'Santé',
    hue: '#D32F2F',
    texte: 'Les médecins avaient abandonné tout espoir. Après la consultation et la prière prophétique, les examens de contrôle ont montré une guérison totale. Je rends gloire à Dieu.',
    etoiles: 5,
  },
  {
    initiales: 'GN',
    nom: 'Grace N.',
    pays: 'Ghana',
    consultation: 'Voyage',
    hue: '#0277BD',
    texte: 'Mon dossier de visa était bloqué depuis 3 ans. Il a prophétisé une porte ouverte dans 40 jours. Le 38ème jour, j\'avais mon visa en main. Dieu est fidèle.',
    etoiles: 5,
  },
]
</script>

<template>
  <section id="temoignages" class="testimonials">
    <div class="testimonials__inner">

      <!-- Header -->
      <div class="testimonials__header">
        <span class="testimonials__label">Ce qu'ils disent</span>
        <h2 class="testimonials__title">
          Des vies<br>
          <em>transformées</em>
        </h2>
        <p class="testimonials__desc">
          Des milliers de personnes à travers le monde ont reçu une parole prophétique précise.
          Voici quelques témoignages authentiques.
        </p>
      </div>

      <!-- Grille -->
      <div class="testimonials__grid">
        <article v-for="(t, i) in testimonials" :key="i" class="tcard">
          <div class="tcard__top">
            <Quote :size="20" class="tcard__quote-icon" />
            <div class="tcard__stars">
              <span v-for="s in t.etoiles" :key="s" class="tcard__star">★</span>
            </div>
          </div>

          <p class="tcard__text">{{ t.texte }}</p>

          <div class="tcard__footer">
            <div class="tcard__author">
              <div class="tcard__avatar" :style="{ background: t.hue }">
                {{ t.initiales }}
              </div>
              <div class="tcard__meta">
                <span class="tcard__name">{{ t.nom }}</span>
                <span class="tcard__country">{{ t.pays }}</span>
              </div>
            </div>
            <span class="tcard__tag">{{ t.consultation }}</span>
          </div>
        </article>
      </div>

      <!-- CTA -->
      <div class="testimonials__cta-wrap">
        <p class="testimonials__cta-text">Votre témoignage vous attend.</p>
        <a href="/rdv" class="testimonials__cta">Prendre rendez-vous →</a>
      </div>

    </div>
  </section>
</template>

<style scoped>
.testimonials {
  background: #080D1A;
  padding: 7rem 2rem;
}

.testimonials__inner {
  max-width: 1200px;
  margin: 0 auto;
}

/* Header */
.testimonials__header {
  text-align: center;
  max-width: 520px;
  margin: 0 auto 4rem;
}

.testimonials__label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--gold);
  opacity: 0.65;
  margin-bottom: 0.85rem;
}

.testimonials__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(1.9rem, 4vw, 2.8rem);
  line-height: 1.1;
  letter-spacing: 0.04em;
  color: #F4F0EB;
  margin-bottom: 1rem;
}

.testimonials__title em {
  font-family: var(--f-serif);
  font-style: italic;
  color: rgba(200, 146, 28, 0.65);
}

.testimonials__desc {
  font-family: var(--f-serif);
  font-size: 0.95rem;
  line-height: 1.7;
  color: rgba(232, 228, 220, 0.4);
}

/* Grid */
.testimonials__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
  margin-bottom: 3.5rem;
}

/* Card */
.tcard {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.75rem;
  background: rgba(255, 255, 255, 0.025);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 6px;
  transition: background var(--t), border-color var(--t), transform var(--t);
}

.tcard:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(200, 146, 28, 0.15);
  transform: translateY(-3px);
}

.tcard__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.tcard__quote-icon { color: rgba(200, 146, 28, 0.3); }

.tcard__stars { display: flex; gap: 0.1rem; }

.tcard__star {
  color: #C8921C;
  font-size: 0.7rem;
  opacity: 0.8;
}

.tcard__text {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.88rem;
  line-height: 1.7;
  color: rgba(232, 228, 220, 0.55);
  flex: 1;
}

.tcard__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 0.85rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.tcard__author { display: flex; align-items: center; gap: 0.65rem; }

.tcard__avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--f-display);
  font-size: 0.6rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.tcard__meta { display: flex; flex-direction: column; gap: 0.08rem; }

.tcard__name {
  font-family: var(--f-display);
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  color: rgba(232, 228, 220, 0.78);
}

.tcard__country {
  font-family: var(--f-mono);
  font-size: 0.55rem;
  color: rgba(255, 255, 255, 0.25);
  letter-spacing: 0.06em;
}

.tcard__tag {
  font-family: var(--f-mono);
  font-size: 0.52rem;
  padding: 0.2rem 0.6rem;
  border: 1px solid rgba(14, 170, 114, 0.2);
  color: rgba(14, 170, 114, 0.6);
  border-radius: 2px;
  white-space: nowrap;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

/* CTA */
.testimonials__cta-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  text-align: center;
}

.testimonials__cta-text {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 1.1rem;
  color: rgba(232, 228, 220, 0.4);
}

.testimonials__cta {
  font-family: var(--f-display);
  font-size: 0.72rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--gold);
  text-decoration: none;
  padding-bottom: 0.3rem;
  border-bottom: 1px solid rgba(200, 146, 28, 0.3);
  transition: color var(--t), border-color var(--t);
}

.testimonials__cta:hover {
  color: #F0C040;
  border-color: rgba(240, 192, 64, 0.5);
}

/* Responsive */
@media (max-width: 1024px) {
  .testimonials__grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
  .testimonials__grid { grid-template-columns: 1fr; }
  .testimonials { padding: 5rem 1.5rem; }
}
</style>
