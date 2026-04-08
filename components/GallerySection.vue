<script setup lang="ts">
const photos = [
  { src: '/images/gallery-1.jpg', caption: 'Croisade prophétique — Abidjan, 2024', span: 'wide' },
  { src: '/images/gallery-2.jpg', caption: 'Consultation privée', span: 'normal' },
  { src: '/images/gallery-3.jpg', caption: 'Séminaire des leaders — Paris', span: 'normal' },
  { src: '/images/gallery-4.jpg', caption: 'Prière prophétique nationale', span: 'normal' },
  { src: '/images/gallery-5.jpg', caption: 'Retraite spirituelle — Lagos', span: 'normal' },
  { src: '/images/gallery-6.jpg', caption: 'Conférence internationale', span: 'wide' },
]
</script>

<template>
  <section id="galerie" class="gallery">
    <div class="gallery__inner">

      <div class="gallery__header">
        <span class="gallery__label">En images</span>
        <h2 class="gallery__title">Le ministère <em>à travers le monde</em></h2>
      </div>

      <div class="gallery__grid">
        <div
          v-for="(p, i) in photos"
          :key="i"
          :class="['gallery__item', p.span === 'wide' ? 'gallery__item--wide' : '']"
        >
          <div class="gallery__img-wrap">
            <img :src="p.src" :alt="p.caption" class="gallery__img"
              @error="(e: Event) => ((e.target as HTMLImageElement).parentElement!.querySelector('.gallery__placeholder') as HTMLElement).style.display = 'flex'"
            />
            <div class="gallery__placeholder">
              <svg viewBox="0 0 24 24" fill="none" width="28" height="28">
                <rect x="3" y="5" width="18" height="14" rx="2" stroke="#C4BAA0" stroke-width="1.5"/>
                <circle cx="8.5" cy="10.5" r="1.5" stroke="#C4BAA0" stroke-width="1.5"/>
                <path d="m21 15-5-5L5 19" stroke="#C4BAA0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>{{ p.caption }}</span>
            </div>
          </div>
          <div class="gallery__overlay">
            <p class="gallery__caption">{{ p.caption }}</p>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

<style scoped>
.gallery {
  background: #FDFCF9;
  padding: 7rem 2rem;
}

.gallery__inner { max-width: 1200px; margin: 0 auto; }

.gallery__header { text-align: center; margin-bottom: 3.5rem; }

.gallery__label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.58rem; letter-spacing: 0.16em;
  text-transform: uppercase; color: #B07A14; margin-bottom: 0.85rem;
}

.gallery__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(1.9rem, 4vw, 2.8rem);
  line-height: 1.1; letter-spacing: 0.04em; color: #0C1528;
}

.gallery__title em {
  font-family: var(--f-serif);
  font-style: italic; color: #B07A14;
}

.gallery__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-auto-rows: 230px;
  gap: 1rem;
}

.gallery__item {
  position: relative;
  overflow: hidden;
  border-radius: 6px;
  border: 1px solid #EAE7E0;
  cursor: pointer;
}

.gallery__item--wide { grid-column: span 2; }

.gallery__img-wrap { position: absolute; inset: 0; }

.gallery__img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.5s var(--ease);
}

.gallery__item:hover .gallery__img { transform: scale(1.06); }

.gallery__placeholder {
  position: absolute;
  inset: 0;
  display: none;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.65rem;
  background: #F6F4EF;
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.75rem;
  color: #C4BAA0;
  text-align: center;
  padding: 1rem;
}

.gallery__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(12, 21, 40, 0.72) 0%, transparent 55%);
  display: flex;
  align-items: flex-end;
  padding: 1.1rem;
  opacity: 0;
  transition: opacity 0.3s var(--ease);
}

.gallery__item:hover .gallery__overlay { opacity: 1; }

.gallery__caption {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.9);
}

@media (max-width: 768px) {
  .gallery__grid { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 165px; }
  .gallery { padding: 5rem 1.5rem; }
}

@media (max-width: 480px) {
  .gallery__grid { grid-template-columns: 1fr; grid-auto-rows: 200px; }
  .gallery__item--wide { grid-column: span 1; }
}
</style>
