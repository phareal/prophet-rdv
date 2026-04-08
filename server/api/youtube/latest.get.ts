const MOCK_VIDEOS = [
  {
    id: 'dQw4w9WgXcQ',
    title: '[MOCK] Prophétie sur les nations — 2026',
    desc: 'Le Prophète Jeremiah annonce ce que Dieu prépare pour les nations en cette nouvelle saison.',
    thumbnail: 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
    type: 'Prophétie',
    duration: '',
  },
  {
    id: 'dQw4w9WgXcQ',
    title: '[MOCK] Consultation prophétique en direct',
    desc: 'Session de consultations prophétiques en direct avec des révélations précises et vérifiables.',
    thumbnail: 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
    type: 'En direct',
    duration: '',
  },
  {
    id: 'dQw4w9WgXcQ',
    title: '[MOCK] Témoignages — Prophéties accomplies',
    desc: 'Des personnes témoignent de la précision des prophéties reçues lors de leurs consultations.',
    thumbnail: 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
    type: 'Témoignages',
    duration: '',
  },
]

export default defineEventHandler(async () => {
  const config = useRuntimeConfig()
  const bridgeUrl = config.apiBridgeUrl

  if (!bridgeUrl) {
    console.warn('[YouTube] API_BRIDGE_URL non défini — retour des données mock')
    return MOCK_VIDEOS
  }

  try {
    const data = await $fetch<typeof MOCK_VIDEOS>(`${bridgeUrl}/youtube/latest`)
    return data
  } catch (err: any) {
    console.warn('[YouTube] Bridge indisponible, retour des données mock:', err?.message)
    return MOCK_VIDEOS
  }
})
