<script setup lang="ts">
import TestimonialCard from '@/components/TestimonialCard.vue'

const temoignages = [
  {
    nom: 'Marie-Claire D.', pays: 'Côte d\'Ivoire', initiales: 'MC',
    consultation: 'Mariage', couleurAvatar: '#c2185b',
    texte: 'Après des années d\'attente, le Prophète a prononcé une parole précise sur mon mariage. Trois mois plus tard, j\'ai rencontré mon époux. Sa prophétie s\'est réalisée mot pour mot.',
  },
  {
    nom: 'Pastor Emmanuel K.', pays: 'Nigeria', initiales: 'EK',
    consultation: 'Appel', couleurAvatar: '#3949ab',
    texte: 'Le Prophète a confirmé mon appel au ministère avec des détails que seul Dieu pouvait connaître. Depuis cette consultation, mon église a triplé en nombre.',
  },
  {
    nom: 'Fatou S.', pays: 'Sénégal', initiales: 'FS',
    consultation: 'Affaires', couleurAvatar: '#C8921C',
    texte: 'J\'étais au bord de la faillite. Le Prophète m\'a donné une stratégie précise pour mon commerce. Aujourd\'hui, mon entreprise génère 10 fois plus qu\'avant.',
  },
  {
    nom: 'Jean-Baptiste M.', pays: 'France', initiales: 'JB',
    consultation: 'Carrière politique', couleurAvatar: '#0EAA72',
    texte: 'Candidat à une élection locale que tous pensaient perdue, j\'ai remporté l\'élection avec 67% des voix après la prière du Prophète. Ce que Dieu planifie ne peut être arrêté.',
  },
]
</script>

<template>
  <section class="ts" id="temoignages">
    <div class="ts__container">

      <header class="ts__header">
        <p class="ts__eyebrow">Témoignages</p>
        <h2 class="ts__title">
          Ce que disent ceux qui<br>
          <em>ont consulté</em>
        </h2>
        <p class="ts__desc">
          Des milliers de vies transformées à travers les consultations prophétiques.
        </p>
      </header>

      <div class="ts__grid">
        <TestimonialCard
          v-for="t in temoignages"
          :key="t.nom"
          v-bind="t"
        />
      </div>

      <div class="ts__cta">
        <a href="#rdv" class="ts__cta-btn">
          Prendre rendez-vous
          <span>→</span>
        </a>
      </div>

    </div>
  </section>
</template>

<style scoped>
.ts {
  padding: 5rem 0;
  background: white;
}
.ts__container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 1.75rem;
}

/* Header */
.ts__header {
  text-align: center;
  margin-bottom: 3rem;
}
.ts__eyebrow {
  font-family: var(--f-mono);
  font-size: 0.62rem;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  color: rgba(200,146,28,0.6);
  margin-bottom: 1rem;
}
.ts__title {
  font-family: var(--f-display);
  font-size: clamp(1.75rem, 3.5vw, 2.5rem);
  font-weight: 400;
  letter-spacing: 0.04em;
  color: var(--ink);
  line-height: 1.2;
  margin-bottom: 0.75rem;
}
.ts__title em {
  font-style: normal;
  font-weight: 700;
  color: var(--emerald);
}
.ts__desc {
  font-family: var(--f-serif);
  font-size: 1rem;
  font-style: italic;
  color: var(--text-soft);
  max-width: 420px;
  margin: 0 auto;
}

/* Grid */
.ts__grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}
@media (max-width: 900px) { .ts__grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 500px) { .ts__grid { grid-template-columns: 1fr; } }

/* CTA */
.ts__cta {
  text-align: center;
  margin-top: 3rem;
}
.ts__cta-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.8rem 2rem;
  border-radius: var(--r2);
  background: var(--emerald);
  color: white;
  font-family: var(--f-mono);
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  transition: background var(--t), transform var(--tf);
}
.ts__cta-btn:hover {
  background: #0a9463;
  transform: translateY(-1px);
}
</style>
