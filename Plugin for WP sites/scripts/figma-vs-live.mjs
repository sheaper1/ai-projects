// Визуальная сверка «Figma ↔ live» в ОДНОМ кадре: экспорт ноды Figma (REST) +
// скрин секции живого сайта (puppeteer) → склейка рядом с подписями. Один кадр на
// секцию вместо листания многих скриншотов. Быстро (REST + 1 шот), и видно глазом.
//
// Использование:
//   node scripts/figma-vs-live.mjs <figmaNodeId> <liveUrl> "<css-селектор>" [--name x] [--mobile]
// Выход: .visual/cmp-<name>.png  (слева Figma, справа live, одинаковой ширины)

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import puppeteer from 'puppeteer';
import sharp from 'sharp';

const env = Object.fromEntries( readFileSync( '.env', 'utf8' ).split( '\n' )
	.filter( ( l ) => l.includes( '=' ) && ! l.trim().startsWith( '#' ) )
	.map( ( l ) => { const i = l.indexOf( '=' ); return [ l.slice( 0, i ).trim(), l.slice( i + 1 ).trim() ]; } ) );
const TOKEN = env.FIGMA_TOKEN;
const FILE = env.FIGMA_FILE || 'p1HKLfoMcOwtVUD5rI9V3P';

const args = process.argv.slice( 2 );
const nodeId = args.find( ( a ) => /^\d+[:-]\d+$/.test( a ) )?.replace( '-', ':' );
const url = args.find( ( a ) => /^https?:\/\//.test( a ) );
const sel = args.find( ( a ) => ! /^\d+[:-]\d+$/.test( a ) && ! /^https?:/.test( a ) && ! a.startsWith( '--' ) );
const val = ( n, d ) => { const i = args.indexOf( '--' + n ); return i >= 0 && args[ i + 1 ] ? args[ i + 1 ] : d; };
const name = val( 'name', 'cmp' );
const mobile = args.includes( '--mobile' );
if ( ! nodeId || ! url || ! sel ) { console.error( 'Нужно: <figmaNodeId> <liveUrl> "<селектор>"' ); process.exit( 1 ); }

if ( ! existsSync( '.visual' ) ) mkdirSync( '.visual' );

// 1) Figma export (REST, 1 запрос)
const ir = await fetch( `https://api.figma.com/v1/images/${ FILE }?ids=${ encodeURIComponent( nodeId ) }&format=png&scale=2`, { headers: { 'X-Figma-Token': TOKEN } } );
const ij = await ir.json();
const link = ij.images?.[ nodeId ];
if ( ! link ) { console.error( 'нет рендера Figma', ij.err || ir.status ); process.exit( 1 ); }
let figBuf = Buffer.from( await ( await fetch( link ) ).arrayBuffer() );

// 2) live shot секции
const browser = await puppeteer.launch( { headless: 'new' } );
const page = await browser.newPage();
await page.setViewport( { width: mobile ? 390 : 1440, height: 1000, deviceScaleFactor: 2 } );
await page.goto( url + ( url.includes( '?' ) ? '&' : '?' ) + 'v=' + nodeId.replace( ':', '' ), { waitUntil: 'networkidle2', timeout: 60000 } );
const el = await page.$( sel );
if ( ! el ) { console.error( 'селектор не найден на live:', sel ); await browser.close(); process.exit( 1 ); }
// Промотать секцию в центр и дождаться, пока scroll-reveal доведёт opacity до 1
// (иначе шот ловит бледный полупрозрачный контент).
await page.evaluate( ( s ) => document.querySelector( s ).scrollIntoView( { block: 'center' } ), sel );
await page.evaluate( ( s ) => new Promise( ( res ) => {
	const node = document.querySelector( s );
	let tries = 0;
	const tick = () => {
		const op = parseFloat( getComputedStyle( node ).opacity || '1' );
		const kids = [ ...node.querySelectorAll( '*' ) ].slice( 0, 50 );
		const minOp = kids.reduce( ( m, k ) => Math.min( m, parseFloat( getComputedStyle( k ).opacity || '1' ) ), op );
		if ( minOp >= 0.99 || tries++ > 40 ) return res();
		setTimeout( tick, 100 );
	};
	tick();
} ), sel );
let liveBuf = await el.screenshot();
await browser.close();

// 3) склейка рядом, одинаковая ширина колонок
const COLW = 620;
const norm = async ( buf ) => {
	const im = sharp( buf ).resize( COLW, null, { fit: 'contain', background: '#ffffff' } );
	return im.png().toBuffer();
};
const [ figN, liveN ] = await Promise.all( [ norm( figBuf ), norm( liveBuf ) ] );
const fm = await sharp( figN ).metadata();
const lm = await sharp( liveN ).metadata();
const H = Math.max( fm.height, lm.height );
const LABEL = 28;
const canvas = sharp( { create: { width: COLW * 2 + 10, height: H + LABEL, channels: 4, background: '#cccccc' } } );
const svgLabel = ( t, x ) => Buffer.from( `<svg width="${ COLW }" height="${ LABEL }"><rect width="100%" height="100%" fill="#222"/><text x="8" y="19" font-family="sans-serif" font-size="14" fill="#fff">${ t }</text></svg>` );
const out = `.visual/cmp-${ name }.png`;
await canvas.composite( [
	{ input: svgLabel( 'FIGMA ' + nodeId, 0 ), top: 0, left: 0 },
	{ input: svgLabel( 'LIVE ' + sel, 0 ), top: 0, left: COLW + 10 },
	{ input: figN, top: LABEL, left: 0 },
	{ input: liveN, top: LABEL, left: COLW + 10 },
] ).png().toFile( out );
console.log( out );
