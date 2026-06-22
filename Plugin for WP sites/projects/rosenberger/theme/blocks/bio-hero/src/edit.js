import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'bio-hero' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Content">
					<TextControl
						label="Label (uppercase)"
						value={ attributes.label }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>
					<TextControl
						label="Name"
						value={ attributes.name }
						onChange={ ( name ) => setAttributes( { name } ) }
					/>
					<TextControl
						label="Job title"
						value={ attributes.jobTitle }
						onChange={ ( jobTitle ) => setAttributes( { jobTitle } ) }
					/>
					<TextareaControl
						label="Bio text"
						value={ attributes.bio }
						onChange={ ( bio ) => setAttributes( { bio } ) }
					/>
					<TextControl
						label="Name credit (on photo)"
						value={ attributes.nameCredit }
						onChange={ ( nameCredit ) => setAttributes( { nameCredit } ) }
					/>
				</PanelBody>
				<PanelBody title="Portrait photo" initialOpen={ false }>
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
											style={ { width: '100%', height: 180, objectFit: 'cover', display: 'block', cursor: 'pointer', borderRadius: 6, marginBottom: 8 } }
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
				<div className="bio-hero__content">
					{ attributes.label && <p className="bio-hero__label">{ attributes.label }</p> }
					{ attributes.name && <h1 className="bio-hero__name">{ attributes.name }</h1> }
					{ attributes.jobTitle && <p className="bio-hero__job">{ attributes.jobTitle }</p> }
					{ attributes.bio && <p className="bio-hero__bio">{ attributes.bio }</p> }
				</div>
				<div className="bio-hero__media">
					{ attributes.imageUrl && <img src={ attributes.imageUrl } alt="" /> }
					{ attributes.nameCredit && <p className="bio-hero__credit">{ attributes.nameCredit }</p> }
				</div>
			</section>
		</>
	);
}
