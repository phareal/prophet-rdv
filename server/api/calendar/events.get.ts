const FRENCH_MONTHS: Record<number, string> = {
  0: 'Jan', 1: 'Fév', 2: 'Mar', 3: 'Avr', 4: 'Mai', 5: 'Juin',
  6: 'Juil', 7: 'Août', 8: 'Sep', 9: 'Oct', 10: 'Nov', 11: 'Déc',
}

const MOCK_EVENTS = [
  {
    day: '18', month: 'Avr', year: '2026',
    title: '[MOCK] Nuit de Prophétie & Délivrance',
    lieu: 'Paris, France',
    heure: '20h00 – 00h00',
    places: 'Entrée libre',
    type: 'Croisade',
    featured: true,
  },
  {
    day: '26', month: 'Avr', year: '2026',
    title: '[MOCK] Conférence des Leaders & Entrepreneurs',
    lieu: 'Abidjan, Côte d\'Ivoire',
    heure: '09h00 – 17h00',
    places: 'Places limitées',
    type: 'Conférence',
    featured: false,
  },
  {
    day: '10', month: 'Mai', year: '2026',
    title: '[MOCK] Crusade Prophétique Internationale',
    lieu: 'Lagos, Nigeria',
    heure: '18h00 – 23h00',
    places: 'Entrée libre',
    type: 'Croisade',
    featured: false,
  },
  {
    day: '24', month: 'Mai', year: '2026',
    title: '[MOCK] Retraite Spirituelle — Activation des Appels',
    lieu: 'Montréal, Canada',
    heure: '2 jours (Sam & Dim)',
    places: 'Sur inscription',
    type: 'Retraite',
    featured: false,
  },
]

function parseDesc(description: string, key: string): string {
  const match = description.match(new RegExp(`${key}:\\s*([^\\n]+)`))
  return match ? match[1].trim() : ''
}

function formatTime(dateTime: string): string {
  const d = new Date(dateTime)
  const h = d.getHours().toString().padStart(2, '0')
  const m = d.getMinutes().toString().padStart(2, '0')
  return m === '00' ? `${h}h00` : `${h}h${m}`
}

export default defineEventHandler(async () => {
  const config = useRuntimeConfig()

  if (!config.googleApiKey || !config.googleCalendarId) {
    console.warn('[Calendar] Clés API manquantes — retour des données mock')
    return MOCK_EVENTS
  }

  const params = new URLSearchParams({
    key: config.googleApiKey,
    timeMin: new Date().toISOString(),
    orderBy: 'startTime',
    singleEvents: 'true',
    maxResults: '10',
  })

  const res = await $fetch<{ items: any[] }>(
    `https://www.googleapis.com/calendar/v3/calendars/${encodeURIComponent(config.googleCalendarId)}/events?${params}`
  )

  return res.items.map((item: any, index: number) => {
    const desc = item.description || ''
    const start = new Date(item.start.dateTime || item.start.date)
    const end = item.end?.dateTime ? new Date(item.end.dateTime) : null

    const heure = item.start.dateTime
      ? end
        ? `${formatTime(item.start.dateTime)} – ${formatTime(item.end.dateTime)}`
        : formatTime(item.start.dateTime)
      : '2 jours'

    return {
      day: start.getDate().toString().padStart(2, '0'),
      month: FRENCH_MONTHS[start.getMonth()],
      year: start.getFullYear().toString(),
      title: item.summary || '',
      lieu: parseDesc(desc, 'lieu') || item.location || '',
      heure,
      places: parseDesc(desc, 'places') || 'Entrée libre',
      type: parseDesc(desc, 'type') || 'Conférence',
      featured: index === 0,
    }
  })
})
