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

    {{-- cta_titre_ligne_1/emphase et cta_texte_ligne_1/2 sont des paires de champs
         (Content composer) qui reproduisent le titre <br><em> et le sous-texte <br>
         structurels de CtaSection.vue:29-37 sans déséchapper de champ, comme
         about.blade.php et hero.blade.php le font déjà pour leurs propres titres. --}}
    <h2 class="cta__title">{{ $contenu['cta_titre_ligne_1'] }}<br><em>{{ $contenu['cta_titre_emphase'] }}</em></h2>

    <p class="cta__sub">{{ $contenu['cta_texte_ligne_1'] }}<br>{{ $contenu['cta_texte_ligne_2'] }}</p>

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
