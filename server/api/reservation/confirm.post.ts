import { generateConfirmationPDF } from '~/server/utils/generatePDF'
import { sendWhatsAppConfirmation } from '~/server/utils/sendWhatsApp'
import crypto from 'crypto'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const { nom, date, heure, typeRdv, userPhone } = body

  const confirmationId = crypto.randomUUID().slice(0, 8).toUpperCase()

  // 1. Générer le PDF
  const pdfBuffer = await generateConfirmationPDF({
    nom, date, heure, typeRdv, confirmationId,
  })

  // 2. Envoyer via WhatsApp
  await sendWhatsAppConfirmation({
    userPhone,
    nom, date, heure, typeRdv,
    confirmationId,
    pdfBuffer,
  })

  return { success: true, confirmationId }
})
