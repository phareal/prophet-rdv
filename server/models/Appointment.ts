import mongoose, { Schema, type Document } from 'mongoose'

export interface IAppointment extends Document {
  nom: string
  prenom: string
  email: string
  telephone: string
  pays: string
  date: Date
  heure: string
  typeConsultation: string
  modePaiement: 'mobile_money' | 'virement' | 'paypal' | 'crypto'
  message?: string
  statut: 'en_attente' | 'confirme' | 'annule'
  createdAt: Date
  updatedAt: Date
}

const AppointmentSchema = new Schema<IAppointment>(
  {
    nom: { type: String, required: true, trim: true },
    prenom: { type: String, required: true, trim: true },
    email: { type: String, required: true, lowercase: true, trim: true },
    telephone: { type: String, required: true, trim: true },
    pays: { type: String, required: true, trim: true },
    date: { type: Date, required: true },
    heure: { type: String, required: true },
    typeConsultation: { type: String, required: true },
    modePaiement: {
      type: String,
      required: true,
      enum: ['mobile_money', 'virement', 'paypal', 'crypto'],
    },
    message: { type: String, maxlength: 500 },
    statut: {
      type: String,
      enum: ['en_attente', 'confirme', 'annule'],
      default: 'en_attente',
    },
  },
  {
    timestamps: true,
  }
)

// Index pour la recherche par date et email
AppointmentSchema.index({ date: 1, heure: 1 })
AppointmentSchema.index({ email: 1 })
AppointmentSchema.index({ createdAt: -1 })

export const Appointment =
  mongoose.models.Appointment ||
  mongoose.model<IAppointment>('Appointment', AppointmentSchema)
