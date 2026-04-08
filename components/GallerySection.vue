<script setup lang="ts">
const photos = [
  { src: '/images/prophet-main.jpg',   caption: 'Prophète Jeremiah Nahoum',          span: 'normal' },
  { src: '/images/prophet-main-2.jpeg', caption: 'Consultation prophétique',           span: 'normal' },
  { src: '/images/prophet-main-3.jpeg', caption: 'Le Conseiller des Rois',             span: 'normal' },
  { src: '/images/prophet-main.jpg',   caption: 'Ministère international',            span: 'wide' },
  { src: '/images/prophet-main-2.jpeg', caption: 'Prière et intercession',             span: 'normal' },
  { src: '/images/prophet-main-3.jpeg', caption: 'Parole prophétique aux nations',     span: 'normal' },
]
</script>

<template>
  <section id="galerie" class="gallery">
    <div class="gallery__inner">

      <div class="gallery__header">
        <div class="section-ornament"><span>✦</span></div>
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
            <img
              :src="p.src"
              :alt="p.caption"
              class="gallery__img"
              loading="lazy"
              width="400"
              height="240"
              @error="(e: Event) => ((e.target as HTMLImageElement).parentElement!.querySelector('.gallery__placeholder') as HTMLElement).style.display = 'flex'"
            />
            <div class="gallery__placeholder">
              <svg viewBox="0 0 24 24" fill="none" width="32" height="32">
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
  background: #F6F4EF;
  padding: 8rem 2.5rem;
  position: relative;
}

.gallery::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent 0%, rgba(176,122,20,0.3) 30%, rgba(176,122,20,0.3) 70%, transparent 100%);
}

.gallery__inner { max-width: 1200px; margin: 0 auto; }

.gallery__header { text-align: center; margin-bottom: 3.5rem; }

.gallery__label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.68rem; letter-spacing: 0.18em;
  text-transform: uppercase; color: #B07A14; margin-bottom: 1rem;
}

.gallery__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(2.2rem, 4vw, 3.2rem);
  line-height: 1.1; letter-spacing: 0.04em; color: #0C1528;
}

.gallery__title em {
  font-family: var(--f-serif); font-style: italic; color: #B07A14;
}

.gallery__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-auto-rows: 240px;
  gap: 1rem;
}

.gallery__item {
  position: relative;
  overflow: hidden;
  border-radius: 8px;
  border: 1px solid #EAE7E0;
  cursor: pointer;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05);
  transition: box-shadow var(--t), transform var(--t);
}

.gallery__item:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.gallery__item--wide { grid-column: span 2; }

.gallery__img-wrap { position: absolute; inset: 0; }

.gallery__img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.55s var(--ease);
}

.gallery__item:hover .gallery__img { transform: scale(1.06); }

.gallery__placeholder {
  position: absolute; inset: 0;
  display: none;
  flex-direction: column; align-items: center; justify-content: center;
  gap: 0.75rem;
  background: linear-gradient(135deg, #F0EDE6, #EAE7E0);
  font-family: var(--f-serif); font-style: italic;
  font-size: 0.9rem; color: #C4BAA0;
  text-align: center; padding: 1.25rem;
}

.gallery__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(12, 21, 40, 0.75) 0%, transparent 50%);
  display: flex; align-items: flex-end;
  padding: 1.3rem;
  opacity: 0;
  transition: opacity 0.3s var(--ease);
}

.gallery__item:hover .gallery__overlay { opacity: 1; }

.gallery__caption {
  font-family: var(--f-serif); font-style: italic;
  font-size: 0.95rem; color: rgba(255,255,255,0.92);
}

@media (max-width: 768px) {
  .gallery__grid { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 175px; }
  .gallery { padding: 6rem 1.75rem; }
}

@media (max-width: 480px) {
  .gallery__grid { grid-template-columns: 1fr; grid-auto-rows: 210px; }
  .gallery__item--wide { grid-column: span 1; }
}
</style>
