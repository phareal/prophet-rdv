{{--
  TestimonialsSection.vue ne contient aucun carrousel (pas de ref, pas
  d'index dans <script setup>) : .testimonials__grid est une grille CSS
  statique qui affiche tous les témoignages à la fois, sur 3 colonnes
  (2 puis 1 en dessous de 1024px et 640px, voir _testimonials.css). Cette
  vue reproduit donc cette grille telle quelle, sans x-data ni x-show.
--}}
<section id="temoignages" class="sec-testimonials testimonials">
  <div class="testimonials__inner">

    <div class="testimonials__header">
      <div class="section-ornament"><span>✦</span></div>
      <span class="testimonials__label">Ce qu'ils disent</span>
      <h2 class="testimonials__title">Des vies <em>transformées</em></h2>
      <p class="testimonials__desc">
        Des milliers de personnes à travers le monde ont reçu une parole prophétique précise
        et vérifiable. Voici quelques témoignages authentiques.
      </p>
    </div>

    <div class="testimonials__grid">
      @foreach ($temoignages as $temoignage)
        @include('partials.testimonial-card', ['temoignage' => $temoignage])
      @endforeach
    </div>

    <div class="testimonials__cta-wrap">
      <p class="testimonials__cta-text">Votre témoignage vous attend.</p>
      <a href="{{ home_url('/rdv') }}" class="testimonials__cta">Prendre rendez-vous →</a>
    </div>

  </div>
</section>
