<section class="sec-stats stats">
  <div class="stats__inner">
    @foreach ($stats as $stat)
      <div class="stats__item">
        <span class="stats__num">{{ $stat['valeur'] }}</span>
        <span class="stats__sep-line"></span>
        <span class="stats__label">{{ $stat['libelle'] }}</span>
      </div>
    @endforeach
  </div>
</section>
