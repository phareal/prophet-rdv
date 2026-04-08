<script setup lang="ts">
import { ChevronDown, Calendar, Play } from 'lucide-vue-next'
</script>

<template>
  <section id="accueil" class="hero">
    <div class="hero__bg">
      <div class="hero__bg-radial hero__bg-radial--gold" />
      <div class="hero__bg-radial hero__bg-radial--ivory" />
      <div class="hero__bg-pattern" />
    </div>

    <div class="hero__deco hero__deco--tl" />
    <div class="hero__deco hero__deco--br" />

    <div class="hero__inner">
      <!-- Colonne gauche : texte -->
      <div class="hero__text">
        <div class="hero__badge">
          <span class="hero__badge-dot" />
          <span>Consultation Prophétique Internationale</span>
        </div>

        <h1 class="hero__title">
          <span class="hero__title-pre">Prophète</span>
          <span class="hero__title-name">Jeremiah<br>Nahoum</span>
        </h1>

        <p class="hero__subtitle">"Le Conseiller des Rois"</p>

        <blockquote class="hero__verse">
          <p>"Il révèle les choses profondes et cachées ; il connaît ce qui est dans les ténèbres, et la lumière demeure avec lui."</p>
          <cite>— Daniel 2:22</cite>
        </blockquote>

        <div class="hero__actions">
          <a href="/rdv" class="hero__btn hero__btn--primary">
            <Calendar :size="17" />
            <span>Prendre Rendez-vous</span>
          </a>
          <a href="#video" class="hero__btn hero__btn--ghost">
            <Play :size="15" fill="currentColor" />
            <span>Voir le ministère</span>
          </a>
        </div>

        <div class="hero__stats">
          <div class="hero__stat">
            <span class="hero__stat-num">40+</span>
            <span class="hero__stat-label">Nations</span>
          </div>
          <div class="hero__stat-sep" />
          <div class="hero__stat">
            <span class="hero__stat-num">15+</span>
            <span class="hero__stat-label">Ans de ministère</span>
          </div>
          <div class="hero__stat-sep" />
          <div class="hero__stat">
            <span class="hero__stat-num">10K+</span>
            <span class="hero__stat-label">Consultations</span>
          </div>
        </div>
      </div>

      <!-- Colonne droite : portrait -->
      <div class="hero__portrait-wrap">
        <div class="hero__portrait-frame">
          <div class="hero__portrait-corner hero__portrait-corner--tl" />
          <div class="hero__portrait-corner hero__portrait-corner--br" />
          <div class="hero__portrait">
            <div class="hero__portrait-placeholder">
              <svg viewBox="0 0 200 260" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="200" height="260" fill="#F6F4EF"/>
                <ellipse cx="100" cy="88" rx="42" ry="45" fill="#EDE9E0" stroke="#D4C9A8" stroke-width="1"/>
                <path d="M22 250 C22 180 58 148 100 148 C142 148 178 180 178 250" fill="#EDE9E0" stroke="#D4C9A8" stroke-width="1"/>
                <text x="100" y="225" text-anchor="middle" font-family="serif" font-size="11" fill="#C4BAA0">photo · 400 × 500 px</text>
              </svg>
            </div>
            <img
              src="/images/prophet-main.jpg"
              alt="Prophète Jeremiah Nahoum"
              class="hero__portrait-img"
              @error="(e: Event) => ((e.target as HTMLImageElement).style.display = 'none')"
            />
          </div>
          <div class="hero__portrait-badge">
            <span class="hero__portrait-badge-cross">✦</span>
            <span>Prophète Jeremiah Nahoum</span>
          </div>
        </div>

        <div class="hero__float-card">
          <span class="hero__float-cross">✦</span>
          <p class="hero__float-verse">"Avant que je te forme dans le ventre de ta mère, je te connaissais"</p>
          <span class="hero__float-ref">— Jérémie 1:5</span>
        </div>
      </div>
    </div>

    <a href="#apropos" class="hero__scroll">
      <span class="hero__scroll-label">Défiler</span>
      <ChevronDown :size="16" class="hero__scroll-icon" />
    </a>
  </section>
</template>

<style scoped>
.hero {
  position: relative;
  min-height: 100vh;
  background: #FDFCF9;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.hero__bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.hero__bg-radial {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
}

