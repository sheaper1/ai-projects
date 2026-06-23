import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, TextareaControl, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title="Content">
					<TextControl label="Heading" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
					<TextControl label="Italic" value={ attributes.headingItalic } onChange={ ( headingItalic ) => setAttributes( { headingItalic } ) } />
					<TextareaControl label="Text (HTML: <br>)" value={ attributes.text } onChange={ ( text ) => setAttributes( { text } ) } />
					<TextControl label="Button text" value={ attributes.buttonText } onChange={ ( buttonText ) => setAttributes( { buttonText } ) } />
					<TextControl label="Button URL" value={ attributes.buttonUrl } onChange={ ( buttonUrl ) => setAttributes( { buttonUrl } ) } />
				</PanelBody>
				<PanelBody title="Image">
					<ToggleControl label="Bild links" checked={ !! attributes.imageLeft } onChange={ ( imageLeft ) => setAttributes( { imageLeft } ) } />
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ attributes.imageId }
							onSelect={ ( media ) => setAttributes( { imageId: media.id, imageUrl: media.url } ) }
							render={ ( { open } ) => (
								<div>
									{ attributes.imageUrl && (
										<img src={ attributes.imageUrl } alt="" onClick={ open } style={ { width: '100%', height: 160, objectFit: 'cover', borderRadius: 6, cursor: 'pointer', display: 'block', marginBottom: 8 } } />
									) }
									<Button variant="secondary" onClick={ open }>{ attributes.imageUrl ? 'Replace image' : 'Select image' }</Button>
								</div>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>
			<section { ...useBlockProps( { className: 'split-cta' + ( attributes.imageLeft ? ' split-cta--image-left' : '' ) } ) }>
				<div className="split-cta__inner">
					<div className="split-cta__text">
						<div className="split-cta__copy">
							<h2 className="split-cta__heading">{ attributes.heading } <em>{ attributes.headingItalic }</em></h2>
							{ attributes.text && <div className="split-cta__desc" dangerouslySetInnerHTML={ { __html: attributes.text } } /> }
						</div>
						{ attributes.buttonText && <a className="split-cta__button" href={ attributes.buttonUrl }>{ attributes.buttonText }</a> }
					</div>
					{ attributes.imageUrl && (
						<div className="split-cta__media"><img src={ attributes.imageUrl } alt="" /></div>
					) }
				</div>
			</section>
		</>
	);
}
