<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { CalendarIcon, Loader2, Send, CalendarDays, MapPin, Clock } from 'lucide-vue-next'
import { appointmentSchema, HEURES, MODES_PAIEMENT } from '@/lib/validations'
import { formatDateFr, buildWhatsAppUrl } from '@/lib/utils'
import { useToast } from '@/components/ui/toast/use-toast'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Calendar } from '@/components/ui/calendar'
import ConsultationPicker from '@/components/ConsultationPicker.vue'

const { toast } = useToast()
const isSubmitting = ref(false)
const calendarOpen = ref(false)

// ── Contexte événement (query params) ────────────────────────
const route = useRoute()
const eventContext = computed(() => {
  const { event, eventDate, eventLieu, eventHeure } = route.query
  if (!event) return null
  return {
    title: event as string,
    date: eventDate ? new Date(eventDate as string) : null,
    lieu: eventLieu as string ?? '',
    heure: eventHeure as string ?? '',
  }
})

const { handleSubmit, errors, values, setFieldValue, defineField } = useForm({
  validationSchema: toTypedSchema(appointmentSchema),
  initialValues: { nom:'', prenom:'', email:'', telephone:'', pays:'', heure:'', typeConsultation:'', modePaiement: undefined, message:'' },
})

onMounted(() => {
  const ctx = eventContext.value
  if (!ctx) return
  if (ctx.date) setFieldValue('date', ctx.date)
  setFieldValue('typeConsultation', `Inscription — ${ctx.title}`)
  const detail = [ctx.title, ctx.lieu].filter(Boolean).join(' — ')
  setFieldValue('message', `Inscription pour : ${detail}`)
})

const [nom, nomAttrs]         = defineField('nom')
const [prenom, prenomAttrs]   = defineField('prenom')
const [email, emailAttrs]     = defineField('email')
const [telephone, telAttrs]   = defineField('telephone')
const [pays, paysAttrs]       = defineField('pays')
const [message, messageAttrs] = defineField('message')

const selectedDate         = computed(() => values.date)
const selectedConsultation = computed(() => values.typeConsultation ?? '')
const selectedHeure        = computed(() => values.heure ?? '')
const selectedPaiement     = computed(() => values.modePaiement)
const messageLength        = computed(() => (values.message ?? '').length)
const dateLabel            = computed(() =>
  selectedDate.value ? formatDateFr(selectedDate.value) : 'Choisir une date'
)

function onDateSelect(date: Date) { setFieldValue('date', date); calendarOpen.value = false }
function onHeureSelect(h: string) { setFieldValue('heure', h) }
function onPaiementChange(v: string) {
  setFieldValue('modePaiement', v as 'mobile_money'|'virement'|'paypal'|'crypto')
}

const onSubmit = handleSubmit(async (vals) => {
  isSubmitting.value = true
  try {
    const result = await $fetch<{ success: boolean; whatsappUrl: string; appointmentId: string }>(
      '/api/rdv', { method: 'POST', body: { ...vals, date: vals.date.toISOString() } }
    )
    if (result.success) {
      toast({ title: 'Demande envoyée', description: 'Confirmez sur WhatsApp pour finaliser.' })
      setTimeout(() => window.open(result.whatsappUrl, '_blank'), 800)
      await navigateTo({
        path: '/confirmation',
        query: { nom: vals.nom, prenom: vals.prenom, consultation: vals.typeConsultation,
                 date: vals.date.toISOString(), heure: vals.heure, waUrl: result.whatsappUrl },
      })
    }
  } catch (err: unknown) {
    const e = err as { data?: { message?: string }; statusCode?: number }
    toast({
      title: 'Erreur',
      description: e?.data?.message || (e?.statusCode === 409
        ? 'Ce créneau est déjà réservé. Choisissez une autre heure.'
        : 'Une erreur est survenue. Veuillez réessayer.'),
      variant: 'destructive',
    })
  } finally { isSubmitting.value = false }
})
</script>

