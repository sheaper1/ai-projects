import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'wp-block-library-blog-hero blog-hero' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Überschrift">
					<TextControl label="Eyebrow" value={ attributes.eyebrow } onChange={ ( eyebrow ) => setAttributes( { eyebrow } ) } />
					<TextControl label="Titel (kursiv)" value={ attributes.headingItalic } onChange={ ( headingItalic ) => setAttributes( { headingItalic } ) } />
					<TextControl label="Titel" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="blog-hero__inner">
					<div className="blog-hero__head">
						{ attributes.eyebrow && <p className="blog-hero__eyebrow">{ attributes.eyebrow }</p> }
						<h1 className="blog-hero__title">
							{ attributes.headingItalic && (
								<>
									<em>{ attributes.headingItalic }</em>
									<br />
								</>
							) }
							<span>{ attributes.heading }</span>
						</h1>
					</div>
					<div className="blog-featured">
						<div className="blog-featured__image" />
						<div className="blog-featured__body">
							<p style={ { color: 'var(--wp--preset--color--muted)' } }>Letzter Beitrag wird hier automatisch angezeigt.</p>
						</div>
					</div>
				</div>
			</section>
		</>
	);
}
