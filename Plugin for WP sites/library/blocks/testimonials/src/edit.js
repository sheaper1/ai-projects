import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title="Heading">
					<TextControl label="Heading" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
					<TextControl label="Italic" value={ attributes.headingItalic } onChange={ ( headingItalic ) => setAttributes( { headingItalic } ) } />
				</PanelBody>
				<PanelBody title="Google reviews">
					<RangeControl label="Anzahl" min={ 1 } max={ 9 } value={ attributes.limit } onChange={ ( limit ) => setAttributes( { limit } ) } />
					<RangeControl label="Min. Sterne" min={ 1 } max={ 5 } value={ attributes.minRating } onChange={ ( minRating ) => setAttributes( { minRating } ) } />
				</PanelBody>
			</InspectorControls>
			<section { ...useBlockProps( { className: 'testimonials' } ) }>
				<div className="testimonials__inner">
					<h2 className="testimonials__heading">{ attributes.heading } <em>{ attributes.headingItalic }</em></h2>
					<p style={ { color: 'var(--wp--preset--color--muted)' } }>
						Google-Bewertungen (live aus dem grw-Plugin): { attributes.limit } Karten, ab { attributes.minRating } Sternen.
						Vorschau erscheint im Frontend.
					</p>
				</div>
			</section>
		</>
	);
}