<template>
  <div class="fp">
    <div class="fp__inner">

      <!-- ── EN-TÊTE ── -->
      <header class="fp__header">
        <p class="fp__eyebrow">{{ eventContext ? 'Inscription événement' : 'Rendez-vous' }}</p>
        <h2 class="fp__heading">
          {{ eventContext ? 'Réserver ma' : 'Demander une' }}<br>
          <span class="fp__heading-accent">{{ eventContext ? 'place' : 'consultation' }}</span>
        </h2>
        <p class="fp__subheading">
          Remplissez le formulaire. Vous recevrez une confirmation par email et WhatsApp.
        </p>
      </header>

      <!-- ── BANDEAU ÉVÉNEMENT ── -->
      <div v-if="eventContext" class="fp__event-banner">
        <CalendarDays :size="14" class="fp__event-banner__icon" />
        <div class="fp__event-banner__body">
          <span class="fp__event-banner__title">{{ eventContext.title }}</span>
          <span class="fp__event-banner__meta">
            <MapPin :size="11" />{{ eventContext.lieu }}
            <Clock :size="11" />{{ eventContext.heure }}
          </span>
        </div>
      </div>

      <form @submit.prevent="onSubmit" novalidate class="fp__form">

        <!-- ─── TYPE DE CONSULTATION ─── -->
        <fieldset class="fp__section">
          <legend class="fp__section-legend">
            Type de consultation <span class="fp__required">*</span>
          </legend>
          <template v-if="eventContext">
            <div class="fp__consultation-badge">{{ selectedConsultation }}</div>
          </template>
          <template v-else>
            <ConsultationPicker
              :model-value="selectedConsultation"
              @update:model-value="setFieldValue('typeConsultation', $event)"
            />
            <p v-if="errors.typeConsultation" class="fp__error">{{ errors.typeConsultation }}</p>
          </template>
        </fieldset>

        <div class="fp__rule" />

        <!-- ─── NOM / PRÉNOM ─── -->
        <div class="fp__row">
          <div class="fp__field">
            <label for="nom" class="fp__label">Nom <span class="fp__required">*</span></label>
            <input id="nom" v-model="nom" v-bind="nomAttrs"
                   placeholder="Votre nom de famille"
                   :class="['fp__input', errors.nom && 'fp__input--err']" />
            <p v-if="errors.nom" class="fp__error">{{ errors.nom }}</p>
          </div>
          <div class="fp__field">
            <label for="prenom" class="fp__label">Prénom <span class="fp__required">*</span></label>
            <input id="prenom" v-model="prenom" v-bind="prenomAttrs"
                   placeholder="Votre prénom"
                   :class="['fp__input', errors.prenom && 'fp__input--err']" />
            <p v-if="errors.prenom" class="fp__error">{{ errors.prenom }}</p>
          </div>
        </div>

        <!-- ─── EMAIL / TÉLÉPHONE ─── -->
        <div class="fp__row">
          <div class="fp__field">
            <label for="email" class="fp__label">Email <span class="fp__required">*</span></label>
            <input id="email" type="email" v-model="email" v-bind="emailAttrs"
                   placeholder="votre@email.com"
                   :class="['fp__input', errors.email && 'fp__input--err']" />
            <p v-if="errors.email" class="fp__error">{{ errors.email }}</p>
          </div>
          <div class="fp__field">
            <label for="telephone" class="fp__label">
              Téléphone / WhatsApp <span class="fp__required">*</span>
            </label>
            <input id="telephone" type="tel" v-model="telephone" v-bind="telAttrs"
                   placeholder="+33 6 12 34 56 78"
                   :class="['fp__input fp__input--mono', errors.telephone && 'fp__input--err']" />
            <p v-if="errors.telephone" class="fp__error">{{ errors.telephone }}</p>
          </div>
        </div>

        <!-- ─── PAYS / DATE ─── -->
        <div class="fp__row">
          <div class="fp__field">
            <label for="pays" class="fp__label">
              Pays de résidence <span class="fp__required">*</span>
            </label>
            <input id="pays" v-model="pays" v-bind="paysAttrs"
                   placeholder="France, Côte d'Ivoire…"
                   :class="['fp__input', errors.pays && 'fp__input--err']" />
            <p v-if="errors.pays" class="fp__error">{{ errors.pays }}</p>
          </div>
          <div class="fp__field">
            <label class="fp__label">Date souhaitée <span class="fp__required">*</span></label>
            <Popover v-model:open="calendarOpen">
              <PopoverTrigger as-child>
                <button type="button"
                        :disabled="!!eventContext?.date"
                        :class="['fp__input fp__input--btn', !selectedDate && 'fp__input--placeholder', errors.date && 'fp__input--err', eventContext?.date && 'fp__input--locked']">
                  <CalendarIcon class="fp__cal-icon" :size="15" />
                  <span>{{ dateLabel }}</span>
                </button>
              </PopoverTrigger>
              <PopoverContent align="start" class="fp__cal-popover">
                <Calendar :model-value="selectedDate" @update:model-value="onDateSelect" />
              </PopoverContent>
            </Popover>
            <p v-if="errors.date" class="fp__error">{{ errors.date }}</p>
          </div>
        </div>

        <!-- ─── GRILLE BAS ─── -->
        <div class="fp__bottom">

          <!-- col gauche : heure + message -->
          <div class="fp__bottom-col">
            <div class="fp__field">
              <label class="fp__label">Heure souhaitée <span class="fp__required">*</span></label>
              <div class="fp__time-grid">
                <button
                  v-for="h in HEURES" :key="h" type="button"
                  @click="onHeureSelect(h)"
                  :class="['fp__time-slot', selectedHeure === h && 'fp__time-slot--active']"
                >{{ h }}</button>
              </div>
              <p v-if="errors.heure" class="fp__error">{{ errors.heure }}</p>
            </div>

            <div class="fp__field fp__field--grow">
              <div class="fp__field-header">
                <label for="message" class="fp__label">
                  Message <span class="fp__label-optional">(facultatif)</span>
                </label>
                <span class="fp__char-count">{{ messageLength }}/500</span>
              </div>
              <textarea id="message" v-model="message" v-bind="messageAttrs"
                        placeholder="Décrivez votre situation…"
                        maxlength="500"
                        :class="['fp__input fp__input--textarea fp__input--grow', errors.message && 'fp__input--err']" />
              <p v-if="errors.message" class="fp__error">{{ errors.message }}</p>
            </div>
          </div>

          <!-- col droite : paiement + note + CTA -->
          <div class="fp__bottom-col">
            <div class="fp__field">
              <label for="modePaiement" class="fp__label">Mode de paiement <span class="fp__required">*</span></label>
              <select
                id="modePaiement"
                :value="selectedPaiement"
                @change="onPaiementChange(($event.target as HTMLSelectElement).value)"
                :class="['fp__input fp__select', !selectedPaiement && 'fp__input--placeholder', errors.modePaiement && 'fp__input--err']"
              >
                <option value="" disabled selected>Choisir…</option>
                <option v-for="mode in MODES_PAIEMENT" :key="mode.value" :value="mode.value">
                  {{ mode.label }}
                </option>
              </select>
              <p v-if="errors.modePaiement" class="fp__error">{{ errors.modePaiement }}</p>
            </div>

            <div class="fp__note">
              <p>
                <strong>Note :</strong>
                Demande traitée en 24–48h. Vous serez redirigé vers WhatsApp pour confirmer.
              </p>
            </div>

            <button type="submit" :disabled="isSubmitting" class="fp__cta">
              <Loader2 v-if="isSubmitting" :size="16" class="fp__cta-spin" />
              <Send v-else :size="16" />
              <span>{{ isSubmitting ? 'Envoi…' : 'Envoyer ma demande' }}</span>
            </button>
          </div>

        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
