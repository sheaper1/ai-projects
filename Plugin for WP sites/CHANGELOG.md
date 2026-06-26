# CHANGELOG

Человекочитаемый журнал работы параллельно с git. **Каждый ИИ дописывает строку
после завершённой задачи** (перед `git push`). Новые записи — сверху.

- [Claude] 2026-06-26 — **Block-first QA: группа /ueber-mich/ (мульти-агент).** Запустил
  `qa-page-parallel` по 6 блокам страницы. Итог после разбора (часть находок —
  следствие моей ошибки в маппинге node→блок, эталон порядка = сидер `scripts/pages/uber-mich.mjs`):
  • **promise-list, value-cards** — прошли, отмечены `qa-state pass` (value-cards: добавил
    `<br>` в заголовки карточек 1/2 в сидере → 2 строки как в Figma, ре-сид).
  • **bio-hero** (инсет текста px→vw, кредит через `<br>`/wp_kses) и **quote-cover**
    (вес 300→400) — правки по верным нодам приняты; остались дефекты на доводку
    (bio-hero вертикальное прижатие, quote-cover мобайл: r-40 флюидный→32px, min-height).
  • **founder-bio** — агент по МОЕЙ неверной ноде (3050=consultation-cta) переписал блок;
    откатил (`git restore`), его верный эталон — 2009:2991, переделки нет.
  • **founder-story** — в Figma-фрейме нет его дизайна (кастомная секция), агент верно не трогал.
  Также: verify-агенты в воркфлоу теперь пишут overlay/live/figma по фикс-путям и отдают
  в `artifacts` — оркестратор сам сверяет картинки.

- [Claude] 2026-06-26 — **/ueber-mich: добивка хвостов (seeder-first маппинг).** Свёрил
  блоки с верными нодами по сидеру. **founder-bio** оказался уже корректным (нода 2991:
  заголовок body/r-40/400, абзацы Light/muted, фото 47.3%) → pass без правок. **bio-hero**:
  поднял нижний инсет текст-блока 48px→8.6vw (Figma: низ на 124px от низа кадра) → pass.
  **quote-cover**: мобильная min-height 320→400 (Figma mobile 400). Pass: bio-hero,
  founder-bio (всего по /ueber-mich: bio-hero, founder-bio, promise-list, value-cards).
  Открытые хвосты (системные, не хардкодить): quote-cover мобильный шрифт (токен r-40
  флюид→32px vs дизайн 40px), мобильный gutter vw vs 16px, quote-cover bg jpg→webp.
  founder-story оставлен как есть (нет дизайна в Figma-фрейме — кастомная секция).

- [Claude] 2026-06-26 — **testimonials: нижний отступ секции.** На главной точки
  пагинации прилипали к нижней кромке surface-полосы (низ был `0`, 150px добавлялись
  только перед `consultation-cta`). Сверка с Figma (`Home 1440` → `Frame 27` 2009:2506,
  высота 810: точки на y=652+8 → под ними 150px, симметрично верху). Сделал отступ
  симметричным (`padding` снизу = `section-space`), убрал ставший лишним `:has`-спецслучай.
  Собрано, задеплоено, проверено на staging — точки теперь с воздухом снизу.

- [Claude] 2026-06-26 — **References: нативный CPT вместо PropStack (добивка по спеке клиента).**
  Базовый CPT `reference` уже был (таксономии `reference-type`/`reference-city`,
  динамические мета-поля, шаблоны single/archive, каталог с AJAX-фильтром Typ+Lage+Sort,
  условный рендер пустых полей в property-stats для референсов). Закрыл 2 пробела
  спеки: (1) **фиксированные термины Objektart** Haus/Wohnung/Grundstück/Gewerbe —
  идемпотентный сидер в reference-cpt.php (клиент видит типы сразу, без разработчика;
  Lage остаётся открытой таксономией); (2) **блок «Ähnliche Referenzen»**
  (`rosenberger/reference-related`) на single — до 3 других референсов по той же
  Objektart ИЛИ Lage, добор свежими, карточки = общая rosenberger_rc_card_html.
  Проверено на staging: 4 термина созданы, Grundstück-сингл показывает только
  Grundstücksfläche (Stock/Bad скрыты, без «0»/пустых строк), related рендерит 3 карты.
- [Claude] 2026-06-26 — **immobilienbewertung: добивка глазами (находки пользователя).**
  Пользователь нашёл банальное, что агенты пропустили/отмахнулись. Починил сам по
  скриншотам: problem-cards — текст прижат к низу карточки (`margin-top:auto`; агент
  видел зазор 109px и отмахнулся); about — убран фикс `min-height:218px`, высота от
  контента + grid-stretch (на immobilienbewertung карточки зияли пустотой; главная
  проверена — ок); testimonials — `limit:3→0` (все отзывы) → карусель реально
  листается с точками (была статика 3-в-ряд). split-cta оказался ок (абзацы уже
  починил wpautop). Урок: агенты-верифик подтверждают «matches» на визуально
  сломанном — финальный глаз обязателен, «pass» на веру не принимать.
- [Claude] 2026-06-26 — **QA immobilienbewertung: 5 FULL-блоков мультиагентом (qa-page-parallel).**
  Воркфлоу qa-page-parallel (5 фикс ∥ → деплой → 5 верифик ∥). Подтверждены:
  page-hero (зазоры заголовок↔подзаголовок 32→16, отступ CTA, min-width кнопки,
  моб. высота фото), split-cta (абзацы через wpautop, моб. порядок текст↑/фото↓,
  моб. пропорция 648/733, паддинг). problem-cards: `<br>` заголовков карточек —
  правка в сидере (\\n→<br> для «Eine Spanne»/«Keine Verpflichtung»), проверено
  lines=2/br=1. testimonials: код (прямые углы, тень, <br>) задеплоен — финальный
  глаз. tipper-form: стилизация задеплоена, но чипы/логика воронки (funnel.html:
  Haus/Wohnung/Gewerbe/Liegenschaft + авто-переход vs Figma Wohnung/Haus/Grundstück
  + «Weiter») — продуктовое решение, не стайлинг. qa-state: page-hero/split-cta/
  problem-cards пройдены. Воркфлоу звать по scriptPath после правок (вызов по name
  кэширует старую версию). ~764k токенов/прогон.
- [Claude] 2026-06-26 — **Главная: добавлена секция Google-отзывов + переиспользуемый qa-page-parallel.**
  В import-homepage добавлен блок `library/testimonials` (после about) — рендерит 6
  реальных Google-отзывов через grw (карусель, звёзды, лого). Мульти-агентный QA
  зафиксирован как именованный воркфлоу `.claude/workflows/qa-page-parallel.js`
  (фикс ∥ → деплой → верифик ∥, args=sections) + документация в скилле qa-page +
  память. Зафиксировано «property-catalog (Aktuelle Objekte) — намеренно не нужна».
  Region-картинки: выяснил, что в дизайне это плейсхолдеры (Bregenz = копия Feldkirch),
  живые реальные фото лучше — решение по кадрированию за владельцем.
- [Claude] 2026-06-26 — **QA главной: about/process-steps/faq/referral (воркфлоу + добивка).**
  Workflow (4 фикс-агента ∥ → деплой → 4 верифик-агента ∥): about/referral чисты;
  process-steps — веса h3 500→600, разделитель под шагом 4 убран, `<br>` подзаголовка;
  faq — render `esc_html→wp_kses` под `<br>`. Добивка мной (дёшево, без агентов):
  `<br>` в дефолтах about-карточек («Ehrliche<br>Bewertung» и т.д.) и faq Q2/Q3 —
  переносы как в Figma (проверено: lines=2/br=1). Контент этих блоков идёт из
  block.json-дефолтов (на главной они без атрибутов). process-steps spacing НЕ
  трогал: проверил дизайн — шаг pitch 125/высота 61 = зазор ~64px ≈ живые 62px,
  рекомендация агента «gap-32» была ошибочной. qa-state: 4 секции пройдены.
- [Claude] 2026-06-26 — **QA главной: 5 секций мультиагентом (фикс) + верификация.**
  5 параллельных агентов, каждый правил свой блок: pain-points (заголовок 550→511,
  колонка 648→632 → текст 552), cards-stack (body 470→518 → текст 422), region-grid
  (вес заголовка 500→400), consultation-cta (вес 300→400), value-cards
  (esc_html→wp_kses_post + превью под <br>). Build+deploy — последовательно мной.
  Верификация по задеплоенному сайту (числа+наложение, desktop+mobile): pain-points,
  cards-stack, consultation-cta — подтверждены, qa-state pass. region-grid вес 400
  подтверждён, но Bregenz — другой ассет (контент, на решение). value-cards: код
  готов, но <br> живёт в контенте — добавить в сидер. Урок: первый замер region-grid
  словил кэш (500), пересверка дала 400 — одному замеру не доверять.
- [Claude] 2026-06-26 — **Trust-bar: айтемы под дизайн (QA секции 2/11).**
  `.trust-bar__items` были `heading`-шрифт / вес 600 / gap 32; дизайн —
  Roboto Flex Medium (body) / 500 / gap 24. Поправил все три → вес 500, шрифт body,
  gap r-24. Проверено: числа (500, Roboto Flex, 18) + наложение desktop (айтемы
  совмещаются), mobile = только Google-рейтинг как в макете. Общий блок → чинится
  на всех страницах с trust-bar. qa-state: trust-bar пройден.
