# Интеграция Propstack ↔ Rosenberger — заметки и грабли

Документ для будущих сессий: как устроена интеграция, на что не наступать,
чек-листы. Дополнять при каждом нетривиальном изменении.

## Архитектура (вариант B)

- Объекты приходят из **Propstack CRM** через плагин `propstack-real-estate`
  (CPT `propstack_property`, мета `_property_*`). **Тело плагина правим
  по-минимуму** и точечно (легко мёрджить с версиями Alex).
- **Оформление — в теме `rosenberger`.** Тема «мостит» данные плагина к нашим
  блокам, чтобы переиспользовать готовый дизайн:
  - `theme/inc/propstack-bridge.php` — фильтр `get_post_metadata`: у постов
    `propstack_property` отдаёт `property_*` (наши ключи) из плагинных
    `_property_*`, приводя форматы («85 m²», «450.000 €», статус «Verfügbar»).
  - `theme/templates/single-propstack_property.html` — копия `single-property.html`,
    переиспользует блоки `rosenberger/property-*`.
  - `theme/templates/archive-propstack_property.html` — архив (`/immobilien/`).
  - Каталог с фильтрами — блок `rosenberger/property-catalog` (страница
    `/alle-immobilien/`, ядро `rosenberger-core`), `post_type=propstack_property`.
  - `theme/inc/propstack-leads.php` — WPForms «Kontakt» (id 180) → `send_lead()`
    (асинхронно через cron-событие `rosenberger_propstack_send_lead`).

## Доступы / окружение

- Деплой: `node scripts/deploy-stack.mjs [--changed]` (тема + `rosenberger-core`)
  через Code Snippets REST. Доступ — только **WP app-password REST** в `.env`
  (`WP_URL`, `WP_USER`, `WP_APP_PASSWORD`). SFTP/SSH нет.
- Файлы самого плагина Propstack deploy-stack НЕ деплоит — для них
  `node scripts/push-propstack-files.mjs` (список файлов в `FILES`).
- Хостинг — **Plesk** (`cp.digirelation.com`), БД `rosenberger_db`, **префикс
  таблиц `ri_`**, phpMyAdmin через панель (SSO).
- Propstack API-ключ — в WP-админке (Propstack → API Verbindung). База
  `https://api.propstack.de/v1`, заголовок `X-Api-Key`.

## ГРАБЛИ (на что наступали — не повторять)

1. **НЕ ставить плагин через большой ГЛОБАЛЬНЫЙ Code Snippet.** `deploy-propstack.mjs`
   (удалён) лил 33 файла base64 + `activate_plugin` на КАЖДОМ запросе → staging
   ушёл в жёсткий 500 (REST/админка тоже легли), сниппет «воскрешал» плагин.
   Ставить штатно (zip/WP-CLI) или точечным `push-propstack-files.mjs` (он плагин
   НЕ активирует). На PHP 8.3 плагин активируется чисто через wp-admin.

2. **Recovery при жёстком 500:** Plesk File Manager → переименовать папку плагина
   в `*.off` (WP пропустит отсутствующий активный плагин); если виноват сниппет —
   удалить строку в `ri_snippets` (phpMyAdmin). REST в этот момент мёртв.

3. **`/units` отдаёт всего ~33 поля** (нет описаний, энергопаспорта, Objektart,
   оснащения). Нужен **`/units?expand=1`** → полный объект (302 поля). Уже стоит в
   `api-client::get_properties`.

4. **expand переименовывает поля и оборачивает скаляры в `{label,value}`:**
   - `status` → **`property_status`** ⚠️ (из-за этого `determine_post_status` не
     видел id → ВСЕ объекты молча уходили в черновики; на фронте пусто, одиночные
     видны только админу). **После любых правок expand проверять REST
     `?status=publish`, а не только что синк прошёл.**
   - описания: `short_note` (Kurz), `description_note` (Objektbeschreibung),
     `long_description_note`, `furnishing_note`, `location_note`, `other_note`.
   - Objektart: `object_type`/`rs_type` (коды APARTMENT/LIVING → нем. подписи).
   - энергия (AT): `aut_hwb`, `aut_hwb_class`, `aut_fgee`, `aut_fgee_class`.
   - площади: `living_space`, `net_floor_space` (Nutzfläche), **`plot_area`**
     (Grundstück) — НЕ `property_space_value` (это дубль жилой площади!).
   - `safe()` разворачивает `{label,value}`, `normalize_expand()` мапит имена —
     в `class-field-mapper.php`. `compute_hash()` тоже через `safe()`.

5. **Публичные статусы.** Плагин Sync → «Öffentliche Status-IDs» по умолчанию
   `1,2` (заглушка). Реальные: **Vermarktung=243237, Reserviert=243238**. Без них
   объекты не публикуются.

6. **Галерея.** Плагин импортировал фото, но не сохранял их ID — `sync-service`
   теперь пишет `_property_gallery_ids` + ставит featured. Залить фото в Propstack
   через браузер нельзя (песочница file_upload), API images отдаёт 302. Демо-фото
   ставим на стороне WP (`_property_gallery_ids` / featured из медиатеки staging).

7. **Каталог фильтрует по `property_*_num`** (числовые спутники). Для propstack их
   генерит `rosenberger_pc_propstack_numeric` из `_property_*`. `post_type` каталога
   = **`propstack_property`** (массив `[property, propstack_property]` показывает и
   6 старых демо-объектов CPT `property` — был баг «9 вместо 3»).

8. **API-ключ — права.** У текущего ключа, похоже, только **Units: Lesen**
   (`GET /contacts` отдаёт пусто). Для моста лидов нужны **Contacts: Lesen+Schreiben,
   Activities: Schreiben** — это включает Alex в Propstack.

9. **WPForms на staging виснет** на отправке письма (`wp_mail`, нет SMTP) — НЕ наш
   баг. Мост лидов поэтому асинхронный (cron), не блокирует форму.

10. **Карусели:** общий `rb-carousel.js` делает картинки слайдов инертными
    (`pointer-events:none`, `draggable=false`) — тащим карусель, а не картинку.

## Текущее состояние

- 3 тест-объекта в Propstack (5599404 Dornbirn→post 197, 5599530 Bregenz→198,
  5599540 Feldkirch→199). У Dornbirn — полные данные (описание, галерея, аккордеоны).
- Single + архив + каталог `/alle-immobilien/` рендерят объекты в дизайне Rosenberger.

## TODO

- Фильтры **Kaufen/Mieten** и **Objektart** в каталоге не фильтруют propstack —
  объекты не помечены нашими таксономиями `property-type`/`property-city` и мета
  `property_listing_type` (плагин ставит свои `property_type`/`property_city`/
  `marketing_type`). Решение: при синке миррорить в наши таксономии + тип сделки.
- Включить у API-ключа права Contacts+Activities и проверить мост лидов вживую.
- Очистить тест-данные: 3 объекта в Propstack + posts 197/198/199 + демо-фото/тексты.
- (опц.) контакт-карточка объекта могла бы показывать реального маклера из Propstack
  (`broker`) вместо агента из настроек сайта.
