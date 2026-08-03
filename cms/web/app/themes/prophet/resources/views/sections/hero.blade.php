@php
    $heroImageId = carbon_get_theme_option('hero_image');
    $heroImageUrl = $heroImageId ? wp_get_attachment_image_url($heroImageId, 'full') : false;
    $heroImageUrl = $heroImageUrl ?: (string) \Roots\asset('resources/images/prophet-main.jpg');
@endphp

<section id="accueil" class="sec-hero hero">
  <div class="hero__bg">
    <div class="hero__bg-radial hero__bg-radial--gold"></div>
    <div class="hero__bg-radial hero__bg-radial--ivory"></div>
    <div class="hero__bg-pattern"></div>
  </div>

  <div class="hero__deco hero__deco--tl"></div>
  <div class="hero__deco hero__deco--br"></div>

  <div class="hero__inner">
    {{-- Colonne gauche : texte --}}
    <div class="hero__text">
      <div class="hero__badge">
        <span class="hero__badge-dot"></span>
        <span>Consultation Prophétique Internationale</span>
      </div>

      <h1 class="hero__title">
        <span class="hero__title-pre">{{ $contenu['hero_surtitre'] }}</span>
        {{-- Deux champs (voir Content.php) reproduisent le <br> structurel de
             HeroSection.vue:26 sans déséchapper de champ. --}}
        <span class="hero__title-name">{{ $contenu['hero_titre_ligne_1'] }}<br>{{ $contenu['hero_titre_ligne_2'] }}</span>
      </h1>

      <p class="hero__subtitle">&quot;{{ $contenu['hero_sous_titre'] }}&quot;</p>

      <blockquote class="hero__verse">
        <p>&quot;Il révèle les choses profondes et cachées ; il connaît ce qui est dans les ténèbres, et la lumière demeure avec lui.&quot;</p>
        <cite>— Daniel 2:22</cite>
      </blockquote>

      <div class="hero__actions">
        <a href="{{ home_url('/rdv') }}" class="hero__btn hero__btn--primary">
          <x-icon name="calendar" :size="17" />
          <span>{{ $contenu['hero_cta_principal'] }}</span>
        </a>
        <a href="#video" class="hero__btn hero__btn--ghost">
          <x-icon name="play" :size="15" class="hero__btn-play-icon" />
          <span>{{ $contenu['hero_cta_secondaire'] }}</span>
        </a>
      </div>

      <div class="hero__stats">
        <div class="hero__stat">
          <span class="hero__stat-num">40+</span>
          <span class="hero__stat-label">Nations</span>
        </div>
        <div class="hero__stat-sep"></div>
        <div class="hero__stat">
          <span class="hero__stat-num">15+</span>
          <span class="hero__stat-label">Ans de ministère</span>
        </div>
        <div class="hero__stat-sep"></div>
        <div class="hero__stat">
          <span class="hero__stat-num">10K+</span>
          <span class="hero__stat-label">Consultations</span>
        </div>
      </div>
    </div>

    {{-- Colonne droite : portrait --}}
    <div class="hero__portrait-wrap">
      <div class="hero__portrait-frame">
        <div class="hero__portrait-corner hero__portrait-corner--tl"></div>
        <div class="hero__portrait-corner hero__portrait-corner--br"></div>
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
            src="{{ $heroImageUrl }}"
            alt="Prophète Jeremiah Nahoum"
            class="hero__portrait-img"
            width="400"
            height="500"
            fetchpriority="high"
            onerror="this.style.display='none'"
          />
        </div>
        <div class="hero__portrait-badge">
          <span class="hero__portrait-badge-cross">✦</span>
          <span>Prophète Jeremiah Nahoum</span>
        </div>
      </div>

      <div class="hero__float-card">
        <span class="hero__float-cross">✦</span>
        <p class="hero__float-verse">&quot;Avant que je te forme dans le ventre de ta mère, je te connaissais&quot;</p>
        <span class="hero__float-ref">— Jérémie 1:5</span>
      </div>
    </div>
  </div>

  <a href="#apropos" class="hero__scroll">
    <span class="hero__scroll-label">Défiler</span>
    <x-icon name="chevron-down" :size="16" class="hero__scroll-icon" />
  </a>
</section>
