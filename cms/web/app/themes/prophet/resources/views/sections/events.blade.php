@php($totalPages = (int) ceil(count($events) / $pageSize))

<section class="sec-events events" id="evenements"
         x-data="{ page: 1, taille: {{ $pageSize }}, total: {{ $totalPages }} }">
  <div class="events__inner">

    <div class="events__header">
      <div class="section-ornament"><span>✦</span></div>
      <span class="events__label">Agenda prophétique</span>
      <h2 class="events__title">
        Événements<br>
        <em>à venir</em>
      </h2>
      <p class="events__desc">
        Croisades, conférences et retraites spirituelles à travers le monde.
        Rejoignez-nous pour une rencontre avec Dieu.
      </p>
    </div>

    <div class="events__list">
      @foreach ($events as $i => $event)
        <article
          class="event-card{{ $event['featured'] ? ' event-card--featured' : '' }}"
          x-show="Math.floor({{ $i }} / taille) + 1 === page"
        >
          <div class="event-card__date">
            <span class="event-card__day">{{ $event['day'] }}</span>
            <span class="event-card__month">{{ $event['month'] }}</span>
            <span class="event-card__year">{{ $event['year'] }}</span>
          </div>

          <div class="event-card__content">
            <div class="event-card__top">
              <span class="event-card__type" data-type="{{ $event['type'] }}">{{ $event['type'] }}</span>
              @if ($event['featured'])
                <span class="event-card__featured-badge">Prochain événement</span>
              @endif
            </div>
            <h3 class="event-card__title">{{ $event['title'] }}</h3>
            <div class="event-card__meta">
              <span class="event-card__meta-item">
                <x-icon name="map-pin" :size="13" /> {{ $event['lieu'] }}
              </span>
              <span class="event-card__meta-item">
                <x-icon name="clock" :size="13" /> {{ $event['heure'] }}
              </span>
              <span class="event-card__meta-item">
                <x-icon name="users" :size="13" /> {{ $event['places'] }}
              </span>
            </div>
          </div>

          <a href="{{ esc_url($event['lien']) }}" class="event-card__cta">
            <span>S'inscrire</span>
            <x-icon name="arrow-right" :size="14" />
          </a>
        </article>
      @endforeach
    </div>

    @if ($totalPages > 1)
      <nav class="events__pagination">
        <button
          type="button"
          class="pagination__btn"
          x-on:click="page = Math.max(1, page - 1)"
          x-bind:disabled="page === 1"
          aria-label="Page précédente"
        >
          <x-icon name="chevron-left" :size="15" />
        </button>

        @for ($p = 1; $p <= $totalPages; $p++)
          <button
            type="button"
            class="pagination__page"
            x-bind:class="{ 'pagination__page--active': page === {{ $p }} }"
            x-on:click="page = {{ $p }}"
          >{{ $p }}</button>
        @endfor

        <button
          type="button"
          class="pagination__btn"
          x-on:click="page = Math.min(total, page + 1)"
          x-bind:disabled="page === total"
          aria-label="Page suivante"
        >
          <x-icon name="chevron-right" :size="15" />
        </button>
      </nav>
    @endif

  </div>
</section>
