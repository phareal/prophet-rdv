<script setup lang="ts">
const photos = [
  { num: 1, caption: 'Culte de guérison',        location: 'Abidjan, 2024' },
  { num: 2, caption: 'Conseil aux leaders',       location: 'Lagos, 2024' },
  { num: 3, caption: 'Conférence prophétique',    location: 'Paris, 2023' },
  { num: 4, caption: 'Prière d\'intercession',    location: 'Dakar, 2024' },
  { num: 5, caption: 'Séminaire de formation',    location: 'Lomé, 2023' },
  { num: 6, caption: 'Rencontre des nations',     location: 'Genève, 2024' },
]
</script>

<template>
  <section class="gallery" id="galerie">
    <div class="gallery__container">

      <header class="gallery__header">
        <p class="gallery__eyebrow">Galerie</p>
        <h2 class="gallery__title">
          Moments de <em>grâce</em>
        </h2>
        <p class="gallery__desc">
          Des témoignages visuels de la puissance de Dieu à travers le ministère du Prophète Jeremiah Nahoum.
        </p>
      </header>

      <div class="gallery__grid">
        <figure
          v-for="photo in photos"
          :key="photo.num"
          class="gallery__item"
        >
          <!-- Placeholder — remplacer par <img src="/images/gallery-N.jpg" ... /> -->
          <div class="gallery__placeholder">
            <svg class="gallery__ph-icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="6" y="10" width="36" height="28" rx="3" stroke="currentColor" stroke-width="1.2" fill="none"/>
              <circle cx="16" cy="20" r="4" stroke="currentColor" stroke-width="1.2" fill="none"/>
              <path d="M6 32 L16 22 L24 30 L32 22 L42 32" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" fill="none"/>
            </svg>
            <p class="gallery__ph-num">Photo {{ photo.num }}</p>
            <p class="gallery__ph-path">/images/gallery-{{ photo.num }}.jpg</p>
          </div>

          <figcaption class="gallery__caption">
            <p class="gallery__caption-title">{{ photo.caption }}</p>
            <p class="gallery__caption-loc">{{ photo.location }}</p>
          </figcaption>
        </figure>
      </div>

      <p class="gallery__hint">
        Ajoutez vos photos dans
        <code>/public/images/</code> et mettez à jour
        <code>GallerySection.vue</code>
      </p>

    </div>
  </section>
</template>

<style scoped>
.gallery {
  padding: 5rem 0;
  background: #F8F6F1;
}
.gallery__container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 1.75rem;
}

/* Header */
.gallery__header {
  text-align: center;
  margin-bottom: 3rem;
}
.gallery__eyebrow {
  font-family: var(--f-mono);
  font-size: 0.62rem;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  color: var(--emerald);
  margin-bottom: 1rem;
  opacity: 0.7;
}
.gallery__title {
  font-family: var(--f-display);
  font-size: clamp(1.75rem, 3.5vw, 2.5rem);
  font-weight: 400;
  letter-spacing: 0.04em;
  color: var(--ink);
  margin-bottom: 0.75rem;
}
.gallery__title em {
  font-style: normal;
  font-weight: 700;
  color: var(--emerald);
}
.gallery__desc {
  font-family: var(--f-serif);
  font-size: 1rem;
  font-style: italic;
  color: var(--text-soft);
  max-width: 420px;
  margin: 0 auto;
}

/* Grid */
.gallery__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}
@media (max-width: 700px) {
  .gallery__grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 440px) {
  .gallery__grid { grid-template-columns: 1fr; }
}

.gallery__item {
  position: relative;
  border-radius: var(--r3);
  overflow: hidden;
  background: white;
  border: 1px solid #EAE6DD;
  box-shadow: var(--sh-sm);
  transition: box-shadow var(--t);
}
.gallery__item:hover {
  box-shadow: var(--sh-md);
}
.gallery__item:hover .gallery__caption {
  opacity: 1;
  transform: translateY(0);
}

/* Placeholder */
.gallery__placeholder {
  height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  background: #F3F0E8;
}
.gallery__ph-icon {
  width: 36px; height: 36px;
  color: rgba(26,23,20,0.2);
}
.gallery__ph-num {
  font-family: var(--f-serif);
  font-size: 0.82rem;
  color: rgba(26,23,20,0.3);
}
.gallery__ph-path {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  color: rgba(26,23,20,0.18);
}

/* Caption overlay */
.gallery__caption {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 1rem;
  background: linear-gradient(to top, rgba(15,14,11,0.78) 0%, transparent 55%);
  opacity: 0;
  transform: translateY(4px);
  transition: opacity var(--t), transform var(--t);
}
.gallery__caption-title {
  font-family: var(--f-display);
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  color: white;
}
.gallery__caption-loc {
  font-family: var(--f-mono);
  font-size: 0.62rem;
  color: rgba(255,255,255,0.55);
  margin-top: 0.2rem;
}

/* Hint */
.gallery__hint {
  text-align: center;
  margin-top: 2rem;
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.85rem;
  color: rgba(26,23,20,0.32);
}
.gallery__hint code {
  font-family: var(--f-mono);
  font-style: normal;
  font-size: 0.75rem;
  color: var(--emerald);
}
</style>
