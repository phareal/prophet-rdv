<header class="sec-navbar navbar"
        x-data="{ ouvert: false, scrolle: false }"
        x-on:scroll.window="scrolle = window.scrollY > 50"
        x-bind:class="{ 'navbar--scrolled': scrolle }">
  <div class="navbar__inner">
    <a href="#accueil" class="navbar__logo">
      <span class="navbar__logo-cross">✦</span>
      <div class="navbar__logo-text">
        <span class="navbar__logo-title">Prophète Jeremiah</span>
        <span class="navbar__logo-sub">Le Conseiller des Rois</span>
      </div>
    </a>

    <nav class="navbar__nav">
      <a href="#accueil" class="navbar__link">Accueil</a>
      <a href="#apropos" class="navbar__link">À propos</a>
      <a href="#services" class="navbar__link">Services</a>
      <a href="#video" class="navbar__link">Vidéos</a>
      <a href="#temoignages" class="navbar__link">Témoignages</a>
    </nav>

    <div class="navbar__actions">
      <a href="{{ home_url('/rdv') }}" class="navbar__cta">Prendre RDV</a>
      <button type="button" class="navbar__burger"
              x-on:click="ouvert = !ouvert"
              x-bind:aria-label="ouvert ? 'Fermer' : 'Menu'">
        <span x-show="ouvert" x-cloak><x-icon name="x" :size="22" /></span>
        <span x-show="!ouvert"><x-icon name="menu" :size="22" /></span>
      </button>
    </div>
  </div>

  {{-- TheNavbar.vue:50 : <transition name="mobile-menu">. .mobile-menu-enter-*/
       -leave-* sont copiées telles quelles dans _navbar.css ; Alpine ne connaît
       pas ces noms nativement mais x-transition:enter/:leave acceptent
       n'importe quelle classe CSS (même modèle que hero-left.blade.php). --}}
  <div class="navbar__mobile" x-show="ouvert" x-cloak
       x-transition:enter="mobile-menu-enter-active"
       x-transition:enter-start="mobile-menu-enter-from"
       x-transition:leave="mobile-menu-leave-active"
       x-transition:leave-end="mobile-menu-leave-to">
    <a href="#accueil" class="navbar__mobile-link" x-on:click="ouvert = false">Accueil</a>
    <a href="#apropos" class="navbar__mobile-link" x-on:click="ouvert = false">À propos</a>
    <a href="#services" class="navbar__mobile-link" x-on:click="ouvert = false">Services</a>
    <a href="#video" class="navbar__mobile-link" x-on:click="ouvert = false">Vidéos</a>
    <a href="#temoignages" class="navbar__mobile-link" x-on:click="ouvert = false">Témoignages</a>
    <a href="{{ home_url('/rdv') }}" class="navbar__mobile-cta" x-on:click="ouvert = false">Prendre RDV →</a>
  </div>
</header>
