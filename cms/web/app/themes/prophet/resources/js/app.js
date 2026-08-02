import Alpine from 'alpinejs'

// Fait entrer les images statiques dans le graphe Vite pour qu'elles soient
// buildées et référençables via @asset() côté Blade. Les icônes (resources/images/icons)
// sont exclues : elles sont inlinées directement par x-icon, jamais via @asset().
import.meta.glob(['../images/*.{jpg,jpeg,png,webp,gif,avif}'], { eager: true })

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
