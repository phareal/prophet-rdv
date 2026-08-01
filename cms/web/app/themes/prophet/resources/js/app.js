import Alpine from 'alpinejs'

window.Alpine = Alpine
Alpine.start()

// Reproduit l'animation fade-up déclenchée à l'entrée dans le viewport.
const observer = new IntersectionObserver(
  (entries) => {
    for (const entry of entries) {
      if (!entry.isIntersecting) continue
      entry.target.classList.add('is-visible')
      observer.unobserve(entry.target)
    }
  },
  { threshold: 0.15 }
)

document.querySelectorAll('[data-observe]').forEach((el) => observer.observe(el))
