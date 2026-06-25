// Структурный линтер блоков — детерминированный гейт «формы» блока.
// Ловит то, что не видит tokens:scan/php -l: динамический save, import style.scss,
// корректный block.json, иконки/фото мимо правил, самописные карусели.
//
// Принцип: правило в коде = 100% соблюдения; правило в тексте = ~80%.
// Severity: error → блокирует (exit 1); warning → печатает, но не валит.
//
// Запуск:
//   node scripts/lint-blocks.mjs                 — все блоки (library + проекты)
//   node scripts/lint-blocks.mjs --block <путь>  — один блок (для PostToolUse-хука)
//   node scripts/lint-blocks.mjs --warn-as-error — варнинги тоже валят

import { readFileSync, existsSync, readdirSync, statSync } from 'node:fs';
import { resolve, dirname, join, basename } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptsDir = dirname( fileURLToPath( import.meta.url ) );
const projectRoot = resolve( scriptsDir, '..' );

const args = process.argv.slice( 2 );
const warnAsError = args.includes( '--warn-as-error' );
const blockIdx = args.indexOf( '--block' );
const singleBlock = blockIdx >= 0 ? args[ blockIdx + 1 ] : null;

const read = ( p ) => { try { return readFileSync( p, 'utf8' ); } catch { return null; } };

// --- Собрать список папок блоков --------------------------------------------
function findBlockDirs() {
	if ( singleBlock ) {
		// Принимаем путь к папке блока ИЛИ к файлу внутри неё — поднимаемся до block.json.
		let dir = resolve( singleBlock );
		if ( ! existsSync( dir ) ) return [];
		if ( statSync( dir ).isFile() ) dir = dirname( dir );
		while ( dir !== dirname( dir ) ) {
			if ( existsSync( join( dir, 'block.json' ) ) ) return [ dir ];
			dir = dirname( dir );
		}
		return [];
	}
	const roots = [];
	const lib = join( projectRoot, 'library', 'blocks' );
	if ( existsSync( lib ) ) roots.push( lib );
	const projects = join( projectRoot, 'projects' );
	if ( existsSync( projects ) ) {
		for ( const p of readdirSync( projects ) ) {
			const b = join( projects, p, 'theme', 'blocks' );
			if ( existsSync( b ) ) roots.push( b );
		}
	}
	const dirs = [];
	for ( const root of roots ) {
		for ( const slug of readdirSync( root ) ) {
			const dir = join( root, slug );
			if ( existsSync( join( dir, 'block.json' ) ) ) dirs.push( dir );
		}
	}
	return dirs;
}

// --- Проверка одного блока --------------------------------------------------
function lintBlock( dir ) {
	const errors = [];
	const warnings = [];
	const rel = dir.replace( projectRoot + '\\', '' ).replace( projectRoot + '/', '' ).replace( /\\/g, '/' );

	// 1. block.json валиден + динамический + html:false ----------------------
	const bjRaw = read( join( dir, 'block.json' ) );
	let bj = null;
	try {
		bj = JSON.parse( bjRaw );
	} catch ( e ) {
		errors.push( `block.json не парсится: ${ e.message }` );
		return { rel, errors, warnings };
	}

	if ( ! bj.render ) {
		errors.push( 'block.json без "render" — нужен динамический блок (file:./render.php). Статический HTML в save запрещён.' );
	} else {
		const renderFile = bj.render.replace( /^file:\.\//, '' );
		if ( ! existsSync( join( dir, renderFile ) ) ) {
			errors.push( `render указан (${ bj.render }), но файла нет на диске.` );
		}
	}

	if ( bj.supports && bj.supports.html === true ) {
		errors.push( 'supports.html:true — должно быть false (динамический блок, без статического HTML).' );
	} else if ( ! bj.supports || bj.supports.html === undefined ) {
		warnings.push( 'supports.html не задан — поставь "html": false явно.' );
	}

	// 2. JS-правила (только если есть src/index.js) --------------------------
	const indexPath = join( dir, 'src', 'index.js' );
	const indexJs = read( indexPath );
	if ( indexJs ) {
		// 2a. import './style.scss' — без него CSS блока не компилируется.
		const hasScss = existsSync( join( dir, 'src', 'style.scss' ) );
		if ( hasScss && ! /import\s+['"]\.\.?\/style\.scss['"]/.test( indexJs ) ) {
			errors.push( "src/index.js не импортирует './style.scss' — стили блока не попадут в build (страница без CSS)." );
		}

		// 2b. save должен быть динамическим: () => null ИЛИ InnerBlocks.Content.
		const saveNull = /save\s*:\s*\(\s*\)\s*=>\s*null/.test( indexJs );
		const importsSave = /import\s+save\s+from\s+['"]\.\/save['"]/.test( indexJs );
		if ( saveNull ) {
			// ок
		} else if ( importsSave ) {
			const saveJs = read( join( dir, 'src', 'save.js' ) ) || '';
			// Валидно: save возвращает null ИЛИ <InnerBlocks.Content />. Иначе — статический HTML.
			if ( ! /return\s+null|=>\s*null|InnerBlocks\.Content/.test( saveJs ) ) {
				errors.push( 'save.js возвращает статический HTML — допустим только null или <InnerBlocks.Content />.' );
			}
		} else {
			errors.push( "save не динамический: ожидается 'save: () => null' или import save → <InnerBlocks.Content />." );
		}
	}

	// 3. render.php: иконки/фото/карусели (warning — эвристики) ---------------
	const renderPhp = bj.render ? read( join( dir, bj.render.replace( /^file:\.\//, '' ) ) ) : null;
	if ( renderPhp ) {
		if ( /<svg[\s>]/i.test( renderPhp ) ) {
			warnings.push( 'inline <svg> в render.php — иконки должны идти через медиатеку (media ID/URL), не инлайном.' );
		}
		if ( /\.(png|jpe?g)\b/i.test( renderPhp ) ) {
			warnings.push( 'ссылка на .png/.jpg в render.php — растровые фото должны быть .webp.' );
		}
		// Карусель: разметка есть, а движок RbCarousel не подключён.
		if ( /data-track|carousel|slider/i.test( renderPhp ) ) {
			const viewJs = read( join( dir, 'view.js' ) ) || '';
			if ( ! /RbCarousel/.test( viewJs ) ) {
				warnings.push( 'похоже на карусель, но view.js не зовёт RbCarousel — самописные листатели не принимаются (drag + бесконечный цикл обязательны).' );
			}
		}
	}

	return { rel, errors, warnings };
}

// --- Прогон -----------------------------------------------------------------
const dirs = findBlockDirs();
if ( ! dirs.length ) {
	console.log( singleBlock ? `lint-blocks: блок не найден (${ singleBlock })` : 'lint-blocks: блоков не найдено.' );
	process.exit( 0 );
}

let errCount = 0;
let warnCount = 0;
for ( const dir of dirs.sort() ) {
	const { rel, errors, warnings } = lintBlock( dir );
	if ( ! errors.length && ! warnings.length ) continue;
	const slug = basename( dir );
	console.log( `\n— ${ slug }  (${ rel })` );
	for ( const e of errors ) { console.log( `  ✗ ${ e }` ); errCount++; }
	for ( const w of warnings ) { console.log( `  ⚠ ${ w }` ); warnCount++; }
}

const failed = errCount > 0 || ( warnAsError && warnCount > 0 );
console.log( `\n${ failed ? '❌' : '✅' } lint-blocks: блоков ${ dirs.length }, ошибок ${ errCount }, предупреждений ${ warnCount }.` );
process.exit( failed ? 1 : 0 );
