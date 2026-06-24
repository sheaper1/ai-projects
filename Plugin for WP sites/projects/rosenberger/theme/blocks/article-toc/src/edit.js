import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'wp-block-library-article-toc article-toc is-ready' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Inhaltsverzeichnis">
					<TextControl label="Überschrift" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="article-toc__inner">
					<div className="article-toc__card">
						<div className="article-toc__toggle">
							<span className="article-toc__title">{ attributes.heading }</span>
						</div>
						<div className="article-toc__body">
							<div className="article-toc__body-inner">
								<hr className="article-toc__divider" />
								<p style={ { color: 'var(--wp--preset--color--muted)', margin: 0 } }>Wird aus den Überschriften des Artikels erzeugt.</p>
							</div>
						</div>
					</div>
				</div>
			</section>
		</>
	);
}
