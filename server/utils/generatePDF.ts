import { PDFDocument, rgb, StandardFonts } from 'pdf-lib'

export async function generateConfirmationPDF(data: {
  nom: string
  date: string
  heure: string
  typeRdv: string
  confirmationId: string
}) {
  const pdfDoc = await PDFDocument.create()
  const page = pdfDoc.addPage([595, 842]) // A4
  const font = await pdfDoc.embedFont(StandardFonts.Helvetica)
  const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold)

  // En-tête
  page.drawText('Confirmation de Rendez-vous', {
    x: 50, y: 780,
    size: 26, font: fontBold,
    color: rgb(0.1, 0.1, 0.45),
  })

  page.drawLine({
    start: { x: 50, y: 765 },
    end: { x: 545, y: 765 },
    thickness: 1,
    color: rgb(0.8, 0.8, 0.8),
  })

  // Détails
  const details = [
    { label: 'Nom', value: data.nom },
    { label: 'Type de rendez-vous', value: data.typeRdv },
    { label: 'Date', value: data.date },
    { label: 'Heure', value: data.heure },
    { label: 'Référence', value: data.confirmationId },
  ]

  details.forEach(({ label, value }, i) => {
    page.drawText(`${label} :`, {
      x: 50, y: 710 - i * 40,
      size: 12, font: fontBold,
      color: rgb(0.3, 0.3, 0.3),
    })
    page.drawText(value, {
      x: 220, y: 710 - i * 40,
      size: 12, font,
      color: rgb(0.1, 0.1, 0.1),
    })
  })

  // Message
  page.drawText('Que Dieu vous bénisse pour cette démarche.', {
    x: 50, y: 480,
    size: 13, font,
    color: rgb(0.2, 0.2, 0.5),
  })

  // Footer
  page.drawText('Prophet RDV — Ministère du Prophète Jeremiah', {
    x: 50, y: 50,
    size: 10, font,
    color: rgb(0.6, 0.6, 0.6),
  })

  return await pdfDoc.save()
}