- [Claude] 2026-06-26 — **Hero главной: vw-отступ + object-position (QA секции 1/11).**
  Боковой отступ Hero был `var(gutter)` с cap 48px → на 1920 не масштабировался
  (leftPct 3.3%→2.5%). Сделал чистый `3.333vw` (mobile явно 16px), убрал осиротевший
  `--hc-pad-x`. Заодно `object-position: center`→`bottom` (код противоречил комменту
  и макету object-bottom). Проверено: leftPct 3.3% на 1440 и 1920, mobile 16px,
  деплой. Library не синхронил — там другой форк без бага (правило #14). qa-state:
  hero-cover отмечен пройденным → следующий прогон SIMPLE. Также в qa-page дописана
  обязательная проверка mobile (desktop+mobile на каждую секцию).
- [Claude] 2026-06-26 — **Оркестратор qa-page + кэш состояния QA (флоу пользователя).**
  Скилл `qa-page`: 1) инвентаризация секций (есть/нет vs Figma), 2) недостающие
  РЕАЛЬНЫЕ секции → подтверждение → стандартная разработка (build-page), 3) QA по
  секциям — полный или упрощённый. `scripts/qa-state.mjs` хранит «прошло» по хэшу
  (исходник блока + theme.json): неизменный общий блок → упрощённый QA (без
  глобального), правка токена/блока → хэш слетел → полный заново. Глобальное (линт,
  токены, вес, hover) кэшируется; локальное (контент, Figma-нода страницы) гоняется
  всегда. Проверено: check/pass/SIMPLE + реакция на theme.json. См. [[qa-page-orchestrator]].
- [Claude] 2026-06-26 — **Новый скилл section-overlay: наложение секции дизайн↔лайв.**
  `scripts/section-diff.mjs` + скилл: совмещает живую секцию и Figma-ноду в один
  кадр — канальное наложение (Figma=пурпур, лайв=зелёный, совпало=серое) + diff +
  рядом. Идея пользователя. Проверено на pain-points: сдвиг переноса заголовка и
  дрейф ритма правой колонки видно глазом, а не «+39px». Дополняет числовой visual-qa.
- [Claude] 2026-06-26 — **visual-qa: движок ловит выброс веса ЗАГОЛОВКОВ (новый детектор).**
  qa-compare теперь считает доминирующий вес крупных h1–h3 и флагует выбивающиеся.
  Раньше вес проверялся только у `<p>` — заголовки пропускались. На главной поймал
  сам: consultation-cta heading 300 и region-grid title 500 при сиблингах 400. Это
  закрывает класс «хардкод font-weight в SCSS блока», невидимый body-проверке.
- [Claude] 2026-06-26 — **visual-qa: shot.mjs прокручивает страницу перед съёмкой (Phase 2 чинит lazy-фоны).**
  Без прокрутки секции ниже первого экрана снимались ПУСТЫМИ (scroll-reveal opacity:0
  + lazy-фоны не грузятся) — Phase-2-снимок региона был серым, картинки не сверить.
  Теперь shot гоняет страницу до низа и назад. Доказано на главной: после фикса
  карточки региона отрендерились, видно что нижняя-правая картинка ≠ дизайн.
- [Claude] 2026-06-26 — **visual-qa: движок не флагует вес Hero ложно + урок про полный эталон.**
  qa-compare исключает hero-зону (y<высоты hero) из проверки веса тела: hero-копия
  по дизайну Medium/500, не body-300 — был ложный выброс. Проверено на главной:
  полный эталон Figma (а не урезанный руками) поднял реальные находки — ширина
  карточек cards-stack (422→374), все описания pain-points (+16), заголовок (+39).
  Урок «эталон сохранять вербатим, свёрнутые инстансы = слепая зона» зашит в
  SKILL.md и память [[visual-qa-full-reference]]. Картинки/центрирование — Фаза 2.
- [Claude] 2026-06-26 — **QA-гейт: структурный линтер блоков + критик (по research-отчёту).**
  Слой 1: `scripts/lint-blocks.mjs` (`npm run lint:blocks`) — динамический save (`()=>null`/
  `InnerBlocks.Content`), `import './style.scss'`, валидный block.json (`render`+`html:false`);
  варнинги: inline-svg/png/карусель без RbCarousel. Встроен в `npm run check` → попадает в
  pre-push. Прогон на 75 блоках: 0 ошибок, 21 варнинг (существующий код не трогал). Слой 3:
  read-only критик `block-reviewer` + команда/скилл `/review-block` + `REVIEW.md` (судейские
  правила: переносы `<br>`, значения из Figma, hover, адаптив, иконки/фото). Слой 2 (Claude-хуки
  `scripts/hook-lint.mjs`) — установка в `.claude/settings.local.json` ждёт согласия пользователя.
  `.claude/` в git не идёт → детерминизм в `scripts/` (коммит), критик/хуки per-machine. См. [[qa-gate-layers]].
- [Claude] 2026-06-25 — **Propstack: стили объекта под Rosenberger (вариант B) + конвейер проверен.**
  Создан тестовый объект в Propstack (Höchsterstraße 24, Dornbirn, Vermarktung, 450k, 85m², 3 Zi),
  синкнут и опубликован на staging. Попутно фикс: «Öffentliche Status-IDs» плагина были `1,2`
  (заглушка) — реальный Vermarktung=`243237`, без правки все объекты висели бы черновиками;
  поставил `243237,243238`. Стили БЕЗ правок плагина: тема перехватывает чтение `property_*` у
  постов `propstack_property` фильтром `get_post_metadata` (`inc/propstack-bridge.php`) и отдаёт
  значения из плагинных `_property_*` в нужном формате; блочный шаблон `single-propstack_property.html`
  переиспользует наши блоки property-hero/stats/overview/gallery/details/location. Объект рендерится
  в полном дизайне Rosenberger (проверено в браузере). Зазоры: нет фото у тест-объекта (пустой
  hero/галерея), Objektart/Kategorie из `/units` приходят пустыми (мелкий маппинг). См. [[propstack-integration-state]].
