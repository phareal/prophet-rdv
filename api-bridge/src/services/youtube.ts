import { TTLCache } from '../cache.js'
import type { Video } from '../types.js'

const cache = new TTLCache<Video[]>()
const TTL_MS = 30 * 60 * 1000 // 30 minutes

interface PlaylistItem {
  snippet: {
    title: string
    description: string
    resourceId: { videoId: string }
    thumbnails?: {
      high?: { url: string }
      medium?: { url: string }
    }
  }
}

export async function fetchLatestVideos(
  apiKey: string,
  channelId: string
): Promise<{ videos: Video[]; cached: boolean; ttl: number }> {
  const CACHE_KEY = `youtube:${channelId}`

  const hit = cache.get(CACHE_KEY)
  if (hit) {
    return { videos: hit, cached: true, ttl: cache.ttl(CACHE_KEY) }
  }

  // playlistItems.list coûte 1 quota unit vs 100 pour search.list
  const uploadsPlaylistId = channelId.replace(/^UC/, 'UU')
  const params = new URLSearchParams({
    key: apiKey,
    playlistId: uploadsPlaylistId,
    part: 'snippet',
    maxResults: '3',
  })

  const res = await fetch(
    `https://www.googleapis.com/youtube/v3/playlistItems?${params}`
  )

  if (!res.ok) {
    const body = await res.text()
    throw new Error(`YouTube API ${res.status}: ${body}`)
  }

  const data = (await res.json()) as { items: PlaylistItem[] }

  const videos: Video[] = data.items.map((item) => {
    const { snippet } = item
    const videoId = snippet.resourceId.videoId
    return {
      id: videoId,
      title: snippet.title,
      desc: snippet.description,
      thumbnail:
        snippet.thumbnails?.high?.url ??
        snippet.thumbnails?.medium?.url ??
        `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`,
      type: 'Vidéo',
      duration: '',
    }
  })

  cache.set(CACHE_KEY, videos, TTL_MS)
  return { videos, cached: false, ttl: Math.floor(TTL_MS / 1000) }
}
