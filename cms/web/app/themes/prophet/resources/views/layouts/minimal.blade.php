{{--
  Layout minimal, sans le chrome de la page d'accueil.

  Aucun `layouts/` n'existe dans l'arbre Nuxt : seule pages/index.vue:15-30
  montait TheNavbar / AppFooter / FloatingWhatsApp. pages/rdv.vue:14-31 et
  pages/confirmation.vue:24 ne rendaient que leur propre `<div>` racine sur le
  fond sombre #080D1A, avec leur propre lien de retour (.rdv-back) pour la
  page /rdv/. layouts/app.blade.php, qui inclut inconditionnellement
  partials.navbar / partials.footer / partials.floating-whatsapp, habillait
  donc à tort ces deux pages du chrome clair de la home — la bulle WhatsApp
  flottante en particulier recouvrait le CTA du formulaire. template-rdv et
  template-confirmation étendent ce layout-ci à la place de layouts.app.
--}}
<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="{{ get_bloginfo('charset') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php(wp_head())
  </head>
  <body @php(body_class())>
    <main id="main">
      @yield('content')
    </main>

    @php(wp_footer())
  </body>
</html>
