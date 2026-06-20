# AGENT-HANDOFF — Рабочий журнал

> Общий файл координации между агентами (Claude / Codex) и пользователем.
> **Правило:** перед началом работы прочитать этот файл целиком. После каждого
> значимого шага — обновить разделы «Журнал действий» и «План работ (чеклист)».
> Не удалять чужие записи, только дописывать. Дата формата ГГГГ-ММ-ДД.

Последнее обновление: 2026-06-19 (Codex).

---

## 7.13. Thank-you page + booking redirect (v1.9.0, 2026-06-19, Codex)

- Added the WordPress-rendered `/vielen-dank/` page based on the supplied `vielen-dank.html` design.
- The page confirms receipt, introduces Fabio, and links to the existing Cal.com strategy-call calendar (verified live as 30 minutes).
- Added the official Fabio portrait from `digirelation.com/assets/img/team/fabio-s.webp` to the plugin bundle.
- Successful contact submissions now redirect to `/vielen-dank/`; validation and mail errors remain on `/kontakt/`.
- The plugin creates/publishes the thank-you page once during the v1.9.0 update and stores its page ID.
- Added `noindex, nofollow` for the post-submit page and responsive styles matching the existing landing/contact design system.
- PHP syntax checks passed for the plugin entry point, contact handler, and new template.
- Deployed over the active `digirelation-landingpage-v121` plugin through WordPress Admin. Public checks passed for `/vielen-dank/` and `/kontakt/`; the new page loads CSS v1.9.0, the bundled Fabio portrait, the Cal.com link, and `noindex, nofollow`.
- ZIP: `digirelation-landingpage-plugin/digirelation-landingpage-v1.9.0.zip` (root folder intentionally matches the active plugin directory for in-place updates).

---

## 7.10. ACF editor UX and content migration (v1.5.1, 2026-06-19, Codex)

- Plugin `digirelation-landingpage-v121` upgraded and deployed as version 1.5.1.
- Block upgraded to ACF Blocks v3. Narrow sidebar fields are hidden; `Inhalte bearbeiten` opens the official expanded editor panel.
- Expanded editor verified live: 1024 px wide at a 2048 px viewport, with 12 horizontal section tabs.
- Starter content is persisted in real ACF block data: 38 repeater rows for KPI, problem, solution, comparison, steps, references, logos and FAQ.
- 11 bundled images were imported into the WordPress media library and assigned to ACF fields.
- Live checks confirmed 139 populated text controls, 38 repeater rows and 11 selected images.
- ACF URL validation was resolved by persisting the full CTA URL `https://start.digirelation.com/kontakt/`.
- The CTA field is now a text field, so both relative and absolute URLs can be saved without ACF validation errors.
- The three migrated solution checklists were re-saved through ACF so each renders as two separate list items.
- Public smoke test passed for `/performance-landingpage/` and `/kontakt/`; the contact form still posts to `/wp-admin/admin-post.php` with a nonce.

---

## 0. Правила работы (читать первым)

1. **Правило «1 клик вместо тысяч токенов».** Если какое-то действие
   пользователь может сделать сам **быстро и просто (буквально в 1–2 клика)**,
   а автоматизация этого силами агента съест **много токенов / времени**
   (например: ручной перебор страниц через браузер, загрузка/перемещение файла,
   серия кликов в админке, экспорт/импорт через UI) — **НЕ делать это дорогим
   способом.** Нужно **прерваться** и написать пользователю **короткую чёткую
   инструкцию**, что именно сделать (1–2 шага), и дождаться. Экономия токенов и
   скорость важнее «сделать всё самому».
   - Исключение: пользователь явно недоступен/ушёл и попросил действовать
     автономно — тогда выполнить самому, но всё равно оставить в журнале пометку
     «это можно было сделать в 1 клик: …», чтобы в следующий раз предложить.

---

## 1. Задача

Адаптировать готовый статический лендинг (`landingpage.html` + `kontakt.html`)
под **WordPress: Gutenberg + ACF** на сайте `https://start.digirelation.com`.
Все медиафайлы должны оказаться на сайте и использоваться в блоках Gutenberg
вместе с ACF-полями. Цель страницы — продажа услуг веб-дизайна, заточена под
конверсию (performance-лендинг).

Воронка: лендинг **продаёт** → `kontakt.html` (форма) **конвертирует**.
Все основные CTA ведут на страницу контакта. Оффер — бесплатный Website-Check.

---

## 2. Факты о сайте (КРИТИЧНО — читать перед работой)

- URL: `https://start.digirelation.com` (название сайта в WP: «Akquise-Strategie»).
- Стек: **WordPress**, PHP 8.2.31, nginx, хостинг **Plesk**.
- Установлено (видно по REST namespaces): **Elementor Pro**, **WP Rocket** (кэш),
  **Code Snippets**, **PixelYourSite (Facebook)**, **Google Site Kit**, ACF.
- **ACF Pro с блоками установлен** (подтвердил пользователь).
- Весь существующий сайт построен на **Elementor**, НЕ на Gutenberg.
  Наш лендинг делаем на Gutenberg+ACF осознанно (ради скорости) — это
  отдельный «движок» на сайте. Решение подтверждено пользователем.

### Доступ и ограничения (определяет способ работы)

- У пользователя **только доступ к админке WP** (браузер). Нет SSH / FTP /
  панели хостинга / редактирования файлов.
