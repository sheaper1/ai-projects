import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'wp-block-library-blog-grid blog-grid' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Einstellungen">
					<TextControl label="Überschrift" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
					<RangeControl label="Beiträge pro Seite" min={ 3 } max={ 24 } value={ attributes.postsPerPage } onChange={ ( postsPerPage ) => setAttributes( { postsPerPage } ) } />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="blog-grid__inner">
					{ attributes.heading && <h2 className="blog-grid__heading">{ attributes.heading }</h2> }
					<div className="blog-grid__list">
						{ [ 0, 1, 2 ].map( ( i ) => (
							<div className="blog-card" key={ i }>
								<div className="blog-card__image" />
								<div className="blog-card__body">
									<p style={ { color: 'var(--wp--preset--color--muted)', margin: 0 } }>Artikel</p>
								</div>
							</div>
						) ) }
					</div>
					<p style={ { color: 'var(--wp--preset--color--muted)' } }>Beiträge werden automatisch geladen.</p>
				</div>
			</section>
		</>
	);
}
