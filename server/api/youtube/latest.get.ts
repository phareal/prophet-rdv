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

  if (!config.youtubeApiKey || !config.youtubeChannelId) {
    console.warn('[YouTube] Clés API manquantes — retour des données mock')
    return MOCK_VIDEOS
  }

  const params = new URLSearchParams({
    key: config.youtubeApiKey,
    channelId: config.youtubeChannelId,
    part: 'snippet',
    order: 'date',
    maxResults: '3',
    type: 'video',
  })

  const res = await $fetch<{ items: any[] }>(
    `https://www.googleapis.com/youtube/v3/search?${params}`
  )

  return res.items.map((item: any) => ({
    id: item.id.videoId,
    title: item.snippet.title,
    desc: item.snippet.description,
    thumbnail:
      item.snippet.thumbnails?.high?.url ||
      item.snippet.thumbnails?.medium?.url ||
      `https://img.youtube.com/vi/${item.id.videoId}/hqdefault.jpg`,
    type: item.snippet.liveBroadcastContent === 'live' ? 'En direct' : 'Vidéo',
    duration: '',
  }))
})
