// Переиспользуемый мульти-агентный QA страницы: наложение ДО + параллельный фикс →
// один деплой → наложение ПОСЛЕ + параллельная верификация.
//
// ⛔ PREFLIGHT (память qa-workflow-preflight) — 3 проверки ПЕРЕД запуском:
//   1. Запускать через Workflow({ scriptPath: '<этот файл>', args }), НЕ {name}
//      ({name} кэширует старую версию). После launch — grep снапшота на `urlOf`.
//   2. Каждый section.url реально содержит секцию: curl <url> | grep <sel> > 0, код 200.
//   3. Figma-нода = нода СЕКЦИИ (не страницы), выверена по тексту сидера; страница
//      контентом совпадает с Figma-демо (region/bludenz ↔ Figma Bludenz).
//
// args = {
//   url: 'https://rosenberger.digirelation.dev/',
//   sections: [ { slug, sel, node, mnode, hint }, ... ]   // node/mnode — Figma desktop/mobile node-id
// }
// Серийным остаётся ТОЛЬКО build+deploy (один staging физически не параллелится).
//
// АРТЕФАКТЫ — по ПАПКЕ НА БЛОК: .visual/qa/<slug>/
//   figma.png          — рендер дизайна (снимает Fix, переиспользует Verify)
//   before-live.png    — живая секция ДО фикса
//   before-overlay.png — наложение ДО  (пурпур=Figma / зелёный=лайв / серое=совпало)
//   after-live.png     — живая секция ПОСЛЕ деплоя
//   after-overlay.png  — наложение ПОСЛЕ
// Так оркестратор (или человек) открывает одну папку блока и за секунду видит
// «до vs после» — справился агент или нет, и где докрутить промпт/структуру.
// Контент/дефолты блока (тексты, <br>) — в block.json-дефолтах или сидере, не в SCSS.

export const meta = {
  name: 'qa-page-parallel',
  description: 'Мульти-агентный QA страницы: наложение ДО + фикс ∥ → деплой → наложение ПОСЛЕ + верификация ∥',
  phases: [
    { title: 'Fix', detail: 'агент на секцию: наложение ДО → QA vs Figma (desktop+mobile) → фикс своего блока' },
    { title: 'Deploy', detail: 'build --force + check + deploy + свежий замер' },
    { title: 'Verify', detail: 'агент на секцию: наложение ПОСЛЕ + числа → вердикт pass/fail' },
  ],
}

const A = typeof args === 'string' ? (() => { try { return JSON.parse(args) } catch { return {} } })() : (args || {})
const URL = A.url || 'https://rosenberger.digirelation.dev/'
const SECTIONS = A.sections || []
if (!SECTIONS.length) { log('Нет секций в args.sections — нечего проверять.'); return { error: 'no sections' } }

