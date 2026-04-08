<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps<{
  modelValue?: Date
  disabledDates?: (date: Date) => boolean
}>()
const emit = defineEmits<{ (e: 'update:modelValue', d: Date): void }>()

const MOIS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']
const JOURS_FR = ['Lu','Ma','Me','Je','Ve','Sa','Di']
const today = new Date()

const currentMonth = ref(props.modelValue ? new Date(props.modelValue) : new Date())
currentMonth.value.setDate(1)

const monthLabel = computed(() =>
  `${MOIS_FR[currentMonth.value.getMonth()]} ${currentMonth.value.getFullYear()}`
)

const days = computed(() => {
  const y = currentMonth.value.getFullYear()
  const m = currentMonth.value.getMonth()
  const first = new Date(y, m, 1)
  const last  = new Date(y, m + 1, 0)
  let startDow = first.getDay() - 1
  if (startDow < 0) startDow = 6
  const result: Array<{ date: Date | null; key: string }> = []
  for (let i = 0; i < startDow; i++) result.push({ date: null, key: `e${i}` })
  for (let d = 1; d <= last.getDate(); d++) result.push({ date: new Date(y, m, d), key: `${y}-${m}-${d}` })
  return result
})

function prevMonth() { const d = new Date(currentMonth.value); d.setMonth(d.getMonth()-1); currentMonth.value = d }
function nextMonth() { const d = new Date(currentMonth.value); d.setMonth(d.getMonth()+1); currentMonth.value = d }

function isDisabled(date: Date) {
  if (date.getDay() === 0) return true
  const d = new Date(date); d.setHours(0,0,0,0)
  const t = new Date(today); t.setHours(0,0,0,0)
  if (d <= t) return true
  return props.disabledDates?.(date) ?? false
}

function isSelected(date: Date) {
  if (!props.modelValue) return false
  return date.getDate() === props.modelValue.getDate() &&
         date.getMonth() === props.modelValue.getMonth() &&
         date.getFullYear() === props.modelValue.getFullYear()
}
function isToday(date: Date) {
  return date.getDate() === today.getDate() &&
         date.getMonth() === today.getMonth() &&
         date.getFullYear() === today.getFullYear()
}
function selectDate(date: Date) { if (!isDisabled(date)) emit('update:modelValue', date) }
</script>

<template>
  <div class="p-4 select-none" style="background:var(--color-surface); min-width:260px;">
    <!-- Navigation -->
    <div class="flex items-center justify-between mb-4">
      <button type="button" @click="prevMonth" class="cal-nav-btn">
        <ChevronLeft class="h-4 w-4" />
      </button>
      <span class="font-display text-sm font-semibold" style="color:#0F172A; letter-spacing:0.04em;">
        {{ monthLabel }}
      </span>
      <button type="button" @click="nextMonth" class="cal-nav-btn">
        <ChevronRight class="h-4 w-4" />
      </button>
    </div>

    <!-- En-têtes -->
    <div class="grid grid-cols-7 mb-2">
      <div v-for="j in JOURS_FR" :key="j"
           class="text-center py-1 font-mono"
           style="font-size:0.65rem; letter-spacing:0.05em;"
           :style="j === 'Di' ? 'color:#c0392b; opacity:0.6' : 'color:rgba(28,25,23,0.35)'">
        {{ j }}
      </div>
    </div>

    <!-- Jours -->
    <div class="grid grid-cols-7 gap-y-1">
      <template v-for="cell in days" :key="cell.key">
        <div v-if="!cell.date" />
        <button
          v-else type="button"
          :disabled="isDisabled(cell.date)"
          @click="selectDate(cell.date)"
          :class="[
            'cal-day',
            isSelected(cell.date) ? 'cal-day--selected' : '',
            isToday(cell.date) && !isSelected(cell.date) ? 'cal-day--today' : '',
            isDisabled(cell.date) ? 'cal-day--disabled' : '',
            cell.date.getDay() === 0 ? 'cal-day--sunday' : '',
          ]"
        >
          {{ cell.date.getDate() }}
        </button>
      </template>
    </div>

    <p class="font-serif italic text-center mt-3" style="font-size:0.7rem; color:rgba(192,57,43,0.55);">
      Dimanches non disponibles
    </p>
  </div>
</template>

<style scoped>
.cal-nav-btn {
  width: 28px; height: 28px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 6px;
  border: 1px solid rgba(186,117,23,0.3);
  background: var(--color-surface);
  color: rgba(28,25,23,0.5);
  cursor: pointer;
  transition: border-color 150ms, color 150ms;
}
.cal-nav-btn:hover { border-color: #BA7517; color: #BA7517; }

.cal-day {
  width: 34px; height: 34px;
  margin: auto;
  border-radius: 50%;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.75rem;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  border: none;
  background: transparent;
  color: rgba(28,25,23,0.75);
  transition: background 150ms, color 150ms;
}
.cal-day:not(.cal-day--disabled):not(.cal-day--selected):hover {
  background: rgba(29,158,117,0.1);
  color: #1D9E75;
}
.cal-day--selected {
  background: #1D9E75 !important;
  color: white !important;
  box-shadow: 0 2px 8px rgba(29,158,117,0.3);
}
.cal-day--today {
  border: 1.5px solid #BA7517;
  color: #BA7517;
  font-weight: 600;
}
.cal-day--disabled {
  opacity: 0.25;
  cursor: not-allowed;
}
.cal-day--sunday {
  color: rgba(192,57,43,0.5);
}
</style>
