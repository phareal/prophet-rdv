<section id="galerie" class="sec-gallery gallery">
  <div class="gallery__inner">

    <div class="gallery__header">
      <div class="section-ornament"><span>✦</span></div>
      <span class="gallery__label">En images</span>
      <h2 class="gallery__title">Le ministère <em>à travers le monde</em></h2>
    </div>

    <div class="gallery__grid">
      @foreach ($photos as $photo)
        <div
          class="gallery__item{{ $photo['format'] === 'wide' ? ' gallery__item--wide' : '' }}"
          data-format="{{ $photo['format'] }}"
        >
          <div class="gallery__img-wrap">
            {{-- Une photo sans image associée (wp_get_attachment_image_url en échec) ne
                 doit pas casser la grille : pas de <img src=""> émis, seul le placeholder
                 s'affiche, rendu visible d'entrée via un style inline plutôt que par le
                 gestionnaire @error de la source, qui suppose une <img> déjà présente. --}}
            @if ($photo['url'])
              <img
                src="{{ esc_url($photo['url']) }}"
                alt="{{ $photo['legende'] }}"
                class="gallery__img"
                loading="lazy"
                width="400"
                height="240"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"
              >
            @endif
            <div class="gallery__placeholder" @if (! $photo['url']) style="display:flex" @endif>
              <svg viewBox="0 0 24 24" fill="none" width="32" height="32">
                <rect x="3" y="5" width="18" height="14" rx="2" stroke="#C4BAA0" stroke-width="1.5"/>
                <circle cx="8.5" cy="10.5" r="1.5" stroke="#C4BAA0" stroke-width="1.5"/>
                <path d="m21 15-5-5L5 19" stroke="#C4BAA0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>{{ $photo['legende'] }}</span>
            </div>
          </div>
          <div class="gallery__overlay">
            <p class="gallery__caption">{{ $photo['legende'] }}</p>
          </div>
        </div>
      @endforeach
    </div>

  </div>
</section>
