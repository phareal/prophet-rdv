interface Entry<T> {
  data: T
  expiresAt: number
}

export class TTLCache<T> {
  private store = new Map<string, Entry<T>>()

  get(key: string): T | null {
    const entry = this.store.get(key)
    if (!entry) return null
    if (Date.now() > entry.expiresAt) {
      this.store.delete(key)
      return null
    }
    return entry.data
  }

  set(key: string, data: T, ttlMs: number): void {
    this.store.set(key, { data, expiresAt: Date.now() + ttlMs })
  }

  /** Durée restante en secondes avant expiration (ou 0 si absent/expiré) */
  ttl(key: string): number {
    const entry = this.store.get(key)
    if (!entry) return 0
    const remaining = Math.floor((entry.expiresAt - Date.now()) / 1000)
    return remaining > 0 ? remaining : 0
  }

  invalidate(key: string): void {
    this.store.delete(key)
  }
}
