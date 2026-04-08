<script setup lang="ts">
import { Quote } from 'lucide-vue-next'

const testimonials = [
  { initiales: 'MC', nom: 'Marie-Claire D.', pays: 'Côte d\'Ivoire', consultation: 'Mariage', hue: '#c2185b', etoiles: 5,
    texte: 'Trois mois après la prophétie, j\'ai rencontré mon époux. Chaque mot s\'est réalisé avec une précision qui m\'a laissée sans voix. Seul Dieu pouvait connaître ces détails.' },
  { initiales: 'EK', nom: 'Pastor Emmanuel K.', pays: 'Nigeria', consultation: 'Appel', hue: '#3949ab', etoiles: 5,
    texte: 'Il a confirmé mon appel au ministère avec des détails que seul Dieu pouvait connaître — le nom de ma ville d\'origine, l\'année de ma conversion, et la nation vers laquelle Dieu m\'envoyait.' },
  { initiales: 'FS', nom: 'Fatou S.', pays: 'Sénégal', consultation: 'Affaires', hue: '#B07A14', etoiles: 5,
    texte: 'Au bord de la faillite, sa stratégie divine a multiplié mon chiffre d\'affaires par 10 en 8 mois. Ce n\'est pas de la magie — c\'est la direction de l\'Esprit Saint.' },
  { initiales: 'JB', nom: 'Jean-Baptiste M.', pays: 'France', consultation: 'Politique', hue: '#0A9060', etoiles: 5,
    texte: 'Élu avec 67% des voix après sa prière et sa prophétie sur ma candidature. Il m\'avait annoncé la date exacte de ma victoire deux mois à l\'avance.' },
  { initiales: 'AB', nom: 'Amara B.', pays: 'Guinée', consultation: 'Santé', hue: '#D32F2F', etoiles: 5,
    texte: 'Les médecins avaient abandonné tout espoir. Après la consultation et la prière prophétique, les examens de contrôle ont montré une guérison totale. Je rends gloire à Dieu.' },
  { initiales: 'GN', nom: 'Grace N.', pays: 'Ghana', consultation: 'Voyage', hue: '#0277BD', etoiles: 5,
    texte: 'Mon dossier de visa était bloqué depuis 3 ans. Il a prophétisé une porte ouverte dans 40 jours. Le 38ème jour, j\'avais mon visa en main. Dieu est fidèle.' },
]
</script>

<template>
  <section id="temoignages" class="testimonials">
    <div class="testimonials__inner">

      <div class="testimonials__header">
        <span class="testimonials__label">Ce qu'ils disent</span>
        <h2 class="testimonials__title">Des vies <em>transformées</em></h2>
        <p class="testimonials__desc">
          Des milliers de personnes à travers le monde ont reçu une parole prophétique précise.
        </p>
      </div>

      <div class="testimonials__grid">
        <article v-for="(t, i) in testimonials" :key="i" class="tcard">
          <div class="tcard__top">
            <Quote :size="18" class="tcard__quote-icon" />
            <div class="tcard__stars">
              <span v-for="s in t.etoiles" :key="s" class="tcard__star">★</span>
            </div>
          </div>
          <p class="tcard__text">{{ t.texte }}</p>
          <div class="tcard__footer">
            <div class="tcard__author">
              <div class="tcard__avatar" :style="{ background: t.hue }">{{ t.initiales }}</div>
              <div class="tcard__meta">
                <span class="tcard__name">{{ t.nom }}</span>
                <span class="tcard__country">{{ t.pays }}</span>
              </div>
            </div>
            <span class="tcard__tag">{{ t.consultation }}</span>
          </div>
        </article>
      </div>

      <div class="testimonials__cta-wrap">
        <p class="testimonials__cta-text">Votre témoignage vous attend.</p>
        <a href="/rdv" class="testimonials__cta">Prendre rendez-vous →</a>
      </div>

    </div>
  </section>
</template>

<style scoped>
.testimonials {
  background: #fff;
  padding: 7rem 2rem;
}

.testimonials__inner { max-width: 1200px; margin: 0 auto; }

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
  color: #B07A14;
  margin-bottom: 0.85rem;
}

.testimonials__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(1.9rem, 4vw, 2.8rem);
  line-height: 1.1;
  letter-spacing: 0.04em;
  color: #0C1528;
  margin-bottom: 1rem;
}

.testimonials__title em {
  font-family: var(--f-serif);
  font-style: italic;
  color: #B07A14;
}

.testimonials__desc {
  font-family: var(--f-serif);
  font-size: 0.95rem;
  line-height: 1.7;
  color: #9B9590;
}

.testimonials__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
  margin-bottom: 3.5rem;
}

.tcard {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.75rem;
  background: #FDFCF9;
  border: 1px solid #EAE7E0;
  border-radius: 6px;
  transition: background var(--t), border-color var(--t), box-shadow var(--t), transform var(--t);
}

.tcard:hover {
  background: #fff;
  border-color: #D4CFC6;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.07);
  transform: translateY(-3px);
}

.tcard__top { display: flex; align-items: center; justify-content: space-between; }
.tcard__quote-icon { color: #D4C9A8; }
.tcard__stars { display: flex; gap: 0.1rem; }
.tcard__star { color: #B07A14; font-size: 0.72rem; }

.tcard__text {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.9rem;
  line-height: 1.7;
  color: #4B4540;
  flex: 1;
}

.tcard__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 0.85rem;
  border-top: 1px solid #EAE7E0;
}

.tcard__author { display: flex; align-items: center; gap: 0.65rem; }

.tcard__avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-family: var(--f-display);
  font-size: 0.6rem; font-weight: 700;
  color: white; flex-shrink: 0;
}

.tcard__meta { display: flex; flex-direction: column; gap: 0.08rem; }

.tcard__name {
  font-family: var(--f-display);
  font-size: 0.65rem; font-weight: 600;
  letter-spacing: 0.06em; color: #1A1714;
}

.tcard__country {
  font-family: var(--f-mono);
  font-size: 0.55rem; color: #9B9590; letter-spacing: 0.06em;
}

.tcard__tag {
  font-family: var(--f-mono);
  font-size: 0.52rem;
  padding: 0.2rem 0.6rem;
  border: 1px solid rgba(10, 144, 96, 0.25);
  color: #0A9060;
  border-radius: 2px;
  white-space: nowrap;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  background: rgba(10, 144, 96, 0.04);
}

.testimonials__cta-wrap {
  display: flex; flex-direction: column;
  align-items: center; gap: 1rem; text-align: center;
}

.testimonials__cta-text {
  font-family: var(--f-serif);
  font-style: italic; font-size: 1.1rem; color: #9B9590;
}

.testimonials__cta {
  font-family: var(--f-display);
  font-size: 0.72rem; letter-spacing: 0.1em; text-transform: uppercase;
  color: #0C1528; text-decoration: none;
  padding-bottom: 0.3rem;
  border-bottom: 1px solid #0C1528;
  transition: color var(--t), border-color var(--t);
}

.testimonials__cta:hover { color: #B07A14; border-color: #B07A14; }

@media (max-width: 1024px) { .testimonials__grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .testimonials__grid { grid-template-columns: 1fr; } .testimonials { padding: 5rem 1.5rem; } }
</style>