- ✅ **Загрузка плагина ZIP работает** (Плагины → Добавить новый → Загрузить плагин).
- ✅ **Code Snippets установлен** — можно выполнять PHP из админки.
- ❌ **REST API authorization НЕ РАБОТАЕТ.** Сервер (nginx+PHP-FPM) срезает
  заголовок `Authorization`, WordPress не видит ключ → 401. Починить можно
  только в конфиге сервера, доступа нет. **Путь «через REST API» закрыт.**
- Application Password создан, но из-за этого бесполезен:
  - Пользователь: `digirelationsupport`
  - Пароль приложения: `RobS CoOf 3tq4 GPLp LKjW nKbF` (НЕ РАБОТАЕТ, при
    желании отозвать в профиле WP). Это не пароль от входа.

**Вывод по деплою:** работаем через **(а) ZIP-плагин** и **(б) Code Snippets**.
Никакого REST. Ручные клики в админке — минимизировать.

---

## 3. Принятые решения

1. Движок страницы — **Gutenberg + ACF** (не Elementor). Подтверждено.
2. Доставка кода — **один плагин (ZIP)**, который пользователь загружает 1 кликом.
3. Архитектура контента — **ACF Blocks**, по одному блоку на секцию. Каждый блок
   рендерит ТОЧНО тот же HTML/CSS, что в исходнике (дизайн 1:1), а
   редактируемый текст/картинки вынесены в ACF-поля (репитеры для повторов).
4. CSS дизайн-системы — один подключаемый файл внутри плагина.
5. Медиа — кладём в плагин (`/assets`) и/или импортируем в Медиатеку через
   хук активации либо Code Snippet. Через REST залить нельзя.
6. ACF field groups регистрируем **в коде** (`acf_add_local_field_group`),
   чтобы пользователю не настраивать поля руками.

### Почему так (для контекста)

Нативная пересборка в core-блоки потеряла бы кастомный дизайн (тёмная тема,
Satoshi, градиенты, glow, bento) и заняла бы много времени. ACF-блоки с
render-шаблонами из готового HTML сохраняют дизайн точно и остаются
редактируемыми. ZIP-плагин обходит отсутствие файлового доступа.

---

## 4. Исходники (карта файлов)

Папка проекта: `performance-landingpage-website/`

- `landingpage.html` (730 строк) — продающая страница. Весь CSS внутри `<style>`,
  немного JS внизу (reveal-on-scroll через IntersectionObserver + мобильное меню).
- `kontakt.html` (198 строк) — страница контакта. Те же токены/CSS, что и лендинг.
  Layout: 2 колонки. Слева — lead (eyebrow, H1, подзаголовок, value-лист из 3,
  trust-line: Google-бейдж «4,9 — Offen» + KPI `100+` / `6 Länder`, mini logo-strip).
  Справа — **sticky form-card**. Хедер упрощён (лого + «Zurück zur Übersicht»).
  Форма (`action="#"` — endpoint OFFEN): поля
  `company` Unternehmen (req), `first` Vorname (req), `last` Nachname (опц),
  `email` E-Mail (req), `phone` Mobilnummer (req), `url` aktuelle Website (опц).
- `design-system.md` — токены и компоненты (цвета, типографика, радиусы).
- `README.md` — бриф, позиционирование, открытые пункты.
- `assets/about/ueberuns.png` — картинка для Bento-секции (Leistungen).
- `assets/about/muki-alex-workspace.jpg` — фото для How-it-works (sticky).
- `assets/portfolio/*` — 9 скриншотов референсов:
  `blickwinkel.jpg, tennis-academy.jpg, viridis.png, frachtgut.png,
  rebuild.png, jmc.png, silke-scholz.jpg, nm-pro-assistant.png, bk-partners.png`

### Дизайн-токены (ключевое, полный список в design-system.md)

```
--bg:#070F16  --panel:#0A151C  --card:#0d1820
--heading:#F2F8FA  --body:#9AA7B4  --muted:#6B7886
--accent:#AECFE4  --accent-top:#C8DFEF  --accent-hover:#7DAACD
--accent-ink:#0A2540  --success:#10D180  --gold:#F5C24B
--radius-btn:8px  --radius-card:16px  --radius-lg:20px  --maxw:1180px
font: Satoshi (Fontshare), fallback Inter/system
```

---

## 5. Структура лендинга → план блоков

Порядок секций в `landingpage.html` (каждая = кандидат в ACF-блок):

1. **Header** — sticky, blur, лого «d digirelation», nav-ссылки (якоря) + CTA
   «Website-Check sichern» → kontakt. Моб. меню (бургер).
