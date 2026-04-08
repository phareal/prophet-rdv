<script setup lang="ts">
import { useToast } from './use-toast'
import { X, CheckCircle2, AlertCircle } from 'lucide-vue-next'

const { toasts, dismiss } = useToast()
</script>

<template>
  <div class="toaster" aria-live="polite">
    <TransitionGroup name="toast" tag="div" class="toaster__list">
      <div
        v-for="t in toasts"
        :key="t.id"
        :class="['toast', t.variant === 'destructive' ? 'toast--error' : 'toast--success']"
      >
        <component
          :is="t.variant === 'destructive' ? AlertCircle : CheckCircle2"
          :size="18"
          class="toast__icon"
        />
        <div class="toast__content">
          <p v-if="t.title" class="toast__title">{{ t.title }}</p>
          <p v-if="t.description" class="toast__desc">{{ t.description }}</p>
        </div>
        <button @click="dismiss(t.id)" class="toast__close" aria-label="Fermer">
          <X :size="14" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toaster {
  position: fixed;
  bottom: 1.25rem;
  right: 1.25rem;
  z-index: 100;
  width: 100%;
  max-width: 360px;
  pointer-events: none;
}
.toaster__list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.toast {
  pointer-events: auto;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem 1.1rem;
  border-radius: 12px;
  border: 1px solid;
  box-shadow: 0 8px 32px rgba(0,0,0,0.14), 0 2px 8px rgba(0,0,0,0.07);
  animation: toast-in 0.3s var(--ease) both;
}
.toast--success {
  background: white;
  border-color: rgba(14,170,114,0.2);
  color: #1B1916;
}
.toast--error {
  background: #fef2f2;
  border-color: rgba(192,57,43,0.2);
  color: #7f1d1d;
}

.toast__icon {
  flex-shrink: 0;
  margin-top: 1px;
}
.toast--success .toast__icon { color: #0EAA72; }
.toast--error .toast__icon   { color: #c0392b; }

.toast__content { flex: 1; min-width: 0; }
.toast__title {
  font-family: var(--f-display);
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.03em;
}
.toast__desc {
  font-family: var(--f-serif);
  font-size: 0.88rem;
  margin-top: 0.15rem;
  opacity: 0.7;
}

.toast__close {
  flex-shrink: 0;
  opacity: 0.45;
  transition: opacity 150ms;
  color: inherit;
}
.toast__close:hover { opacity: 1; }

.toast-enter-active,
.toast-leave-active { transition: all 0.3s var(--ease); }
.toast-enter-from   { opacity: 0; transform: translateX(110%); }
.toast-leave-to     { opacity: 0; transform: translateX(110%); }
</style>
