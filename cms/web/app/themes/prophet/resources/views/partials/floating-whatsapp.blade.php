@php($waUrl = \ProphetCore\Services\Whatsapp::url($phone1, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.'))

<div class="sec-floating-whatsapp floating-wa" x-data="{ ouvert: false }">
  {{-- Popup --}}
  <div class="floating-wa__popup" x-show="ouvert" x-cloak>
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

  {{-- Bouton principal --}}
  <button type="button" class="floating-wa__btn" x-on:click="ouvert = !ouvert" x-bind:aria-label="ouvert ? 'Fermer' : 'WhatsApp'">
    <span x-show="ouvert" x-cloak><x-icon name="x" :size="22" /></span>
    <span x-show="!ouvert"><x-icon name="message-circle" :size="22" /></span>
    <span class="floating-wa__pulse"></span>
  </button>
</div>
