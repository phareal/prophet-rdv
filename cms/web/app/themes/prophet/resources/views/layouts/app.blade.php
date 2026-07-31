<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="{{ get_bloginfo('charset') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php(wp_head())
  </head>
  <body @php(body_class())>
    @php(do_action('get_header'))
    <main id="main">
      @yield('content')
    </main>
    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
