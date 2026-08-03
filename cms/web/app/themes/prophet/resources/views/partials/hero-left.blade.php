{{--
  Port de HeroLeft.vue. Le carrousel de témoignages (`hero__carousel`) est
  bien réel dans la source (ref `current`, `setInterval` de 4s, points de
  navigation) — à ne pas confondre avec TestimonialsSection.vue (tâche 18)
  qui, lui, n'en a pas. Les données proviennent du même View Composer que
  la grille de témoignages de la page d'accueil (App\View\Composers\Testimonials),
  au lieu du tableau `temoignages` codé en dur dans le script setup.

  Le carrousel Vue (`<transition name="tcard" mode="out-in">`) est reproduit
  avec `x-transition`, en pointant vers les classes `.tcard-enter-*` /
  `.tcard-leave-*` déjà copiées telles quelles dans _hero-left.css : Alpine
  ne connaît pas nativement ces noms, mais x-transition:enter / :leave
  acceptent n'importe quelle classe CSS, donc le CSS scoped d'origine reste
  inchangé.
--}}
<div class="sec-hero-left hero">
  <div class="hero__accent"></div>

  <div class="hero__body">

    {{-- Badge --}}
    <span class="hero__badge">Consultation Prophétique</span>

    {{-- Titre --}}
    <div class="hero__title-block">
      <h1 class="hero__title">
        Prophète
        <span class="hero__title-name">Jeremiah<br>Nahoum</span>
      </h1>
      <p class="hero__subtitle">"Le Conseiller des Rois"</p>
    </div>

    <div class="hero__sep"></div>

    {{-- Portrait placeholder --}}
    <div class="hero__portrait">
      <div class="hero__portrait-avatar">
        <svg viewBox="0 0 56 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <ellipse cx="28" cy="20" rx="11" ry="12" fill="rgba(200,146,28,0.15)" stroke="rgba(200,146,28,0.3)" stroke-width="1"/>
          <path d="M9 60 C9 44 18 36 28 36 C38 36 47 44 47 60" fill="rgba(14,170,114,0.1)" stroke="rgba(14,170,114,0.25)" stroke-width="1"/>
        </svg>
      </div>
      <div class="hero__portrait-info">
        <p class="hero__portrait-label">Prophète Jeremiah Nahoum</p>
        <p class="hero__portrait-hint">photo · 400 × 500 px</p>
      </div>
    </div>

    <div class="hero__sep"></div>

    {{-- Verset --}}
    <blockquote class="hero__verse">
      <p>"Il révèle les choses profondes et cachées ; il connaît ce qui est dans les ténèbres, et la lumière demeure avec lui."</p>
      <footer>— Daniel 2:22</footer>
    </blockquote>

    <div class="hero__sep"></div>

    {{-- Contacts --}}
    <div class="hero__contacts">
      <p class="hero__section-label">Contact direct</p>

      <a href="{{ esc_url(\ProphetCore\Services\Whatsapp::url($phone1, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.')) }}" target="_blank" rel="noopener noreferrer" class="hero__wa hero__wa--primary">
        <span class="hero__wa-icon hero__wa-icon--green">
          <x-icon name="message-circle" :size="15" />
        </span>
        <span class="hero__wa-info">
          <span class="hero__wa-label">WhatsApp principal</span>
          <span class="hero__wa-number">{{ $phone1 }}</span>
        </span>
        <span class="hero__wa-arrow">→</span>
      </a>

      @if ($phone2)
        <a href="{{ esc_url(\ProphetCore\Services\Whatsapp::url($phone2, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.')) }}" target="_blank" rel="noopener noreferrer" class="hero__wa hero__wa--secondary">
          <span class="hero__wa-icon hero__wa-icon--dim">
            <x-icon name="phone" :size="15" />
          </span>
          <span class="hero__wa-info">
            <span class="hero__wa-label">WhatsApp secondaire</span>
            <span class="hero__wa-number hero__wa-number--dim">{{ $phone2 }}</span>
          </span>
        </a>
      @endif
    </div>

    {{-- Carrousel témoignages --}}
    <div class="hero__carousel"
         x-data="{
           current: 0,
           temoignages: @js($temoignages),
           timer: null,
           goTo(i) { this.current = i; this.restart() },
           restart() {
             clearInterval(this.timer)
             this.timer = setInterval(() => { this.current = (this.current + 1) % this.temoignages.length }, 4000)
           },
         }"
         x-init="restart()">
      <p class="hero__section-label">Témoignages</p>

      <div class="hero__carousel-track">
        <template x-for="(temoignage, i) in temoignages" :key="i">
          <article class="hero__tcard" x-show="current === i"
                   x-transition:enter="tcard-enter-active"
                   x-transition:enter-start="tcard-enter-from"
                   x-transition:enter-end="tcard-enter-to"
                   x-transition:leave="tcard-leave-active"
                   x-transition:leave-start="tcard-leave-from"
                   x-transition:leave-end="tcard-leave-to">
            <p class="hero__tcard-text" x-text="temoignage.texte"></p>
            <div class="hero__tcard-footer">
              <div class="hero__tcard-author">
                <span class="hero__tcard-avatar" x-bind:style="{ background: temoignage.couleur }" x-text="temoignage.initiales"></span>
                <span class="hero__tcard-meta">
                  <span class="hero__tcard-name" x-text="temoignage.nom"></span>
                  <span class="hero__tcard-country" x-text="temoignage.pays"></span>
                </span>
              </div>
              <span class="hero__tcard-tag" x-text="temoignage.consultation"></span>
            </div>
          </article>
        </template>
      </div>

      <div class="hero__carousel-dots">
        <template x-for="(temoignage, i) in temoignages" :key="i">
          <button type="button" class="hero__dot"
                  x-bind:class="{ 'hero__dot--active': i === current }"
                  x-on:click="goTo(i)"
                  x-bind:aria-label="`Témoignage ${i + 1}`"></button>
        </template>
      </div>
    </div>

    {{-- Pied --}}
    <div class="hero__footer">
      <p class="hero__footer-copy">© {{ date('Y') }} Prophète Jeremiah Nahoum</p>
    </div>

  </div>
</div>
