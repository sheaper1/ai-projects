import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'quote-cover' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Content">
					<TextareaControl
						label="Quote text (use line break for new line)"
						value={ attributes.text }
						onChange={ ( text ) => setAttributes( { text } ) }
					/>
				</PanelBody>
				<PanelBody title="Background photo" initialOpen={ false }>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ attributes.imageId }
							onSelect={ ( media ) => setAttributes( { imageId: media.id, imageUrl: media.url } ) }
							render={ ( { open } ) => (
								<div>
									{ attributes.imageUrl && (
										<img
											src={ attributes.imageUrl }
											alt=""
											onClick={ open }
											style={ { width: '100%', height: 140, objectFit: 'cover', display: 'block', cursor: 'pointer', borderRadius: 6, marginBottom: 8 } }
										/>
									) }
									<Button variant="secondary" onClick={ open }>
										{ attributes.imageUrl ? 'Replace photo' : 'Select photo' }
									</Button>
								</div>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				{ attributes.imageUrl && (
					<img className="quote-cover__bg" src={ attributes.imageUrl } alt="" aria-hidden="true" />
				) }
				<div className="quote-cover__overlay" aria-hidden="true"></div>
				{ attributes.text && <p className="quote-cover__text">{ attributes.text }</p> }
			</section>
		</>
	);
}