/* ── Wrapper ─────────────────────────────────────────────── */
.fp {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #FFFFFF;
  overflow: hidden;
}
.fp__inner {
  flex: 1;
  display: flex;
  flex-direction: column;
  max-width: 600px;
  width: 100%;
  margin: 0 auto;
  padding: 1.25rem 1.75rem;
  animation: fade-up 0.4s var(--ease) 0.05s both;
  min-height: 0;
}

/* ── En-tête ─────────────────────────────────────────────── */
.fp__header { margin-bottom: 1rem; }

.fp__eyebrow {
  font-family: var(--f-mono);
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: rgba(200,146,28,0.82);
  margin-bottom: 0.35rem;
}

.fp__heading {
  font-family: var(--f-display);
  font-weight: 400;
  font-size: clamp(1.05rem, 2vw, 1.35rem);
  color: var(--ink);
  line-height: 1.15;
  letter-spacing: 0.04em;
}
.fp__heading-accent {
  display: block;
  font-weight: 700;
  color: var(--emerald);
  font-size: clamp(1.25rem, 2.4vw, 1.6rem);
}
.fp__subheading {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.85rem;
  color: var(--text-soft);
  line-height: 1.45;
  margin-top: 0.3rem;
}

/* ── Bandeau événement ───────────────────────────────────── */
.fp__event-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.65rem 0.9rem;
  border-radius: var(--r2);
  background: rgba(176,122,20,0.06);
  border: 1px solid rgba(176,122,20,0.22);
  margin-bottom: 0.25rem;
}
.fp__event-banner__icon { color: #B07A14; flex-shrink: 0; margin-top: 2px; }
.fp__event-banner__body { display: flex; flex-direction: column; gap: 0.25rem; }
.fp__event-banner__title {
  font-family: var(--f-display);
  font-size: 0.82rem;
  font-weight: 600;
  color: #0C1528;
  letter-spacing: 0.02em;
}
.fp__event-banner__meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--f-mono);
  font-size: 0.6rem;
  color: #5C5752;
  letter-spacing: 0.05em;
}
.fp__event-banner__meta svg { opacity: 0.6; }

.fp__consultation-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.85rem;
  border-radius: var(--r2);
  border: 1px solid rgba(176,122,20,0.3);
  background: rgba(176,122,20,0.06);
  font-family: var(--f-mono);
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  color: #B07A14;
}

/* ── Formulaire ──────────────────────────────────────────── */
.fp__form {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  min-height: 0;
}

