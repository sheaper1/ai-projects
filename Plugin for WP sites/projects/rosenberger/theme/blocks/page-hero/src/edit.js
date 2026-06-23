import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, Button } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { headingStart, headingItalic, headingEnd, subtitle, buttonText, buttonUrl, disclaimer, imageUrl } = attributes;
	const blockProps = useBlockProps( { className: 'page-hero' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Heading', 'rosenberger' ) }>
					<TextControl label="Start (normal)" value={ headingStart } onChange={ v => setAttributes( { headingStart: v } ) } />
					<TextControl label="Italic" value={ headingItalic } onChange={ v => setAttributes( { headingItalic: v } ) } />
					<TextControl label="End (normal)" value={ headingEnd } onChange={ v => setAttributes( { headingEnd: v } ) } />
				</PanelBody>
				<PanelBody title={ __( 'Content', 'rosenberger' ) }>
					<TextareaControl label="Subtitle" value={ subtitle } onChange={ v => setAttributes( { subtitle: v } ) } />
					<TextControl label="Button Text" value={ buttonText } onChange={ v => setAttributes( { buttonText: v } ) } />
					<TextControl label="Button URL" value={ buttonUrl } onChange={ v => setAttributes( { buttonUrl: v } ) } />
					<TextControl label="Disclaimer" value={ disclaimer } onChange={ v => setAttributes( { disclaimer: v } ) } />
				</PanelBody>
				<PanelBody title={ __( 'Image', 'rosenberger' ) }>
					{ imageUrl && <img src={ imageUrl } style={ { width: '100%', height: 120, objectFit: 'cover', marginBottom: 8, borderRadius: 4 } } /> }
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ m => setAttributes( { imageId: m.id, imageUrl: m.url } ) }
							allowedTypes={ [ 'image' ] }
							value={ attributes.imageId }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>{ imageUrl ? 'Replace image' : 'Select image' }</Button>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="page-hero__content">
					<div className="page-hero__inner">
						<h1 className="page-hero__heading">
							{ headingStart } { headingItalic && <em>{ headingItalic } </em> }{ headingEnd }
						</h1>
						{ subtitle && <p className="page-hero__subtitle">{ subtitle }</p> }
						{ buttonText && (
							<div className="page-hero__cta">
								<span className="page-hero__button">{ buttonText }</span>
								{ disclaimer && <p className="page-hero__disclaimer">{ disclaimer }</p> }
							</div>
						) }
					</div>
				</div>
				{ imageUrl && (
					<div className="page-hero__image">
						<img src={ imageUrl } alt="" />
					</div>
				) }
			</section>
		</>
	);
}
