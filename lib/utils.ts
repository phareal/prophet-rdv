import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * Formate une date en français
 */
export function formatDateFr(date: Date): string {
  return new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(date)
}

/**
 * Encode un message WhatsApp pour l'URL wa.me
 */
export function buildWhatsAppUrl(phone: string, message: string): string {
  const cleaned = phone.replace(/[^+\d]/g, '')
  return `https://wa.me/${cleaned}?text=${encodeURIComponent(message)}`
}

/**
 * Construit le message WhatsApp de confirmation de RDV
 */
export function buildRdvWhatsAppMessage(data: {
  nom: string
  prenom: string
  typeConsultation: string
  date: Date
  heure: string
  pays: string
}): string {
  const dateFr = formatDateFr(data.date)
  return `Bonjour Prophète Jeremiah Nahoum,

Je viens de soumettre une demande de rendez-vous sur votre site :

👤 Nom : ${data.prenom} ${data.nom}
🔮 Consultation : ${data.typeConsultation}
📅 Date souhaitée : ${dateFr}
⏰ Heure : ${data.heure}
🌍 Pays : ${data.pays}

Je confirme ma demande et attends votre retour.

Merci et que Dieu vous bénisse.`
}
