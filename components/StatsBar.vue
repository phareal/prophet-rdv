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
        <span class="stats__sep-line" />
        <span class="stats__label">{{ s.label }}</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.stats {
  background: #fff;
  border-top: 1px solid #EAE7E0;
  border-bottom: 3px solid #EAE7E0;
  border-bottom-color: transparent;
  position: relative;
}

.stats::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent 0%, rgba(176,122,20,0.3) 30%, rgba(176,122,20,0.3) 70%, transparent 100%);
}

.stats__inner {
  max-width: 960px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
}

.stats__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  padding: 2.5rem 1rem;
  border-right: 1px solid #EAE7E0;
  transition: background var(--t);
  position: relative;
}

.stats__item::before {
  content: '';
  position: absolute;
  bottom: 0; left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 2px;
  background: #B07A14;
  transition: width 0.3s var(--ease);
}

.stats__item:hover::before { width: 60%; }
.stats__item:last-child { border-right: none; }
.stats__item:hover { background: #FDFCF9; }

.stats__num {
  font-family: var(--f-display);
  font-size: clamp(2.2rem, 4vw, 3rem);
  font-weight: 700;
  color: #B07A14;
  line-height: 1;
  letter-spacing: 0.02em;
}

.stats__sep-line {
  display: block;
  width: 24px; height: 1px;
  background: rgba(176, 122, 20, 0.3);
}

.stats__label {
  font-family: var(--f-mono);
  font-size: 0.65rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #5C5752;
}

@media (max-width: 640px) {
  .stats__inner { grid-template-columns: repeat(2, 1fr); }
  .stats__item:nth-child(2) { border-right: none; }
  .stats__item:nth-child(1),
  .stats__item:nth-child(2) { border-bottom: 1px solid #EAE7E0; }
}
</style>
