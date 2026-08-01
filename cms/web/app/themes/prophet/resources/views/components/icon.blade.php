@props(['name', 'class' => '', 'size' => 24])

@php
    $chemin = get_theme_file_path("resources/images/icons/{$name}.svg");
    $svg = is_readable($chemin) ? file_get_contents($chemin) : '';

    if ($svg !== '') {
        $svg = str_replace(
            '<svg',
            sprintf('<svg class="%s" width="%d" height="%d" aria-hidden="true" focusable="false"',
                esc_attr($class), (int) $size, (int) $size),
            $svg
        );
    }
@endphp

{!! $svg !!}
