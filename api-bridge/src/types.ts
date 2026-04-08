export interface Video {
  id: string
  title: string
  desc: string
  thumbnail: string
  type: string
  duration: string
}

export interface CalendarEvent {
  day: string
  month: string
  year: string
  title: string
  lieu: string
  heure: string
  places: string
  type: string
  featured: boolean
}

export interface ApiError {
  error: string
  code: number
}
