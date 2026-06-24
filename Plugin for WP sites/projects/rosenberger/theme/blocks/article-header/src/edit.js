import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	const blockProps = useBlockProps( { className: 'wp-block-library-article-header article-header' } );

	return (
		<section { ...blockProps }>
			<div className="article-header__inner">
				<div className="article-header__head">
					<h1 className="article-header__title">Artikel-Titel</h1>
					<p style={ { color: 'var(--wp--preset--color--muted)', margin: 0 } }>Datum · Lesezeit · Autor</p>
				</div>
				<div className="article-header__image" />
			</div>
		</section>
	);
}
