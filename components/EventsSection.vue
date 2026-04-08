<script setup lang="ts">
import { MapPin, Clock, Users, ArrowRight, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const { data: events, status } = await useFetch('/api/calendar/events')

const FRENCH_MONTH_INDEX: Record<string, number> = {
  Jan:0, Fév:1, Mar:2, Avr:3, Mai:4, Juin:5,
  Juil:6, Août:7, Sep:8, Oct:9, Nov:10, Déc:11,
}

function eventHref(ev: { title: string; day: string; month: string; year: string; lieu: string; heure: string; places: string }) {
  if (ev.places !== 'Sur inscription') return '/rdv'
  const date = new Date(+ev.year, FRENCH_MONTH_INDEX[ev.month] ?? 0, +ev.day)
  const params = new URLSearchParams({
    event: ev.title,
    eventDate: date.toISOString(),
    eventLieu: ev.lieu,
    eventHeure: ev.heure,
  })
  return `/rdv?${params.toString()}`
}

const typeColor: Record<string, string> = {
  Croisade: '#B07A14',
  Conférence: '#0A9060',
  Retraite: '#7B5EA7',
}

const PAGE_SIZE = 4
const page = ref(1)

const totalPages = computed(() => Math.ceil((events.value?.length ?? 0) / PAGE_SIZE))
const paginatedEvents = computed(() => {
  const list = events.value ?? []
  return list.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE)
})

watch(events, () => { page.value = 1 })
</script>

<template>
  <section id="evenements" class="events">
    <div class="events__inner">

      <div class="events__header">
        <div class="section-ornament"><span>✦</span></div>
        <span class="events__label">Agenda prophétique</span>
        <h2 class="events__title">
          Événements<br>
          <em>à venir</em>
        </h2>
        <p class="events__desc">
          Croisades, conférences et retraites spirituelles à travers le monde.
          Rejoignez-nous pour une rencontre avec Dieu.
        </p>
      </div>

      <div class="events__list">
        <div v-if="status === 'pending'" class="events__loading">
          <div v-for="n in 4" :key="n" class="event-card event-card--skeleton" />
        </div>

        <article
          v-for="(ev, i) in paginatedEvents"
          :key="i"
          :class="['event-card', ev.featured ? 'event-card--featured' : '']"
        >
          <div class="event-card__date">
            <span class="event-card__day">{{ ev.day }}</span>
            <span class="event-card__month">{{ ev.month }}</span>
            <span class="event-card__year">{{ ev.year }}</span>
          </div>

          <div class="event-card__content">
            <div class="event-card__top">
              <span
                class="event-card__type"
                :style="{ color: typeColor[ev.type] || '#B07A14', borderColor: `${typeColor[ev.type] || '#B07A14'}40` }"
              >{{ ev.type }}</span>
              <span v-if="ev.featured" class="event-card__featured-badge">Prochain événement</span>
            </div>
            <h3 class="event-card__title">{{ ev.title }}</h3>
            <div class="event-card__meta">
              <span class="event-card__meta-item">
                <MapPin :size="13" /> {{ ev.lieu }}
              </span>
              <span class="event-card__meta-item">
                <Clock :size="13" /> {{ ev.heure }}
              </span>
              <span class="event-card__meta-item">
                <Users :size="13" /> {{ ev.places }}
              </span>
            </div>
          </div>

          <a :href="eventHref(ev)" class="event-card__cta">
            <span>S'inscrire</span>
            <ArrowRight :size="14" />
          </a>
        </article>
      </div>

      <nav v-if="totalPages > 1" class="events__pagination">
        <button
          class="pagination__btn"
          :disabled="page === 1"
          @click="page--"
          aria-label="Page précédente"
        >
          <ChevronLeft :size="15" />
        </button>

        <button
          v-for="p in totalPages"
          :key="p"
          class="pagination__page"
          :class="{ 'pagination__page--active': p === page }"
          @click="page = p"
        >{{ p }}</button>

        <button
          class="pagination__btn"
          :disabled="page === totalPages"
          @click="page++"
          aria-label="Page suivante"
        >
          <ChevronRight :size="15" />
        </button>
      </nav>

    </div>
  </section>
</template>

<style scoped>
.events {
  background: #FDFCF9;
  padding: 8rem 2.5rem;
  position: relative;
}

.events::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent 0%, rgba(176,122,20,0.3) 30%, rgba(176,122,20,0.3) 70%, transparent 100%);
}

.events__inner { max-width: 920px; margin: 0 auto; }

.events__header {
  text-align: center;
  max-width: 540px;
  margin: 0 auto 4rem;
}

.events__label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.68rem; letter-spacing: 0.18em; text-transform: uppercase;
  color: #B07A14; margin-bottom: 1rem;
}

.events__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(2.2rem, 4vw, 3.2rem);
  line-height: 1.1; letter-spacing: 0.04em; color: #0C1528; margin-bottom: 1.25rem;
}

.events__title em {
  font-family: var(--f-serif); font-style: italic; color: #B07A14;
}

