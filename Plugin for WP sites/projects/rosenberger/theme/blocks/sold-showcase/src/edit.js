import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'sold-showcase' } );
	const set = ( key ) => ( value ) => setAttributes( { [ key ]: value } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Heading">
					<TextControl label="Heading" value={ attributes.heading } onChange={ set( 'heading' ) } />
					<TextControl label="Italic heading" value={ attributes.headingItalic } onChange={ set( 'headingItalic' ) } />
				</PanelBody>
				<PanelBody title="Card">
					<TextControl label="Title" value={ attributes.title } onChange={ set( 'title' ) } />
					<TextareaControl label="Text" value={ attributes.text } onChange={ set( 'text' ) } />
					<TextControl label="Location label" value={ attributes.locationLabel } onChange={ set( 'locationLabel' ) } />
					<TextControl label="Location value" value={ attributes.locationValue } onChange={ set( 'locationValue' ) } />
					<TextControl label="Price label" value={ attributes.priceLabel } onChange={ set( 'priceLabel' ) } />
					<TextControl label="Price value" value={ attributes.priceValue } onChange={ set( 'priceValue' ) } />
					<TextControl label="Area label" value={ attributes.areaLabel } onChange={ set( 'areaLabel' ) } />
					<TextControl label="Area value" value={ attributes.areaValue } onChange={ set( 'areaValue' ) } />
					<TextControl label="Rooms label" value={ attributes.roomsLabel } onChange={ set( 'roomsLabel' ) } />
					<TextControl label="Rooms value" value={ attributes.roomsValue } onChange={ set( 'roomsValue' ) } />
					<TextControl label="Link text" value={ attributes.buttonText } onChange={ set( 'buttonText' ) } />
					<TextControl label="Link URL" value={ attributes.buttonUrl } onChange={ set( 'buttonUrl' ) } />
					<TextControl label="CTA text" value={ attributes.ctaText } onChange={ set( 'ctaText' ) } />
					<TextControl label="CTA URL" value={ attributes.ctaUrl } onChange={ set( 'ctaUrl' ) } />
				</PanelBody>
				<PanelBody title="Image" initialOpen={ false }>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ attributes.imageId }
							onSelect={ ( media ) => setAttributes( { imageId: media.id, imageUrl: media.url } ) }
							render={ ( { open } ) => (
								<div>
									{ attributes.imageUrl && (
										<img src={ attributes.imageUrl } alt="" onClick={ open } style={ { width: '100%', height: 180, objectFit: 'cover', display: 'block', cursor: 'pointer', borderRadius: 8, marginBottom: 8 } } />
									) }
									<Button variant="secondary" onClick={ open }>{ attributes.imageUrl ? 'Replace image' : 'Select image' }</Button>
								</div>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="sold-showcase__inner">
					<header className="sold-showcase__header">
						<h2 className="sold-showcase__heading">{ attributes.heading } <em>{ attributes.headingItalic }</em></h2>
					</header>
					<div className="sold-showcase__frame">
						<div className="sold-showcase__card">
							<div className="sold-showcase__content">
								<h3>{ attributes.title }</h3>
								<p className="sold-showcase__text">{ attributes.text }</p>
							</div>
							<div className="sold-showcase__media">{ attributes.imageUrl && <img src={ attributes.imageUrl } alt="" /> }</div>
						</div>
					</div>
				</div>
			</section>
		</>
	);
}
