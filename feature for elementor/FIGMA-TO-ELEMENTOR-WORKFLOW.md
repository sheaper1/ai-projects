# Figma → Elementor: рабочий процесс проекта

Этот документ описывает проверенный процесс переноса страниц SuessCo из Figma в Elementor. Цель — получить один JSON-файл, который вставляется через **Paste from other site → Ctrl+V**, не зависит от исходного сайта и визуально соответствует Figma.

## 1. Требуемый результат

Для каждой страницы должны быть выполнены все условия:

- Figma используется как главный источник структуры и визуального результата.
- Header и footer не включаются в JSON.
- Все используемые изображения и SVG загружены в медиатеку staging.
- JSON содержит только staging URL и реальные WordPress media ID.
- Стили не зависят от Global Kit исходного сайта.
- Внутренние ссылки не содержат post ID исходного WordPress.
- Desktop, tablet и mobile настройки сохранены.
- JSON корректно вставляется через Elementor Clipboard.
- После вставки страница сравнивается с Figma или эталонной реализацией.

## 2. Источники данных и их приоритет

Использовать источники в таком порядке:

1. **Figma** — канонический список секций, порядок блоков, композиция и визуал.
2. **Существующая Elementor-страница SuessCo** — донор контейнеров, виджетов, текстов, responsive-настроек и custom CSS.
3. **Elementor Kit исходного сайта** — источник глобальных цветов и типографики.
4. **Скриншоты и live-сравнение** — финальная проверка результата.

Важно: существующая WordPress-страница может быть старее Figma. Нельзя слепо копировать её секции. Сначала составляется список секций Figma, затем проверяется, всё ли присутствует в WordPress. Если секция есть в Figma, но отсутствует в WordPress, её нужно найти в других страницах/шаблонах или собрать отдельно.

Пример обнаруженной ошибки: в About Us исходная WordPress-страница не содержала секцию **«Vier konkrete Differenzierer gegenüber generischer Sensorik»**, хотя она была в Figma. Секция была найдена на другой странице, адаптирована и добавлена перед блоком команды.

## 3. Определение нужного Figma-макета

Ссылка Figma может указывать не на отдельный экран, а на большое полотно с десятками страниц.

Порядок действий:

1. Извлечь `fileKey` и `node-id` из URL.
2. Получить screenshot или metadata указанного node.
3. Если node является большим полотном, определить нужный desktop-макет визуально.
4. Найти парный mobile-макет рядом с desktop-версией.
5. Записать полный список секций сверху вниз.
6. Отдельно отметить header/footer и исключить их из результата.

Для SuessCo полезно сверять заголовок hero с заголовками WordPress-страниц. Например:

- `High-Tech Sensoren für Bauwerke` → Home.
- `Sensorik für die Bauwerksüberwachung` → Über uns / About Us.

## 4. Поиск Elementor-донора

Через WordPress REST API исходного сайта получить список страниц и найти страницу по slug/title. Нельзя сохранять учётные данные в документации или новых файлах — используется только уже настроенная авторизация проекта.

Нужные поля страницы:

- `id`
- `slug`
- `title`
- `meta._elementor_data`
- `meta._elementor_page_settings`
- `meta._elementor_edit_mode`

`_elementor_data` обычно является JSON-строкой. После `ConvertFrom-Json` результатом должен быть массив top-level контейнеров.

Проверить:

- количество top-level секций;
- типы виджетов;
- основные заголовки;
- наличие `template` widgets;
- наличие dynamic tags;
- список URL изображений;
- соответствие списка секций Figma.

Если используется `template` widget с `template_id`, его нельзя оставлять как есть: такого ID может не быть на staging. Нужно получить `_elementor_data` шаблона и встроить его контейнеры непосредственно в страницу.

## 5. Форматы Elementor JSON

Elementor использует два разных формата. Их нельзя путать.

### 5.1 Import-файл

Используется через Templates → Import:

```json
{
  "version": "0.4",
  "title": "page-name",
  "type": "page",
  "page_settings": { "hide_title": "yes" },
  "content": []
}
```

### 5.2 Clipboard JSON

Используется через **Paste from other site → Ctrl+V**:

```json
{
  "type": "elementor",
  "siteurl": "https://staging.digirelation.dev/wp-json/",
  "elements": []
}
```

Для текущего процесса нужен именно Clipboard JSON.

Критическая проверка: начало файла должно выглядеть так:

```json
{"type":"elementor","siteurl":"https://staging.digirelation.dev/wp-json/","elements":[{"id":...
```

Неверный вариант:

```json
"elements":{"value":[...]}
```

Такой wrapper Elementor не принимает. `elements` обязан быть массивом напрямую.

## 6. Работа с медиа

### 6.1 Сбор файлов

Рекурсивно собрать URL из:

- `image.url`
- `background_image.url`
- `background_overlay_image.url`
- `selected_icon.value.url`
- gallery/repeater массивов
- HTML в `text-editor` или `html` widgets
- custom widgets