.fp__section { border: none; padding: 0; }
.fp__section-legend {
  font-family: var(--f-mono);
  font-size: 0.68rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(26,23,20,0.65);
  margin-bottom: 0.45rem;
  display: block;
  width: 100%;
}
.fp__required { color: var(--emerald); }

.fp__rule {
  height: 1px;
  background: #E8E4DC;
}

.fp__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
@media (max-width: 560px) { .fp__row { grid-template-columns: 1fr; } }

.fp__field { display: flex; flex-direction: column; gap: 0.22rem; }
.fp__field-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.fp__label {
  font-family: var(--f-mono);
  font-size: 0.68rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(26,23,20,0.65);
}
.fp__label-optional {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.78rem;
  text-transform: none;
  letter-spacing: 0;
  font-weight: 400;
  color: var(--text-faint);
}

/* ── Inputs ──────────────────────────────────────────────── */
.fp__input {
  width: 100%;
  padding: 0.46rem 0.75rem;
  border-radius: var(--r2);
  border: 1px solid #DDD9D0;
  background: white;
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 0.95rem;
  color: var(--text);
  outline: none;
  transition: border-color var(--t), box-shadow var(--t);
  -webkit-appearance: none;
  appearance: none;
}
.fp__input:focus {
  border-color: var(--emerald);
  box-shadow: 0 0 0 2px rgba(14,170,114,0.1);
}
.fp__input::placeholder {
  font-style: italic;
  color: var(--text-faint);
}
.fp__input--mono { font-family: var(--f-mono); font-size: 0.84rem; }
.fp__input--err {
  border-color: #c0392b !important;
  box-shadow: 0 0 0 2px rgba(192,57,43,0.08) !important;
}
.fp__input--btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  text-align: left;
  font-size: 0.88rem;
}
.fp__input--placeholder { color: var(--text-faint) !important; font-style: italic; }
.fp__input--locked { opacity: 0.7; cursor: default; background: #F6F4EF !important; }
.fp__input--textarea {
  resize: none;
  line-height: 1.45;
}
.fp__cal-icon { color: rgba(26,23,20,0.3); flex-shrink: 0; }
.fp__cal-popover { width: auto; padding: 0; }

.fp__error {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.75rem;
  color: #c0392b;
}
.fp__char-count {
  font-family: var(--f-mono);
  font-size: 0.6rem;
  color: rgba(26,23,20,0.28);
}

/* ── Grille bas ──────────────────────────────────────────── */
.fp__bottom {
  flex: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  align-items: start;
  min-height: 0;
}
.fp__bottom-col {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  height: 100%;
}
.fp__field--grow {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.fp__input--grow {
  flex: 1;
  min-height: 52px;
  resize: none;
}

/* ── Créneaux horaires ────────────────────────────────────── */
.fp__time-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}
.fp__time-slot {
  padding: 0.28rem 0.65rem;
  border-radius: var(--r2);
  border: 1px solid #DDD9D0;
  background: white;
  font-family: var(--f-mono);
  font-size: 0.75rem;
  color: rgba(26,23,20,0.65);
  transition: border-color var(--tf), color var(--tf), background var(--tf);
}
.fp__time-slot:hover {
  border-color: var(--emerald);
  color: var(--emerald);
  background: rgba(14,170,114,0.04);
}
.fp__time-slot--active {
  border-color: var(--emerald) !important;
  background: var(--emerald) !important;
  color: white !important;
}

/* ── Select paiement ─────────────────────────────────────── */
.fp__select {
  cursor: pointer;
  font-family: var(--f-serif);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%231a1714' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  padding-right: 2rem;
}

/* ── Note ────────────────────────────────────────────────── */
.fp__note {
  padding: 0.55rem 0.8rem;
  border-radius: var(--r2);
  background: #F6F3EB;
  border: 1px solid #E5E0D4;
}
.fp__note p {
  font-family: var(--f-serif);
  font-style: italic;
  font-size: 0.78rem;
  line-height: 1.45;
  color: var(--text-soft);
}
.fp__note strong {
  font-style: normal;
  font-weight: 600;
  color: var(--text);
}

/* ── CTA ─────────────────────────────────────────────────── */
.fp__cta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  height: 42px;
  margin-top: auto;
  border-radius: var(--r2);
  background: var(--emerald);
  color: white;
  font-family: var(--f-mono);
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  transition: background var(--t), transform var(--tf);
}
.fp__cta:hover:not(:disabled) {
  background: #0a9463;
  transform: translateY(-1px);
}
.fp__cta:active:not(:disabled) { transform: none; }
.fp__cta:disabled { opacity: 0.5; cursor: not-allowed; }
.fp__cta-spin { animation: spin 1s linear infinite; }
</style>
