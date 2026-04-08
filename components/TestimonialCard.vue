<script setup lang="ts">
defineProps<{
  nom: string
  pays: string
  initiales: string
  consultation: string
  texte: string
  couleurAvatar?: string
}>()
</script>

<template>
  <article class="tcard">
    <blockquote class="tcard__text">"{{ texte }}"</blockquote>
    <footer class="tcard__footer">
      <div
        class="tcard__avatar"
        :style="{ background: couleurAvatar?.replace('bg-', '') || '#0EAA72' }"
      >{{ initiales }}</div>
      <div class="tcard__meta">
        <p class="tcard__name">{{ nom }}</p>
        <p class="tcard__pays">{{ pays }}</p>
      </div>
      <span class="tcard__badge">{{ consultation }}</span>
    </footer>
  </article>
</template>

<style scoped>
.tcard {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 1.5rem;
  background: white;
  border-radius: var(--r3);
  border: 1px solid #EAE6DD;
  box-shadow: var(--sh-sm);
  transition: box-shadow var(--t), border-color var(--t);
}
.tcard:hover {
  box-shadow: var(--sh-md);
  border-color: #D8D4CB;
}

.tcard__text {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.95rem;
  line-height: 1.65;
  color: var(--text-soft);
  flex: 1;
  margin-bottom: 1.25rem;
}

.tcard__footer {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.tcard__avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--f-display);
  font-size: 0.65rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}
.tcard__meta { flex: 1; min-width: 0; }
.tcard__name {
  font-family: var(--f-display);
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.tcard__pays {
  font-family: var(--f-mono);
  font-size: 0.62rem;
  color: var(--text-faint);
  margin-top: 0.1rem;
}
.tcard__badge {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  padding: 0.2rem 0.6rem;
  border-radius: 2px;
  border: 1px solid rgba(14,170,114,0.2);
  color: var(--emerald);
  white-space: nowrap;
  flex-shrink: 0;
}
</style>
