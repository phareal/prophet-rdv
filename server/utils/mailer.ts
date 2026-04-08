import nodemailer from 'nodemailer'
import { formatDateFr } from '~/lib/utils'

interface RdvEmailData {
  nom: string
  prenom: string
  email: string
  telephone: string
  pays: string
  date: Date
  heure: string
  typeConsultation: string
  modePaiement: string
  message?: string
}

function getModePaiementLabel(mode: string): string {
  const labels: Record<string, string> = {
    mobile_money: 'Mobile Money',
    virement: 'Virement bancaire',
    paypal: 'PayPal',
    crypto: 'Crypto USDT',
  }
  return labels[mode] || mode
}

function createTransporter() {
  const config = useRuntimeConfig()
  return nodemailer.createTransport({
    host: config.smtpHost,
    port: config.smtpPort,
    secure: config.smtpSecure,
    auth: {
      user: config.smtpUser,
      pass: config.smtpPass,
    },
  })
}

/**
 * Email de confirmation envoyé au client
 */
export async function sendConfirmationEmailToClient(data: RdvEmailData): Promise<void> {
  const config = useRuntimeConfig()
  const transporter = createTransporter()
  const dateFr = formatDateFr(data.date)

  const html = `
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'Inter', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: #0F172A; padding: 40px 32px; text-align: center; }
    .header h1 { color: #1D9E75; font-size: 22px; margin: 0 0 4px; letter-spacing: 0.5px; }
    .header p { color: #94a3b8; font-size: 14px; margin: 0; }
    .gold-bar { height: 3px; background: linear-gradient(90deg, #BA7517, #f0a829, #BA7517); }
    .body { padding: 32px; }
    .greeting { font-size: 18px; color: #0F172A; font-weight: 600; margin-bottom: 8px; }
    .text { color: #475569; line-height: 1.7; margin-bottom: 24px; }
    .card { background: #E1F5EE; border-left: 4px solid #1D9E75; border-radius: 8px; padding: 20px 24px; margin: 24px 0; }
    .card-row { display: flex; margin-bottom: 10px; }
    .card-label { color: #64748b; font-size: 13px; width: 160px; flex-shrink: 0; }
    .card-value { color: #0F172A; font-size: 14px; font-weight: 500; }
    .badge { display: inline-block; background: #1D9E75; color: white; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 500; }
    .footer { background: #0F172A; padding: 24px 32px; text-align: center; }
    .footer p { color: #64748b; font-size: 12px; margin: 4px 0; }
    .footer a { color: #1D9E75; text-decoration: none; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>✝ Prophète Jeremiah Nahoum</h1>
      <p>Le Conseiller des Rois</p>
    </div>
    <div class="gold-bar"></div>
    <div class="body">
      <div class="greeting">Bonjour ${data.prenom} ${data.nom},</div>
      <p class="text">
        Votre demande de rendez-vous a bien été reçue. Nous vous contacterons très prochainement pour confirmer votre consultation.
      </p>
      <div class="card">
        <div class="card-row"><span class="card-label">🔮 Consultation</span><span class="card-value"><span class="badge">${data.typeConsultation}</span></span></div>
        <div class="card-row"><span class="card-label">📅 Date souhaitée</span><span class="card-value">${dateFr}</span></div>
        <div class="card-row"><span class="card-label">⏰ Heure</span><span class="card-value">${data.heure}</span></div>
        <div class="card-row"><span class="card-label">🌍 Pays</span><span class="card-value">${data.pays}</span></div>
        <div class="card-row"><span class="card-label">💳 Paiement</span><span class="card-value">${getModePaiementLabel(data.modePaiement)}</span></div>
        <div class="card-row"><span class="card-label">📞 Téléphone</span><span class="card-value">${data.telephone}</span></div>
      </div>
      <p class="text">
        Pour toute question urgente, vous pouvez contacter directement le Prophète via WhatsApp :
        <br><strong style="color:#1D9E75">${config.prophetPhone1}</strong>
        ${config.prophetPhone2 ? `&nbsp;|&nbsp;<strong style="color:#1D9E75">${config.prophetPhone2}</strong>` : ''}
      </p>
      <p class="text" style="font-style: italic; color: #BA7517;">
        "Car je connais les projets que j'ai formés sur vous, dit l'Éternel, projets de paix et non de malheur, afin de vous donner un avenir et de l'espérance." — Jér. 29:11
      </p>
    </div>
    <div class="footer">
      <p>© ${new Date().getFullYear()} Prophète Jeremiah Nahoum — Le Conseiller des Rois</p>
      <p>Ce message a été envoyé automatiquement suite à votre demande sur <a href="${config.public.siteUrl}">${config.public.siteUrl}</a></p>
    </div>
  </div>
</body>
</html>
  `

  await transporter.sendMail({
    from: `"Prophète Jeremiah Nahoum" <${config.smtpUser}>`,
    to: data.email,
    subject: `✅ Confirmation de votre demande de RDV — ${data.typeConsultation}`,
    html,
  })
}

/**
 * Email de notification envoyé au prophète
 */
export async function sendNotificationEmailToProphet(data: RdvEmailData): Promise<void> {
  const config = useRuntimeConfig()
  const transporter = createTransporter()
  const dateFr = formatDateFr(data.date)

  const html = `
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; background: #f8fafc; }
    .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: #1D9E75; padding: 24px 32px; }
    .header h1 { color: white; font-size: 20px; margin: 0; }
    .body { padding: 32px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
    td:first-child { color: #64748b; font-size: 13px; width: 40%; }
    td:last-child { color: #0F172A; font-size: 14px; font-weight: 500; }
    .message-box { background: #f8fafc; border-radius: 8px; padding: 16px; color: #475569; margin-top: 16px; white-space: pre-wrap; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header"><h1>🔔 Nouvelle demande de RDV</h1></div>
    <div class="body">
      <table>
        <tr><td>Nom complet</td><td>${data.prenom} ${data.nom}</td></tr>
        <tr><td>Email</td><td>${data.email}</td></tr>
        <tr><td>Téléphone</td><td>${data.telephone}</td></tr>
        <tr><td>Pays</td><td>${data.pays}</td></tr>
        <tr><td>Type de consultation</td><td>${data.typeConsultation}</td></tr>
        <tr><td>Date souhaitée</td><td>${dateFr}</td></tr>
        <tr><td>Heure</td><td>${data.heure}</td></tr>
        <tr><td>Mode de paiement</td><td>${getModePaiementLabel(data.modePaiement)}</td></tr>
      </table>
      ${data.message ? `<div class="message-box"><strong>Message :</strong>\n${data.message}</div>` : ''}
    </div>
  </div>
</body>
</html>
  `

  await transporter.sendMail({
    from: `"Site Prophet RDV" <${config.smtpUser}>`,
    to: config.prophetEmail,
    subject: `📅 Nouveau RDV : ${data.prenom} ${data.nom} — ${data.typeConsultation} — ${dateFr}`,
    html,
    replyTo: data.email,
  })
}
