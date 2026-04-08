import { TTLCache } from '../cache.js'
import type { CalendarEvent } from '../types.js'

const cache = new TTLCache<CalendarEvent[]>()
const TTL_MS = 10 * 60 * 1000 // 10 minutes

const FRENCH_MONTHS: Record<number, string> = {
  0: 'Jan', 1: 'Fév', 2: 'Mar', 3: 'Avr', 4: 'Mai', 5: 'Juin',
  6: 'Juil', 7: 'Août', 8: 'Sep', 9: 'Oct', 10: 'Nov', 11: 'Déc',
}

interface GCalItem {
  summary?: string
  description?: string
  location?: string
  start: { dateTime?: string; date?: string }
  end?: { dateTime?: string }
}

function stripHtml(html: string): string {
  return html.replace(/<[^>]+>/g, '\n').replace(/&nbsp;/g, ' ').replace(/\n{2,}/g, '\n')
}

function parseField(description: string, key: string): string {
  const plain = stripHtml(description)
  const match = plain.match(new RegExp(`${key}:\\s*([^\\n]+)`))
  return match ? match[1].trim() : ''
}

function formatTime(dateTime: string): string {
  const d = new Date(dateTime)
  const h = d.getHours().toString().padStart(2, '0')
  const m = d.getMinutes().toString().padStart(2, '0')
  return m === '00' ? `${h}h00` : `${h}h${m}`
}

export async function fetchCalendarEvents(
  apiKey: string,
  calendarId: string
): Promise<{ events: CalendarEvent[]; cached: boolean; ttl: number }> {
  const CACHE_KEY = `calendar:${calendarId}`

  const hit = cache.get(CACHE_KEY)
  if (hit) {
    return { events: hit, cached: true, ttl: cache.ttl(CACHE_KEY) }
  }

  const params = new URLSearchParams({
    key: apiKey,
    timeMin: new Date().toISOString(),
    orderBy: 'startTime',
    singleEvents: 'true',
    maxResults: '10',
  })

  const res = await fetch(
    `https://www.googleapis.com/calendar/v3/calendars/${encodeURIComponent(calendarId)}/events?${params}`
  )

  if (!res.ok) {
    const body = await res.text()
    throw new Error(`Calendar API ${res.status}: ${body}`)
  }

  const data = (await res.json()) as { items: GCalItem[] }

  const events: CalendarEvent[] = (data.items ?? [])
    .filter((item) => Boolean(item.summary))
    .map((item, index) => {
      const desc = item.description ?? ''
      const start = new Date(item.start.dateTime ?? item.start.date ?? '')
      const endDt = item.end?.dateTime

      const heure = item.start.dateTime
        ? endDt
          ? `${formatTime(item.start.dateTime)} – ${formatTime(endDt)}`
          : formatTime(item.start.dateTime)
        : '2 jours'

      return {
        day: start.getDate().toString().padStart(2, '0'),
        month: FRENCH_MONTHS[start.getMonth()],
        year: start.getFullYear().toString(),
        title: item.summary!,
        lieu: parseField(desc, 'lieu') || item.location || '',
        heure,
        places: parseField(desc, 'places') || 'Entrée libre',
        type: parseField(desc, 'type') || 'Conférence',
        featured: index === 0,
      }
    })

  cache.set(CACHE_KEY, events, TTL_MS)
  return { events, cached: false, ttl: Math.floor(TTL_MS / 1000) }
}
