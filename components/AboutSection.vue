<script setup lang="ts">
import { CheckCircle2 } from 'lucide-vue-next'

const gifts = [
  'Prophétie de précision — noms, dates, détails vérifiables',
  'Conseil stratégique pour dirigeants et entrepreneurs',
  'Intercession et délivrance spirituelle',
  'Activation des appels prophétiques',
  'Guidance dans les décisions de carrière et de mariage',
  'Révélation sur les années et les saisons spirituelles',
]
</script>

<template>
  <section id="apropos" class="about">
    <div class="about__inner">

      <!-- Image / Portrait -->
      <div class="about__visual">
        <div class="about__frame">
          <div class="about__frame-corner about__frame-corner--tl" />
          <div class="about__frame-corner about__frame-corner--br" />
          <div class="about__portrait">
            <img
              src="/images/prophet-main.jpg"
              alt="Prophète Jeremiah Nahoum"
              class="about__portrait-img"
              @error="(e: Event) => ((e.target as HTMLImageElement).style.display='none')"
            />
            <div class="about__portrait-placeholder">
              <svg viewBox="0 0 160 200" fill="none">
                <rect width="160" height="200" fill="#F6F4EF"/>
                <ellipse cx="80" cy="68" rx="34" ry="36" fill="#EDE9E0" stroke="#D4C9A8" stroke-width="1"/>
                <path d="M20 190 C20 140 48 115 80 115 C112 115 140 140 140 190" fill="#EDE9E0" stroke="#D4C9A8" stroke-width="1"/>
                <text x="80" y="160" text-anchor="middle" font-family="serif" font-size="9" fill="#B8AD95">photo prophète</text>
              </svg>
            </div>
            <div class="about__portrait-badge">
              <span class="about__badge-cross">✦</span>
              <span>Prophète Jeremiah Nahoum</span>
            </div>
          </div>
        </div>

        <div class="about__float-verse">
          <p class="about__float-text">"Avant que je te forme dans le ventre de ta mère, je te connaissais"</p>
          <span class="about__float-ref">— Jérémie 1:5</span>
        </div>
      </div>

      <!-- Texte -->
      <div class="about__text">
        <span class="about__section-label">À propos du ministère</span>

        <h2 class="about__title">
          Un prophète<br>
          <em>au service des nations</em>
        </h2>

        <div class="about__body">
          <p>
            Depuis plus de 15 ans, le Prophète Jeremiah Nahoum exerce un ministère prophétique
            international reconnu pour sa précision et sa profondeur spirituelle. Surnommé
            <strong>"Le Conseiller des Rois"</strong>, il a accompagné des chefs d'État,
            des hommes d'affaires, des pasteurs et des familles à travers plus de 40 nations.
          </p>
          <p>
            Son ministère est fondé sur la révélation divine : des prophéties qui citent des noms,
            des dates et des événements avec une exactitude qui ne peut venir que de Dieu.
            Chaque consultation est un rendez-vous avec l'Éternel.
          </p>
        </div>

        <div class="about__gifts">
          <p class="about__gifts-title">Domaines de ministère</p>
          <ul class="about__gifts-list">
            <li v-for="(gift, i) in gifts" :key="i" class="about__gift">
              <CheckCircle2 :size="14" class="about__gift-icon" />
              <span>{{ gift }}</span>
            </li>
          </ul>
        </div>

        <a href="/rdv" class="about__cta">Réserver une consultation →</a>
      </div>

    </div>
  </section>
</template>

<style scoped>
.about {
  background: #FDFCF9;
  padding: 7rem 2rem;
}

.about__inner {
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1.15fr;
  gap: 6rem;
  align-items: center;
}

/* Visual */
.about__frame {
  position: relative;
  padding: 1rem;
}

.about__frame-corner {
  position: absolute;
  width: 48px;
  height: 48px;
  pointer-events: none;
}

.about__frame-corner--tl {
  top: 0; left: 0;
  border-top: 1px solid #B07A14;
  border-left: 1px solid #B07A14;
}

.about__frame-corner--br {
  bottom: 0; right: 0;
  border-bottom: 1px solid #D4CFC6;
  border-right: 1px solid #D4CFC6;
}

.about__portrait {
  position: relative;
  overflow: hidden;
  border-radius: 4px;
  border: 1px solid #E5E2DB;
  background: #F6F4EF;
}

.about__portrait-img {
  width: 100%;
  display: block;
  aspect-ratio: 4/5;
  object-fit: cover;
}

.about__portrait-placeholder {
  display: block;
  width: 100%;
  aspect-ratio: 4/5;
}

.about__portrait-placeholder svg {
  width: 100%;
  height: 100%;
  display: block;
}

.about__portrait-badge {
  position: absolute;
  bottom: 1rem;
  left: 50%;
  transform: translateX(-50%);
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-family: var(--f-display);
  font-size: 0.58rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #1A1714;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
  padding: 0.45rem 0.9rem;
  border: 1px solid #E5E2DB;
  border-radius: 2px;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.about__badge-cross { color: #B07A14; font-size: 0.65rem; }

.about__float-verse {
  margin-top: 1.25rem;
  padding: 0.85rem 1rem;
  border-left: 2px solid rgba(176, 122, 20, 0.3);
  background: rgba(176, 122, 20, 0.04);
  border-radius: 0 4px 4px 0;
}

.about__float-text {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.82rem;
  line-height: 1.55;
  color: #6B6560;
  margin-bottom: 0.35rem;
}

.about__float-ref {
  font-family: var(--f-mono);
  font-size: 0.57rem;
  letter-spacing: 0.1em;
  color: #B07A14;
}

/* Texte */
.about__section-label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: #B07A14;
  margin-bottom: 1rem;
}

.about__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(1.8rem, 3vw, 2.6rem);
  line-height: 1.15;
  letter-spacing: 0.04em;
  color: #0C1528;
  margin-bottom: 1.5rem;
}

.about__title em {
  font-family: var(--f-serif);
  font-style: italic;
  color: #B07A14;
}

.about__body {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  margin-bottom: 1.75rem;
}

.about__body p {
  font-family: var(--f-serif);
  font-size: 1rem;
  line-height: 1.75;
  color: #6B6560;
}

.about__body strong { color: #1A1714; font-weight: 600; }

.about__gifts-title {
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #9B9590;
  margin-bottom: 0.75rem;
}

.about__gifts-list {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  list-style: none;
  padding: 0;
  margin: 0 0 2rem;
}

.about__gift {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  font-family: var(--f-serif);
  font-size: 0.9rem;
  color: #4B4540;
  line-height: 1.4;
}

.about__gift-icon {
  color: #0A9060;
  flex-shrink: 0;
  margin-top: 0.1rem;
}

.about__cta {
  display: inline-flex;
  align-items: center;
  font-family: var(--f-display);
  font-size: 0.72rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #0C1528;
  text-decoration: none;
  padding-bottom: 0.3rem;
  border-bottom: 1px solid #0C1528;
  transition: color var(--t), border-color var(--t);
}

.about__cta:hover {
  color: #B07A14;
  border-color: #B07A14;
}

@media (max-width: 900px) {
  .about__inner { grid-template-columns: 1fr; gap: 3rem; }
  .about__visual { max-width: 380px; margin: 0 auto; }
  .about { padding: 5rem 1.5rem; }
}
</style>
