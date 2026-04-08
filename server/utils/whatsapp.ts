import { buildWhatsAppUrl, formatDateFr } from '~/lib/utils'

/**
 * Construit l'URL WhatsApp pour confirmer un RDV
 */
export function buildConfirmationWhatsAppUrl(data: {
  nom: string
  prenom: string
  typeConsultation: string
  date: Date
  heure: string
  pays: string
  telephone: string
}): string {
  const config = useRuntimeConfig()
  const dateFr = formatDateFr(data.date)

  const message = `Bonjour Prophète Jeremiah Nahoum,

Je viens de soumettre une demande de rendez-vous sur votre site :

👤 Nom : ${data.prenom} ${data.nom}
🔮 Consultation : ${data.typeConsultation}
📅 Date souhaitée : ${dateFr}
⏰ Heure : ${data.heure}
🌍 Pays : ${data.pays}
📞 Contact : ${data.telephone}

Je confirme ma demande et attends votre retour.

Que Dieu vous bénisse.`

  return buildWhatsAppUrl(config.prophetPhone1, message)
}