Нельзя ограничиваться только прямыми свойствами `settings`: многие URL находятся во вложенных объектах.

### 6.2 Загрузка

1. Скачать все файлы в отдельную папку страницы.
2. Проверить, какие файлы уже есть на staging.
3. Повторно не загружать существующие файлы.
4. Недостающие файлы загрузить через WordPress Media.
5. Для SVG использовать активный Safe SVG — не конвертировать SVG в PNG без необходимости.
6. Дождаться завершения всей очереди upload, а не только первых файлов.
7. Проверить количество success/error/pending.

### 6.3 Замена ссылок

WordPress может переименовать дубликат:

```text
original.webp → original-1.webp
```

Поэтому нельзя просто менять домен в URL. После загрузки нужно получить фактические:

- `media.id`
- `media.source_url`

Далее для каждого media object:

```json
{
  "url": "https://staging.digirelation.dev/wp-content/uploads/.../actual-name.webp",
  "id": 123,
  "source": "library"
}
```

Для URL внутри HTML заменяется только URL; media ID там отсутствует.

Финальная проверка:

- количество уникальных staging media URL соответствует количеству нужных файлов;
- каждый URL присутствует в staging Media Library;
- ссылок на `suessco.digirelation.dev` не осталось.

## 7. Перенос Global Kit в локальные настройки

Самая частая визуальная проблема: страница вставляется, но использует Global Kit staging. Например, вместо Poppins появляются Calibri, H1 становится 72px вместо 80px, H2 — 56px вместо 64px, кнопки получают radius 8px вместо 50px.

### 7.1 Цвета SuessCo

Основные значения:

```text
Primary:    #14234B
Secondary:  #000E56
Text:       #14234B
Accent:     #1476FF
White:      #FFFFFF
Background: #F7F7F8
```

### 7.2 Типографика SuessCo

Основной шрифт: `Poppins`.

```text
H1: 80px / tablet 48px / mobile 40px / 500 / line-height 1.2
H2: 64px / tablet 48px / mobile 40px / 500 / line-height 1.2
H3: 40px / tablet 34px / mobile 32px / 500 / line-height 1.2
H4: 32px / tablet 28px / mobile 24px / 500 / line-height 1.2
Body: 18px / 400 / line-height 1.5
Letter spacing headings/body: -0.02em
```

### 7.3 Кнопки SuessCo

Default button:

```text
Font: Poppins 18px / 400
Background: #1476FF
Text: #FFFFFF
Radius: 50px
Padding: 18px 40px
Border: 1px solid #FFFFFF40
```

Индивидуальные значения конкретной кнопки имеют приоритет. Например, hero-кнопка может иметь белый background или padding `18px 24px` — такие настройки нельзя перезаписывать default-значениями.

### 7.4 Обработка `__globals__`

Для каждого settings object:

1. Прочитать `__globals__`.
2. Сопоставить `globals/colors?id=...` с цветом исходного Kit.
3. Сопоставить `globals/typography?id=...` с typography object исходного Kit.
4. Записать значения непосредственно в settings.
5. Удалить `__globals__`.

После этого применить missing defaults для элементов, которые полагались не на `__globals__`, а на site-wide defaults:

- heading;
- text-editor;
- button;
- icon-box.

Правило: добавлять только отсутствующее или пустое значение. Существующую индивидуальную настройку не перезаписывать.

## 8. Dynamic links

Elementor может хранить внутреннюю ссылку так:

```text
[elementor-tag ... post_id=1100 ...]
```

Post ID исходного сайта не переносим. Dynamic tag заменяется обычным `link`:

```json
{
  "url": "https://staging.digirelation.dev/sensoren/",
  "is_external": "",
  "nofollow": "",
  "custom_attributes": ""
}
```

После замены удалить `__dynamic__`.

Для SuessCo использовались соответствия:

```text
source post 1100 → /sensoren/
source post 8    → /kontakt/
```

## 9. Header и footer

В Clipboard JSON должны входить только контейнеры самой страницы.

При сравнении live-страниц нужно отличать:

- header Theme Builder;
- footer Theme Builder;
- sticky header spacer;
- фактические top-level контейнеры страницы.

Высота всего документа не подходит как единственный критерий сравнения, потому что header/footer на source и staging могут различаться.

Сравнивать нужно соответствующие page containers по порядку и содержимому.

## 10. Визуальное сравнение

После вставки открыть:

- staging-страницу;
- Figma или исходную live-страницу SuessCo.

Сравнивать при одинаковой ширине viewport.

### 10.1 Что измерять

- количество page sections;
- высоту каждой соответствующей секции;
- font-family;
- font-size;
- font-weight;
- line-height;
- цвет текста;
- button padding/radius/background;
- загрузку изображений;
- background/gradient;
- desktop/tablet/mobile направление контейнеров;
- gap и padding;
- видимость скрытых элементов.

### 10.2 Практический способ

Через DOM собрать computed styles для:

