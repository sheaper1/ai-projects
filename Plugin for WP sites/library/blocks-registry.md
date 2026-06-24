# Реестр блоков библиотеки

Каталог всех эталонных блоков из `/library/blocks/`. **Перед созданием нового блока
сверься с этой таблицей**: если похожий есть — копируй его; если нет — создавай новый
и допиши строку сюда.

Статусы: `stable` — готов к использованию, `draft` — в работе, `deprecated` — устарел.

| Имя  | Slug (name)   | Описание                                                            | Теги                              | Статус |
|------|---------------|---------------------------------------------------------------------|-----------------------------------|--------|
| Hero | `library/hero` | Первый экран: заголовок, подзаголовок и кнопки. Макет заблокирован (InnerBlocks). | hero, первый экран, обложка, cover | stable |
| Hero Cover | `library/hero-cover` | Полноэкранный Hero: фоновое фото (WebP), верхняя панель (меню/лого/контакт), заголовок и две колонки. Адаптивный. Тексты/фото — атрибуты (RichText + MediaUpload). | hero, cover, обложка, недвижимость, фон | stable |
| Trust Bar | `library/trust-bar` | Компактная полоса доверия: рейтинг Google и три ключевых преимущества. Адаптивная. | trust, рейтинг, google, преимущества | stable |
| Pain Points | `library/pain-points` | Проблемы клиента: заголовок и пять пунктов с SVG-иконками из медиатеки. | pain, проблемы, список, иконки | stable |
| Cards Stack | `library/cards-stack` | Секция услуг: карточки накладываются друг на друга при скролле (CSS sticky), номер справа меняется (IntersectionObserver, `view.js`). Адаптив планшет/моб. | услуги, карточки, скролл, stack, счётчик | stable |
| About | `library/about` | Секция «о себе»: фоновое фото (cover), заголовок/текст/кнопка слева и ряд из 4 карточек-преимуществ поверх фото. Репитер карточек, адаптив. | о себе, about, история, преимущества, фон | stable |
| Region Grid | `library/region-grid` | 2×2 сетка регионов: полноформатная фотокарточка (600px высота) с backdrop-blur pill-ссылкой по центру. Заголовок секции с курсивным акцентом. Репитер: 4 карточки (фото, название, URL). Адаптив: 1 колонка на мобильных. | регионы, фото, сетка, карточки, blur, pill | stable |
| Property Catalog | `library/property-catalog` | Каталог объектов CPT `property`: 3 фильтра (Lage, Typ, Sortieren nach) через native select + GET-форму, 3×2 сетка карточек (744px, meta: Lage/Kaufpreis/Fläche/Zimmer), кнопка «Alle Objekte ansehen». Серверный рендер (render.php), `view.js` для авто-submit. | каталог, недвижимость, CPT, фильтр, карточки, grid | stable |
| Process Steps | `library/process-steps` | Секция процесса: интро слева, CTA-кнопка и шаги с номерами справа. Репитер шагов, адаптив. | process, steps, timeline, cta | stable |
| Sold Showcase | `library/sold-showcase` | Референсная карточка проданного объекта: заголовок, мета-данные, большое фото и CTA под слайдерную композицию. | sold, showcase, reference, property, image | stable |
| Referral CTA | `library/referral-cta` | Секция Tippgeber: текст с CTA слева и крупное фото справа. | referral, tippgeber, cta, split, image | stable |
| FAQ Section | `library/faq-section` | FAQ-секция: заголовок слева и список вопросов через `details/summary` справа. Репитер вопросов. | faq, accordion, questions, details | stable |
| Consultation CTA | `library/consultation-cta` | Финальный CTA на фоне с крупным заголовком, подзаголовком и pill-кнопкой. | cta, consultation, cover, final, background | stable |
| Bio Hero | `library/bio-hero` | Split-hero: метка, имя, должность и био-текст слева; портрет справа. | hero, bio, портрет, split, о себе | stable |
| Founder Bio | `library/founder-bio` | Две колонки: текст (заголовок + абзацы) слева, высокий портрет справа. | founder, bio, портрет, о себе, split | stable |
| Founder Story | `library/founder-story` | Две колонки: крупный заголовок слева, лид + текст + цитата справа. | founder, story, история, цитата, split | stable |
| Promise List | `library/promise-list` | Две колонки: заголовок слева, нумерованный список обещаний с разделителями справа. | promise, список, обещания, нумерация | stable |
| Quote Cover | `library/quote-cover` | Полноширинное фоновое фото с крупной центрированной курсивной цитатой. | quote, цитата, cover, фон, обложка | stable |
| Value Cards | `library/value-cards` | Три горизонтальные карточки-преимущества: SVG-иконка, заголовок, описание. Репитер. | value, карточки, преимущества, иконки, репитер | stable |
| Problem Cards | `library/problem-cards` | Заголовок + вводный абзац сверху и ряд карточек-проблем (иконка на бежевом кружке, заголовок, текст), бордюры схлопнуты. Репитер. | pain, problem, проблемы, карточки, ряд, репитер | stable |
| Split CTA | `library/split-cta` | Две колонки: текст (заголовок + абзацы + кнопка) и изображение. Опция `imageLeft` меняет сторону фото. На мобилке фото сверху. | split, cta, текст, изображение, две колонки | stable |
| Testimonials | `library/testimonials` | Отзывы Google (динамически из плагина grw через helper rosenberger_google_reviews): заголовок + карточки (аватар, имя, дата, золотые звёзды, текст). Фильтр по мин. рейтингу. | testimonials, отзывы, google, reviews, динамический | stable |
| Blog Hero | `library/blog-hero` | Шапка блога: eyebrow, крупный заголовок (курсивный акцент) и featured-карточка последней статьи (обложка + мета + Weiterlesen). Серверный рендер по core-постам. | blog, hero, neuigkeiten, featured | stable |
| Blog Grid | `library/blog-grid` | Сетка статей блога (3 кол.) с мета-строкой (дата/время чтения/автор), бейджем sticky и пагинацией. Исключает featured-пост (он в blog-hero). | blog, grid, artikel, posts, пагинация | stable |
| Article Header | `library/article-header` | Шапка статьи (single post): заголовок, мета (дата/время/автор), обложка с бейджем. Берёт текущую запись. | article, post, header, заголовок | stable |
| Article TOC | `library/article-toc` | Inhaltsverzeichnis: авто-оглавление из h2/h3 статьи (CSS-counters, нумерация 1 / 1.1), аккордеон (grid-rows). Наполняется `view.js`, без заголовков прячется. | toc, оглавление, inhaltsverzeichnis, accordion | stable |
| Article Related | `library/article-related` | «Das könnte Ihnen auch gefallen»: 3 похожие статьи (та же рубрика, добор последними). Карточки общие с blog-grid. | related, похожие, posts, empfehlung | stable |
| Region Hero | `library/region-hero` | Hero страницы региона (CPT region): «Immobilienmakler in {Ort}» (город курсивом из title), подзаголовок/кнопка/note из меты, фото — featured image, full-bleed. | region, hero, makler, ort | stable |
| Region Properties | `library/region-properties` | Карусель объектов региона: фильтр по городу = slug записи region (таксономия property-city/reference-city). source: property (Aktuelle) или reference (Verkauft). Общий RbCarousel, карточка деталь-слева/фото-справа. | region, objekte, property, reference, carousel | stable |
| Region Services | `library/region-services` | Услуги региона: заголовок (курсив города) + кнопка секции + 3 карточки (иконка 64, заголовок, текст, «Erfahren Sie mehr →»), accent-бордер. По макету региона. | region, services, leistungen, cards | stable |
| Error 404 | `library/error-404` | Контент 404: заголовок «Seite / nicht gefunden» (80px) + лид + кнопка «Zur Startseite», ниже full-width фото с гигантским «404». Ставится в `templates/404.html`. Фото — медиа по slug `rosenberger-404-building`. | 404, error, not found, ошибка | stable |
| Thank You | `library/thank-you` | Страница «Danke»: центрированный «Vielen Dank / für Ihre Anfrage!» + лид + кнопка, ниже surface-полоса с 3 карточками-шагами (иконка 64, заголовок, текст; репитер, accent-бордер). Страница через `scripts/import-danke.mjs`. | thank you, danke, success, спасибо | stable |
