# Scroll-reveal (общий модуль)

Универсальная анимация появления при скролле для **всех** блоков библиотеки.
Каждый блок (`wp-block-library-*`) плавно проявляется снизу вверх (fade-up),
когда попадает в зону видимости. Правка отдельных блоков не нужна.

## Что внутри

- `reveal.js` — IntersectionObserver, навешивается на все блоки автоматически.
- `reveal.css` — скрытое/проявленное состояние.

## Безопасность (почему «без ошибок»)

- **Нет JS / краулеры** — контент виден всегда: скрытое состояние включается
  классом `.reveal-ready`, который ставит сам скрипт. Нет JS → нет скрытия.
- **prefers-reduced-motion** — при «уменьшить движение» анимации полностью нет.
- **Без мигания** — `reveal.css`/`reveal.js` подключаются в `<head>` (не в
  футере), скрытое состояние применяется до первой отрисовки.
- **Вложенные блоки** — анимируются независимо и тоже всегда проявляются.

## Подключение в проект

Скопируй `reveal.js` → `theme/assets/js/reveal.js` и
`reveal.css` → `theme/assets/css/reveal.css`, затем в `functions.php` проекта
внутри хука `wp_enqueue_scripts` добавь (важно: оба в `<head>`, не в футере):

```php
wp_enqueue_style(
    'rb-reveal',
    get_theme_file_uri( '/assets/css/reveal.css' ),
    array(),
    (string) filemtime( get_stylesheet_directory() . '/assets/css/reveal.css' )
);
wp_enqueue_script(
    'rb-reveal',
    get_theme_file_uri( '/assets/js/reveal.js' ),
    array(),
    (string) filemtime( get_stylesheet_directory() . '/assets/js/reveal.js' ),
    false // в <head>, чтобы скрытое состояние применилось до отрисовки
);
```

## Настройка

- Дистанция/длительность/кривая — в `reveal.css` (`translateY`, `700ms`,
  `cubic-bezier`).
- Момент срабатывания — `rootMargin` / `threshold` в `reveal.js`.
- Исключить блок из анимации — добавь ему правило
  `.reveal-ready &.is-revealed, .reveal-ready & { opacity:1; transform:none; }`
  в его `style.scss` или сними у него класс в `reveal.js`.
