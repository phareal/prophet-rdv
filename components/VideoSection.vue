<script setup lang="ts">
import { Play, Youtube } from 'lucide-vue-next'

const { data: videos } = await useFetch('/api/youtube/latest')

const channelUrl = 'https://www.youtube.com/@ProphetJeremiahNahoum'
</script>

<template>
  <section id="video" class="video-section">
    <div class="video-section__inner">

      <div class="video-section__header">
        <div class="section-ornament"><span>✦</span></div>
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
          <a
            :href="`https://www.youtube.com/watch?v=${v.id}`"
            target="_blank"
            rel="noopener noreferrer"
            class="vcard__thumb"
          >
            <img
              :src="v.thumbnail"
              :alt="v.title"
              class="vcard__img"
              @error="(e: Event) => ((e.target as HTMLImageElement).style.display='none')"
            />
            <div class="vcard__thumb-placeholder">
              <Youtube :size="36" class="vcard__yt-icon" />
            </div>
            <div class="vcard__play">
              <div class="vcard__play-btn">
                <Play :size="20" fill="currentColor" />
              </div>
            </div>
            <span class="vcard__duration">{{ v.duration }}</span>
          </a>
          <div class="vcard__body">
            <span class="vcard__type">{{ v.type }}</span>
            <h3 class="vcard__title">{{ v.title }}</h3>
            <p class="vcard__desc">{{ v.desc }}</p>
          </div>
        </div>
      </div>

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
  padding: 8rem 2.5rem;
  position: relative;
}

.video-section::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent 0%, rgba(176,122,20,0.3) 30%, rgba(176,122,20,0.3) 70%, transparent 100%);
}

.video-section__inner { max-width: 1200px; margin: 0 auto; }

.video-section__header {
  text-align: center;
  max-width: 580px;
  margin: 0 auto 4.5rem;
}

.video-section__label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.68rem; letter-spacing: 0.18em; text-transform: uppercase;
  color: #B07A14; margin-bottom: 1rem;
}

.video-section__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(2.2rem, 4vw, 3.2rem);
  line-height: 1.1; letter-spacing: 0.04em;
  color: #0C1528; margin-bottom: 1.25rem;
}

.video-section__title em {
  font-family: var(--f-serif); font-style: italic; color: #B07A14;
}

.video-section__desc {
  font-family: var(--f-serif); font-size: 1.1rem; line-height: 1.75; color: #4A4540;
}

/* Grid */
.video-section__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.75rem;
  margin-bottom: 3.5rem;
}

/* Card */
.vcard {
  background: #fff;
  border: 1px solid #EAE7E0;
  border-radius: 8px;
  overflow: hidden;
  transition: box-shadow var(--t), transform var(--t);
  box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}

.vcard:hover {
  box-shadow: 0 12px 40px rgba(0,0,0,0.09);
  transform: translateY(-4px);
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
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.5s var(--ease);
  display: block;
}

.vcard:hover .vcard__img { transform: scale(1.04); }

.vcard__thumb-placeholder {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #EDE9E0, #E5E0D5);
}

.vcard__yt-icon { color: #C4BAA0; }

.vcard__play {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(12, 21, 40, 0.28);
  opacity: 0;
  transition: opacity 0.25s var(--ease);
}

.vcard__thumb:hover .vcard__play { opacity: 1; }

.vcard__play-btn {
  width: 54px; height: 54px;
  border-radius: 50%;
  background: rgba(255,255,255,0.95);
  display: flex; align-items: center; justify-content: center;
  color: #0C1528;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
  transition: transform var(--t);
}

.vcard__thumb:hover .vcard__play-btn { transform: scale(1.08); }

.vcard__duration {
  position: absolute; bottom: 0.7rem; right: 0.7rem;
  font-family: var(--f-mono); font-size: 0.58rem;
  color: #fff;
  background: rgba(12, 21, 40, 0.72);
  padding: 0.2rem 0.55rem; border-radius: 2px;
}

/* Body */
.vcard__body {
  padding: 1.4rem 1.6rem;
  display: flex; flex-direction: column; gap: 0.5rem;
}

.vcard__type {
  font-family: var(--f-mono);
  font-size: 0.62rem; letter-spacing: 0.1em;
  text-transform: uppercase; color: #B07A14;
}

.vcard__title {
  font-family: var(--f-display);
  font-size: 0.9rem; font-weight: 600;
  letter-spacing: 0.04em; color: #0C1528; line-height: 1.3;
}

.vcard__desc {
  font-family: var(--f-serif);
  font-size: 0.95rem; line-height: 1.65; color: #5C5752;
}

/* Channel link */
.video-section__channel { display: flex; justify-content: center; }

.video-section__channel-btn {
  display: inline-flex; align-items: center; gap: 0.65rem;
  font-family: var(--f-mono); font-size: 0.7rem;
  letter-spacing: 0.1em; text-transform: uppercase;
  color: #fff; background: #FF0000;
  padding: 0.9rem 2.25rem; border-radius: 3px;
  text-decoration: none; font-weight: 600;
  transition: opacity var(--t), transform var(--t);
  box-shadow: 0 4px 20px rgba(255, 0, 0, 0.2);
}

.video-section__channel-btn:hover {
  opacity: 0.88; transform: translateY(-2px);
}

@media (max-width: 900px) {
  .video-section__grid { grid-template-columns: repeat(2, 1fr); }
  .video-section { padding: 6rem 1.75rem; }
}

@media (max-width: 600px) { .video-section__grid { grid-template-columns: 1fr; } }
</style>
