<script setup lang="ts">
import {
  Heart, Sparkles, Mic2, Landmark, Briefcase, Flame,
  Star, HeartPulse, Plane, Building2, ShoppingBag, CalendarDays,
} from 'lucide-vue-next'

const props = defineProps<{ modelValue: string }>()
const emit = defineEmits<{ (e: 'update:modelValue', v: string): void }>()

const consultations = [
  { label: 'Mariage',            icon: Heart },
  { label: 'Anges',              icon: Sparkles },
  { label: 'Appel',              icon: Mic2 },
  { label: 'Carrière politique', icon: Landmark },
  { label: 'Affaires',           icon: Briefcase },
  { label: 'Autels',             icon: Flame },
  { label: 'Étoiles',            icon: Star },
  { label: 'Santé',              icon: HeartPulse },
  { label: 'Voyage',             icon: Plane },
  { label: 'Entreprise',         icon: Building2 },
  { label: 'Commerce',           icon: ShoppingBag },
  { label: 'Année 2026',         icon: CalendarDays },
]
</script>

<template>
  <div class="cp">
    <div class="cp__grid" role="listbox" aria-label="Type de consultation">
      <button
        v-for="c in consultations"
        :key="c.label"
        type="button"
        role="option"
        :aria-selected="modelValue === c.label"
        @click="emit('update:modelValue', c.label)"
        :class="['cp__item', modelValue === c.label && 'cp__item--active']"
      >
        <component :is="c.icon" :size="14" class="cp__item-icon" />
        <span class="cp__item-label">{{ c.label }}</span>
      </button>
    </div>
    <div class="cp__status">
      <p v-if="modelValue" class="cp__status-selected">{{ modelValue }}</p>
      <p v-else class="cp__status-empty">Choisissez un type de consultation</p>
    </div>
  </div>
</template>

<style scoped>
.cp { display: flex; flex-direction: column; gap: 0.45rem; }

.cp__grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.35rem;
}
@media (min-width: 480px) {
  .cp__grid { grid-template-columns: repeat(6, 1fr); }
}

.cp__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 0.45rem 0.2rem;
  border-radius: var(--r2);
  border: 1px solid #DDD9D0;
  background: white;
  cursor: pointer;
  transition: border-color var(--tf), background var(--tf);
}
.cp__item:hover {
  border-color: var(--emerald);
  background: rgba(14,170,114,0.04);
}
.cp__item--active {
  border-color: var(--emerald) !important;
  background: var(--emerald) !important;
}

.cp__item-icon {
  color: rgba(26,23,20,0.52);
  transition: color var(--tf);
}
.cp__item:hover .cp__item-icon { color: var(--emerald); }
.cp__item--active .cp__item-icon { color: rgba(255,255,255,0.9) !important; }

.cp__item-label {
  font-family: var(--f-mono);
  font-size: 0.63rem;
  text-align: center;
  line-height: 1.2;
  color: rgba(26,23,20,0.65);
  transition: color var(--tf);
}
.cp__item:hover .cp__item-label { color: var(--emerald); }
.cp__item--active .cp__item-label { color: rgba(255,255,255,0.9) !important; }

.cp__status { min-height: 1rem; }
.cp__status-selected {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.82rem;
  color: var(--emerald);
}
.cp__status-empty {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.82rem;
  color: var(--text-faint);
}
</style>
