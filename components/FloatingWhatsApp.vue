<script setup lang="ts">
import { MessageCircle, X } from 'lucide-vue-next'
import { ref } from 'vue'
import { buildWhatsAppUrl } from '@/lib/utils'

const config = useRuntimeConfig()
const phone1 = config.public.prophetPhone1 as string
const waUrl = buildWhatsAppUrl(phone1, 'Bonjour Prophète Jeremiah Nahoum, je souhaite prendre rendez-vous.')

const open = ref(false)
</script>

<template>
  <div class="floating-wa">
    <!-- Popup -->
    <transition name="popup">
      <div v-if="open" class="floating-wa__popup">
        <div class="floating-wa__popup-head">
          <div class="floating-wa__popup-avatar">JN</div>
          <div>
            <p class="floating-wa__popup-name">Prophète Jeremiah Nahoum</p>
            <p class="floating-wa__popup-status">
              <span class="floating-wa__popup-dot" />
              En ligne
            </p>
          </div>
          <button class="floating-wa__close" @click="open = false" aria-label="Fermer">
            <X :size="16" />
          </button>
        </div>
        <div class="floating-wa__popup-body">
          <div class="floating-wa__bubble">
            <p>Bonjour 🙏<br>Cliquez ci-dessous pour démarrer une conversation ou prendre rendez-vous directement.</p>
            <span class="floating-wa__bubble-time">Maintenant</span>
          </div>
        </div>
        <a :href="waUrl" target="_blank" rel="noopener noreferrer" class="floating-wa__popup-btn">
          <MessageCircle :size="16" />
          <span>Démarrer la conversation</span>
        </a>
      </div>
    </transition>

    <!-- Bouton principal -->
    <button class="floating-wa__btn" @click="open = !open" :aria-label="open ? 'Fermer' : 'WhatsApp'">
      <transition name="icon-swap" mode="out-in">
        <X v-if="open" :size="22" key="close" />
        <MessageCircle v-else :size="22" key="open" />
      </transition>
      <span class="floating-wa__pulse" />
    </button>
  </div>
</template>

<style scoped>
.floating-wa {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  z-index: 500;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.75rem;
}

/* Bouton */
.floating-wa__btn {
  position: relative;
  width: 58px;
  height: 58px;
  border-radius: 50%;
  border: none;
  background: #25D366;
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(37, 211, 102, 0.35);
  transition: transform var(--t), box-shadow var(--t);
}

.floating-wa__btn:hover {
  transform: scale(1.08);
  box-shadow: 0 6px 28px rgba(37, 211, 102, 0.45);
}

/* Pulse */
.floating-wa__pulse {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2px solid rgba(37, 211, 102, 0.4);
  animation: wa-pulse 2.5s ease-out infinite;
  pointer-events: none;
}

@keyframes wa-pulse {
  0% { opacity: 0.7; transform: scale(1); }
  100% { opacity: 0; transform: scale(1.5); }
}

/* Popup */
.floating-wa__popup {
  width: 300px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #EAE7E0;
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
  overflow: hidden;
}

.floating-wa__popup-head {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.1rem;
  background: #25D366;
  color: #fff;
}

.floating-wa__popup-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--f-display);
  font-size: 0.65rem;
  font-weight: 700;
  flex-shrink: 0;
}

.floating-wa__popup-name {
  font-family: var(--f-display);
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  line-height: 1.2;
}

.floating-wa__popup-status {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-family: var(--f-mono);
  font-size: 0.55rem;
  opacity: 0.85;
}

.floating-wa__popup-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.9);
  animation: blink 2s ease-in-out infinite;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

.floating-wa__close {
  margin-left: auto;
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  padding: 0.2rem;
  transition: color var(--t);
}

.floating-wa__close:hover { color: #fff; }

.floating-wa__popup-body {
  padding: 1rem 1.1rem;
  background: #F0F0F0;
}

.floating-wa__bubble {
  background: #fff;
  border-radius: 0 8px 8px 8px;
  padding: 0.75rem 0.9rem;
  box-shadow: 0 1px 4px rgba(0,0,0,0.07);
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.floating-wa__bubble p {
  font-family: var(--f-serif);
  font-size: 0.85rem;
  line-height: 1.55;
  color: #1A1714;
}

.floating-wa__bubble-time {
  font-family: var(--f-mono);
  font-size: 0.52rem;
  color: #5C5752;
  align-self: flex-end;
}

.floating-wa__popup-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.85rem;
  background: #25D366;
  color: #fff;
  font-family: var(--f-mono);
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  text-decoration: none;
  transition: background var(--t);
}

.floating-wa__popup-btn:hover { background: #1aad4d; }

/* Transitions */
.popup-enter-active,
.popup-leave-active {
  transition: opacity 0.25s var(--ease), transform 0.25s var(--ease);
}

.popup-enter-from,
.popup-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.97);
}

.icon-swap-enter-active,
.icon-swap-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}

.icon-swap-enter-from { opacity: 0; transform: scale(0.7); }
.icon-swap-leave-to { opacity: 0; transform: scale(0.7); }

@media (max-width: 480px) {
  .floating-wa { bottom: 1.25rem; right: 1.25rem; }
  .floating-wa__popup { width: 270px; }
}
</style>
