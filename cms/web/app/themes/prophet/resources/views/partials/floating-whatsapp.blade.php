@php($waUrl = \ProphetCore\Services\Whatsapp::url($phone1, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.'))

<div class="sec-floating-whatsapp floating-wa" x-data="{ ouvert: false }">
  {{-- Popup — FloatingWhatsApp.vue:16 : <transition name="popup">. .popup-enter-*/
       -leave-* copiées telles quelles dans _floating-whatsapp.css (même modèle
       que hero-left.blade.php / navbar.blade.php). --}}
  <div class="floating-wa__popup" x-show="ouvert" x-cloak
       x-transition:enter="popup-enter-active"
       x-transition:enter-start="popup-enter-from"
       x-transition:leave="popup-leave-active"
       x-transition:leave-end="popup-leave-to">
    <div class="floating-wa__popup-head">
      <div class="floating-wa__popup-avatar">JN</div>
      <div>
        <p class="floating-wa__popup-name">Prophète Jeremiah Nahoum</p>
        <p class="floating-wa__popup-status">
          <span class="floating-wa__popup-dot"></span>
          En ligne
        </p>
      </div>
      <button type="button" class="floating-wa__close" x-on:click="ouvert = false" aria-label="Fermer">
        <x-icon name="x" :size="16" />
      </button>
    </div>
    <div class="floating-wa__popup-body">
      <div class="floating-wa__bubble">
        <p>Bonjour 🙏<br>Cliquez ci-dessous pour démarrer une conversation ou prendre rendez-vous directement.</p>
        <span class="floating-wa__bubble-time">Maintenant</span>
      </div>
    </div>
    <a href="{{ esc_url($waUrl) }}" target="_blank" rel="noopener noreferrer" class="floating-wa__popup-btn">
      <x-icon name="message-circle" :size="16" />
      <span>Démarrer la conversation</span>
    </a>
  </div>

  {{-- Bouton principal — FloatingWhatsApp.vue:46 : <transition name="icon-swap"
       mode="out-in">. Alpine n'a pas d'équivalent à mode="out-in" (qui attend la
       fin de la sortie avant de démarrer l'entrée) pour deux x-show indépendants ;
       chaque icône reçoit donc sa propre transition icon-swap-*, ce qui produit un
       fondu croisé au lieu d'un enchaînement strict — fidèle dans l'esprit, pas
       trame pour trame. --}}
  <button type="button" class="floating-wa__btn" x-on:click="ouvert = !ouvert" x-bind:aria-label="ouvert ? 'Fermer' : 'WhatsApp'">
    <span x-show="ouvert" x-cloak
          x-transition:enter="icon-swap-enter-active"
          x-transition:enter-start="icon-swap-enter-from"
          x-transition:leave="icon-swap-leave-active"
          x-transition:leave-end="icon-swap-leave-to"><x-icon name="x" :size="22" /></span>
    <span x-show="!ouvert"
          x-transition:enter="icon-swap-enter-active"
          x-transition:enter-start="icon-swap-enter-from"
          x-transition:leave="icon-swap-leave-active"
          x-transition:leave-end="icon-swap-leave-to"><x-icon name="message-circle" :size="22" /></span>
    <span class="floating-wa__pulse"></span>
  </button>
</div>
