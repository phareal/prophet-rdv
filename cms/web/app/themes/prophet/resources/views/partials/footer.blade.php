@php
    $waMessage = 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.';

    $reseaux = [
        ['icon' => 'youtube', 'label' => 'YouTube', 'href' => $contenu['footer_youtube']],
        ['icon' => 'facebook', 'label' => 'Facebook', 'href' => $contenu['footer_facebook']],
        ['icon' => 'instagram', 'label' => 'Instagram', 'href' => 'https://www.instagram.com/ProphetJeremiahNahoum'],
    ];
@endphp

<footer class="sec-footer footer">
  <div class="footer__inner">
    <div class="footer__cols">

      {{-- Marque --}}
      <div class="footer__col footer__col--brand">
        <div class="footer__logo">
          <span class="footer__logo-cross">✦</span>
          <div>
            <p class="footer__brand-name">Prophète Jeremiah Nahoum</p>
            <p class="footer__brand-sub">"Le Conseiller des Rois"</p>
          </div>
        </div>
        <p class="footer__brand-desc">{{ $contenu['footer_description'] }}</p>

        {{-- Réseaux sociaux --}}
        <div class="footer__socials">
          @foreach ($reseaux as $reseau)
            <a href="{{ esc_url($reseau['href']) }}" target="_blank" rel="noopener noreferrer" class="footer__social" aria-label="{{ $reseau['label'] }}">
              <x-icon :name="$reseau['icon']" :size="16" />
            </a>
          @endforeach
        </div>

        {{-- Contacts --}}
        <div class="footer__contacts">
          <a href="{{ esc_url(\ProphetCore\Services\Whatsapp::url($phone1, $waMessage)) }}" target="_blank" rel="noopener noreferrer" class="footer__contact">
            <span class="footer__ci footer__ci--wa"><x-icon name="message-circle" :size="13" /></span>
            <span>{{ $phone1 }}</span>
          </a>
          @if ($phone2)
            <a href="{{ esc_url(\ProphetCore\Services\Whatsapp::url($phone2, $waMessage)) }}" target="_blank" rel="noopener noreferrer" class="footer__contact">
              <span class="footer__ci footer__ci--ph"><x-icon name="phone" :size="13" /></span>
              <span>{{ $phone2 }}</span>
            </a>
          @endif
        </div>
      </div>

      {{-- Navigation --}}
      <div class="footer__col">
        <h4 class="footer__col-title">Navigation</h4>
        <nav class="footer__nav">
          <a href="{{ home_url('/') }}" class="footer__link">Accueil</a>
          <a href="{{ home_url('/#apropos') }}" class="footer__link">À propos</a>
          <a href="{{ home_url('/#services') }}" class="footer__link">Services</a>
          <a href="{{ home_url('/#galerie') }}" class="footer__link">Galerie</a>
          <a href="{{ home_url('/#temoignages') }}" class="footer__link">Témoignages</a>
          <a href="{{ home_url('/#evenements') }}" class="footer__link">Événements</a>
          <a href="{{ home_url('/rdv') }}" class="footer__link">Prendre RDV</a>
        </nav>
      </div>

      {{-- Services --}}
      <div class="footer__col">
        <h4 class="footer__col-title">Services</h4>
        <nav class="footer__nav">
          @foreach (['Mariage', 'Affaires', 'Carrière politique', 'Santé', 'Voyage', 'Appel prophétique'] as $service)
            <a href="{{ home_url('/rdv') }}" class="footer__link">{{ $service }}</a>
          @endforeach
        </nav>
      </div>

      {{-- RDV --}}
      <div class="footer__col">
        <h4 class="footer__col-title">Rendez-vous</h4>
        <p class="footer__rdv-desc">
          Consultations disponibles 7j/7 pour toutes les nations.
        </p>
        <a href="{{ home_url('/rdv') }}" class="footer__rdv-btn">Réserver maintenant →</a>
        <blockquote class="footer__verse">
          <p>"Appelez-moi et je vous répondrai."</p>
          <cite>— Jérémie 33:3</cite>
        </blockquote>
      </div>

    </div>

    <div class="footer__bottom">
      <p class="footer__copy">© {{ date('Y') }} Prophète Jeremiah Nahoum · {{ $contenu['footer_mentions'] }}</p>
      <div class="footer__bottom-socials">
        @foreach ($reseaux as $reseau)
          <a href="{{ esc_url($reseau['href']) }}" target="_blank" rel="noopener noreferrer" class="footer__bottom-social" aria-label="{{ $reseau['label'] }}">
            <x-icon :name="$reseau['icon']" :size="14" />
          </a>
        @endforeach
      </div>
      <p class="footer__love">Conçu pour le Royaume de Dieu</p>
    </div>
  </div>
</footer>
