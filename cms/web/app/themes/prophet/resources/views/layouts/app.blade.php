<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="{{ get_bloginfo('charset') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php(wp_head())
  </head>
  <body @php(body_class())>
    @include('partials.navbar')

    <main id="main">
      @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.floating-whatsapp')

    @php(wp_footer())
  </body>
</html>
