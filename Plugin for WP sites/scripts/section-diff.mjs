// Наложение секции: Figma-рендер ↔ живой скрин ОДНОЙ секции, совмещённые в один
// артефакт, где расхождение видно глазом. Дополняет числовой qa-compare и
// полностраничный visual-diff: тут — точечно по секции, с ИСТИННЫМ наложением.
//
// На вход — два уже снятых PNG (агент снимает: живой через shot.mjs, Figma через
// get_screenshot→curl). Скрипт нормализует ширину и выдаёт 3 вида:
//   -side.png    Figma | лайв (рядом)
//   -diff.png    разница яркости (красное = расходится)
//   -overlay.png КАНАЛЬНОЕ наложение: Figma→пурпурный, лайв→зелёный.
//                совпало → серое; пурпурный призрак = есть в Figma/нет на лайве;
//                зелёный призрак = есть на лайве/нет в Figma; сдвиг → цветная кайма.
//
// Запуск:
//   node scripts/section-diff.mjs --figma .visual/fig-x.png --live .visual/x.png --out .visual/x [--width 760]

import sharp from 'sharp';
import { mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const args = process.argv.slice( 2 );
const val = ( n, d ) => { const i = args.indexOf( `--${ n }` ); return i >= 0 && args[ i + 1 ] ? args[ i + 1 ] : d; };
const figma = val( 'figma', null );
const live = val( 'live', null );
const out = resolve( process.cwd(), val( 'out', '.visual/section' ) );
const W = parseInt( val( 'width', '760' ), 10 );
if ( ! figma || ! live ) { console.error( 'Нужны --figma <png> и --live <png>' ); process.exit( 1 ); }
mkdirSync( dirname( out ), { recursive: true } );

// Серое-raw на общей ширине (для канального наложения и diff).
const grayAt = async ( file ) => {
	const { data, info } = await sharp( file ).flatten( { background: '#fff' } )
		.resize( { width: W } ).grayscale().raw().toBuffer( { resolveWithObject: true } );
	return { data, w: info.width, h: info.height };
};

const F = await grayAt( figma );
const L = await grayAt( live );
const H = Math.min( F.h, L.h );

// 1. Канальное наложение: R+B = Figma, G = лайв.
const ov = Buffer.alloc( W * H * 3 );
// 2. Diff яркости.
const heat = Buffer.alloc( W * H * 3 );
let miss = 0;
const TH = 36;
for ( let y = 0; y < H; y++ ) {
	for ( let x = 0; x < W; x++ ) {
		const g = y * W + x;
		const f = F.data[ g ], l = L.data[ g ];
		const i = g * 3;
		ov[ i ] = f; ov[ i + 1 ] = l; ov[ i + 2 ] = f;   // magenta(F) + green(L)
		const d = Math.abs( f - l );
		if ( d > TH ) { miss++; heat[ i ] = 255; heat[ i + 1 ] = 40; heat[ i + 2 ] = 40; }
		else { const v = Math.round( l * 0.3 + 178 ); heat[ i ] = heat[ i + 1 ] = heat[ i + 2 ] = v; }
	}
}

await sharp( ov, { raw: { width: W, height: H, channels: 3 } } ).png().toFile( `${ out }-overlay.png` );
await sharp( heat, { raw: { width: W, height: H, channels: 3 } } ).png().toFile( `${ out }-diff.png` );

// 3. Рядом: Figma | лайв.
const half = Math.round( W * 0.66 );
const a = sharp( figma ).flatten( { background: '#fff' } ).resize( { width: half } );
const b = sharp( live ).flatten( { background: '#fff' } ).resize( { width: half } );
const [ am, bm ] = [ await a.metadata(), await b.metadata() ];
const sideH = Math.max( am.height, bm.height );
await sharp( { create: { width: half * 2 + 8, height: sideH, channels: 3, background: '#ffffff' } } )
	.composite( [ { input: await a.png().toBuffer(), top: 0, left: 0 }, { input: await b.png().toBuffer(), top: 0, left: half + 8 } ] )
	.png().toFile( `${ out }-side.png` );

const pct = ( 100 * miss / ( W * H ) ).toFixed( 1 );
console.log( `🎯 секция: расхождение яркости ${ pct }% (порог ${ TH }), высоты Figma ${ F.h } / лайв ${ L.h }` );
console.log( `   overlay → ${ out }-overlay.png  (пурпур=Figma, зелёный=лайв, серое=совпало)` );
console.log( `   diff    → ${ out }-diff.png     (красное=расходится)` );
console.log( `   side    → ${ out }-side.png` );
console.log( '   ⚠ совмещение по верх-лев углу: бери Figma-ноду ВСЕЙ секции и лайв-селектор всей секции (одна ширина).' );
