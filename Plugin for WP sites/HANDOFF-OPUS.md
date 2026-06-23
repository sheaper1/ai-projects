# Handoff для Opus — незакрытые блоки и задачи

> Этот файл ведёт Claude Sonnet. Задачи здесь — либо сложные (требуют больше
> рассуждений), либо отложенные пользователем. Забери и доделай.
>
> Перед началом: прочитай `AGENTS.md` (§6a плейбук качества), `CHANGELOG.md`
> (хвост), `MEMORY.md`. Сделай `git pull`.

---

## 1. Многошаговая форма оценки недвижимости (ГЛАВНОЕ)

**Страница:** `immobilienbewertung`  
**Место в странице:** секция 8 (сейчас заменена заглушкой `consultation-cta`)  
**Figma:** файл `p1HKLfoMcOwtVUD5rI9V3P`, страница «UI Design»

### Что нужно сделать

Создать новый блок `library/property-evaluation-form` — многошаговая форма
оценки недвижимости (4 шага).

**Шаг 1 — тип объекта:**
- Три варианта с иконками: Wohnung / Haus / Grundstück
- Выбор одного из трёх (radio-style карточки)

**Шаг 2 — площадь и год постройки:**
- Поле «Wohnfläche» (m²) — числовой input
- Поле «Baujahr» — числовой input

**Шаг 3 — адрес:**
- Поле «Adresse» (улица, город)

**Шаг 4 — контакт:**
- Поле «Ihr Name»
- Поле «Telefon oder E-Mail»
- Кнопка отправки «Bewertung anfragen»
- После отправки — thank-you сообщение

### Реализация

- **Блок динамический** (`render.php`), JS-логика (шаги) инлайном или отдельным
  `src/view.js` (не в `edit.js`).
- PHP-отправка через `wp_mail()` на email из настроек сайта
  (`rosenberger/setting → contact_email`). Тема письма: `Bewertungsanfrage: {тип} — {имя}`.
- Данные формы НЕ хранятся в БД (простой mail-only workflow).
- Прогресс-бар шагов вверху (1/4, 2/4, ...).
- Кнопки «Weiter» / «Zurück».
- CSS: через токены проекта, без хардкода цветов.

### После создания блока

1. Скопировать блок из `library/blocks/property-evaluation-form/` в
   `projects/rosenberger/theme/blocks/property-evaluation-form/`
2. Зарегистрировать в `projects/rosenberger/theme/functions.php`
   (по образцу других блоков там же)
3. В `scripts/import-immobilienbewertung.mjs` **заменить** секцию
   `<!-- wp:library/consultation-cta ... -->` (строки ~177–185) на
   `<!-- wp:library/property-evaluation-form {} /-->`
4. `npm run build` → `npm run check` → `node scripts/deploy-stack.mjs`
   → `node scripts/import-immobilienbewertung.mjs`
5. Визуальная сверка по §6a: `npm run shot`, сравни с Figma-нодой

### Figma-нода формы

Сначала найди секцию через `get_metadata` на ноде desktop-страницы
(из `AGENTS.md §10`). Ищи секцию с заголовком «Ihre kostenlose Bewertung anfragen»
(или похожим) — она 8-я сверху. Потом `get_design_context(nodeId)` для точных значений.

---

## 2. Тексты-черновики в `import-immobilienbewertung.mjs`

Файл: `scripts/import-immobilienbewertung.mjs`

| Что | Статус |
|-----|--------|
| FAQ ответы 2–5 | Черновик (в Figma свёрнуты) — нужен ревью пользователя или сверка с Figma раскрытием |
| Заголовок Process Steps | «Ihre Bewertung in drei Schritten» — исправлено от ошибки в Figma («Vermietung/fünf»), уточни у пользователя |
| Заголовок FAQ | «zur Bewertung» — исправлено от ошибки в Figma («zur Vermietung»), уточни у пользователя |
| CTA slug `/kontakt/` | Уточни актуальность у пользователя |

---

## 3. Технический долг (по возможности)

- `faq-section/src/style.scss` строка 2: `padding: 150px ...` — хардкод px,
  должно быть `var(--wp--custom--layout--section-space)`. Исправить глобально
  (library + projects/rosenberger/theme). Не забыть `npm run check` после.

---

## Порядок работы

1. Задача #1 (форма) — главная, начни с неё.
2. Задача #2 (тексты) — покажи пользователю на ревью, не правь молча.
3. Задача #3 (tech debt) — если остаётся время.

После каждой задачи: `CHANGELOG.md` → коммит `[Opus] …` → `git push`.
Убери выполненное из этого файла (или удали файл, если всё закрыто).
