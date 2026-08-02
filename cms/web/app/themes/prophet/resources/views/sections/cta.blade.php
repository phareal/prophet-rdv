@php($waUrl = \ProphetCore\Services\Whatsapp::url($phone1, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.'))

<section class="sec-cta cta">
  <div class="cta__bg">
    <div class="cta__bg-glow cta__bg-glow--gold"></div>
    <div class="cta__bg-lines"></div>
  </div>

  <div class="cta__inner">
    <div class="cta__ornament">
      <span class="cta__ornament-line"></span>
      <span class="cta__ornament-cross">✦</span>
      <span class="cta__ornament-line"></span>
    </div>

    <blockquote class="cta__verse">
      <p>"Appelez-moi, et je vous répondrai ; je vous annoncerai de grandes choses, des choses cachées, que vous ne connaissez pas."</p>
      <cite>— Jérémie 33:3</cite>
    </blockquote>

    {{-- cta_titre/cta_texte/cta_bouton sont des champs uniques (Content composer) qui
         remplacent le titre <br><em> et le sous-texte <br> statiques de la source, comme
         about.blade.php et hero.blade.php le font déjà pour about_titre/hero_titre. --}}
    <h2 class="cta__title">{{ $contenu['cta_titre'] }}</h2>

    <p class="cta__sub">{{ $contenu['cta_texte'] }}</p>

    <div class="cta__actions">
      <a href="{{ home_url('/rdv') }}" class="cta__btn cta__btn--primary">
        <x-icon name="calendar" :size="17" />
        <span>{{ $contenu['cta_bouton'] }}</span>
      </a>
      <a href="{{ esc_url($waUrl) }}" target="_blank" rel="noopener noreferrer" class="cta__btn cta__btn--wa">
        <x-icon name="message-circle" :size="17" />
        <span>WhatsApp direct</span>
      </a>
    </div>

    <p class="cta__note">Disponible 7j/7 · Consultation internationale · Réponse en 24h</p>
  </div>
</section>