- [Claude] 2026-06-25 — **Propstack: интеграция плагина-синка (v1 от Alex) в проект.**
  Плагин `propstack-real-estate` (sync объектов из Propstack CRM: CPT, крон, импорт фото,
  лиды обратно, вебхуки) добавлен в `projects/rosenberger/plugin/`. Установлен и активен на
  staging (спит без API-ключа). Решение: тело плагина не трогаем (вариант B — стили под
  Rosenberger через theme-override, после первого синка). NB: при первой установке через
  одноразовый Code-сниппет (`deploy-propstack.mjs`) сайт ушёл в 500 — виноват не плагин, а
  тяжёлый ГЛОБАЛЬНЫЙ сниппет (~270 КБ, переписывал 33 файла + activate_plugin на каждом
  запросе). Восстановили (rename папки в Plesk + удаление сниппета #88 в phpMyAdmin), сниппет-
  установщик удалён из репо. Плагин ставить штатно (zip/WP-CLI), НЕ глобальным сниппетом — на
  PHP 8.3 он активируется чисто. Дальше: ввод API-ключа владельцем → первый синк → стили.
- [Claude] 2026-06-25 — **DSGVO: установлен Complianz (cookie-consent для AT/DE).**
  `scripts/setup-complianz.mjs` ставит и активирует плагин `complianz-gdpr` одноразовым
  Code Snippet (фазы: установка из wp.org → активация → регион EU). Плагин активен. Баннер
  на фронте появится после мастера в WP-админке (Complianz сам генерит Datenschutz/Cookie-
  доки и требует подтверждения региона — это шаг владельца, юр-тексты утверждает он).
  Мой гейт карты уже слушает события согласия Complianz (`cmplz_event_marketing` / WP Consent
  API), так что после мастера карта подхватит согласие автоматически. NB: фактические утечки
  (шрифты, карта) уже закрыты до баннера — без согласия трекинга на сайте нет.
- [Claude] 2026-06-25 — **DSGVO: карта (Leaflet/OSM) под согласие + локальный Leaflet.**
  Карта на /kontakt/ и в объектах тянула Leaflet с `unpkg.com` + плитки OpenStreetMap +
  Nominatim до всякого согласия — нарушение. Сделал общий гейт `assets/js/rb-map.js`
  (`window.RbMap.gate`): до согласия рисует немецкий плейсхолдер «Zum Schutz Ihrer Daten …
  Karte laden» + ссылку на Datenschutz (стили `assets/css/rb-map.css`, токены), карта
  грузится только по клику ИЛИ по согласию через WP Consent API (`wp_has_consent`,
  `wp_listen_for_consent_change`) / событие Complianz `cmplz_event_marketing` (категория
  `marketing`). Сам Leaflet 1.9.4 самохостится (`assets/vendor/leaflet/`, убран unpkg).
  `contact-section` и `property-location` view.js переведены на гейт. Проверено: на /kontakt/
  0 обращений к unpkg, плейсхолдер рендерится, leaflet локальный 200.
- [Claude] 2026-06-25 — **DSGVO: самохостинг шрифтов (убран Google CDN).** Главный риск для
  AT/DE — передача IP в Google Fonts без согласия. Перевёл Nunito Sans + Roboto Flex на
  локальные woff2: `scripts/selfhost-fonts.mjs` качает нужные подмножества (latin + latin-ext,
  18 файлов ~516К) и генерит `assets/css/fonts.css` с `@font-face`. `functions.php` теперь
  подключает локальный `fonts.css` вместо `fonts.googleapis.com`. Убрал ещё одну утечку —
  Google-`@import` в `hero-cover/src/style.scss` (тема + эталон library). Проверено: на
  главной 0 обращений к googleapis/gstatic, woff2 отдаётся 200 (font/woff2). Шаг к запуску
  без cookie-согласия для шрифтов.
- [Claude] 2026-06-25 — **Favicon заменён на официальный знак бренда.** Пользователь дал
  готовые иконки (`favicon/w256-Icon-dark.svg` #142335 / `-beige.svg` #DECEC4). Пересобрал
  `assets/favicon.svg` из их путей (отцентрован в 256×256), переключение по теме оставил:
  навигационный `#142335` на светлой, бежевый `#DECEC4` на тёмной. Задеплоено, проверено 200.
- [Claude] 2026-06-25 — **Favicon бренда с переключением по теме (светлая/тёмная).** Раньше
  favicon не было вовсе. Сделал `assets/favicon.svg` из монограммы логотипа (знак «RO»,
  paths из `rosenberger-logo.svg`, отцентрован в 48×48). Цвет переключается сам по теме ОС
  через `@media (prefers-color-scheme: dark)` ВНУТРИ SVG: тёмно-синий `#142335` на светлой,
  белый `#ffffff` на тёмной — один файл, надёжнее двух `<link media>` (Safari капризничает).
  Подключён в `<head>` через `wp_head` в `functions.php` (перебивает дефолт WP). Проверено:
  тег в head, SVG 200 (image/svg+xml), media-правило на месте.
- [Claude] 2026-06-25 — **Фикс битой ссылки на регион-страницах + slug Über mich.** Аудит:
  CTA «Mehr über mich» на 4 регион-страницах вёл на `/uber-mich/` → 404 (живая страница —
  `/ueber-mich/`, id 62). Поправил `buttonUrl` в `seed-regions.mjs`, перезалил регионы —
  все 4 теперь ведут на `/ueber-mich/` (200). Заодно убрал мину: `import-uber-mich.mjs` и
  `scripts/pages/uber-mich.mjs` создавали slug `uber-mich` (разошёлся бы с живым → дубль) —
  привёл к `ueber-mich`. Проверено: ссылки feldkirch/bregenz/dornbirn/bludenz → 200.
- [Claude] 2026-06-25 — **Tippgeber: правильная 3-шаговая форма по дизайну (новый блок `tip-form`).**
  Раньше на Tippgeber стоял общий демо-фаннел оценки — не совпадал с макетом. Сделал
  отдельный блок `library/tip-form` строго по Figma (`2009:4608`): степпер ① Das Objekt
  (Adresse + Objektart) → ② Die Situation (Bezug + Текст) → ③ Ihre Daten (Anrede/Vorname/
  Nachname/Email/Telefon). Шаг 1 один-в-один по макету, шаги 2-3 спроектированы разумно
  (в Figma не было). Фронт-JS в `view.js` (статикой, не инлайн — без граблей wptexturize):
  переключение шагов + валидация + мост к скрытой WPForms `tippgeber` (контакты по полям,
  сводка шагов 1-2 → Objektangaben). Стили на токенах, мобилка проверена. Фикс: `[hidden]`
  не скрывал панели (мой `display:flex` перебивал) — добавил `.tip-form__panel[hidden]{display:none}`.
  E2E (`scripts/test-tipform.mjs`): 3 шага → submit → редирект /danke/. **Immobilienbewertung
  оставлена на фаннеле** (по решению — дизайн тот же, смысл оценки подходит).
- [Claude] 2026-06-25 — **Immobilienbewertung: многошаговая форма (переиспользован tipper-form).**
  На странице форма была отложена (заглушка consultation-cta) — теперь там реальный фаннел
  «Ihre kostenlose Bewertung anfragen» (нода Figma `2009:5527`). Блок `tipper-form`
  параметризован атрибутом `formSlug` (default `tippgeber`); render.php ищет форму по нему.
  Своя WPForms-форма `bewertung` (`scripts/setup-bewertung-form.mjs`, subject «Neue
  Bewertungsanfrage», редирект `/danke/`) — лиды Bewertung отделены от Tippgeber. Тексты из
  Figma. E2E на `/immobilienbewertung/` проходит (фаннел→submit→/danke/, форма id 189).
  **Мобилка обеих форм проверена** (375px): шаг-выбор и контактный шаг — карточки/поля в одну
  колонку, Weiter на всю ширину, без переполнений (`scripts/shot-funnel-mobile.mjs`).
  ВНИМАНИЕ: e2e/мобил-тесты создают реальные лиды (qa-tipp@example.com) на обеих формах.
- [Claude] 2026-06-25 — **Tippgeber: многошаговый фаннел подключён к WPForms (работает).**
  Раньше фаннел «Tipp einsenden» был демо без отправки (`WPF.formId=null`). Теперь:
  (1) `scripts/setup-tipper-form.mjs` создаёт форму WPForms `tippgeber` (Anrede, Vorname,
  Nachname, Email[req], Telefon, PLZ + textarea «Objektangaben») одноразовым Code Snippet,
  письмо на office-email, подтверждение = редирект `/danke/`. (2) `render.php` авто-находит
  форму по slug (как contact-section), инжектит `window.RB_TIPPER={formId,field}` и скрытую
  `[wpforms]`. (3) `funnel.html` читает инжектнутый мост; все ответы опросника собираются в
  читаемую сводку → в textarea, контактные поля — по отдельности. Грабли: `the_content`
  (wptexturize) энтити-кодировал ` && ` с пробелами внутри инлайн-скрипта (HTML-строки в JS
  путают токенайзер) → JS падал. Убрал пробельный `&&` из своей строки. E2E
  (`scripts/test-tipper.mjs`) проходит: фаннел → контактный шаг → submit → редирект `/danke/`.
  ВНИМАНИЕ: тест создаёт реальный лид (qa-tipp@example.com) и письмо на office.
- [Claude] 2026-06-25 — **Аудит сайта + фиксы по итогам.** Прошёлся по всем 11 страницам
  (desktop+mobile). Найдено и исправлено: (1) `/impressum/` отдавал 404 — создал страницу
  со стандартным плейсхолдер-Impressum (AT: §5 ECG/§14 UGB); (2) `/datenschutz/` существовала,
  но контент пустой — наполнил стандартной плейсхолдер-Datenschutzerklärung (DSGVO). Обе на
  core-блоках (group constrained + heading/paragraph), верхний отступ 160px под fixed-шапку,
  скрипт `scripts/import-legal.mjs`. (3) Карточка региона **Bregenz** показывала фото Feldkirch
  (`region-bregenz.webp` не был залит) — залил реальное фото из `media/regions/hero-bregenz.webp`,
  поправил `import-homepage.mjs` (Bregenz больше не подменяется Feldkirch). Попутно починил
  пред­существующий TDZ-баг в `import-homepage.mjs` (`getMedia` звался до объявления — скрипт
  падал). Проверено: оба URL 200 + контент, region-bregenz.webp 200, сетка регионов
  feldkirch/bludenz/dornbirn/bregenz. Открытым остаётся текст 5-го пункта Pain Points «Übergang»
  (дубль-плейсхолдер, нужен реальный текст от клиента).
- [Claude] 2026-06-25 — **Фикс: белая полоса между секциями (blockGap).** На /immobilie-vermieten/
  между отзывами и FAQ (обе на surface-фоне) просвечивала белая полоса — 24px blockGap
  корневого flow-layout показывал белый фон body. Глобально убрал margin-block-start у
  полноширинных секций в `.entry-content.is-layout-flow` (`wp-block-library-*` /
  `wp-block-rosenberger-*`) — каждая секция держит свои отступы сама. Зазор стал 0,
  секции вплотную; проверено в браузере. Тот же приём, что уже был для подвала.
- [Claude] 2026-06-25 — **Меню: шапка не затемняется + навигация по всем страницам + футер.**
  (1) Скрим меню вынесен в `.site-menu::before` с `top:120px` (моб. 84px) — затемнение
  больше НЕ заходит на шапку, логотип остаётся чистым (контент под шапкой затемняется как
  прежде). (2) Ссылки меню и футера привязаны к реальным URL: Verkauf→/immobilie-verkaufen/,
  Bewertung→/immobilienbewertung/, Vermietung→/immobilie-vermieten/, Aktuelle Objekte→
  /alle-immobilien/, Referenzen→/references/, регионы→/region/{feldkirch,dornbirn,bregenz,
  bludenz}/ (было /verkauf/, /bewertung/, /feldkirch/ и т.п. — 404). (3) «Standorte» в меню —
  группа-категория с 4 регион-ссылками. Все nav-URL отвечают 200 (кроме /impressum/ — страницы
  ещё нет). Задеплоено, открытое меню проверено в браузере: шапка чистая, Standorte на месте.
- [Claude] 2026-06-25 — **Фикс z-index: меню проваливалось под Leaflet-карту.** На /kontakt/
  открытое мобильное/бургер-меню рисовалось ПОД картой — у Leaflet свои слои/контролы с
  z-index до 1000, а у `.site-menu` было 200, у `.site-header` — 100. Поднял `.site-header`
  → 10000, `.site-menu` → 100000 (выше всех слоёв Leaflet). Проверено: панель меню теперь
  целиком поверх карты.
- [Claude] 2026-06-25 — **Контактная форма переведена на Tippgeber-принцип (мост к WPForms).**
  По просьбе: вместо стилизации вывода WPForms — СВОЯ кастомная форма точно по дизайну
  (label 14px contrast@.4, инпут 48px бордер `--wp--custom--field--border`, телефон с
  флагом-адорнментом 🇦🇹+caret, select со своей стрелкой, textarea 154, submit-пилюля),
  а рядом скрытая (off-screen) настоящая WPForms-форма. `view.js`-мост (как `tipper-form`):
  на submit заполняет скрытые поля по field-ID (`wpforms-{id}-field_{n}`, маппинг
  name/email/phone/subject/message строится в render.php из `wpforms_decode`) и кликает
  нативный `wpforms-submit-{id}` → WPForms валидирует, шлёт письмо и редиректит на /danke/.
  Преимущество: полный контроль разметки/стилей под Figma, заявки всё равно в админке WP.
  Пересобрано/задеплоено, мост проверен структурно (скрытая форма + field_1..5 + submit на
  месте), desktop+mobile сверены с макетом. Готово.
- [Claude] 2026-06-25 — **Контактная страница `/kontakt/` (WPForms) по Figma.** Новый блок
  `contact-section`: слева заголовок «*Jetzt Kontakt* aufnehmen» + лид + WPForms-форма
  (Name/Email/Phone-Smart с флагом/Subject-select/Nachricht/«JETZT ANFRAGEN»), стилизована
  под макет (label 14px contrast@.4, инпут 48px бордер токен `--wp--custom--field--border`
  #e4e4e4, submit — тёмная пилюля). Справа Leaflet-карта (OSM, красный divIcon-пин,
  переиспользован паттерн `property-location`) + карточка «Contact Information» с контактами
  из настроек сайта (`rosenberger_contact`). Форма создаётся программно одноразовым Code
  Snippet (`scripts/setup-contact-form.mjs`, идемпотентно по slug `kontakt`): поля,
  уведомление на office-email, подтверждение = редирект на `/danke/`. Блок находит форму по
  slug автоматически (formId=0). Страница — `scripts/import-kontakt.mjs`; `has-light-hero`
  + отступ под фикс-шапку. Собрано, задеплоено, форма создана, страница сверена desktop+mobile
  (совпадает; форма рабочая: submit → письмо → /danke/). Готово.
- [Claude] 2026-06-25 — **Страницы 404 + Danke (thank you) по Figma.** Два новых блока:
  `error-404` (заголовок «Seite / nicht gefunden» 80px + лид + кнопка, ниже full-width
  фото здания с гигантским курсивным «404»; фото-webp из Figma в медиа по slug
  `rosenberger-404-building`, резолв в render.php) → шаблон `templates/404.html`; и
  `thank-you` (центрированный «Vielen Dank / für Ihre Anfrage!» + лид + кнопка, ниже
  surface-полоса с 3 карточками-шагами на репитере: иконка 64 + заголовок + текст,
  accent-бордер, общие границы) → страница `/danke/` через `scripts/import-danke.mjs`.
  3 иконки (конверт@, телефон+письмо, дом+лупа) — SVG из Figma через `figma:icon` в
  медиа. Значения программно из Figma, токены через `npm run tokens` (contrast/muted/
  surface/accent, r-80/24/18/32/16). Грабли: первый светлый блок прятал заголовок под
  фикс-шапку (120px) — добавлен `padding-top: calc(120px + r-48)` (моб. `96px + r-40`)
  по образцу region-hero/blog-hero + `has-light-hero` для 404 и danke (тёмное меню).
  Контактная страница (форма) — по запросу пользователя отложена. Собрано, задеплоено,
  обе страницы сверены с макетом desktop+mobile (совпадает). Готово.
- [Claude] 2026-06-24 — **Регионы: глубокая сверка секций по фидбеку (fidelity-pass).**
  Пользователь прошёлся по HTML и нашёл расхождения с Figma — все вытянуты программно
  (`get_design_context` по нодам) и исправлены на всех 4 страницах: (1) services —
  `problem-cards` (был свёрстан под 4 кол., без ссылок) заменён новым блоком
  **`region-services`** (точно по `2009:6685`: иконка 64, accent-бордер, заголовок 56
  с `<br>`+курсивом города, кнопка секции, «Erfahren Sie mehr →» в каждой карточке
  со ссылкой на /immobilie-*). (2) intro split — реальное фото интерьера из дизайна
  (`2009:6679`) вместо случайного. (3) about — переносы `<br>` в заголовке и
  карточках (render → `wp_kses_post`). (4) process-steps — цифра шага r-32→**r-40
  курсив accent** (по `2009:6747`). (5) `region-properties` карточка переписана под
  `2009:6770`: фон-панель `surface` без скруглений, фото без радиуса, мета
  12px/uppercase/muted + значения 16px, точки accent/contrast, заголовок «*Verkauft*
  in {Ort} und Umgebung» / «Aktuelle Objekte in {Ort}». (6) CTA — полный текст из
  макета. Урок: сверять КАЖДУЮ секцию по дизайн-ноде, reuse-блок проверять на
  структурную пригодность (`region-services` vs `problem-cards`). Пересобрано,
  задеплоено, пересеяно, секции сверены визуально.
- [Claude] 2026-06-24 — **Регионы: careful re-audit по фидбеку.** Поймана грубая
  ошибка: при первой сборке сверил наличие секций ГРЕПОМ по классам, а не нода-в-ноду
  с макетом → **пропустил блок `about`** («Darauf können Sie sich verlassen» —
  тёмная секция с портретом и 4 карточками, Figma `2009:6717`). Добавлен в
  post-content регионов. Плюс **hero-фото заменены на реальные панорамы городов из
  Figma** (download_assets с нод `2009:6653/6969/7279/7589` → webp в media/regions/,
  загрузка в медиатеку, featured per-region) вместо случайных фото из медиатеки.
  Урок усилен в память (`reuse-block-structural-fit`): не доверять грепу для
  полноты — сверять список секций по дереву нод Figma. Пересеяно, все секции
  сверены визуально (hero/about совпали с макетом). Порядок: hero→trust→intro→
  services→about→process→sold→testimonials→objects→faq→cta.
- [Claude] 2026-06-24 — **Регионы: фиксы по фидбеку.** (1) `problem-cards` был
  жёстко на 4 колонки → 3 сервис-карточки региона не растягивались; число колонок
  сделано динамическим (`--pc-cols` = число карточек). (2) intro (split-cta):
  заголовок с курсивом «in {Ort}?» + кнопка «Kostenlos beraten lassen» → /kontakt/
  (было «Mehr über mich»). (3) hero-подзаголовок — перенос `<br>` как в макете.
  (4) hero-фото регионов заменены на крупные (full-bleed больше не мылит). (5)
  trust-bar — Google-бейдж (живой рейтинг плагина, как на главной). Урок в память
  (`reuse-block-structural-fit`): сверять структурные допущения reuse-блока и
  каждую секцию визуально. Пересобрано/задеплоено/пересеяно, секции сверены.
- [Claude] 2026-06-24 — **Регионы (Bludenz/Bregenz/Dornbirn/Feldkirch) как CPT
  `region` без архива + `single-region.html`.** 4 структурно одинаковые страницы:
  собрана раз, клиент правит 4 записи. Новый CPT `region` (no archive, slug
  `/region/`, мета hero-полей + мета-бокс, одноразовый flush rewrite). 2 новых
  блока темы: `region-hero` («Immobilienmakler in {Ort}», город курсивом из title,
  подзаголовок/кнопка из меты, full-bleed фото) и `region-properties` (карусель
  объектов с фильтром по городу = slug записи: source property→«Aktuelle Objekte»,
  reference→«Verkauft», общий RbCarousel). Остальные секции — переиспользуемые
  блоки в post-content (trust-bar, split-cta, problem-cards, process-steps,
  testimonials, faq-section, consultation-cta), имя города предзаполнено паттерном.
  `has-light-hero` для single region. Seed `scripts/seed-regions.mjs` (термины
  property-city/reference-city, 4 записи, контент-черновик). Собрано, задеплоено,
  все 4 региона отвечают 200, Bludenz сверен по секциям с макетом (hero, intro,
  сервисы, шаги, Verkauft/Aktuelle карусели с фильтром по городу, отзывы, FAQ, CTA)
  — совпадает. Тексты регионов — черновик для клиента. Готово.
- [Claude] 2026-06-24 — **Blog + Single Article (core-посты, не CPT).** Сделаны по
  Figma (Blog `2009:3307`, Single Article `2009:10852`). 5 новых блоков темы:
  `blog-hero` (eyebrow + курсивный заголовок + featured-карточка последней статьи),
  `blog-grid` (сетка 3 кол. + пагинация, исключает featured), `article-header`
  (заголовок r-48/500 + мета + обложка с бейджем), `article-toc` (Inhaltsverzeichnis:
  авто-оглавление из h2/h3 через `view.js`, CSS-counters 1/1.1, аккордеон grid-rows,
  прячется без заголовков), `article-related` («Das könnte Ihnen auch gefallen»,
  3 похожие статьи по рубрике). Шаблоны `templates/home.html` (блог) и `single.html`
  (статья, post-content в колонке 848px). Общий хелпер `inc/blog.php` (мета-строка
  дата/время-чтения/автор + карточка), мета-иконки из Figma в `assets/blog/`
  (date/time/person/chevron, currentColor). `has-light-hero` распространён на блог/
  сингл (тёмная шапка). Шрифты: добавлены Nunito Sans 500/600/700. Seed-демо
  `scripts/seed-blog.mjs` (страница Blog→page_for_posts, рубрика, 7 статей с
  обложками и h2/h3). Собрано, задеплоено, проверено desktop+mobile (блог и статья
  совпадают с макетом, TOC и пагинация работают) — готово.
- [Claude] 2026-06-24 — **Ретро по CHANGELOG + чистка кодировки.** Починена битая
   кириллица (мохибейк) в 66 исторических строках лога — детерминированно через
   `iconv-lite` (`win1251`→`utf8`), без угадывания. Найдена ПРИЧИНА рекуррентного
   мохибейка: дозапись текста через PowerShell (`Out-File`/`>>`, дефолт UTF-16) →
   зафиксировано правило в `AGENTS.md` §4.5 (русские тексты писать только Write/Edit).
   Ретро-уроки в памяти (`changelog-retro`). Только доки/лог — без деплоя.
- [Claude] 2026-06-24 — **References: фиксы по фидбеку.** (1) Таб «Alle» — в начало
  (был в конце). (2) `single-reference` hero: убрана кнопка (в макете её нет),
  цена в одну строку (`white-space:nowrap`) — «Auf Anfrage» больше не переносится.
  (3) **Глобально**: подвал вплотную к CTA (убран зазор blockGap корневого layout)
  — для всех страниц. (4) `reference-overview`: больше отступ между колонками
  (фикс. колонки 649/551 + gap до 110px) и от галереи (section-space). (5)
  `property-stats` для референсов добавляет Grundstücksfläche — секция больше не
  пустует на Grundstück (Bludenz). (6) Галерея: изображения нельзя кликнуть/сохранить
  (`pointer-events:none` + `draggable=false`), drag карусели работает (на треке).
  (7) Все отзывы-референсы — 5 звёзд. Проверено desktop+mobile.
- [Claude] 2026-06-24 — **References + CPT `reference`**: новый тип записи
  «Referenzen» (проданный объект + отзыв клиента) рядом с `property`. Плагин:
  `reference-fields/-cpt/-meta-box/-catalog.php` (мета `property_*` переиспользуется
  + `reference_*` для отзыва; таксономии `reference-type`/`reference-city`; REST
  `/rosenberger/v1/references` для AJAX-каталога). Тема: блоки `reference-catalog`
  (табы-Typ + Lage + Sortierung, AJAX, пагинация, 2-кол. сетка), `reference-overview`
  (фото + Objektbeschreibung), `reference-testimonial` (Zufriedene Kundenstimmen),
  `references-hero` (заголовок + баннер из Figma). Шаблоны `archive-reference.html`
  (hero → trust-bar → catalog → testimonials → CTA) и `single-reference.html`
  (property-hero/stats/gallery reuse + overview + testimonial + CTA). Сид
  `seed-references.mjs` (4 записи). Собрано, check 0 нарушений, задеплоено,
  перемалинки сброшены, обе страницы 200, визуальная сверка desktop+mobile —
  совпадает с Figma (references `2009:6147`, single `2009:11430`). Тексты
  Objektbeschreibung — черновик для клиента.
- [Claude] 2026-06-24 — `single-property`: фиксы по фидбеку. (1) **Hero** — левая
  половина белая с текстом, правая — одно фото; добавлен `overflow:hidden`, фото
  больше не «течёт». (2) **Карусели переведены на общий движок**
  `assets/js/rb-carousel.js` (`RbCarousel`): drag/свайп + **бесконечный цикл**
  (hero и gallery); правило зафиксировано в AGENTS §6a и памяти. (3) **Шапка** на
  светлом Hero — тёмное меню/лого (body-класс `has-light-hero`). (4)
  **Objektbeschreibung**: Lage/Ausstattung/Sonstiges — жирные подзаголовки. (5)
  Кнопки карточки маклера — **в ряд** (были в столбик). (6) **Карта**: у POI-карточек
  появились иконки (transit/highway/train/plane, theme-assets, черновик). (7)
  **Мобайл по макету**: Hero — сначала контент, потом фото; Übersicht — порядок
  Kurz→Objekt→карточка маклера (grid-areas). Проверено на живом URL (desktop+mobile).
- [Claude] 2026-06-24 — Шаблон CPT `single-property.html` собран по макету «single
  object» (богатый дизайн). **Мета-модель property расширена** ~25 полями (схема —
  один источник `includes/property-fields.php`: register_post_meta + мета-бокс +
  REST из неё), мета-бокс переписан схемно (text/textarea/wysiwyg/select + пикер
  галереи). 6 новых theme-блоков (читают мета записи через context['postId']):
  `property-hero` (2 колонки + карусель), `property-stats` (6 показателей с иконками),
  `property-overview` (Kurzbeschreibung + тёмная карточка маклера | Objektbeschreibung),
  `property-gallery` (coverflow), `property-details` (аккордеоны: открытый «Eckdaten
  & Flächen» 18 полей + 5 свободных WYSIWYG), `property-location` (Leaflet/OSM по
  адресу + карточки достижимости). Финальный CTA — reuse `consultation-cta`.
  Иконки/CTA-фон/фото — из Figma (SVG→theme assets, JPEG→WebP). Данные маклера —
  глобально в «Настройках сайта» (новые agent_*). Токены `subtle`/`success` в
  theme.json. Сид демо-объекта `scripts/seed-single-object.mjs` (id 88, Dornbirn).
  Проверено на живом URL объекта — все 7 секций совпали (desktop + mobile). Черновики
  (клиенту): тексты 5 аккордеонов, POI-список, координаты, slug `/kontakt/`, 2-я
  кнопка CTA.
- [Opus] 2026-06-24 — `property-catalog`: 2 фикса по фидбеку. (1) **Range-фильтры
  чинены** — мета хранится строками («ca. 130 m²», «€ 680.000»), NUMERIC давал 0 и
  фильтр показывал пусто; добавлены числовые спутники `*_num` (хук + сид), фильтр
  по ним. (2) **Кастомный дропдаун сортировки** вместо нативного: белое меню,
  выбранное подсвечено тёмным; завязан на AJAX. Проверено браузером (Haus→2,
  Zimmer≥5, URL обновляется без перезагрузки).
- [Opus] 2026-06-24 — `property-catalog` каталог-режим переведён на **AJAX** (без
  перезагрузки): REST `rosenberger/v1/properties` отдаёт сетку, `view.js` подменяет
  результаты + `replaceState`. Общий рендер в плагине (`includes/property-catalog.php`)
  — одна разметка для SSR и AJAX. Стили по Figma: диапазоны-пилюли, тёмная CTA
  «Kostenlos beraten lassen» + reset (вместо «Filtern»), мобильный аккордеон «FILTER».
  Добавлено поле `property_plot_area` (Grundstücksfläche) в CPT/мета-бокс/сид —
  карточка показывала «—». Правило «фильтры/сортировки = AJAX» зафиксировано в §6a.
- [Claude] 2026-06-24 — Страница `alle-immobilien` собрана по плейбуку §6a. Блок
  `property-catalog` расширен атрибутом `layout` (compact | catalog): catalog-режим
  добавляет боковой фильтр (Kaufen/Mieten-тоггл, чекбоксы типов, range-инпуты),
  сортировку, пагинацию и collapsed-border сетку (3 кол). PHP-хелперы `pc_url` и
  `pc_page_url` защищены `if (!function_exists(...))`. Деплой + импорт страницы
  выполнены, `/alle-immobilien/` доступна, все 5 секций рендерятся.

- [Claude] 2026-06-23 — Страница `immobilienbewertung` собрана по плейбуку §6a: все
  10 секций переиспользуют существующие блоки (page-hero, trust-bar, split-cta×2,
  problem-cards, about, process-steps, consultation-cta, testimonials, faq-section).
  Блок `about` получил атрибут `columns` (default 4, BW=3) для 3-колоночной сетки.
  Ассеты (4 WebP-фото + 4 SVG-иконки) загружены в медиатеку. Задеплоено, страница
  создана id=115. ⚠ Черновики: FAQ ответы 2–5, заголовок steps (исправлен с Vermietung
  на Bewertung), секция 8 (форма) заменена на consultation-cta — сделать позже.

Формат: `- [Claude] YYYY-MM-DD — что сделано (затронуто) — статус`.
Статус: `готово` (собрано+задеплоено) или `wip` (не доведено, назван блокер).

---

- [Claude] 2026-06-23 — Тонкая команда `/build-page <slug>` (`.claude/commands/build-page.md`): оркестрирует плейбук §6a со стоп-гейтами — reuse-first карта секций, значения/ассеты программно, сборка, и ГЛАВНОЕ — посекционная сверка `npm run shot` («не идём дальше, пока не совпало»), в конце список на ревью. Чтобы слабая модель (Sonnet) не бросала на «почти» и не выдумывала сложное молча. Сухой прогон на immobilienbewertung: 9 секций — чистый reuse, команда корректно флагует стоп-гейты (4-шаговая форма оценки 537:3327, пустой Frame 21, copy-paste опечатки заголовков «Vermietung/fünf» вместо «Bewertung/drei»). Команда коммитится (не в .gitignore) → доступна новым сессиям.
- [Claude] 2026-06-23 — immobilie-vermieten собрана по плейбуку §6a (проверка воспроизводимости): страница id=106, 10 секций. Reuse-first сработал — 9/10 секций существующие блоки (page-hero, trust-bar, problem-cards, split-cta ×2, process-steps, testimonials, faq-section, consultation-cta), новый код = только опция `imageLeft` у split-cta (фото слева для секции «Wie ich Ihre Mieter auswähle»). Иконки (4) почищены `npm run figma:icon`, проверка `npm run shot` (desktop+mobile, нарезка) — все секции совпали с Figma, переносы строк и курсив заголовков сняты точечными скриншотами нод. Подтверждает: процесс из §6a даёт ровное качество механически. Собрано/задеплоено.
- [Claude] 2026-06-23 — Плейбук качества + инструменты (чтобы и Sonnet держал уровень): `AGENTS.md` §6a — пошаговый чек-лист на каждую секцию (reuse-first → значения из Figma → ассеты webp/svg → сверка каждой секции) + хитрости (бренд-цвета инлайном не в SCSS; аккордеон на CSS grid-rows; данные плагина через helper; CS PHP через json_decode; bash /tmp ≠ node). Два новых скрипта: `npm run shot` (скрин секции/страницы staging с авто-ужатием и нарезкой высоких — заменяет ручной puppeteer) и `npm run figma:icon` (чистит SVG-экспорт иконки от фон-холста #1E1E1E и геометрии страницы). Оба проверены. Цель — закодировать приёмы, давшие точность на immobilie-verkaufen, в механический процесс. Тулинг, без деплоя сайта.
- [Claude] 2026-06-23 — faq-section: анимация переписана на CSS grid-rows (вместо WAAPI-height, что дёргалось). `<details>` заменён на button+region (доступно, aria-expanded/controls). Раскрытие — `grid-template-rows: 0fr↔1fr` (плавно, без расчёта высоты/рывков). Отступы вынесены в `__answer-inner`, иначе 0fr не схлопывался в 0 (был остаточный padding 24px). Прогрессивное улучшение: без JS контент открыт; JS добавляет `.is-enhanced` (сворачивает) + `.is-ready` через 2×rAF (первичное сворачивание без анимации). Проверено: закрытые = 0px, открытый = 96px, иконка plus↔minus. theme+library, собрано/задеплоено.
- [Claude] 2026-06-23 — faq-section: иконки по Figma + анимация (заменено на grid-rows, см. выше). Заменил CSS-«кружок с чёрточками» на точную иконку simple-line-icons (тонкое кольцо r11.25 + плюс/минус с закруглёнными штрихами, цвет muted #747C86 — взято из SVG-экспорта ноды 536:2557). Добавлен `view.js` (viewScript): плавное раскрытие/сворачивание ответа через Web Animations API (height 0↔scrollHeight) + поворот иконки и скрытие верт. штриха (plus→minus). `<details>` сохранён для no-JS. Синхронизировано theme+library, собрано/задеплоено/проверено (клик в браузере — аккордеон анимируется).
- [Claude] 2026-06-23 — immobilie-verkaufen этап 3 (низ) + страница ГОТОВА: `faq-section` («Häufige Fragen / zum Verkauf», 4 вопроса — 1-й раскрыт с реальным ответом из Figma, ответы 2–4 написаны по смыслу = ЧЕРНОВИК для клиента; дубль-вопрос из макета убран) + `consultation-cta` («Ihr kostenloses Erstgespräch», фон-дерево WebP 197KB + вуаль, белая кнопка). Переносы строк по Figma («zum <em>Verkauf</em>»). Мобильная сверка всей страницы (375px) пройдена: все секции адаптивны без переполнений (problem-cards в колонку, split-cta фото сверху, sold-карусель, отзывы стопкой). Десктоп+мобайл = весь immobilie-verkaufen собран. Кнопки → /kontakt/ (подтвердить slug). Собрано/задеплоено/проверено.
- [Claude] 2026-06-23 — Google-отзывы динамически (grw → site-wide): helper `rosenberger-core/includes/google-reviews.php` (`rosenberger_google_reviews` + `_positive`) читает данные плагина grw из transient `grw_feed_<ver>_<feedid>_reviews` (ключ из опций grw_version/grw_feed_ids, last-known-good в опции на случай протухания). ① trust-bar переведён на динамический бейдж: Google-«G» + реальный рейтинг 4.4 + золотые звёзды + «14 Bewertungen» (fallback на старый badge-img) — работает на ВСЕХ страницах с trust-bar (проверено на главной и immobilie-verkaufen). ② Новый блок `library/testimonials`: реальные Google-отзывы (аватар, имя, нем. дата, звёзды, текст с line-clamp), фильтр rating>=4 + текст, сорт по дате. Цвета бренда (золото/Google-G) — инлайном в render, не в SCSS (токены чисты). Добавлен в immobilie-verkaufen. Собрано, задеплоено (тема+плагин), проверено. Осталось: faq-section + consultation-cta + mobile-сверка.
- [Claude] 2026-06-23 — sold-showcase наполнен данными: seed-properties создал 6 объектов CPT (таксономии ок, но мета не записалась — баг: его `api()` всегда префиксит `/wp/v2`, а CS-эндпоинт — нет → 404). Новый `scripts/seed-property-meta.mjs` пишет мету (price/area/rooms/status) + featured-фото через рабочий CS-механизм (как deploy-stack); важно: PHP-массив через `json_decode('<json>',true)`, а не JS-литерал `[{…}]` (это парс-ошибка PHP). 3 объекта помечены `Verkauft` → sold-showcase на immobilie-verkaufen отрисовался (карточка + мета + фото + карусель). Проверено в браузере.
- [Claude] 2026-06-23 — immobilie-verkaufen этап 2 (середина): новый блок `library/split-cta` («Sie verkaufen, / ich mache den Rest» — текст+кнопка слева, фото справа, на мобилке фото сверху; фото Rectangle 65 → WebP 33KB). `process-steps` переиспользован для «Ihr Verkauf / in acht Schritten» (8 шагов 01–08, без subtext/кнопки). `sold-showcase` переиспользован («Erfolgreich verkauft / in Vorarlberg»). Переносы строк по Figma. Собрано, задеплоено, проверено 1440 — split-cta и process-steps совпадают. ⚠ sold-showcase рендерит пустое состояние: на сайте 0 объектов CPT `property` (нужен seed демо или реальные Verkauft-объекты — решение за пользователем; затрагивает и главную). Осталось: этап 3 (testimonials + FAQ + consultation-cta + mobile-сверка).
- [Claude] 2026-06-23 — Preflight в `CLAUDE.md` (анти-тупёж): вшит чек-лист перед любой задачей — сначала прочитать AGENTS/CHANGELOG/MEMORY и применять известные правила (SVG-иконки, переносы, import-скрипт, значения из Figma), потом код; не утверждать отсутствие инструмента без перепроверки; bash /tmp ≠ Windows-node. Ретро по сессии: терял время на лишних вопросах о scope (суть была в AGENTS), на PNG-иконках вместо SVG (правило было в памяти), на «нет Figma MCP» без перепроверки. Цель — чтобы это не повторялось у обоих ИИ.
- [Claude] 2026-06-23 — Правило «переносы строк из Figma» (`AGENTS.md` §16): в заголовках/подписях ставить `<br>` ровно как в макете (`<br aria-hidden />` из get_design_context), не полагаться на автоперенос. Применено к problem-cards: заголовок «Was einen / Verkauf zäh macht». Только контент (re-import), без пересборки.
- [Claude] 2026-06-23 — immobilie-verkaufen этап 1 (верх страницы): новый блок `library/problem-cards` (заголовок+интро слева, ряд из 4 карточек-проблем с иконкой на бежевом кружке, схлопнутые бордюры, заголовки выровнены, текст внизу) — собран в library+тему. Hero = `page-hero` (фото Rectangle 67 → WebP 330KB), trust = `trust-bar` (дефолты = Figma). 4 иконки экспортированы из Figma как SVG (`download_assets`, очищены от фона холста) → медиатека через svg-media. Скрипт `import-immobilie-verkaufen.mjs` создаёт страницу (id=83, slug immobilie-verkaufen). Точные значения/тексты из get_design_context (536:1972). Собрано, задеплоено, проверено в браузере 1440 — совпадает. Осталось: этап 2 (split-cta + process-steps 8 шагов + sold-showcase), этап 3 (testimonials + FAQ + CTA + mobile). Кнопка Hero ведёт на `/kontakt/` (подтвердить slug).
- [Claude] 2026-06-23 — Смена Figma-источника: основной файл теперь `p1HKLfoMcOwtVUD5rI9V3P` (страница «UI Design» `142:3`) вместо устаревшего `3AzuInZ4YD95cLiQgiD24W`. Подключён Figma Remote MCP (`mcp.figma.com`, OAuth, `.mcp.json`) — работает по fileKey+nodeId из URL, без desktop-приложения и платного Dev Mode. Обновлены fileKey и карта страниц (desktop/mobile node-id, 19 страниц) в `AGENTS.md` §2/§10 и `CLAUDE.md`; карта перегенерирована штатным `figma-pages.mjs`. В новом файле появилась отдельная страница `contact` (`752:3714`). Только документация — без деплоя.
- [Claude] 2026-06-23 — страница «Tippgeber» (сессия, ошибки и ходы):
    ОШИБКА 1: угадал URL Figma-иконки телефона вместо того чтобы взять из get_design_context → WP отклонил файл "Du bist leider nicht berechtigt"; исправил: вызвал get_design_context(2005:2951), нашёл node 2005:3018 (mobile icon), взял screenshot PNG → загрузил успешно
    ОШИБКА 2: использовал imgInformationPoint/imgCoin как image assets из Figma — те вернули SVG, WP отклонил; исправил: get_screenshot(node) для всех трёх иконок → PNG 64×64
    ОШИБКА 3: опечатка в FAQ-тексте "ich klä das Weitere" → пропущено "re"; исправил перед финальным push
    ОК: 5 новых блоков (page-hero, how-it-works, provision-callout, tipper-types, tipper-form); WPForms-воронка из архива пользователя адаптирована через CSS-переменные; page id=76 создана через import-tippgeber.mjs; все 10 медиа в медиатеке — готово
- [Claude] 2026-06-23 — страница «Über mich» (сессия, ошибки и ходы):
    ОШИБКА 1: создал HTML-шаблон `templates/page-uber-mich.html` → неверно, страницы создаются через скрипт REST API; удалил шаблон, создал `scripts/import-uber-mich.mjs`
    ОШИБКА 2: в trust-bar не добавил Google badge → пользователь заметил по скриншоту; добавил `rosenberger-google-rating` в список медиа скрипта
    ОШИБКА 3: founder-bio контент был 663px вместо 550px → не учёл padding при задании max-width; исправил на 646px (550 + 48×2)
    ОШИБКА 4: consultation-cta font-weight остался 400 → не добавил явный font-weight в CSS; добавил 300
    ОШИБКА 5: хедер на Über mich сразу тёмный, а на главной прозрачный → header.js проверял только `.hero-cover`, пропустил `.bio-hero`; добавил второй класс в querySelector
    ОШИБКА 6: `<br>` в заголовках не рендерился → использовал esc_html вместо wp_kses_post; исправил в founder-bio и promise-list render.php
    ОК: bio-hero сделал 100vh по правилу vw/vh; 3 фото загрузил из Figma CDN в медиатеку — готово
- [Claude] 2026-06-23 — staging удалён (задача пользователя):
    Убрал .env.prod, флаг --prod из deploy-stack, STAGING_* из import-homepage → одна среда rosenberger.digirelation.dev — готово
- [Claude] 2026-06-23 — главная страница на продакшн: 48 медиа загружено, все блоки (hero→trust→pain→cards→about→regions→catalog→process→sold→referral→faq→cta), front page назначена, contacts seeded — готово
- [Claude] 2026-06-23 — деплой на продакшн rosenberger.digirelation.dev: установлен Code Snippets, активирована тема rosenberger + rosenberger-core, деактивирован Elementor и связанные плагины; добавлен флаг --prod в deploy-stack.mjs — готово

- [Claude] 2026-06-23 — Переиспользование блоков: новый `scripts/blocks-list.mjs` + `npm run blocks` печатает каталог из `block.json` (slug/категория/описание/keywords, library+тема — всегда актуально). В `AGENTS.md` §6 шаг 1 сделан обязательным: перед новым блоком прогнать `npm run blocks` и при совпадении копировать+допиливать, а не создавать с нуля.
- [Claude] 2026-06-23 — Карта страниц как процесс: новый `scripts/figma-pages.mjs` строит таблицу desktop/mobile из сохранённого ответа `get_metadata` (пэйрит фреймы по имени, флагует непарные mobile), + правило в `AGENTS.md` §10: каждый новый проект/Figma-файл → СНАЧАЛА сгенерировать свою карту страниц (шаги get_metadata→скрипт→чистка junk→синонимы). Так «работа по названию страницы» переносится на любой будущий проект. Проверено: воспроизводит таблицу Rosenberger из дампа.
- [Claude] 2026-06-23 — Карта страниц Figma в `AGENTS.md` §10: таблица node-id desktop/mobile для всех 17 страниц дизайна (получена из `get_metadata` файла, распарсена из сохранённого tool-result без траты контекста). Теперь работа по названию страницы («сделай Über mich») без копирования ссылок — оба ИИ берут оба node-id из таблицы. Зафиксировано: отдельной страницы Kontakt в дизайне НЕТ (только thank you).
- [Claude] 2026-06-23 — Визуальный дифф (QA для слабых моделей): новый `scripts/visual-diff.mjs` + `npm run visual`. Скриншот staging (puppeteer, full page, авто-скролл) ↔ PNG фрейма Figma (sharp): даунскейл гасит шум шрифтов/антиалиаса, на выходе `*-side.png` (макет|реализация), `*-diff.png` (heatmap) и **% расхождения по 6 зонам** + дельта высот — чтобы модель знала ГДЕ разъехалось, а не сравнивала на глаз. Советник, не push-гейт (дизайн↔браузер шумны). Проверено на главной staging: высоты 3552/3486 (дельта 66px), секции выровнены построчно. Правила в `AGENTS.md` §8 (шаг 2a, обязателен для вида), §10 (maxDimension=высоте фрейма для полноширинного PNG), §12 (числа дёшевы, картинки — точечно). `.visual/` в gitignore. Стек уже в deps (puppeteer/sharp).
- [Claude] 2026-06-23 — Токены ч.2 (enforce + чистка): `tokens:scan` встроен в `npm run check` (и pre-push) — `✗`-хардкод блокирует push. Починены все найденные хардкод-цвета в `property-catalog`, `region-grid` (theme+library) и `hero-cover` (library синхронизирован с темой): убраны fallback'и `var(--токен, #hex)` (в т.ч. протухший `surface-muted, #969696`), `color:#fff`→`on-dark`, `background:#fff`→`base`. Замены рантайм-тождественны (токены в теме определены), затронутые блоки пересобраны; деплой не запускал — в дереве wip Codex. Остались только `⚠` (#ccc/#000 без токена — alpha/градиент, допустимо).
- [Claude] 2026-06-23 — Конвейер Figma→токены: новый `scripts/figma-tokens.mjs` + `npm run tokens` / `tokens:scan`. Резолвер «сырое значение из Figma → токен `theme.json`» (детерминированный, только парс theme.json, без Figma/MCP) — чтобы не угадывать имя токена и не перечитывать theme.json в контекст ИИ; сканер `tokens:scan` ловит `#hex`-литералы в `blocks/*/src/*.scss` (есть токен → exit 1). Проверено: `get_variable_defs` фрейма Home показал, что Figma почти не использует переменные (значит маппинг — ручной, и тут проскакивают хардкоды); скан сразу нашёл 5 хардкодов (`property-catalog`, `region-grid` — #fff/#969696 вместо токенов). Правила прописаны в `AGENTS.md` §5/§6/§12 для обоих ИИ. Тулинг (рендер сайта не меняется) → без деплоя; хардкоды блоков не правил (нужны rebuild+deploy+визуалка, плюс в дереве wip Codex). Осталось: ① Sass `fig(px)`, ③ вынос Google Fonts в enqueue.
- [Claude] 2026-06-22 — Инфраструктура (часть 2): `deploy-stack.mjs` — поллинг статуса активации вместо слепого `setTimeout(1500)` (таймаут 30с); добавлен opt-in диф-деплой `--changed` (локальный кэш хэшей `.deploy-cache.json`, нет кэша → полная заливка, дефолт без изменений = ноль риска). Новый `scripts/check.mjs` + `npm run check` + `.githooks/pre-push` (`git config core.hooksPath .githooks`): php -l изменённых PHP и запрет секретов/артефактов в git; учитывает, что корень репо — родительская папка. `AGENTS.md` дополнен новыми командами и включением hook. Локально проверено (node --check, прогон check/build/diff, юнит-тест диф-логики); деплой не запускал — в дереве wip Codex — инфра. Осталось: #2 серверный диф-деплой по-умолчанию, #6 общий boilerplate секций (нужна визуальная проверка).
- [Claude] 2026-06-22 — Инфраструктура (скорость+качество): `build-blocks.mjs` стал инкрементальным (пересборка только изменённых блоков по mtime, флаг `--force`) — холостая сборка с ~30–60с до ~4с; новый `scripts/diff-blocks.mjs` + `npm run diff:blocks` показывает дрейф library↔theme (нашёл 14/20 расхождений); `deploy-stack.mjs` — протухающие Figma-CDN URL заменены загрузкой ассетов в медиатеку (referral/consultation), у sold-showcase убраны мёртвые image-атрибуты (теперь CPT-карусель); `blocks-registry.md` дополнен 6 пропущенными блоками; правило Hero (всегда vw/vh) в `AGENTS.md`; починена битая кодировка шапки `CHANGELOG.md`. Локальные проверки (node --check, прогон скриптов) пройдены; деплой не запускал — в дереве wip Codex, не смешиваю — инфра.
- [Codex] 2026-06-22 — Mobile CTA/header/menu: глобальный контракт кнопок исправляет `width:100%` overflow через `border-box`; header приведён к Figma desktop/mobile, добавлено компактное светлое состояние при скролле и на внутренних страницах; реализовано анимированное меню по Figma `2005:1432` с оригинальными SVG-ассетами, адаптивом, Escape/focus-trap. Локальная сборка и проверки пройдены; деплой заблокирован лимитом среды — wip.
- [Codex] 2026-06-22 — `sold-showcase`: исправлена неработающая CPT-карусель (реальное смещение ленты, drag/swipe, клавиатура и синхронизация точек); desktop-композиция приведена к Figma `2005:1133` — карточка 1152px, колонки 488/664px, стрелки в боковых полях; на мобильном карточка и CTA 343px, навигация собрана в три устойчивые зоны, hover CTA наследуется из глобальных стилей темы. Собрано, задеплоено и проверено в Chrome на 1440/375 без JS-ошибок — готово.
- [Codex] 2026-06-22 — `sold-showcase`: синхронизирован и доведён между `theme` и `library` как CPT-карусель по объектам `property` со статусом `Verkauft`; добавлены `view.js` в library, упрощённые атрибуты блока, чистый UTF-8 в `render.php`/`style.scss`, hover/focus состояния и мобильная CTA-кнопка на всю ширину. Собрано, library пересобрана, задеплоено — готово.
- [Codex] 2026-06-22 — Главная Rosenberger: добавлены недостающие секции `process-steps`, `sold-showcase`, `referral-cta`, `faq-section`, `consultation-cta` в library и theme; `deploy-stack.mjs` теперь синхронизирует их на `hero-cover-test`, а главная staging пересобрана из тестовой страницы. В `AGENTS.md` добавлено жёсткое правило брать изображения и иконки только из Figma-ассетов. Собрано, задеплоено, front page sync выполнен — готово.
- [Codex] 2026-06-22 — property-catalog: в блок добавлены редактируемые `heading`, `headingItalic` и `subtext` для основного заголовка секции; строки и рендер дочищены от битой кодировки, в `AGENTS.md` зафиксировано глобальное правило работать только в UTF-8. Синхронизировано в theme и library — готово.
- [Codex] 2026-06-21 — property-catalog: селекты переведены на кастомный UI в стиле сайта поверх нативной формы; убрана фиксированная высота карточек, ряд теперь тянется по самой высокой карточке, а высота изображения приведена к пропорции из Figma. Добавлено правило в AGENTS.md: следовать Figma точно, но не слепо для живого контента. Собрано, задеплоено, staging 200 — готово.
- [Codex] 2026-06-21 — property-catalog: секция выведена из узкого contentSize в wideSize по макету; у карточек выровнена внутренняя вертикаль и нижняя линия CTA. Синхронизировано в theme и library, собрано, задеплоено, staging 200 — готово.
- [Codex] 2026-06-21 — region-grid: на главной исправлена контрастность текста в светлой секции — заголовок и подписи pill переведены с base на contrast. Синхронизировано в theme и library, собрано, задеплоено, staging 200 — готово.
- [Codex] 2026-06-21 — Front page staging: сломанная главная (page 296) заменена контентом из hero-cover-test; восстановлен region-bregenz, объектам CPT назначены sample featured images, визуально перепроверены region-grid и property-catalog. Добавлен scripts/sync-front-page.mjs для быстрой синхронизации главной — готово.
- [Claude] 2026-06-21 — Property Catalog: блок library/property-catalog — 3 фильтра (Lage/Typ/Sortieren), 3×2 сетка карточек CPT property, мета (Lage/Kaufpreis/Fläche/Zimmer), CTA-кнопка, view.js auto-submit. Добавлен на страницу 882 через REST. Staging ✓
- [Claude] 2026-06-21 — Region Grid: блок library/region-grid (2×2 сетка регионов, backdrop-blur pill-ссылка, репитер 4 карточки). Собран library+project, задеплоен, страница 882 обновлена через REST API, about-атрибуты восстановлены. Staging ✓
- [Claude] 2026-06-21 — Property CPT: taxonomy property-type + property-city, мета-поля (price/area/rooms/status), meta box в Gutenberg, блок rosenberger/property-meta (render.php), шаблоны single-property.html + archive-property.html, CSS стили карточек и страниц, seed-скрипт с Code Snippets fallback. staging /objekte/ — готово.
- [Claude] 2026-06-21 — `footer`: переделан по Figma (2005:1394) — CTA-заголовок 56px + H256-иконка, белый логотип, Block Bindings для контактов, соцсети (4 иконки SVG inline), 4 nav-колонки, копирайт. Мобилка: стек 2×2. Задеплоено, staging 200 — готово.
- [Codex] 2026-06-21 — trust-bar/about: мобильные стили доведены по макету; trust-bar переведён на поля 24px и бейдж 144.19x40, about избавлен от жёсткой мобильной высоты и укорочен по портретной зоне. Собрано, задеплоено, staging 200 — готово.
- [Codex] 2026-06-21 — about: мобильный портрет в блоке о себе отцентрирован по кадру; синхронизировано в theme и library, собрано, задеплоено, staging 200 — готово.
- [Codex] 2026-06-21 — Мобилка: trust-bar скрыт на ≤600px; cards-stack stack-on-scroll включён на мобилке/планшете без счётчика; pain-points — отступ между заголовком и списком иконок доведён до 64px; about — мобильная секция с портретом перестроена без кривого наложения. Синхронизировано в theme и library, собрано, задеплоено, staging 200 — готово.
- [Codex] 2026-06-21 — `about`: мобильная секция доведена по Figma `home mobile` —
  intro переведён на поля 16px/343px, кнопка растянута на всю ширину, карточки
  перестроены в одну колонку с точным вертикальным разносом под портретом,
  скорректированы позиционирование фото и затемнение фона. Синхронизировано в
  `theme` и `library`, собрано (`npm run build`, `npm run build:library`),
  задеплоено, staging 200 — готово.
- [Claude] 2026-06-21 — `AGENTS.md` переписан в полноценную мастер-инструкцию: TL;DR,
  карта репо, золотые правила, справочник токенов, пошаговая сборка блока, цикл
  задачи, деплой, Figma, грабли, экономия токенов, текущее состояние. Любой ИИ (в
  т.ч. новый чат/Codex) читает его первым и сразу работает. `CLAUDE.md` → указатель
  на него. Инфра, без деплоя.
- [Claude] 2026-06-21 — `about`: фикс — заголовок был тёмным (глобальные стили темы
  красят h2 в contrast), задан явный `on-dark`; фон переведён на `object-position:
  right top`, чтобы не обрезалась голова. Проверено в браузере.
- [Claude] 2026-06-21 — Новый блок `about` (секция «о себе», node `2005:1044`):
  фоновое фото (cover, WebP 30KB) + заголовок/текст/кнопка слева и ряд из 4 белых
  карточек-преимуществ поверх фото. Репитер карточек, MediaUpload фона с превью,
  ширины в px (контент 1344, карточки grid 4×, gap 16), адаптив. Точные значения
  из get_design_context. Собрано, задеплоено, проверено в браузере — совпадает.
- [Claude] 2026-06-21 — Превью медиа в репитерах увеличено и перенесено НАД кнопкой
  выбора (cards-stack фото 150px, pain-points иконка 80px, trust-bar бейдж) —
  лучше видно. Только edit.js. Подтверждено в редакторе.
- [Claude] 2026-06-21 — В репитерах добавлены превью-миниатюры текущего
  фото/иконки/бейджа рядом с кнопкой выбора (cards-stack, pain-points, trust-bar)
  — сразу видно, что выбрано. Только edit.js. Подтверждено в редакторе.
- [Claude] 2026-06-21 — Репитеры: pain-points, cards-stack и trust-bar были с
  фиксированным числом строк. Добавлены полноценные репитеры (добавить/удалить/↑↓)
  по образцу hero-cover — клиент управляет строками. Правка только в edit.js,
  render/CSS/дизайн не менялись; фронт 200, не сломан. Также в edit-превью
  добавлены контейнеры `__inner` (как на фронте).
- [Claude] 2026-06-21 — Ширины контента: pain-points и trust-bar растягивались на
  весь экран (не было центрированного контейнера; в pain-points колонка через
  `45vw`). Добавлен внутренний контейнер `max-width: 1344px; margin: 0 auto`,
  колонки в px (список pain-points — 648px), `vw` убран. Правило зафиксировано
  (vw только в Hero). Проверено вживую — готово.
- [Claude] 2026-06-21 — `cards-stack`: заимствована механика из референса (по
  просьбе) — плавный stack-on-scroll (уезжающие карточки scale+translate+fade,
  z-index), счётчик-одометр со сдвигом трека и линией-прогрессом, rAF на scroll.
  Дизайн оставлен наш по Figma (белые карточки без скруглений, фото в край,
  счётчик `contrast`). Проверено вживую в браузере: наложение и смена 01→02→03
  работают — готово.
- [Claude] 2026-06-21 — `cards-stack` ПЕРЕДЕЛАН строго по Figma (`get_design_context`,
  node `2005:1010`): исправлены перепутанные цвета (секция `surface` #f8f5f3,
  карточки `base` #fff, без скругления/тени), счётчик переделан как в макете
  (число + вертикальная линия + затухающий тотал), кнопки pill 18px `contrast`,
  подставлены 3 реальных фото из макета (медиатека, `media/cards/`). Собрано,
  задеплоено, фото на странице, структура подтверждена.
- [Claude] 2026-06-21 — Новый блок `cards-stack` (первая версия) — была собрана
  «на глаз», цвета/счётчик/фото не по дизайну; переделана (см. выше).
- [Claude] 2026-06-21 — Pain Points: `#ddd` в редакторском контроле заменён на
  токен `--wp--preset--color--border`. Заголовок оказался корректным (не баг).
  Текст 5-го пункта «Übergang» — плейсхолдер в самой Figma, нужен от клиента.
- [Claude] 2026-06-21 — Общая инфраструктура для двух ИИ: `AGENTS.md` (единый вход
  и протокол) и этот `CHANGELOG.md` — готово (инфра, без деплоя).
- [Claude] 2026-06-21 — Зафиксирована незакоммиченная работа Codex: блок
  Pain Points (library+theme), `svg-media.php`, SVG-бейдж Trust Bar, синхронизация
  секций в `deploy-stack.mjs` — собрано, задеплоено (staging 200, все 3 блока
  рендерятся), запушено — готово. Остаётся фикс плейсхолдера 5-го пункта.
- [Codex] 2026-06-21 — Hero и Trust Bar по макету Rosenberger (theme) — готово.
- [Codex] 2026-06-21 — Глобальная дизайн-система: палитра, типографика, spacing,
  контейнеры, радиусы в `theme.json`; контракт токенов в `CLAUDE.md` — готово.
- [Claude] ранее — Единый таб «Настройки сайта» + Block Bindings для шапки/подвала.
- [Claude] ранее — Шапка/подвал вынесены в части темы; плагин `rosenberger-core`.
- [Codex] 2026-06-21 — `about`: мобильный портрет в блоке о себе отцентрирован по кадру
  через правку `object-position` под референс Figma; синхронизировано в `theme` и
  `library`, собрано (`npm run build`, `npm run build:library`), задеплоено, staging
  200 — готово.
- [Claude] 2026-06-25 — Универсальный scroll-reveal для всех блоков библиотеки:
  общий модуль `library/shared/reveal/` (IntersectionObserver, fade-up). Без правки
  блоков, навешивается по классу `wp-block-library-*`; безопасно (no-JS/краулеры
  видят контент, `prefers-reduced-motion`, без мигания — подключение в `<head>`).
  Скопировано в тему rosenberger + enqueue в `functions.php`, задеплоено, проверено
  в браузере (блоки проявляются по скроллу) — готово.