.hero__bg-radial--gold {
  width: 700px; height: 700px;
  top: -200px; right: -150px;
  background: radial-gradient(circle, rgba(200, 146, 28, 0.09) 0%, transparent 70%);
}

.hero__bg-radial--ivory {
  width: 600px; height: 600px;
  bottom: -150px; left: -150px;
  background: radial-gradient(circle, rgba(176, 122, 20, 0.07) 0%, transparent 70%);
}

.hero__bg-pattern {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(176,122,20,0.028) 1px, transparent 1px),
    linear-gradient(90deg, rgba(176,122,20,0.028) 1px, transparent 1px);
  background-size: 72px 72px;
  mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
}

.hero__deco {
  position: absolute;
  pointer-events: none;
}

.hero__deco--tl {
  top: 8rem; left: 3.5rem;
  width: 110px; height: 110px;
  border-top: 1px solid rgba(176, 122, 20, 0.22);
  border-left: 1px solid rgba(176, 122, 20, 0.22);
}

.hero__deco--br {
  bottom: 5rem; right: 3.5rem;
  width: 80px; height: 80px;
  border-bottom: 1px solid rgba(12, 21, 40, 0.1);
  border-right: 1px solid rgba(12, 21, 40, 0.1);
}

/* Layout */
.hero__inner {
  position: relative;
  z-index: 2;
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
  padding: 9rem 2.5rem 5rem;
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 5rem;
  align-items: center;
}

/* Badge */
.hero__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  font-family: var(--f-mono);
  font-size: 0.68rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #B07A14;
  border: 1px solid rgba(176, 122, 20, 0.25);
  background: rgba(176, 122, 20, 0.05);
  padding: 0.45rem 1rem;
  border-radius: 2px;
  margin-bottom: 2rem;
}

.hero__badge-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #B07A14;
  animation: blink 2.5s ease-in-out infinite;
}

/* Titre */
.hero__title {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin-bottom: 1rem;
}

.hero__title-pre {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(0.9rem, 1.5vw, 1.05rem);
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: #A09890;
  line-height: 1;
  margin-bottom: 0.3rem;
}

.hero__title-name {
  font-family: var(--f-display);
  font-weight: 700;
  font-size: clamp(3.4rem, 6vw, 5.5rem);
  letter-spacing: 0.025em;
  line-height: 0.9;
  color: #0C1528;
  text-shadow: 0 2px 40px rgba(176, 122, 20, 0.06);
}

/* Sous-titre */
.hero__subtitle {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: clamp(1.2rem, 2.2vw, 1.6rem);
  color: #B07A14;
  margin-bottom: 2rem;
  letter-spacing: 0.01em;
}

/* Verset */
.hero__verse {
  max-width: 440px;
  padding: 1.1rem 1.3rem;
  border-left: 2px solid rgba(176, 122, 20, 0.4);
  background: rgba(176, 122, 20, 0.04);
  border-radius: 0 6px 6px 0;
  margin-bottom: 2.25rem;
}

.hero__verse p {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 1.05rem;
  line-height: 1.7;
  color: #4A4540;
  margin-bottom: 0.5rem;
}

.hero__verse cite {
  font-family: var(--f-mono);
  font-size: 0.65rem;
  letter-spacing: 0.12em;
  color: #B07A14;
  font-style: normal;
}

/* Actions */
.hero__actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2.5rem;
  flex-wrap: wrap;
}

.hero__btn {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  font-family: var(--f-mono);
  font-size: 0.7rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  text-decoration: none;
  padding: 0.9rem 1.8rem;
  border-radius: 3px;
  transition: opacity var(--t), transform var(--t), box-shadow var(--t);
  font-weight: 600;
}

.hero__btn--primary {
  background: #0C1528;
  color: #FDFCF9;
  box-shadow: 0 4px 20px rgba(12, 21, 40, 0.2);
}

.hero__btn--primary:hover {
  opacity: 0.88;
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(12, 21, 40, 0.25);
}

.hero__btn--ghost {
  border: 1px solid #D4CFC6;
  color: #4B4540;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.hero__btn--ghost:hover {
  border-color: #B07A14;
  color: #B07A14;
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(176, 122, 20, 0.1);
}