- `h1, h2, h3`;
- `.elementor-widget-text-editor`;
- `.elementor-button`;
- `.elementor-widget-icon-box`;
- top-level `.elementor-element` контейнеров.

Сравнивать не только screenshot, но и фактические CSS-значения. Скриншот показывает проблему, computed styles объясняют причину.

Пример обнаруженного расхождения About Us:

```text
Staging: Calibri, H1 72px, H2 56px, radius 8px
Design:  Poppins, H1 80px, H2 64px, radius 50px
```

## 11. Проверка JSON перед выдачей

Обязательный checklist:

- [ ] JSON успешно проходит `ConvertFrom-Json`.
- [ ] `type` равен `elementor`.
- [ ] `siteurl` равен staging `/wp-json/`.
- [ ] `elements` является массивом, не `elements.value`.
- [ ] Количество секций соответствует Figma.
- [ ] Все element IDs имеют 7–8 hex-символов.
- [ ] Дубликатов element ID нет.
- [ ] Все media URL ведут на staging.
- [ ] Все media URL существуют в Media Library.
- [ ] Реальные media ID записаны в JSON.
- [ ] Ссылок на исходный домен нет.
- [ ] `template` widgets с внешними ID отсутствуют.
- [ ] `__globals__` отсутствуют.
- [ ] dynamic Elementor tags отсутствуют.
- [ ] Немецкий текст корректен в UTF-8.
- [ ] Header/footer отсутствуют.
- [ ] Desktop/tablet/mobile настройки сохранены.
- [ ] Визуальное сравнение выполнено.

## 12. UTF-8 на Windows

PowerShell 5 может прочитать UTF-8 без BOM как ANSI и превратить:

```text
für → fГјr
```

Всегда использовать:

```powershell
Get-Content -Raw -Encoding UTF8
```

Записывать UTF-8 без BOM:

```powershell
[IO.File]::WriteAllText($path, $json, (New-Object Text.UTF8Encoding($false)))
```

После записи проверять точную строку с немецкими символами, а не только успешный JSON parse.

## 13. Известные ошибки и причины

### Elementor показывает сообщение про версии/features

Вероятная причина: page-import JSON вставляется через Paste from other site или `elements` имеет неправильную форму.

Исправление: использовать Clipboard wrapper и проверить, что `elements` — прямой массив.

### Страница выглядит похоже, но шрифты и размеры отличаются

Причина: элементы наследуют staging Global Kit.

Исправление: развернуть source globals и добавить missing defaults непосредственно в widgets.

### Часть секций отсутствует

Причина: WordPress-донор старее Figma.

Исправление: сравнить section inventory с Figma и найти отсутствующий блок в других страницах/шаблонах.

### SVG не загружается

Причина: WordPress по умолчанию запрещает SVG MIME type.

Исправление: использовать активный Safe SVG и повторить загрузку оригинальных SVG.

### Изображение имеет неправильный URL после upload

Причина: WordPress добавил suffix `-1`, `-2` и т. п.

Исправление: получать реальный `source_url` через Media REST, а не конструировать URL вручную.

### В JSON остались template IDs

Причина: экспорт содержит Elementor `template` widget.

Исправление: получить данные шаблона и встроить его top-level контейнеры.

## 14. Структура файлов проекта

Рекомендуемая схема:

```text
AGENTS.md
FIGMA-TO-ELEMENTOR-WORKFLOW.md
suessco-home-assets/
suessco-home-elementor-clipboard.json
suessco-about-assets/
suessco-about-us-elementor-clipboard.json
```

Для новой страницы использовать отдельную папку assets и отдельный Clipboard JSON.

## 15. Порядок выполнения новой страницы

Краткий алгоритм:

1. Открыть Figma и определить точный desktop/mobile макет.
2. Записать полный список секций.
3. Найти страницу-донор в исходном WordPress.
4. Сравнить секции донора с Figma.
5. Встроить внешние Elementor templates.
6. Найти и добавить отсутствующие Figma-секции.
7. Собрать все media URL рекурсивно.
8. Скачать полный набор assets.
9. Переиспользовать существующие staging media.
10. Загрузить недостающие файлы.
11. Получить реальные media ID/source URL.
12. Переписать media objects и HTML URL.
13. Развернуть Global Kit в локальные styles.
14. Заменить dynamic post links обычными URL.
15. Удалить header/footer/template dependencies.
16. Создать Clipboard wrapper.
17. Проверить структуру, ID, URL и UTF-8.
18. Вставить на тестовую страницу.
19. Сравнить computed styles и секции с эталоном.
20. Исправить только подтверждённые расхождения.

## 16. Критерий готовности

Страница готова только тогда, когда:

- все секции Figma присутствуют;
- изображения загружаются со staging;
- ключевые computed styles совпадают с дизайном;
- JSON вставляется через Ctrl+V без ошибки;
- отсутствуют зависимости от исходного WordPress;
- проверены desktop и mobile варианты.

