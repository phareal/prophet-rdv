import twilio from 'twilio'
import { put } from '@vercel/blob'

export async function sendWhatsAppConfirmation({
  userPhone,
  nom,
  date,
  heure,
  typeRdv,
  confirmationId,
  pdfBuffer,
}: {
  userPhone: string
  nom: string
  date: string
  heure: string
  typeRdv: string
  confirmationId: string
  pdfBuffer: Uint8Array
}) {
  const config = useRuntimeConfig()
  const client = twilio(config.twilioAccountSid, config.twilioAuthToken)

  // Uploader le PDF temporairement (WhatsApp nécessite une URL publique)
  const blob = await put(`confirmations/${confirmationId}.pdf`, pdfBuffer, {
    access: 'public',
    contentType: 'application/pdf',
  })

  // Message au bénéficiaire
  const userMessage = `
✅ *Confirmation de Rendez-vous*

Bonjour *${nom}*,

Votre rendez-vous est confirmé :
📅 *Date :* ${date}
🕐 *Heure :* ${heure}
📋 *Type :* ${typeRdv}
🔖 *Référence :* ${confirmationId}

Votre PDF de confirmation :
${blob.url}

_Que Dieu vous bénisse._
  `.trim()

  await client.messages.create({
    from: `whatsapp:${config.twilioWhatsappNumber}`,
    to: `whatsapp:${userPhone}`,
    body: userMessage,
  })

  // Message au père spirituel (admin)
  const adminMessage = `
📬 *Nouvelle Réservation Confirmée*

👤 *Nom :* ${nom}
📅 *Date :* ${date}
🕐 *Heure :* ${heure}
📋 *Type :* ${typeRdv}
🔖 *Référence :* ${confirmationId}

PDF : ${blob.url}
  `.trim()

  await client.messages.create({
    from: `whatsapp:${config.twilioWhatsappNumber}`,
    to: `whatsapp:${config.adminWhatsappNumber}`,
    body: adminMessage,
  })
}
