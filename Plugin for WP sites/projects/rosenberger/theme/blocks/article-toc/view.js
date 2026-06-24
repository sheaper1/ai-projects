/* Article TOC — строит оглавление из заголовков статьи и аккордеон. */
( function () {
	function slugify( text ) {
		return text
			.toLowerCase()
			.replace( /[^\p{L}\p{N}]+/gu, '-' )
			.replace( /^-+|-+$/g, '' );
	}

	function buildToc( toc ) {
		var list = toc.querySelector( '[data-toc-list]' );
		var content =
			document.querySelector( '.wp-block-post-content' ) ||
			document.querySelector( '.entry-content' );
		if ( ! list || ! content ) {
			toc.remove();
			return;
		}

		var heads = content.querySelectorAll( 'h2, h3' );
		if ( ! heads.length ) {
			toc.remove();
			return;
		}

		var topLi = null;
		var subOl = null;
		var used = {};

		heads.forEach( function ( h, i ) {
			if ( ! h.id ) {
				var base = slugify( h.textContent || 'abschnitt' ) || 'abschnitt';
				var id = base;
				while ( used[ id ] || document.getElementById( id ) ) {
					id = base + '-' + i;
					if ( ! used[ id ] && ! document.getElementById( id ) ) {
						break;
					}
					id = base + '-' + i + '-' + Math.floor( i + 1 );
				}
				h.id = id;
			}
			used[ h.id ] = true;

			var li = document.createElement( 'li' );
			li.className = 'toc-item';
			var a = document.createElement( 'a' );
			a.href = '#' + h.id;
			a.textContent = h.textContent;
			li.appendChild( a );

			if ( h.tagName === 'H3' && topLi ) {
				if ( ! subOl ) {
					subOl = document.createElement( 'ol' );
					subOl.className = 'toc-sub';
					topLi.appendChild( subOl );
				}
				li.classList.add( 'toc-item--sub' );
				subOl.appendChild( li );
			} else {
				list.appendChild( li );
				topLi = li;
				subOl = null;
			}
		} );

		toc.removeAttribute( 'hidden' );
		toc.classList.add( 'is-ready' );

		var toggle = toc.querySelector( '.article-toc__toggle' );
		if ( toggle ) {
			toggle.addEventListener( 'click', function () {
				var collapsed = toc.classList.toggle( 'is-collapsed' );
				toggle.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
			} );
		}
	}

	function init() {
		document.querySelectorAll( '.article-toc' ).forEach( buildToc );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
