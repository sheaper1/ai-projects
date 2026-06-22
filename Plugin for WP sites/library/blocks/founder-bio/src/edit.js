import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, Flex, FlexItem, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'founder-bio' } );
	const paragraphs = Array.isArray( attributes.paragraphs ) ? attributes.paragraphs : [];

	const setPara = ( index, value ) =>
		setAttributes( { paragraphs: paragraphs.map( ( p, i ) => ( i === index ? value : p ) ) } );
	const addPara = () => setAttributes( { paragraphs: [ ...paragraphs, '' ] } );
	const removePara = ( index ) => setAttributes( { paragraphs: paragraphs.filter( ( _, i ) => i !== index ) } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Content">
					<TextControl
						label="Heading"
						value={ attributes.heading }
						onChange={ ( heading ) => setAttributes( { heading } ) }
					/>
					{ paragraphs.map( ( para, index ) => (
						<div key={ index } style={ { marginBottom: 8 } }>
							<TextareaControl
								label={ `Paragraph ${ index + 1 }` }
								value={ para }
								onChange={ ( value ) => setPara( index, value ) }
							/>
							<Flex justify="flex-start" gap={ 2 }>
								<FlexItem>
									<Button size="small" isDestructive onClick={ () => removePara( index ) }>Delete</Button>
								</FlexItem>
							</Flex>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addPara }>+ Add paragraph</Button>
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
											style={ { width: '100%', height: 200, objectFit: 'cover', display: 'block', cursor: 'pointer', borderRadius: 6, marginBottom: 8 } }
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
				<div className="founder-bio__content">
					{ attributes.heading && <h2 className="founder-bio__heading">{ attributes.heading }</h2> }
					{ paragraphs.map( ( para, index ) => (
						<p className="founder-bio__para" key={ index }>{ para }</p>
					) ) }
				</div>
				<div className="founder-bio__media">
					{ attributes.imageUrl && <img src={ attributes.imageUrl } alt="" /> }
				</div>
			</section>
		</>
	);
}
