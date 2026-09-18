<section id="services" class="sec-services services">
  <div class="services__inner">

    <div class="services__header">
      <div class="section-ornament"><span>✦</span></div>
      <span class="services__label">Domaines prophétiques</span>
      <h2 class="services__title">
        Consultations<br>
        <em>disponibles</em>
      </h2>
      <p class="services__desc">
        Chaque consultation est une rencontre avec Dieu à travers son prophète.
        Choisissez le domaine pour lequel vous recherchez une parole divine.
      </p>
    </div>

    <div class="services__grid">
      @foreach ($services as $service)
        <article class="svc-card" style="--service-color: {{ esc_attr($service['couleur']) }}">
          <div class="svc-card__icon">
            <x-icon :name="$service['icone']" :size="22" />
          </div>
          <h3 class="svc-card__title">{{ $service['titre'] }}</h3>
          <p class="svc-card__desc">{{ $service['description'] }}</p>
          <a href="{{ home_url('/rdv') }}" class="svc-card__link">Réserver →</a>
        </article>
      @endforeach
    </div>

    <div class="services__footer">
      <a href="{{ home_url('/rdv') }}" class="services__cta">Prendre rendez-vous maintenant</a>
    </div>

  </div>
</section>
