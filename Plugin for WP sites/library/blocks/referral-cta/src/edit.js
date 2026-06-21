import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'referral-cta' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Content">
					<TextControl label="Heading" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
					<TextControl label="Italic heading" value={ attributes.headingItalic } onChange={ ( headingItalic ) => setAttributes( { headingItalic } ) } />
					<TextareaControl label="Text" value={ attributes.text } onChange={ ( text ) => setAttributes( { text } ) } />
					<TextControl label="Button text" value={ attributes.buttonText } onChange={ ( buttonText ) => setAttributes( { buttonText } ) } />
					<TextControl label="Button URL" value={ attributes.buttonUrl } onChange={ ( buttonUrl ) => setAttributes( { buttonUrl } ) } />
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
				<div className="referral-cta__inner">
					<div className="referral-cta__content">
						<h2 className="referral-cta__heading">{ attributes.heading } <em>{ attributes.headingItalic }</em></h2>
						<p className="referral-cta__text">{ attributes.text }</p>
						<span className="referral-cta__button">{ attributes.buttonText }</span>
					</div>
					<div className="referral-cta__media">{ attributes.imageUrl && <img src={ attributes.imageUrl } alt="" /> }</div>
				</div>
			</section>
		</>
	);
}
