<script setup lang="ts">
import { MapPin, Calendar, Clock, Users, ArrowRight } from 'lucide-vue-next'

const events = [
  {
    date: { day: '18', month: 'Avr', year: '2026' },
    title: 'Nuit de Prophétie & Délivrance',
    lieu: 'Paris, France',
    heure: '20h00 – 00h00',
    places: 'Entrée libre',
    type: 'Croisade',
    featured: true,
  },
  {
    date: { day: '26', month: 'Avr', year: '2026' },
    title: 'Conférence des Leaders & Entrepreneurs',
    lieu: 'Abidjan, Côte d\'Ivoire',
    heure: '09h00 – 17h00',
    places: 'Places limitées',
    type: 'Conférence',
    featured: false,
  },
  {
    date: { day: '10', month: 'Mai', year: '2026' },
    title: 'Crusade Prophétique Internationale',
    lieu: 'Lagos, Nigeria',
    heure: '18h00 – 23h00',
    places: 'Entrée libre',
    type: 'Croisade',
    featured: false,
  },
  {
    date: { day: '24', month: 'Mai', year: '2026' },
    title: 'Retraite Spirituelle — Activation des Appels',
    lieu: 'Montréal, Canada',
    heure: '2 jours (Sam & Dim)',
    places: 'Sur inscription',
    type: 'Retraite',
    featured: false,
  },
]

const typeColor: Record<string, string> = {
  Croisade: '#B07A14',
  Conférence: '#0A9060',
  Retraite: '#7B5EA7',
}
</script>

<template>
  <section id="evenements" class="events">
    <div class="events__inner">

      <div class="events__header">
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
        <article
          v-for="(ev, i) in events"
          :key="i"
          :class="['event-card', ev.featured ? 'event-card--featured' : '']"
        >
          <!-- Date -->
          <div class="event-card__date">
            <span class="event-card__day">{{ ev.date.day }}</span>
            <span class="event-card__month">{{ ev.date.month }}</span>
            <span class="event-card__year">{{ ev.date.year }}</span>
          </div>

          <!-- Contenu -->
          <div class="event-card__content">
            <div class="event-card__top">
              <span
                class="event-card__type"
                :style="{ color: typeColor[ev.type] || '#B07A14', borderColor: typeColor[ev.type] ? `${typeColor[ev.type]}30` : undefined }"
              >{{ ev.type }}</span>
              <span v-if="ev.featured" class="event-card__featured-badge">Prochain événement</span>
            </div>
            <h3 class="event-card__title">{{ ev.title }}</h3>
            <div class="event-card__meta">
              <span class="event-card__meta-item">
                <MapPin :size="12" />
                {{ ev.lieu }}
              </span>
              <span class="event-card__meta-item">
                <Clock :size="12" />
                {{ ev.heure }}
              </span>
              <span class="event-card__meta-item">
                <Users :size="12" />
                {{ ev.places }}
              </span>
            </div>
          </div>

          <!-- Action -->
          <a href="/rdv" class="event-card__cta">
            <span>S'inscrire</span>
            <ArrowRight :size="14" />
          </a>
        </article>
      </div>

    </div>
  </section>
</template>

<style scoped>
.events {
  background: #fff;
  padding: 7rem 2rem;
}

.events__inner { max-width: 900px; margin: 0 auto; }

.events__header {
  text-align: center;
  max-width: 520px;
  margin: 0 auto 4rem;
}

.events__label {
  display: inline-block;
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: #B07A14;
  margin-bottom: 0.85rem;
}

.events__title {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(1.9rem, 4vw, 2.8rem);
  line-height: 1.1;
  letter-spacing: 0.04em;
  color: #0C1528;
  margin-bottom: 1rem;
}

.events__title em {
  font-family: var(--f-serif);
  font-style: italic;
  color: #B07A14;
}

.events__desc {
  font-family: var(--f-serif);
  font-size: 0.95rem;
  line-height: 1.7;
  color: #9B9590;
}

/* List */
.events__list {
  display: flex;
  flex-direction: column;
  gap: 1px;
  border: 1px solid #EAE7E0;
  border-radius: 6px;
  overflow: hidden;
}

/* Card */
.event-card {
  display: flex;
  align-items: center;
  gap: 1.75rem;
  padding: 1.5rem 1.75rem;
  background: #fff;
  border-bottom: 1px solid #EAE7E0;
  transition: background var(--t);
}

.event-card:last-child { border-bottom: none; }
.event-card:hover { background: #FDFCF9; }

.event-card--featured {
  background: rgba(176, 122, 20, 0.03);
  border-left: 3px solid #B07A14;
}

.event-card--featured:hover { background: rgba(176, 122, 20, 0.05); }

/* Date */
.event-card__date {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  width: 52px;
  padding: 0.5rem;
  background: #F6F4EF;
  border-radius: 4px;
  border: 1px solid #EAE7E0;
  text-align: center;
}

.event-card__day {
  font-family: var(--f-display);
  font-size: 1.4rem;
  font-weight: 700;
  color: #0C1528;
  line-height: 1;
}

.event-card__month {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #B07A14;
  line-height: 1.4;
}

.event-card__year {
  font-family: var(--f-mono);
  font-size: 0.52rem;
  color: #9B9590;
  line-height: 1.4;
}

/* Content */
.event-card__content { flex: 1; min-width: 0; }

.event-card__top {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 0.35rem;
}

.event-card__type {
  font-family: var(--f-mono);
  font-size: 0.54rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 0.18rem 0.55rem;
  border-radius: 2px;
  border: 1px solid currentColor;
  opacity: 0.85;
}

.event-card__featured-badge {
  font-family: var(--f-mono);
  font-size: 0.52rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #B07A14;
  background: rgba(176, 122, 20, 0.08);
  padding: 0.18rem 0.55rem;
  border-radius: 2px;
}

.event-card__title {
  font-family: var(--f-display);
  font-size: 0.9rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  color: #0C1528;
  margin-bottom: 0.5rem;
  line-height: 1.3;
}

.event-card__meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.event-card__meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-family: var(--f-mono);
  font-size: 0.58rem;
  letter-spacing: 0.06em;
  color: #9B9590;
}

/* CTA */
.event-card__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  flex-shrink: 0;
  font-family: var(--f-mono);
  font-size: 0.6rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #0C1528;
  text-decoration: none;
  padding: 0.55rem 1rem;
  border: 1px solid #D4CFC6;
  border-radius: 2px;
  transition: border-color var(--t), color var(--t), background var(--t);
  white-space: nowrap;
}

.event-card__cta:hover {
  border-color: #B07A14;
  color: #B07A14;
  background: rgba(176, 122, 20, 0.04);
}

@media (max-width: 700px) {
  .event-card {
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1.25rem;
  }
  .event-card__cta { margin-left: auto; }
  .events { padding: 5rem 1.5rem; }
}
</style>
