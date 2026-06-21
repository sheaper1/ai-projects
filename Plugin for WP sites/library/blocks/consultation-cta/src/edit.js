import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'consultation-cta' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Content">
					<TextControl label="Heading" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
					<TextControl label="Italic heading" value={ attributes.headingItalic } onChange={ ( headingItalic ) => setAttributes( { headingItalic } ) } />
					<TextControl label="Text" value={ attributes.text } onChange={ ( text ) => setAttributes( { text } ) } />
					<TextControl label="Button text" value={ attributes.buttonText } onChange={ ( buttonText ) => setAttributes( { buttonText } ) } />
					<TextControl label="Button URL" value={ attributes.buttonUrl } onChange={ ( buttonUrl ) => setAttributes( { buttonUrl } ) } />
				</PanelBody>
				<PanelBody title="Background" initialOpen={ false }>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ attributes.backgroundId }
							onSelect={ ( media ) => setAttributes( { backgroundId: media.id, backgroundUrl: media.url } ) }
							render={ ( { open } ) => (
								<div>
									{ attributes.backgroundUrl && (
										<img src={ attributes.backgroundUrl } alt="" onClick={ open } style={ { width: '100%', height: 180, objectFit: 'cover', display: 'block', cursor: 'pointer', borderRadius: 8, marginBottom: 8 } } />
									) }
									<Button variant="secondary" onClick={ open }>{ attributes.backgroundUrl ? 'Replace image' : 'Select image' }</Button>
								</div>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="consultation-cta__inner">
					<h2 className="consultation-cta__heading">{ attributes.heading } <em>{ attributes.headingItalic }</em></h2>
					<p className="consultation-cta__text">{ attributes.text }</p>
					<span className="consultation-cta__button">{ attributes.buttonText }</span>
				</div>
			</section>
		</>
	);
}