2. **Hero** — eyebrow-бейдж, H1 с подсветкой `.hl`, подзаголовок, 2 CTA
   (primary → kontakt, ghost → #referenzen), trust-row: Google-бейдж
   («4,9 von 5» — OFFEN) + KPI `100+ / 12 / 6`.
3. **Problem** — section-head + 3 карточки (иконка, заголовок, текст).
4. **Lösung/Differenzierung** — section-head + 3 карточки с check-листами.
5. **Features (Bento)** — feat-head (2 кол.) + grid 12: картинка `ueberuns.png`
   (span 7) + 3 карточки (accent «Individuell gebaut», «Technik», «Gefunden werden»).
6. **How it works** — section-head + grid: sticky-картинка `muki-alex-workspace.jpg`
   + 4 нумерованных шага с соединительной линией.
7. **Referenzen** — section-head + grid из **9 карточек** (картинка, название,
   мета «Branche · Ort»). → ACF **repeater**.
8. **Social Proof** — section-head + Google-баннер (4,9 — OFFEN) + 3 карточки
   отзывов (сейчас ПЛЕЙСХОЛДЕРЫ «(Offen)»). → ACF repeater.
9. **Logos** — strip из текстовых вордмарок (6 шт). OFFEN — заменить на лого.
10. **CTA** — панель с eyebrow, заголовком, чек-листом (3), кнопкой → kontakt.
11. **Footer** — лого, ссылки, копирайт.

`kontakt.html` — отдельная страница/блок с формой: поля **Unternehmen,
Vorname, Nachname, E-Mail, Mobilnummer** + опц. **aktuelle Website-URL**.
Endpoint воронки — OFFEN.

### Группировка для MVP (предложение)

Можно начать с минимума, дающего конверсию: **Hero + CTA + Kontakt-форма**,
затем добить остальные секции. Финально — один блок на секцию.

---

## 6. План работ (чеклист со статусами)

Статусы: `[ ]` todo · `[~]` в работе · `[x]` готово · `[!]` заблокировано

- [x] Ознакомиться с проектом и исходниками
- [x] Определить способ доступа (итог: ZIP-плагин + Code Snippets; REST закрыт)
- [x] Подтвердить движок: Gutenberg + ACF
- [x] Прочитать `landingpage.html` целиком
- [x] Прочитать и разобрать `kontakt.html`
- [ ] Завести каркас плагина (структура папок, главный файл, заголовок)
- [ ] Перенести CSS дизайн-системы в файл плагина, настроить enqueue
- [ ] Решить стратегию медиа (бандл в плагине vs импорт в Медиатеку) и реализовать
- [ ] Зарегистрировать ACF field groups в коде для каждой секции
- [ ] Собрать ACF-блоки + render-шаблоны (по секциям, начиная с Hero/CTA)
- [ ] Сверить каждую секцию со скриншотом исходника (дизайн 1:1)
- [ ] Блок/шаблон страницы контакта + форма; подключить endpoint воронки
- [ ] Собрать ZIP, отдать пользователю на загрузку
- [ ] Пользователь загружает плагин, создаёт страницу, расставляет блоки
- [ ] Финальная проверка на сайте (тёмная тема, шрифты, glow, адаптив, форма)

---

## 7. Журнал действий

- **2026-06-18 (Claude):** Изучил проект. Проверил сайт через REST — связь есть,
  но авторизация по Application Password не проходит (nginx режет заголовок
  `Authorization`, 401). Обнаружил, что сайт на Elementor Pro + Code Snippets +
  WP Rocket. Согласовал с пользователем: движок Gutenberg+ACF, деплой через
  ZIP-плагин (+ Code Snippets как канал PHP). Прочитал `landingpage.html`.
  Создал этот файл.
- **2026-06-18 (Claude):** Разобрал `kontakt.html` (структура + поля формы),
  занёс детали в разделы 4 и 5. Следующий шаг — каркас плагина.
- **2026-06-18 (Claude):** Добавил раздел 0 (правило «1 клик вместо токенов»).
  По просьбе пользователя очистил папку: УДАЛЕНЫ `landingpage.html`,
  `kontakt.html`, папка `assets/` (11 картинок). Оставлены 3 MD
  (`AGENT-HANDOFF.md`, `README.md`, `design-system.md`). Исходники не потеряны —
  бэкап в `Downloads\Telegram Desktop\performance-landingpage-website.zip`, плюс
  ждём НОВЫЙ ZIP с обновлённым контентом (см. раздел 7.1). Разбор в разделах 4–6
  описывает СТАРУЮ версию — при приходе нового ZIP переписать под него.

---

## 7.1. ОЖИДАНИЕ НОВОГО ZIP (актуальный статус)

**2026-06-18 (Claude):** Пользователь сказал НЕ собирать блоки сейчас. Скоро
пришлёт в Telegram **новый ZIP с обновлённым контентом / страницами** и ушёл
от компьютера. Поручено быть готовым сделать всё **самостоятельно**.

- Папка приёма: `C:\Users\sheap\Downloads\Telegram Desktop`
- Baseline на момент ожидания: новейший zip там — `performance-landingpage-website.zip`
  (mtime 18:31, это СТАРЫЙ исходник, уже распакован в проект — игнорировать).
- Рядом лежит `acf-export-2026-06-16.json` — вероятно выгрузка ACF field groups
  с сайта. Проверить при сборке (может задать структуру полей).
- **Правило детекта нового файла:** любой `*.zip` в папке приёма с mtime ПОЗЖЕ
  старта ожидания (запущен фоновый watcher с marker-файлом).

### Что делаю автономно, когда придёт новый ZIP:
1. Найти новый архив (watcher вернёт путь).
2. Распаковать во временную папку, просмотреть всё содержимое (новый контент,
   страницы, медиа, возможный ACF-export).
3. Сравнить со старой структурой, обновить разделы 4–6 этого файла.
4. Продолжить по согласованной архитектуре: каркас ZIP-плагина → CSS →
   ACF field groups → ACF-блоки по секциям. Всё фиксировать в журнале.
5. Не публиковать ничего наружу без пользователя; собрать ZIP и оставить
   готовым к загрузке + краткий отчёт.

### Зафиксированные решения по умолчанию (раз пользователь недоступен):
- **Сборщик:** Claude (я). Codex может подхватить по журналу.
- **Медиа:** импорт в Медиатеку на активации плагина (как просил пользователь —
  «медиа на сайте + используются через ACF»). Если новый ZIP диктует иное — адаптировать.
- Если новый ZIP кардинально меняет задачу — приоритет у нового контента,
  старый разбор (разделы 4–6) переписать, отметив в журнале.

---

## 7.2. ПАРАЛЛЕЛЬНАЯ ЗАДАЧА: WPForms-funnel (Immobilien) — ПРИШЛО 22:03

Прилетел архив `task-wpforms-formular.zip`. Начальник (Alexander) спросил:
«можно ли так строить сложные формы и интегрировать их».

**Что это:** многошаговый funnel продажи недвижимости (клон Aroundhome, 58 слайдов,
с ветвлением Haus/Wohnung/Gewerbe/Liegenschaft). Self-contained HTML
(`immobilien-formular.html`) с встроенным «мостом» к **WPForms Pro**:
- funnel = единственный видимый UI;
- настоящая WPForms-форма висит СКРЫТО (offscreen, НЕ display:none) на той же странице;
- по сабмиту JS заполняет её поля по значению и жмёт нативный submit WPForms
  → реальный Entry + нативная Lead-Mail по AJAX, без сторонних сервисов.
- Конфиг для dev — только блок `WPF` (formId + 27 Field-IDs). `formId:null` = превью.

Файлы задачи (в `_staging/form/task-wpforms-formular/`): `CLAUDE.md`,
`immobilien-formular-WPFORMS-SETUP.md` (dev-гайд EN, 27 полей + точные значения),
`immobilien-formular.html` (готовый), `memory.md` (статус).

### ВЕРДИКТ (Claude): ДА, реально и сделано грамотно.
Архитектура «кастомный funnel + скрытая WPForms» — валидный и удачный паттерн.
Код моста чистый (`wpfSet`/`pushToWPForms`/success-fail listeners), собирает
только отвеченные поля по пути. Превью-режим работает автономно.

### Жёсткие требования / на что смотреть:
1. **WPForms Pro ОБЯЗАТЕЛЕН** (хранение Entries — только Pro). На сайте сейчас
   НЕ установлен (проверено: ассеты wpforms дают 404; в REST namespace нет).
   → Нужна установка+лицензия. Это действие админа/начальника.
2. AJAX-сабмит ON; на этой форме НЕТ интерактивной CAPTCHA (иначе блокирует скрипт).
3. **Точное совпадение значений**: values у Dropdown/Checkbox в WPForms должны
   посимвольно совпадать с немецкими лейблами (вкл. `€`, слэши). #1 источник ошибок.
4. Слайды funnel — захардкоженный JSON в HTML (правится в коде, не в UI WPForms).
   То есть «сложная форма» = кастомный funnel, а WPForms — только бэкенд хранения/почты.

### Предложение по эффективности (экономит кучу кликов — правило разд.0):
Вместо ручной сборки 27 полей с точными значениями — **сгенерировать готовый
WPForms-экспорт (JSON) для импорта** (WPForms → Tools → Import). Тогда:
- поля и точные values создаются автоматически (убирает риск #3);
- я заранее знаю Field-IDs → сразу пропишу блок `WPF` в HTML.
Пользователю остаётся: импорт формы (1 действие) + вставка funnel-HTML и шортодка.
Блокер: сначала должен стоять WPForms Pro (нужна схема его import-JSON для проверки).

### Статус: WPForms Pro с лицензией ПОДТВЕРЖДЁН (пользователь, 2026-06-18).
Внешние проверки наличия неинформативны (readme.txt → 403 от правила безопасности,
css → 404 из-за версии путей) — полагаемся на слово пользователя.

**Текущий шаг:** генерируем готовый импорт-JSON формы (27 полей) + прописываем
ID в `immobilien-formular.html`. Чтобы попасть в формат экспорта WPForms,
ЗАПРОШЕН у пользователя образец реального экспорта (WPForms → Tools → Export →
любая форма → Export → .json). Ждём файл. После него:
1. собрать импорт-JSON на 27 полей с точными values (Appendix из SETUP.md);
2. вписать `WPF.formId` + 27 Field-ID в HTML;
3. отдать: файл импорта формы + готовый funnel-HTML + инструкцию вставки.

**Пользователю проще (правило разд.0):** импорт формы и вставка блоков — его клики;
генерация JSON/HTML — на мне.

### ГОТОВО (2026-06-18, Claude): импорт-форма + настроенный HTML собраны.
Получен реальный экспорт WPForms (3 формы) → схема ясна. Также получен PHP-шаблон
их `Kontaktformular` (поля Unternehmensname/Name/E-Mail/Mobilnummer + CRM-webhook
на `crm.digirelation.com`) — образец lead-пайплайна.

Поставка в папке `immobilien-funnel-wpforms/`:
- `immobilien-wpforms-import.json` — форма на 27 полей (show_values on, точные
  значения), готова к WPForms → Tools → Import. Валидна.
- `immobilien-formular.html` — funnel с прописанными Field-IDs 1…27; открыт только
  `WPF.formId` (та же цифра, что в шорткоде).
- `EINBAU.md` — короткая инструкция вставки.

Решения dev (Claude), отмечены в EINBAU.md:
- `telefon` сделал **Text** (а не Phone) — надёжнее для моста (smart-phone теряет
  программный set value). Мост не меняется, при желании вернуть Phone.
- Lead-уведомление → `support@digirelation.com`, отправитель `office@digirelation.com`.
- **CRM-webhook НЕ включил** (в экспорте лежит JWT-секрет; вебхук специфичен для
  Kontaktformular). Могу добавить тот же вебхук с маппингом полей — по запросу.

Сторож (bhmws9vno) остановлен по просьбе пользователя.

### Осталось от пользователя/начальника:
1. Импортировать `immobilien-wpforms-import.json` в WPForms.
2. Вставить funnel (Custom-HTML) + скрытый шорткод на страницу, прописать `formId`.
3. Решить: нужен ли CRM-webhook на эту форму (тогда добавлю).

### Правки по итогам теста на /test/ (2026-06-18, Claude):
Пользователь протестил — funnel работает, но: форма WPForms осталась видимой,
конверт не по центру, снизу «код». Диагноз: `formId` ещё не задан → режим превью
(дамп JSON показывается специально), форма не пряталась (класс не был повешен).
Исправил в `immobilien-formular.html`:
- `.confirm .mailic` → `display:block` (конверт по центру);
- добавил глобальный `.funnel-hidden-form` (off-screen, не display:none);
- добавил JS-автоскрытие: при заданном `WPF.formId` скрипт сам вешает класс на
  `.wpforms-container` → ручные Группа/класс больше не нужны.
JS проверен `node --check`. Файл переотправлен пользователю.
Пользователю осталось только вписать `formId` (= ID из шорткода).

### Проверка live через Chrome (2026-06-18, Claude), стр. /test/, форма id=1043:
- `WPF.formId=1043` подключён, новый скрипт активен, контейнер формы реально
  off-screen (`left:-99999px`, visibleOnScreen=false) — авто-скрытие РАБОТАЕТ.
  (get_page_text показывает поля, т.к. читает DOM за экраном — не баг.)
- БАГ: dev-баннер `.demo-banner` («Vorschau…») виден посетителям → добавил
  авто-скрытие баннера при заданном formId. Файл переотправлен. Альтернатива:
  удалить строку `<div class="demo-banner">…</div>` в HTML блока.
- В форме WPForms есть 2 ЛИШНИХ поля id 28 и 29 (добавлены вручную после
  импорта; 29 без label) — не из funnel, удалить в конструкторе WPForms.
- На форме включён WP Armour honeypot → нужен 1 реальный тест-сабмит для
  проверки прохождения заявки (создаёт entry + письмо на support@). Не делал
  без разрешения (уходит реальное письмо).

---

## 7.3. ЛЕНДИНГ собран как ACF-блок-плагин (2026-06-18, Claude)

Новый ZIP с обновлённым лендингом (905 строк: добавлены Hero+VSL-видео,
Vergleich-таблица, FAQ-аккордеон, горизонтальный stepper) распакован,
исходники восстановлены в `performance-landingpage-website/`.

Собран плагин `digirelation-landingpage-plugin/digirelation-landingpage.zip`:
- ACF-блок `digirelation/landingpage` (block.json + render.php), рендерит весь
  лендинг 1:1;
- CSS извлечён и **заскоуплен под `.digi-lp`** (иначе глобальные `body/header/
  footer/section/*` ломали бы тему); JS (reveal/menu/FAQ) в `assets/js`;
- 11 картинок забандлены в `assets/` (медиа-загрузка не нужна, REST закрыт);
- ACF field group в коде: cta_url, vsl_embed, vsl_badge, google_rating,
  google_reviews, testimonials(repeater). Поля → location block.
- PHP `php -l` чисто (8.3), block.json валиден.
Инструкция: `digirelation-landingpage-plugin/EINBAU-LANDINGPAGE.md`.
Отдано пользователю. Тексты секций пока в render.php (можно вынести в ACF позже).

Решение: один блок на всю страницу (а не 11 блоков по секциям) — ради скорости
и токенов; редактируемость покрыта по «Offen»-пунктам. Полная посекционная
разбивка — опционально следующим шагом.

---

## 7.4. РАБОЧИЙ API через браузер + лендинг развёрнут (2026-06-18, Claude)

**Прорыв по API:** App-Password по REST резался сервером, НО через залогиненную
сессию браузера (Chrome MCP) REST работает: cookie + `X-WP-Nonce`
(nonce из `wpApiSettings.nonce` или `/wp-admin/admin-ajax.php?action=rest-nonce`).
Проверено: `GET /wp/v2/users/me` → 200 (admin, id 1).

Ограничения API: кастомный плагин-ZIP по REST не ставится (только wp.org-слаги);
браузерный file_upload не берёт мои локальные файлы; медиа с диска не залить.
→ ZIP плагина пользователь поставил вручную (1 действие). Дальше всё по API.

Сделано по API:
- Плагин `digirelation-landingpage` активен, блок `digirelation/landingpage`
  зарегистрирован (проверено через `/wp/v2/block-types`).
- `POST /wp/v2/pages` → создана страница **id 1054** «Performance Landingpage»,
  status=draft, шаблон `elementor_canvas`, контент =
  `<!-- wp:digirelation/landingpage {"align":"full"} /-->`.
- Превью проверено: `.digi-lp` рендерится, тёмный фон #070F16, Satoshi, 11 секций,
  картинки 200 (broken был ложным из-за loading=lazy). Скриншот ОК.

Ссылки: превью `/?page_id=1054&preview=true`, редактор `post.php?post=1054`.
Статус: ЧЕРНОВИК (не публиковал — публикация публичного контента ждёт согласия).

### Полезное на будущее (для Codex/себя):
Рабочий способ дёргать REST: в админ-вкладке через `javascript_tool`:
`fetch('/wp-json/...',{headers:{'X-WP-Nonce':nonce},credentials:'include'})`.
nonce: `wpApiSettings.nonce` или fetch `admin-ajax.php?action=rest-nonce`.
Большие/секретные ответы фильтр Chrome может резать ([BLOCKED]) — запрашивать
минимум полей (`_fields=`), без nonce в выводе.

---

## 7.5. Контакт-страница развёрнута через API (2026-06-18, Claude)

`kontakt.html` (новая версия: Strategiegespräch, форма company/first/last/email/
phone/url) развёрнута как **Custom-HTML страница** через REST (cookie+nonce):
- `POST /wp/v2/pages` → id **1055**, slug `kontakt` (URL `/kontakt/`), draft,
  шаблон elementor_canvas, контент = `wp:html` блок.
- CSS заскоуплен под `.digi-lp`, ссылки «назад» → `/performance-landingpage/`.
- Рендер проверен скриншотом — ок.
- **CTA лендинга** (cta_url по умолчанию `/kontakt/`) теперь ведёт на эту страницу
  автоматически — поле не трогали.

ВАЖНО — форма на контакте **визуальная, не отправляет** (`action="#"`,
`onsubmit:return false`). Нужно подключить: либо WPForms-шорткод вместо HTML-формы,
либо endpoint их воронки. Открытый пункт.

Состояние: обе страницы — ЧЕРНОВИКИ (landing 1054, kontakt 1055). Публикация ждёт ок.

---

## 7.6. Лендинг → полная редактируемость через ACF (v1.1.0, 2026-06-18, Claude)

Пользователь: нужно, чтобы человек спокойно редактировал; подтвердил ACF.
Расширил плагин `digirelation-landingpage` до v1.1.0:
- хелпер `digi_lp_t($key,$default)` в render.php (значение поля или дефолт);
- **44 скалярных поля** (заголовки/интро/карточки/шаги/cta/footer) вынесены в
  render через `digi_lp_t`, дефолты = тексты из файла → вид не меняется;
- **репитеры**: `references` (img/name/branche/geo), `faqs` (frage/antwort),
  `testimonials` (quote/name/firma) — пока пусто → fallback на статичный контент;
- ACF field group вынесена в `acf-fields.php` (64 поля), сгруппирована **вкладками**
  по секциям (Allgemein/Hero/Problem/Lösung/Vergleich/Leistungen/Ablauf/
  Referenzen/Kundenstimmen/FAQ/CTA/Footer); главный плагин-файл подключает её
  через require (старая инлайн-группа удалена).
- Все 3 PHP `php -l` чисто. Генератор: `_gen_acf.py` (в папке плагина-исходника).

Каналы итерации: апдейт плагина = re-upload ZIP («Заменить текущую версию»).
Не вынесено в ACF (фаза 2, при желании): hero H1 (из-за span.hl), check-листы
Lösung, list-items Leistungen, строки таблицы Vergleich, logo-strip, KPI, eyebrows.

Статус: ZIP v1.1.0 отдан пользователю на re-upload. После — проверить рендер по API.

---

## 7.7. Исправление критической ошибки редактора (v1.2.1, 2026-06-19, Codex)

Подхватил работу после лимита Claude. На сайте активна v1.2.0; фронтенд лендинга
открывается, но `post.php?post=1054&action=edit` стабильно показывает WordPress
Critical Error. Текст PHP-фатала сервер не выводит.

В `render.php` устранены два рантайм-риска, которые не обнаруживает `php -l`:
- `digi_lp_checks()` теперь защищена `function_exists`, как остальные хелперы;
  это предотвращает `Cannot redeclare` при повторном серверном рендере ACF-блока;
- получение инициала отзыва больше не требует обязательного `mbstring` и имеет
  fallback на `substr`/`strtoupper`.

Версия поднята до **1.2.1**, ZIP пересобран:
`digirelation-landingpage-plugin/digirelation-landingpage.zip`.
Проверки: все 3 PHP-файла проходят `php -l`; отдельный тест дважды подключает
`render.php` в одном процессе — `double-render-ok`; внутри ZIP подтверждена v1.2.1.

Следующий шаг: пользователь заменяет активный плагин этим ZIP. После загрузки
повторно открыть редактор страницы 1054. Если фатал сохранится — получить точный
текст из письма WordPress admin email или временно включить логирование через
Code Snippets, вместо дальнейших предположений.

- **2026-06-19 (Codex):** Сам загрузил v1.2.1 через WP Admin. Первый архив,
  собранный `Compress-Archive`, содержал Windows-разделители путей; WordPress
  создал лишний уровень каталога и не смог активировать файл. Архив пересобран
  с POSIX-путями. Чтобы не удалять неактивную копию без отдельного подтверждения,
  рабочая версия установлена рядом в каталоге `digirelation-landingpage-v121`
  и активирована. Редактор страницы 1054 снова открывается без Critical Error.
  В DOM редактора подтверждены: 101 ACF-поле, 12 вкладок, 9 репитеров и 5 полей
  выбора иконок. Старая сломанная v1.2.1 осталась неактивной в списке плагинов;
  её можно безопасно удалить после подтверждения пользователя.

---

## 7.8. Responsive QA опубликованного лендинга (2026-06-19, Codex)

Проведён read-only QA страницы `/performance-landingpage/` на 1440×900,
1024×768, 768×1024 и 390×844. Ничего на сайте не изменялось.

Подтверждено рабочее: все изображения загружаются; горизонтального overflow у
документа нет; desktop-сетки помещаются; на 768/390 hero и карточки переходят в
одну колонку, stepper — 2 колонки на планшете и 1 на мобильном; Vergleich
переключается в карточный вид; все якоря существуют, повторяющихся ID нет.

Найдены проблемы:
1. **Critical:** все CTA ведут на `/kontakt/`, но URL отвечает 404. Контактная
   страница 1055 осталась черновиком/не опубликована.
2. **High:** mobile menu не открывается по клику; FAQ не раскрывается. `landing.js`
   загружен, reveal-анимация работает, но обработчики menu/FAQ фактически не
   срабатывают на опубликованной странице. Нужна отдельная диагностика порядка
   выполнения/оптимизации WP Rocket.
3. **Medium:** на mobile текст FAQ наследует `white-space: nowrap` от темы;
   длинные вопросы выходят за кнопку и обрезаются из-за `overflow-x:hidden`.
   Нужен scoped override `.digi-lp .faq-q span{white-space:normal;min-width:0}`.
4. **Medium:** по скриншоту пользователя hover логотипа делает wordmark чёрным
   на тёмном фоне. Нужен scoped hover-color для `.digi-lp .logo:hover`.
5. **Content blockers:** VSL не задан (play-кнопка является заглушкой), Google
   reviews показывают `Offen`, testimonials — `(Offen)` placeholders.
6. **External:** Cookiebot пишет, что `start.digirelation.com` не авторизован в
   domain group; баннер согласия может не показываться.

---

## 7.9. Исправления QA + опубликованный Kontakt (v1.3.1, 2026-06-19, Codex)

По прямому разрешению пользователя исправлено и развёрнуто:
- `landing.js` переведён на делегированные click-обработчики: mobile menu и FAQ
  работают даже при изменении порядка/задержке скриптов WP Rocket;
- scoped CSS фиксирует hover логотипа и перенос длинных FAQ-вопросов;
- пустой VSL больше не показывает нерабочую play-заглушку; hero становится
  одноколоночным; `Offen` и фиктивные testimonials скрыты до заполнения ACF;
- добавлен динамический шаблон `[digirelation_contact]` для страницы 1055;
- форма контакта отправляет через `admin-post.php` на `support@digirelation.com`,
  с nonce, honeypot, серверной валидацией и Reply-To заявителя;
- страница Kontakt (1055, `/kontakt/`) опубликована; CTA больше не ведут на 404;
- исправлен mobile min-content overflow формы;
- активна версия плагина **1.3.1**.

Проверено live до финальной настройки кэша:
- desktop FAQ раскрывается;
- mobile 390×844: menu открывается, FAQ раскрывается, FAQ текст переносится,
  document overflow отсутствует, изображения целы;
- tablet 768×1024: hero/reference = 1 колонка, steps = 2 колонки, overflow нет;
- Kontakt: опубликован, динамический шаблон загружен, endpoint/nonce/required
  fields присутствуют; пустая форма блокируется HTML5-валидацией без отправки.

WP Rocket дважды отдавал старый Remove Unused CSS на канонических URL. Поэтому
для страниц 1054 и 1055 отключены только `Remove Unused CSS` и
`Delay JavaScript execution`; cache/minify/defer оставлены. После этого выполнены
`Clear Used CSS` и `Clear and Preload Cache`. Последний повторный browser smoke-test
канонических URL не запустился из-за локальной browser-policy пользователя;
обход не предпринимался. До этого все изменения подтверждены на fresh URL и в
авторизованном Chrome; финальный визуальный refresh пользователю желателен.

---

## 7.11. VSL-URL, кликабельный Google-бейдж, ACF-контакт, ЖИВЫЕ Google-отзывы (v1.6.0→1.7.0, 2026-06-19, Claude)

Подхватил после Codex (была v1.5.3). По запросу пользователя:
- **VSL:** добавлено поле `vsl_url` (text) — YouTube/Vimeo ссылка авто-встраивается
  в iframe (`digi_lp_video_embed`). Приоритет: vsl_url → vsl_embed → play-заглушка.
- **Google-бейдж кликабельный:** поле `google_link` (text) — Hero-бейдж и
  proof-баннер становятся `<a target=_blank>`. Поле `grw_id` для виджета.
- **«Offen» убрано** везде (hero + proof score).
- **Контакт-страница (1055) → полностью ACF:** новая field group `group_digi_contact`
  (location page==1055): eyebrow, h1, sub, repeater вортайлов, google rating/reviews/
  link, тексты формы. `contact.php` переписан на `get_field(...,1055)` с дефолтами.
- **ВСЕ url-поля → type text** (ACF `url`-валидатор резал Google-ссылки с `#lrd=` и
  ломал сохранение страницы 1055 «Validation failed»). Это была причина ошибки.
- **ЖИВЫЕ Google-отзывы (главное):** плагин RichPlugins «Widget for Google Reviews»
  (`[grw id="1101"]`) рендерит отзывы **server-side** (подтверждено: `wp-google-text`
  и `data-count="8"` есть в СЫРОМ HTML лендинга через `fetch` до JS). render.php
  теперь сам вызывает `do_shortcode('[grw id=X]')`, парсит через DOMDocument
  (`digi_lp_parse_grw`): имя `.wp-google-name`, текст `.wp-google-text`, рейтинг
  `--rating:` из `.rpi-stars`, count из `data-count`. Реальные отзывы (до 6,
  обрезка 240 симв. `digi_lp_clip`) подставляются вместо `(Offen)`-плейсхолдеров;
  count заполняет бейдж если ACF `google_reviews` пуст. Рейтинг остаётся ACF (4,9) —
  НЕ перетираю средним по загруженным (все 8 = 5★, но реальный Google = 4,9).
  Прежний клиентский JS-мост (v1.6.2) откатан — всё server-side, надёжнее с WP Rocket.

Проверки: все 5 PHP `php -l` чисто; парсер протестирован локально на реальном
HTML виджета (PHP 8.3) → count 8, 6 отзывов с именами/обрезкой корректно;
server-side рендеринг GRW подтверждён на живом сайте через Chrome. `grw_id`=1101
уже стоит в ACF. ZIP: `digirelation-landingpage-v1.7.0.zip` (корневая папка внутри
= `digirelation-landingpage-v121/`, чтобы WP предлагал Replace активного плагина).

Осталось от пользователя: загрузить ZIP (Replace current) + WP Rocket «Clear cache».
После этого Claude верифицирует фронт через браузер.

### Доустановка v1.7.1 + способы деплоя (2026-06-19, Claude)
Пользователь попросил «сделай ты это» (залить самому). Проверено, какие каналы
деплоя кастомного ZIP реально доступны через залогиненный Chrome:
- ❌ **REST install** — только wp.org-слаги, кастомный ZIP нельзя (уже знали).
- ❌ **Chrome file_upload** — песочница: «only files the user has shared with
  this session» — мои локальные ZIP не принимаются даже после SendUserFile.
- ❌ **Plugin/Theme File Editor** (`plugin-editor.php`) — отключён на сервере
  (`DISALLOW_FILE_EDIT`): «Sorry, you are not allowed to access this page».
- ✅ **Единственный канал: пользователь сам грузит ZIP** (Plugins → Upload →
  Replace current). Логин в wp-admin — тоже только пользователь (пароль вводить
  нельзя). Claude может: подготовить ZIP, верифицировать результат через браузер.
**Вывод для будущего:** не тратить попытки на авто-загрузку ZIP — сразу отдавать
файл пользователю + просить установить, затем Claude проверяет фронт.

v1.7.1: по просьбе убран счётчик отзывов — оба бейджа показывают просто
«Bewertungen auf Google» без числа (рейтинг 4,9, реальные отзывы остаются).
Проверено live через Chrome: цифр не осталось (`no digit`), 6 реальных отзывов
рендерятся, рейтинг 4,9. Установлено пользователем, верифицировано Claude.

---

## 7.12. Impressum/Datenschutz im Footer + Mail-Zustellbarkeit + Security (v1.8.0, 2026-06-19, Claude)

По запросу пользователя — 3 вещи:

**1. Footer-Rechtslinks.** В оба футера добавлены ссылки на
`https://www.digirelation.com/impressum/` и `.../datenschutz/`
(target=_blank, rel=noopener):
- лендинг: `blocks/landingpage/render.php` (nav `.foot-links`);
- контакт: `contact.php` (nav `.foot-links`).
Используют существующий стиль `.foot-links a` — CSS не трогал.

**2. Форма контакта — почему могла «не работать» и фикс.** Структурно форма
корректна (admin-post.php + nonce + honeypot + sanitize + валидация). Риск был в
ДОСТАВКЕ: `wp_mail()` слался без `From`-заголовка → дефолтный отправитель
`wordpress@start.digirelation.com`, который часто режется SPF/спам-фильтром.
Взял рабочие настройки отправителя из `immobilien-wpforms-import.json`
(notifications: `sender_address=office@digirelation.com`,
`email=support@digirelation.com`) и прописал в хендлер:
- `From: digirelation <office@digirelation.com>` (своя почта домена → SPF/доставка);
- `Reply-To:` остаётся на заявителя;
- получатель `support@digirelation.com` (как был).
Если письма ВСЁ РАВНО не дойдут — причина серверная (нет SMTP-релея); тогда
нужен SMTP-плагин/настройка хоста, это вне плагина.

**3. Security-аудит всего плагина.** Проверены render.php, contact.php, главный
файл. Состояние хорошее: весь вывод через `esc_html`/`esc_url`/`esc_attr`;
GRW-отзывы и тестимониалы экранируются; `vsl_embed` через `wp_kses` (whitelist
iframe/video/source); `vsl_url` только YouTube/Vimeo по regex; nonce + honeypot
на форме; `sanitize_text_field` уже срезает переводы строк → header-injection в
Reply-To закрыт. Доусиление: добавлены лимиты длины полей формы
(company 150 / Namen 100 / phone 40) против abuse и раздувания письма.

Версия → **1.8.0**, ZIP пересобран с POSIX-путями, корень внутри =
`digirelation-landingpage-v121/` (= активная директория → WP предложит «Replace
current»). Все 3 PHP `php -l` чисто.
ZIP: `digirelation-landingpage-plugin/digirelation-landingpage-v1.8.0.zip`.

**От пользователя (1 клик каждое):**
1. Plugins → Upload → выбрать `digirelation-landingpage-v1.8.0.zip` →
   «Replace current with uploaded» → Activate.
2. WP Rocket → Clear cache.
3. **Тест формы:** открыть `/kontakt/`, отправить тестовую заявку → проверить,
   пришло ли письмо на `support@digirelation.com`. Это единственный надёжный
   способ подтвердить доставку (реальное письмо — поэтому делает пользователь).
   Если не пришло — см. п.2 выше (серверный SMTP).

---

## 8. Открытые вопросы / нужно от пользователя

Из `README.md` (перед публикацией):
- Реальная оценка Google и число отзывов (для Google-баннера). Сейчас «4,9 — Offen».
- Реальные тестимониалы (имя, фирма, цитата). Сейчас плейсхолдеры «(Offen)».
- **Endpoint/URL формы** бесплатного Website-Check (куда отправлять заявку).
- Настоящие файлы логотипов для logo-strip (сейчас текстовые вордмарки).
- Hero-визуал (по желанию).

Технические — уточнить по ходу:
- Стратегия медиа: бандлить картинки в плагин или импортировать в Медиатеку?
- Нужен ли блок контакта на самой странице или отдельная WP-страница?
