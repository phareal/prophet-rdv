<script setup lang="ts">
import { Play, Youtube } from 'lucide-vue-next'

// Remplacer ces IDs par les vrais IDs YouTube du prophète
const videos = [
  {
    id: 'YOUTUBE_ID_1',
    title: 'Prophétie sur les nations — 2026',
    desc: 'Le Prophète Jeremiah annonce ce que Dieu prépare pour les nations en cette nouvelle saison.',
    duration: '45 min',
    type: 'Prophétie',
  },
  {
    id: 'YOUTUBE_ID_2',
    title: 'Consultation prophétique en direct',
    desc: 'Session de consultations prophétiques en direct avec des révélations précises et vérifiables.',
    duration: '1h 12 min',
    type: 'En direct',
  },
  {
    id: 'YOUTUBE_ID_3',
    title: 'Témoignages — Prophéties accomplies',
    desc: 'Des personnes témoignent de la précision des prophéties reçues lors de leurs consultations.',
    duration: '28 min',
    type: 'Témoignages',
  },
]

const channelUrl = 'https://www.youtube.com/@ProphetJeremiahNahoum'
</script>

<template>
  <section id="video" class="video-section">
    <div class="video-section__inner">

      <div class="video-section__header">
        <span class="video-section__label">Ministère en vidéo</span>
        <h2 class="video-section__title">
          Regardez<br>
          <em>le prophète en action</em>
        </h2>
        <p class="video-section__desc">
          Découvrez le ministère prophétique en vidéo — prédications, prophéties en direct et témoignages.
        </p>
      </div>

      <div class="video-section__grid">
        <div v-for="(v, i) in videos" :key="i" class="vcard">
          <!-- Thumbnail YouTube ou placeholder -->
          <a
            :href="`https://www.youtube.com/watch?v=${v.id}`"
            target="_blank"
            rel="noopener noreferrer"
            class="vcard__thumb"
          >
            <img
              :src="`https://img.youtube.com/vi/${v.id}/maxresdefault.jpg`"
              :alt="v.title"
              class="vcard__img"
              @error="(e: Event) => ((e.target as HTMLImageElement).style.display='none')"
            />
            <div class="vcard__thumb-placeholder">
              <Youtube :size="32" class="vcard__yt-icon" />
            </div>
            <div class="vcard__play">
              <Play :size="22" fill="currentColor" />
            </div>
            <span class="vcard__duration">{{ v.duration }}</span>
          </a>
          <!-- Infos -->
          <div class="vcard__body">
            <span class="vcard__type">{{ v.type }}</span>
            <h3 class="vcard__title">{{ v.title }}</h3>
            <p class="vcard__desc">{{ v.desc }}</p>
          </div>
        </div>
      </div>

      <!-- Lien chaîne YouTube -->
      <div class="video-section__channel">
        <a :href="channelUrl" target="_blank" rel="noopener noreferrer" class="video-section__channel-btn">
          <Youtube :size="18" />
          <span>Voir toutes les vidéos sur YouTube</span>
        </a>
      </div>

    </div>
  </section>
</template>

<style scoped>
.video-section {
  background: #F6F4EF;
  padding: 7rem 2rem;
}

.video-section__inner { max-width: 1200px; margin: 0 auto; }

.video-section__header {
  text-align: center;
  max-width: 560px;
  margin: 0 auto 4rem;
}

.video-section__label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: #B07A14;
  margin-bottom: 0.85rem;
}

.video-section__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(1.9rem, 4vw, 2.8rem);
  line-height: 1.1;
  letter-spacing: 0.04em;
  color: #0C1528;
  margin-bottom: 1rem;
}

.video-section__title em {
  font-family: var(--f-serif);
  font-style: italic;
  color: #B07A14;
}

.video-section__desc {
  font-family: var(--f-serif);
  font-size: 0.95rem;
  line-height: 1.7;
  color: #6B6560;
}

/* Grid */
.video-section__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  margin-bottom: 3rem;
}

/* Card */
.vcard {
  background: #fff;
  border: 1px solid #EAE7E0;
  border-radius: 6px;
  overflow: hidden;
  transition: box-shadow var(--t), transform var(--t);
}

.vcard:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,0.08);
  transform: translateY(-3px);
}

/* Thumbnail */
.vcard__thumb {
  display: block;
  position: relative;
  aspect-ratio: 16/9;
  overflow: hidden;
  background: #EDE9E0;
}

.vcard__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s var(--ease);
  display: block;
}

.vcard:hover .vcard__img { transform: scale(1.04); }

.vcard__thumb-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #EDE9E0;
}

.vcard__yt-icon { color: #C4BAA0; }

.vcard__play {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(12, 21, 40, 0.35);
  color: #fff;
  opacity: 0;
  transition: opacity 0.25s var(--ease);
}

.vcard__thumb:hover .vcard__play { opacity: 1; }

.vcard__duration {
  position: absolute;
  bottom: 0.6rem;
  right: 0.6rem;
  font-family: var(--f-mono);
  font-size: 0.55rem;
  letter-spacing: 0.06em;
  color: #fff;
  background: rgba(12, 21, 40, 0.75);
  padding: 0.2rem 0.5rem;
  border-radius: 2px;
}

/* Body */
.vcard__body {
  padding: 1.25rem 1.4rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.vcard__type {
  font-family: var(--f-mono);
  font-size: 0.55rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #B07A14;
}

.vcard__title {
  font-family: var(--f-display);
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  color: #0C1528;
  line-height: 1.3;
}

.vcard__desc {
  font-family: var(--f-serif);
  font-size: 0.82rem;
  line-height: 1.6;
  color: #9B9590;
}

/* Channel link */
.video-section__channel {
  display: flex;
  justify-content: center;
}

.video-section__channel-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  font-family: var(--f-mono);
  font-size: 0.65rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #fff;
  background: #FF0000;
  padding: 0.85rem 2rem;
  border-radius: 2px;
  text-decoration: none;
  font-weight: 600;
  transition: opacity var(--t), transform var(--t);
  box-shadow: 0 4px 18px rgba(255, 0, 0, 0.18);
}

.video-section__channel-btn:hover {
  opacity: 0.88;
  transform: translateY(-2px);
}

@media (max-width: 900px) {
  .video-section__grid { grid-template-columns: repeat(2, 1fr); }
  .video-section { padding: 5rem 1.5rem; }
}

@media (max-width: 600px) {
  .video-section__grid { grid-template-columns: 1fr; }
}
</style>
