{{--
  Reproduit la balise <article class="tcard"> rendue inline par
  TestimonialsSection.vue (tâche 4 : TestimonialCard.vue est mort, jamais
  monté — voir le commentaire en tête de _testimonials.css).
--}}
<article class="tcard">
  <div class="tcard__top">
    <x-icon name="quote" :size="22" class="tcard__quote-icon" />
    <div class="tcard__stars">
      @for ($s = 0; $s < $temoignage['etoiles']; $s++)
        <span class="tcard__star">★</span>
      @endfor
    </div>
  </div>
  <p class="tcard__text">{{ $temoignage['texte'] }}</p>
  <div class="tcard__footer">
    <div class="tcard__author">
      <div class="tcard__avatar" style="background: {{ $temoignage['couleur'] }}">{{ $temoignage['initiales'] }}</div>
      <div class="tcard__meta">
        <span class="tcard__name">{{ $temoignage['nom'] }}</span>
        <span class="tcard__country">{{ $temoignage['pays'] }}</span>
      </div>
    </div>
    <span class="tcard__tag">{{ $temoignage['consultation'] }}</span>
  </div>
</article>
