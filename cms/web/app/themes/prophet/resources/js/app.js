import Alpine from 'alpinejs'
import flatpickr from 'flatpickr'
import { French } from 'flatpickr/dist/l10n/fr.js'
import 'flatpickr/dist/flatpickr.css'

// Fait entrer les images statiques dans le graphe Vite pour qu'elles soient
// buildées et référençables via @asset() côté Blade. Les icônes (resources/images/icons)
// sont exclues : elles sont inlinées directement par x-icon, jamais via @asset().
import.meta.glob(['../images/*.{jpg,jpeg,png,webp,gif,avif}'], { eager: true })

// Formulaire de rendez-vous (tâche 19) : l'état vee-validate de RdvForm.vue
// devient ce petit composant Alpine, l'envoi réel restant un POST classique
// vers admin-post.php géré par ProphetCore\Rdv\SubmitHandler.
Alpine.data('rdvForm', (initial) => ({
  ...initial,
  envoi: false,
}))

window.Alpine = Alpine
Alpine.start()

document.addEventListener('DOMContentLoaded', () => {
  const input = document.querySelector('[data-flatpickr]')
  if (!input) return

  flatpickr(input, {
    locale: French,
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'l j F Y',
    minDate: new Date().fp_incr(1),
    // Pas de rendez-vous le dimanche — même règle que Validator::validate() côté serveur.
    disable: [(date) => date.getDay() === 0],
    // Champ verrouillé (préremplissage événement) : n'ouvre pas le calendrier au clic.
    clickOpens: !input.readOnly,
  })
})

// Pas d'IntersectionObserver ici : le site d'origine ne déclenche aucune animation
// au défilement. `fade-up` y est une simple animation CSS jouée au chargement, sur
// trois éléments seulement (HeroLeft.vue:169, RdvForm.vue:299,
// pages/confirmation.vue:152), et le port la reproduit telle quelle dans
// _hero-left.css, _rdv-form.css et _confirmation.css.
