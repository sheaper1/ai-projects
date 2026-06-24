import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'wp-block-library-article-related article-related' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Ähnliche Artikel">
					<TextControl label="Überschrift" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="article-related__inner">
					{ attributes.heading && <h2 className="article-related__heading">{ attributes.heading }</h2> }
					<div className="article-related__list">
						{ [ 0, 1, 2 ].map( ( i ) => (
							<div className="blog-card" key={ i }>
								<div className="blog-card__image" />
								<div className="blog-card__body">
									<p style={ { color: 'var(--wp--preset--color--muted)', margin: 0 } }>Artikel</p>
								</div>
							</div>
						) ) }
					</div>
				</div>
			</section>
		</>
	);
}