/* Stats */
.hero__stats {
  display: inline-flex;
  align-items: center;
  gap: 2rem;
  padding: 1.1rem 1.5rem;
  background: #fff;
  border: 1px solid #EAE7E0;
  border-radius: 6px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.04);
}

.hero__stat { display: flex; flex-direction: column; gap: 0.12rem; }

.hero__stat-num {
  font-family: var(--f-display);
  font-size: 1.75rem;
  font-weight: 700;
  color: #B07A14;
  line-height: 1;
  letter-spacing: 0.02em;
}

.hero__stat-label {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #5C5752;
}

.hero__stat-sep {
  width: 1px; height: 36px;
  background: #EAE7E0;
}

/* Portrait */
.hero__portrait-wrap { position: relative; }

.hero__portrait-frame {
  position: relative;
  padding: 0.85rem;
}

.hero__portrait-corner {
  position: absolute;
  width: 46px; height: 46px;
  pointer-events: none;
}

.hero__portrait-corner--tl {
  top: 0; left: 0;
  border-top: 2px solid #B07A14;
  border-left: 2px solid #B07A14;
}

.hero__portrait-corner--br {
  bottom: 0; right: 0;
  border-bottom: 1px solid rgba(12, 21, 40, 0.15);
  border-right: 1px solid rgba(12, 21, 40, 0.15);
}

.hero__portrait {
  position: relative;
  overflow: hidden;
  border-radius: 4px;
  border: 1px solid #E5E2DB;
  aspect-ratio: 4/5;
  width: 100%;
  background: #F6F4EF;
  box-shadow: 0 16px 48px rgba(0,0,0,0.09);
}

.hero__portrait-img {
  position: absolute;
  inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}

.hero__portrait-placeholder {
  position: absolute;
  inset: 0;
  width: 100%; height: 100%;
}

.hero__portrait-placeholder svg { width: 100%; height: 100%; display: block; }

.hero__portrait-badge {
  position: absolute;
  bottom: -0.85rem;
  left: 50%;
  transform: translateX(-50%);
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-family: var(--f-display);
  font-size: 0.62rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #1A1714;
  background: #fff;
  padding: 0.55rem 1.25rem;
  border: 1px solid #E5E2DB;
  border-radius: 3px;
  white-space: nowrap;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.hero__portrait-badge-cross { color: #B07A14; font-size: 0.7rem; }

.hero__float-card {
  margin-top: 2rem;
  padding: 1.1rem 1.3rem;
  background: #fff;
  border: 1px solid #EAE7E0;
  border-left: 2px solid rgba(176, 122, 20, 0.4);
  border-radius: 0 6px 6px 0;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.hero__float-cross { font-size: 0.65rem; color: rgba(176, 122, 20, 0.5); }

.hero__float-verse {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 1rem;
  line-height: 1.6;
  color: #4A4540;
}

.hero__float-ref {
  font-family: var(--f-mono);
  font-size: 0.62rem;
  letter-spacing: 0.1em;
  color: #B07A14;
}

/* Scroll */
.hero__scroll {
  position: absolute;
  bottom: 2.25rem; left: 50%;
  transform: translateX(-50%);
  display: flex; flex-direction: column;
  align-items: center; gap: 0.3rem;
  text-decoration: none;
  color: #C4BAA0;
  transition: color var(--t);
  z-index: 2;
}

.hero__scroll:hover { color: #5C5752; }

.hero__scroll-label {
  font-family: var(--f-mono);
  font-size: 0.55rem; letter-spacing: 0.16em; text-transform: uppercase;
}

.hero__scroll-icon { animation: bounce 2.2s ease-in-out infinite; }

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(6px); }
}

@media (max-width: 960px) {
  .hero__inner {
    grid-template-columns: 1fr;
    gap: 3.5rem;
    padding: 8rem 1.75rem 4.5rem;
    text-align: center;
  }
  .hero__badge { margin-left: auto; margin-right: auto; }
  .hero__title { align-items: center; }
  .hero__verse { text-align: left; max-width: 100%; }
  .hero__actions { justify-content: center; }
  .hero__stats { justify-content: center; }
  .hero__portrait-wrap { max-width: 360px; margin: 0 auto; }
  .hero__deco { display: none; }
}
</style>
