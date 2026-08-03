<section class="sec-videos video-section" id="video">
  <div class="video-section__inner">

    <div class="video-section__header">
      <div class="section-ornament"><span>✦</span></div>
      <span class="video-section__label">Ministère en vidéo</span>
      <h2 class="video-section__title">
        Regardez<br>
        <em>le prophète en action</em>
      </h2>
      <p class="video-section__desc">
        Découvrez le ministère prophétique en vidéo — prédications, prophéties en direct et témoignages.
      </p>
    </div>

    <div class="video-section__grid">
      @foreach ($videos as $video)
        <div class="vcard">
          <a
            href="{{ esc_url('https://www.youtube.com/watch?v=' . $video['id']) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="vcard__thumb"
          >
            <div class="vcard__thumb-placeholder">
              <x-icon name="youtube" :size="36" class="vcard__yt-icon" />
            </div>
            <img
              src="{{ esc_url($video['thumbnail']) }}"
              alt="{{ $video['title'] }}"
              class="vcard__img"
              loading="lazy"
              width="480"
              height="270"
              onerror="this.style.display='none'"
            />
            <div class="vcard__play">
              <div class="vcard__play-btn">
                <x-icon name="play" :size="20" />
              </div>
            </div>
            <span class="vcard__duration">{{ $video['duration'] }}</span>
          </a>
          <div class="vcard__body">
            <span class="vcard__type">{{ $video['type'] }}</span>
            <h3 class="vcard__title">{{ $video['title'] }}</h3>
            <p class="vcard__desc">{{ $video['desc'] }}</p>
          </div>
        </div>
      @endforeach
    </div>

    <div class="video-section__channel">
      <a href="{{ esc_url($channelUrl) }}" target="_blank" rel="noopener noreferrer" class="video-section__channel-btn">
        <x-icon name="youtube" :size="18" />
        <span>Voir toutes les vidéos sur YouTube</span>
      </a>
    </div>

  </div>
</section>