// URL-на-секцию: каждая секция может жить на своей странице (CPT single, архив…).
// Деплой один (все блоки в теме), но шоты/замеры — по странице секции.
const urlOf = (s) => s.url || URL
const pageKey = (u) => (String(u).replace(/[?#].*$/, '').replace(/\/+$/, '').split('/').pop() || 'home')
const numbers = (s) => `.visual/qa-${pageKey(urlOf(s))}`   // -1440.json / -375.json

// Папка артефактов блока (одна на секцию, before/after внутри).
const dir = (s) => `.visual/qa/${s.slug}`

const FIX_SCHEMA = { type: 'object', additionalProperties: false, required: ['slug', 'clean', 'defects', 'changes', 'before'], properties: {
  slug: { type: 'string' }, clean: { type: 'boolean' },
  defects: { type: 'array', items: { type: 'string' } },   // что нашёл по наложению ДО + числам
  changes: { type: 'array', items: { type: 'string' } },   // что поправил в блоке
  // Пути к снимкам ДО — оркестратор сам их прочитает и сверит с ПОСЛЕ.
  before: { type: 'object', additionalProperties: false, required: ['overlay', 'live'], properties: {
    overlay: { type: 'string' }, live: { type: 'string' }, figma: { type: 'string' },
  } },
} }
const VERIFY_SCHEMA = { type: 'object', additionalProperties: false, required: ['slug', 'verdict', 'checks', 'remaining', 'after'], properties: {
  slug: { type: 'string' }, verdict: { type: 'string', enum: ['pass', 'fail'] },
  checks: { type: 'array', items: { type: 'string' } }, remaining: { type: 'array', items: { type: 'string' } },
  // Пути к снимкам ПОСЛЕ для НЕЗАВИСИМОЙ сверки оркестратором (он сам Read overlay/live).
  after: { type: 'object', additionalProperties: false, required: ['overlay', 'live'], properties: {
    overlay: { type: 'string' }, live: { type: 'string' }, figma: { type: 'string' },
  } },
} }

const RULES = `Проект Rosenberger, working dir A:\\Projects\\AI project\\Plugin for WP sites. Figma fileKey p1HKLfoMcOwtVUD5rI9V3P.
Правила: стили только токенами theme.json (var(--wp--...)); структурные ширины можно px; переносы <br> ровно как в Figma (render через wp_kses/wp_kses_post, не esc_html); вес/шрифт точно по дизайну; адаптив по мобильному макету (375, поля 16px).
Figma через ToolSearch (get_design_context, get_screenshot, get_metadata). Живая секция: node scripts/shot.mjs <url> --sel "<sel>" --name <имя>. Наложение: node scripts/section-diff.mjs --figma <png> --live <png> --out <prefix> + Read overlay (серое=совпало, пурпур=Figma/зелёный=лайв=сдвиг).
ВАЖНО: контент блока (тексты, <br>) часто в block.json-ДЕФОЛТАХ или в сидере страницы, а не в SCSS — если перенос/текст не тот, проверь дефолты block.json.`

const fixPrompt = (s) => `${RULES}

Ты QA-чинишь ОДНУ секцию: ${s.slug}. ${s.hint || ''}
Live ${urlOf(s)} · селектор ${s.sel} · Figma desktop ${s.node}${s.mnode ? ' · mobile ' + s.mnode : ''}.
Папка артефактов: ${dir(s)}/ — сначала создай: mkdir -p "${dir(s)}".
Папка блока (правь ТОЛЬКО её): projects/rosenberger/theme/blocks/${s.slug}/ (SCSS/render.php/block.json дефолты).

ШАГ 0 — НАЛОЖЕНИЕ ДО ФИКСА (обязательно, до любых правок кода):
  - дизайн: get_screenshot ${s.node} (maxDimension 1400) → curl -o "${dir(s)}/figma.png" "<url>"
  - лайв:   node scripts/shot.mjs ${urlOf(s)} --sel "${s.sel}" --name qa/${s.slug}/before-live
  - наложение: node scripts/section-diff.mjs --figma "${dir(s)}/figma.png" --live "${dir(s)}/before-live.png" --out "${dir(s)}/before"
  - сам Read "${dir(s)}/before-overlay.png" — зафиксируй расхождения (это base для defects).

ШАГ 1-3 — ПОЧИНКА:
  1) сними точные значения Figma-ноды (вес/размер/ширина/переносы/токены);
  2) сопоставь с числами/наложением ДО;
  3) почини расхождения в своём блоке (переносы — <br>+wp_kses, значения — токены, адаптив 375).

clean=true только если уверен, что починил всё. В before верни фактические пути:
overlay="${dir(s)}/before-overlay.png", live="${dir(s)}/before-live.png", figma="${dir(s)}/figma.png".
ЗАПРЕТ: не трогай theme.json, другие блоки, build/. НЕ запускай build/deploy/git. Верни строго по схеме.`

const verifyPrompt = (s) => `${RULES}

Секция ${s.slug} уже ЗАДЕПЛОЕНА. Ты ВРАЖДЕБНЫЙ ревьюер: цель — НАЙТИ изъяны, а не подтвердить.
НЕ оправдывай дефект («low impact»/«нет токена»/«content-driven» = ЗАПРЕЩЕНО). Сомневаешься → fail.
Live ${urlOf(s)} · селектор ${s.sel} · Figma ${s.node}. Числа: ${numbers(s)}-1440.json (1440), ${numbers(s)}-375.json (375).
Папка артефактов: ${dir(s)}/ (там уже figma.png и before-*.png от фазы фикса — НЕ трогай их, пиши after-*).

ШАГ 1 — НАЛОЖЕНИЕ ПОСЛЕ (обязательно, и смотри глазами):
  - дизайн уже есть: ${dir(s)}/figma.png (если файла нет — пере-сними: get_screenshot ${s.node} → curl -o "${dir(s)}/figma.png" "<url>").
  - лайв:   node scripts/shot.mjs ${urlOf(s)} --sel "${s.sel}" --name qa/${s.slug}/after-live
  - наложение: node scripts/section-diff.mjs --figma "${dir(s)}/figma.png" --live "${dir(s)}/after-live.png" --out "${dir(s)}/after"
  - сам Read "${dir(s)}/after-overlay.png" И Read "${dir(s)}/after-live.png" (серое=совпало, пурпур=Figma/зелёный=лайв=сдвиг), не только stdout.
  - сравни с "${dir(s)}/before-overlay.png": стало лучше или дефект остался?

ШАГ 2 — чек-лист «банального» (числа НЕ видят): зазоры/отступы; контент прижат куда надо (низ/верх как в макете, не зияет и не обрезан); высота карточек по контенту; выравнивание; цвета/радиусы/тени; иконки на месте/цвет; карусель реально листается (точки/драг), грид не рассыпан; кнопки/ховеры; переносы <br>.
ШАГ 3 — числа: веса/ширины/переносы desktop+mobile.
ШАГ 4 — pass ТОЛЬКО если всё совпадает и ничего визуально не криво; иначе fail с конкретикой в remaining.
В after верни фактические пути: overlay="${dir(s)}/after-overlay.png", live="${dir(s)}/after-live.png", figma="${dir(s)}/figma.png" (если шаг не дал файл — пустая строка + честно в remaining).
ЗАПРЕТ: ничего не чини/не деплой. Верни строго по схеме.`

// Уникальные страницы батча (для одного деплоя + замера по каждой).
const PAGES = [...new Set(SECTIONS.map(urlOf))]

// --- Отчёт о распределении: кто какую секцию взял (видно в /workflows и в возврате) ---
log(`Распределение работы: ${SECTIONS.length} секций на ${PAGES.length} стр. · по 1 fix-агенту и 1 verify-агенту на секцию · артефакты в .visual/qa/<slug>/`)
SECTIONS.forEach((s, i) => log(`  #${i + 1} ${s.slug} → fix:${s.slug} + verify:${s.slug} · ${pageKey(urlOf(s))} · Figma ${s.node}${s.mnode ? '/' + s.mnode : ''} · sel ${s.sel}`))

phase('Fix')
const fixes = await parallel(SECTIONS.map((s) => () => agent(fixPrompt(s), { label: `fix:${s.slug}`, phase: 'Fix', schema: FIX_SCHEMA })))

phase('Deploy')
const extractCmds = PAGES.map((u) => `node scripts/qa-extract.mjs ${u} --width 1440 --json .visual/qa-${pageKey(u)}-1440.json\nnode scripts/qa-extract.mjs ${u} --width 375 --json .visual/qa-${pageKey(u)}-375.json`).join('\n')
const deploy = await agent(
  `Working dir A:\\Projects\\AI project\\Plugin for WP sites. Выполни ПОСЛЕДОВАТЕЛЬНО, верни краткий лог:
1) npm run build -- --force
2) npm run check (зелёный? если нет — сообщи и НЕ деплой)
3) node scripts/deploy-stack.mjs --changed
4) Замер чисел по каждой странице батча (${PAGES.length} стр.):
${extractCmds}`,
  { label: 'build+deploy', phase: 'Deploy' }
)

phase('Verify')
const verdicts = await parallel(SECTIONS.map((s) => () => agent(verifyPrompt(s), { label: `verify:${s.slug}`, phase: 'Verify', schema: VERIFY_SCHEMA })))

// Возврат построен для самопроверки оркестратором: на каждую секцию — пути before/after,
// чтобы он сам Read обе картинки и подтвердил/опроверг вердикт агента.
const bySlug = (arr) => Object.fromEntries(arr.filter(Boolean).map((r) => [r.slug, r]))
const F = bySlug(fixes), V = bySlug(verdicts)
const report = SECTIONS.map((s) => ({
  slug: s.slug,
  folder: `${dir(s)}/`,
  fix: F[s.slug] || null,
  verify: V[s.slug] || null,
  before: F[s.slug]?.before || null,
  after: V[s.slug]?.after || null,
}))

return { url: URL, distribution: report.map((r) => r.slug), report, deploy }
