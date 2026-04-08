import mongoose from 'mongoose'
import { appointmentServerSchema } from '~/lib/validations'
import { Appointment } from '~/server/models/Appointment'
import { sendConfirmationEmailToClient, sendNotificationEmailToProphet } from '~/server/utils/mailer'
import { buildConfirmationWhatsAppUrl } from '~/server/utils/whatsapp'

// Cache de connexion MongoDB (évite les reconnexions répétées en dev/SSR)
let isConnected = false

async function connectMongoDB() {
  if (isConnected) return
  const config = useRuntimeConfig()
  await mongoose.connect(config.mongodbUri, {
    bufferCommands: false,
  })
  isConnected = true
  console.log('✅ MongoDB connecté')
}

export default defineEventHandler(async (event) => {
  try {
    // 1. Lire le body
    const body = await readBody(event)

    // 2. Valider avec Zod (côté serveur)
    const parseResult = appointmentServerSchema.safeParse(body)
    if (!parseResult.success) {
      throw createError({
        statusCode: 400,
        statusMessage: 'Données invalides',
        data: parseResult.error.flatten().fieldErrors,
      })
    }

    const data = parseResult.data

    // 3. Connexion MongoDB
    await connectMongoDB()

    // 4. Vérifier qu'aucun RDV n'existe déjà pour ce créneau
    const existing = await Appointment.findOne({
      date: {
        $gte: new Date(data.date.setHours(0, 0, 0, 0)),
        $lte: new Date(data.date.setHours(23, 59, 59, 999)),
      },
      heure: data.heure,
      statut: { $ne: 'annule' },
    })

    if (existing) {
      throw createError({
        statusCode: 409,
        statusMessage: 'Ce créneau est déjà réservé. Veuillez choisir une autre heure.',
      })
    }

    // 5. Sauvegarder en base de données
    const appointment = new Appointment({
      nom: data.nom,
      prenom: data.prenom,
      email: data.email,
      telephone: data.telephone,
      pays: data.pays,
      date: data.date,
      heure: data.heure,
      typeConsultation: data.typeConsultation,
      modePaiement: data.modePaiement,
      message: data.message,
    })

    await appointment.save()

    // 6. Envoyer les emails (en parallèle, non-bloquant pour la réponse)
    const emailData = {
      nom: data.nom,
      prenom: data.prenom,
      email: data.email,
      telephone: data.telephone,
      pays: data.pays,
      date: data.date,
      heure: data.heure,
      typeConsultation: data.typeConsultation,
      modePaiement: data.modePaiement,
      message: data.message,
    }

    // Envoi asynchrone (on ne bloque pas la réponse si l'email échoue)
    Promise.all([
      sendConfirmationEmailToClient(emailData).catch((e) =>
        console.error('Email client échoué:', e)
      ),
      sendNotificationEmailToProphet(emailData).catch((e) =>
        console.error('Email prophète échoué:', e)
      ),
    ])

    // 7. Construire l'URL WhatsApp côté serveur (sera utilisée par le client)
    const whatsappUrl = buildConfirmationWhatsAppUrl({
      nom: data.nom,
      prenom: data.prenom,
      typeConsultation: data.typeConsultation,
      date: data.date,
      heure: data.heure,
      pays: data.pays,
      telephone: data.telephone,
    })

    // 8. Retourner la réponse
    return {
      success: true,
      appointmentId: appointment._id.toString(),
      whatsappUrl,
      message: 'Votre demande de rendez-vous a été soumise avec succès.',
    }
  } catch (error: unknown) {
    if (error && typeof error === 'object' && 'statusCode' in error) {
      throw error
    }
    console.error('Erreur RDV API:', error)
    throw createError({
      statusCode: 500,
      statusMessage: 'Une erreur interne est survenue. Veuillez réessayer.',
    })
  }
})
