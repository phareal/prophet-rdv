import Fastify from 'fastify'
import cors from '@fastify/cors'
import { fetchLatestVideos } from './services/youtube.js'
import { fetchCalendarEvents } from './services/calendar.js'

// ── Config ────────────────────────────────────────────────────────────────────

const PORT = Number(process.env.PORT ?? 4000)

const YOUTUBE_API_KEY   = process.env.YOUTUBE_API_KEY   ?? ''
const YOUTUBE_CHANNEL_ID = process.env.YOUTUBE_CHANNEL_ID ?? ''
const GOOGLE_API_KEY    = process.env.GOOGLE_API_KEY    ?? ''
const GOOGLE_CALENDAR_ID = process.env.GOOGLE_CALENDAR_ID ?? ''

const ALLOWED_ORIGINS = (process.env.ALLOWED_ORIGINS ?? 'http://localhost:3000')
  .split(',')
  .map((o) => o.trim())

// ── Server ────────────────────────────────────────────────────────────────────

const app = Fastify({ logger: { level: 'info' } })

await app.register(cors, {
  origin: (origin, cb) => {
    // Allow requests with no origin (server-to-server, curl…)
    if (!origin || ALLOWED_ORIGINS.includes(origin)) return cb(null, true)
    cb(new Error('Not allowed by CORS'), false)
  },
})

// ── Routes ────────────────────────────────────────────────────────────────────

app.get('/health', async () => ({
  status: 'ok',
  timestamp: new Date().toISOString(),
}))

app.get('/youtube/latest', async (req, reply) => {
  if (!YOUTUBE_API_KEY || !YOUTUBE_CHANNEL_ID) {
    return reply.status(503).send({ error: 'YouTube API non configurée', code: 503 })
  }

  try {
    const { videos, cached, ttl } = await fetchLatestVideos(YOUTUBE_API_KEY, YOUTUBE_CHANNEL_ID)
    reply.header('X-Cache', cached ? 'HIT' : 'MISS')
    reply.header('Cache-Control', `public, max-age=${ttl}`)
    return videos
  } catch (err) {
    const message = err instanceof Error ? err.message : String(err)
    req.log.error({ err }, '[YouTube] Erreur API')
    return reply.status(502).send({ error: 'YouTube API indisponible', detail: message, code: 502 })
  }
})

app.get('/calendar/events', async (req, reply) => {
  if (!GOOGLE_API_KEY || !GOOGLE_CALENDAR_ID) {
    return reply.status(503).send({ error: 'Calendar API non configurée', code: 503 })
  }

  try {
    const { events, cached, ttl } = await fetchCalendarEvents(GOOGLE_API_KEY, GOOGLE_CALENDAR_ID)
    reply.header('X-Cache', cached ? 'HIT' : 'MISS')
    reply.header('Cache-Control', `public, max-age=${ttl}`)
    return events
  } catch (err) {
    const message = err instanceof Error ? err.message : String(err)
    req.log.error({ err }, '[Calendar] Erreur API')
    return reply.status(502).send({ error: 'Calendar API indisponible', detail: message, code: 502 })
  }
})

// ── Start ─────────────────────────────────────────────────────────────────────

try {
  await app.listen({ port: PORT, host: '0.0.0.0' })
  app.log.info(`Bridge API démarré sur http://0.0.0.0:${PORT}`)
} catch (err) {
  app.log.error(err)
  process.exit(1)
}
