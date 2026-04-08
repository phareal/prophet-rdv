<script setup lang="ts">
const stats = [
  { num: '15+', label: 'Ans de ministère' },
  { num: '40+', label: 'Nations touchées' },
  { num: '10K+', label: 'Consultations' },
  { num: '100%', label: 'Parole accomplie' },
]
</script>

<template>
  <section class="stats">
    <div class="stats__inner">
      <div v-for="(s, i) in stats" :key="i" class="stats__item">
        <span class="stats__num">{{ s.num }}</span>
        <span class="stats__label">{{ s.label }}</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.stats {
  background: #fff;
  border-top: 1px solid #EAE7E0;
  border-bottom: 1px solid #EAE7E0;
}

.stats__inner {
  max-width: 900px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
}

.stats__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  padding: 2.2rem 1rem;
  border-right: 1px solid #EAE7E0;
  transition: background var(--t);
}

.stats__item:last-child { border-right: none; }
.stats__item:hover { background: #FDFCF9; }

.stats__num {
  font-family: var(--f-display);
  font-size: clamp(1.8rem, 4vw, 2.6rem);
  font-weight: 700;
  color: #B07A14;
  line-height: 1;
  letter-spacing: 0.03em;
}

.stats__label {
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #9B9590;
}

@media (max-width: 640px) {
  .stats__inner { grid-template-columns: repeat(2, 1fr); }
  .stats__item:nth-child(2) { border-right: none; }
  .stats__item:nth-child(1),
  .stats__item:nth-child(2) { border-bottom: 1px solid #EAE7E0; }
}
</style>
