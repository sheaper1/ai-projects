import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, Notice, PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'sold-showcase' } );
	const set = ( key ) => ( value ) => setAttributes( { [ key ]: value } );

	const ArrowPicker = ( { label, idKey, urlKey } ) => (
		<MediaUploadCheck>
			<MediaUpload
				allowedTypes={ [ 'image/svg+xml' ] }
				value={ attributes[ idKey ] }
				onSelect={ ( m ) => setAttributes( { [ idKey ]: m.id, [ urlKey ]: m.url } ) }
				render={ ( { open } ) => (
					<div style={ { marginBottom: 12 } }>
						<p style={ { fontWeight: 600, marginBottom: 4 } }>{ label }</p>
						{ attributes[ urlKey ] && (
							<img src={ attributes[ urlKey ] } alt="" onClick={ open }
							     style={ { width: 48, height: 48, display: 'block', cursor: 'pointer', marginBottom: 8, background: '#142335', borderRadius: '50%', padding: 4 } } />
						) }
						<Button variant="secondary" onClick={ open }>
							{ attributes[ urlKey ] ? 'Ersetzen' : 'SVG auswählen' }
						</Button>
					</div>
				) }
			/>
		</MediaUploadCheck>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title="Überschrift">
					<TextControl label="Heading" value={ attributes.heading } onChange={ set( 'heading' ) } />
					<TextControl label="Italic heading" value={ attributes.headingItalic } onChange={ set( 'headingItalic' ) } />
				</PanelBody>
				<PanelBody title="CTA-Button">
					<TextControl label="Button-Text" value={ attributes.ctaText } onChange={ set( 'ctaText' ) } />
					<TextControl label="Button-URL" value={ attributes.ctaUrl } onChange={ set( 'ctaUrl' ) } />
				</PanelBody>
				<PanelBody title="Navigation (SVG-Icons)" initialOpen={ false }>
					<ArrowPicker label="← Pfeil (Prev)" idKey="navPrevId" urlKey="navPrevUrl" />
					<ArrowPicker label="→ Pfeil (Next)" idKey="navNextId" urlKey="navNextUrl" />
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="sold-showcase__inner">
					<header className="sold-showcase__header">
						<h2 className="sold-showcase__heading">
							{ attributes.heading } <em>{ attributes.headingItalic }</em>
						</h2>
					</header>
					<Notice status="info" isDismissible={ false }>
						Карусель автоматически показывает объекты CPT <strong>Objekte</strong> со статусом <strong>Verkauft</strong>.
						Стрелки задаются в панели «Navigation (SVG-Icons)».
					</Notice>
				</div>
			</section>
		</>
	);
}
