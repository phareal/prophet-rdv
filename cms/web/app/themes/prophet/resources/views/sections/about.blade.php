@php
    $aboutImageId = carbon_get_theme_option('about_image');
    $aboutImageUrl = $aboutImageId ? wp_get_attachment_image_url($aboutImageId, 'full') : false;
    $aboutImageUrl = $aboutImageUrl ?: (string) \Roots\asset('resources/images/prophet-main-2.jpeg');
@endphp

<section id="apropos" class="sec-about about">
  <div class="about__inner">

    {{-- Portrait --}}
    <div class="about__visual">
      <div class="about__frame">
        <div class="about__frame-corner about__frame-corner--tl"></div>
        <div class="about__frame-corner about__frame-corner--br"></div>
        <div class="about__portrait">
          {{-- Placeholder toujours en fond absolu --}}
          <div class="about__portrait-placeholder">
            <svg viewBox="0 0 160 200" fill="none">
              <rect width="160" height="200" fill="#F6F4EF"/>
              <ellipse cx="80" cy="68" rx="34" ry="36" fill="#EDE9E0" stroke="#D4C9A8" stroke-width="1"/>
              <path d="M20 190 C20 140 48 115 80 115 C112 115 140 140 140 190" fill="#EDE9E0" stroke="#D4C9A8" stroke-width="1"/>
              <text x="80" y="163" text-anchor="middle" font-family="serif" font-size="9" fill="#B8AD95">photo prophète</text>
            </svg>
          </div>
          {{-- Photo par-dessus, cachée si erreur --}}
          <img
            src="{{ $aboutImageUrl }}"
            alt="Prophète Jeremiah Nahoum"
            class="about__portrait-img"
            loading="lazy"
            width="400"
            height="500"
            onerror="this.style.display='none'"
          />
          <div class="about__portrait-badge">
            <span class="about__badge-cross">✦</span>
            <span>Prophète Jeremiah Nahoum</span>
          </div>
        </div>
      </div>

      <div class="about__float-verse">
        <span class="about__float-cross">✦</span>
        <p class="about__float-text">&quot;Avant que je te forme dans le ventre de ta mère, je te connaissais&quot;</p>
        <span class="about__float-ref">— Jérémie 1:5</span>
      </div>
    </div>

    {{-- Texte --}}
    <div class="about__text">
      <div class="section-ornament about__ornament"><span>✦</span></div>
      <span class="about__section-label">À propos du ministère</span>

      {{-- Deux champs (voir Content.php) reproduisent le <br><em> structurel de
           AboutSection.vue:62-65 sans déséchapper de champ. --}}
      <h2 class="about__title">{{ $contenu['about_titre_ligne_1'] }}<br><em>{{ $contenu['about_titre_emphase'] }}</em></h2>

      <div class="about__body">
        {!! $contenu['about_texte'] !!}
      </div>

      <div class="about__gifts">
        <p class="about__gifts-title">Domaines de ministère</p>
        <ul class="about__gifts-list">
          @foreach ($contenu['points_cles'] as $gift)
            <li class="about__gift">
              <x-icon name="check-circle-2" :size="15" class="about__gift-icon" />
              <span>{{ $gift }}</span>
            </li>
          @endforeach
        </ul>
      </div>

      <a href="{{ home_url('/rdv') }}" class="about__cta">Réserver une consultation →</a>
    </div>

  </div>
</section>