.events__desc {
  font-family: var(--f-serif); font-size: 1.1rem; line-height: 1.75; color: #5C5752;
}

/* List */
.events__list {
  border: 1px solid #E5E2DB;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,0.05);
}

.event-card--skeleton {
  height: 96px;
  background: linear-gradient(90deg, #F0EDE6 25%, #E8E4DC 50%, #F0EDE6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
  pointer-events: none;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Card */
.event-card {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding: 1.75rem 2rem;
  background: #fff;
  border-bottom: 1px solid #EAE7E0;
  transition: background var(--t), box-shadow var(--t);
}

.event-card:last-child { border-bottom: none; }
.event-card:hover { background: #FDFCF9; }

.event-card--featured {
  background: rgba(176, 122, 20, 0.025);
  border-left: 3px solid #B07A14;
}

.event-card--featured:hover { background: rgba(176, 122, 20, 0.04); }

/* Date */
.event-card__date {
  display: flex; flex-direction: column;
  align-items: center; flex-shrink: 0;
  width: 58px;
  padding: 0.65rem 0.5rem;
  background: #F6F4EF;
  border-radius: 6px;
  border: 1px solid #EAE7E0;
  text-align: center;
}

.event-card__day {
  font-family: var(--f-display);
  font-size: 1.7rem; font-weight: 700;
  color: #0C1528; line-height: 1;
}

.event-card__month {
  font-family: var(--f-mono);
  font-size: 0.65rem; letter-spacing: 0.08em;
  text-transform: uppercase; color: #B07A14; line-height: 1.5;
}

.event-card__year {
  font-family: var(--f-mono);
  font-size: 0.55rem; color: #5C5752; line-height: 1.4;
}

/* Content */
.event-card__content { flex: 1; min-width: 0; }

.event-card__top {
  display: flex; align-items: center;
  gap: 0.65rem; margin-bottom: 0.4rem;
}

.event-card__type {
  font-family: var(--f-mono);
  font-size: 0.58rem; letter-spacing: 0.1em; text-transform: uppercase;
  padding: 0.2rem 0.6rem; border-radius: 2px;
  border: 1px solid currentColor; opacity: 0.9;
}

.event-card__featured-badge {
  font-family: var(--f-mono);
  font-size: 0.56rem; letter-spacing: 0.08em; text-transform: uppercase;
  color: #B07A14; background: rgba(176,122,20,0.08);
  padding: 0.2rem 0.6rem; border-radius: 2px;
}

.event-card__title {
  font-family: var(--f-display);
  font-size: 1rem; font-weight: 600;
  letter-spacing: 0.03em; color: #0C1528;
  margin-bottom: 0.55rem; line-height: 1.3;
}

.event-card__meta {
  display: flex; align-items: center;
  gap: 1.25rem; flex-wrap: wrap;
}

.event-card__meta-item {
  display: inline-flex; align-items: center;
  gap: 0.35rem;
  font-family: var(--f-mono); font-size: 0.62rem;
  letter-spacing: 0.06em; color: #5C5752;
}

/* CTA */
.event-card__cta {
  display: inline-flex; align-items: center;
  gap: 0.4rem; flex-shrink: 0;
  font-family: var(--f-mono); font-size: 0.65rem;
  letter-spacing: 0.1em; text-transform: uppercase;
  color: #0C1528; text-decoration: none;
  padding: 0.6rem 1.1rem;
  border: 1px solid #D4CFC6; border-radius: 3px;
  transition: border-color var(--t), color var(--t), background var(--t);
  white-space: nowrap;
}

.event-card__cta:hover {
  border-color: #B07A14; color: #B07A14;
  background: rgba(176, 122, 20, 0.04);
}

/* Pagination */
.events__pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  margin-top: 2.5rem;
}

.pagination__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px; height: 34px;
  border: 1px solid #D4CFC6;
  border-radius: 3px;
  background: transparent;
  color: #0C1528;
  cursor: pointer;
  transition: border-color var(--t), color var(--t), background var(--t);
}

.pagination__btn:hover:not(:disabled) {
  border-color: #B07A14;
  color: #B07A14;
}

.pagination__btn:disabled {
  opacity: 0.3;
  cursor: default;
}

.pagination__page {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px; height: 34px;
  border: 1px solid #D4CFC6;
  border-radius: 3px;
  background: transparent;
  font-family: var(--f-mono);
  font-size: 0.7rem;
  letter-spacing: 0.05em;
  color: #5C5752;
  cursor: pointer;
  transition: border-color var(--t), color var(--t), background var(--t);
}

.pagination__page:hover {
  border-color: #B07A14;
  color: #B07A14;
}

.pagination__page--active {
  border-color: #B07A14;
  background: #B07A14;
  color: #fff;
}

@media (max-width: 700px) {
  .event-card { flex-wrap: wrap; gap: 1.1rem; padding: 1.25rem 1.5rem; }
  .event-card__cta { margin-left: auto; }
  .events { padding: 6rem 1.75rem; }
}
</style>
