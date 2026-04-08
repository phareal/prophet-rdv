import { z } from 'zod'

export const CONSULTATION_TYPES = [
  'Mariage',
  'Anges',
  'Appel',
  'Carrière politique',
  'Affaires',
  'Autels',
  'Étoiles',
  'Santé',
  'Voyage',
  'Entreprise',
  'Commerce',
  'Année 2026',
] as const

export const HEURES = [
  '08h00',
  '09h00',
  '10h00',
  '11h00',
  '14h00',
  '15h00',
  '16h00',
  '17h00',
] as const

export const MODES_PAIEMENT = [
  { value: 'mobile_money', label: 'Mobile Money' },
  { value: 'virement', label: 'Virement bancaire' },
  { value: 'paypal', label: 'PayPal' },
  { value: 'crypto', label: 'Crypto USDT' },
] as const

export const appointmentSchema = z.object({
  nom: z.string().min(2, 'Le nom doit contenir au moins 2 caractères'),
  prenom: z.string().min(2, 'Le prénom doit contenir au moins 2 caractères'),
  email: z.string().email('Adresse email invalide'),
  telephone: z.string().min(8, 'Numéro de téléphone invalide'),
  pays: z.string().min(1, 'Veuillez indiquer votre pays de résidence'),
  date: z
    .date({ required_error: 'Veuillez sélectionner une date' })
    .refine((d) => d.getDay() !== 0, {
      message: 'Pas de rendez-vous le dimanche',
    })
    .refine((d) => d > new Date(), {
      message: 'La date doit être dans le futur',
    }),
  heure: z.string().min(1, 'Veuillez sélectionner une heure'),
  typeConsultation: z.string().min(1, 'Veuillez choisir un type de consultation'),
  modePaiement: z.enum(['mobile_money', 'virement', 'paypal', 'crypto'], {
    required_error: 'Veuillez sélectionner un mode de paiement',
  }),
  message: z
    .string()
    .max(500, 'Le message ne peut pas dépasser 500 caractères')
    .optional(),
})

export type AppointmentFormData = z.infer<typeof appointmentSchema>

// Schéma pour la validation côté serveur (date en string ISO)
export const appointmentServerSchema = appointmentSchema.extend({
  date: z
    .string()
    .refine((s) => !isNaN(Date.parse(s)), { message: 'Date invalide' })
    .transform((s) => new Date(s))
    .refine((d) => d.getDay() !== 0, { message: 'Pas de rendez-vous le dimanche' })
    .refine((d) => d > new Date(), { message: 'La date doit être dans le futur' }),
})
