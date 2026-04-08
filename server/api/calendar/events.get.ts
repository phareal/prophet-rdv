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

export default defineEventHandler(async () => {
  const config = useRuntimeConfig()
  const bridgeUrl = config.apiBridgeUrl

  if (!bridgeUrl) {
    console.warn('[Calendar] API_BRIDGE_URL non défini — retour des données mock')
    return MOCK_EVENTS
  }

  try {
    const data = await $fetch<typeof MOCK_EVENTS>(`${bridgeUrl}/calendar/events`)
    return data
  } catch (err: any) {
    console.warn('[Calendar] Bridge indisponible, retour des données mock:', err?.message)
    return MOCK_EVENTS
  }
})
