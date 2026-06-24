import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { headingMain, headingItalic, lead, buttonText, buttonUrl, imageUrl } = attributes;
	const blockProps = useBlockProps( { className: 'wp-block-library-error-404 error-404' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Texte">
					<TextControl label="Überschrift (oben)" value={ headingMain } onChange={ ( v ) => setAttributes( { headingMain: v } ) } />
					<TextControl label="Überschrift (kursiv)" value={ headingItalic } onChange={ ( v ) => setAttributes( { headingItalic: v } ) } />
					<TextareaControl label="Lead (HTML <br> erlaubt)" value={ lead } onChange={ ( v ) => setAttributes( { lead: v } ) } />
					<TextControl label="Button-Text" value={ buttonText } onChange={ ( v ) => setAttributes( { buttonText: v } ) } />
					<TextControl label="Button-Link" value={ buttonUrl } onChange={ ( v ) => setAttributes( { buttonUrl: v } ) } />
				</PanelBody>
				<PanelBody title="Bild">
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ attributes.imageId }
							onSelect={ ( media ) => setAttributes( { imageId: media.id, imageUrl: media.url } ) }
							render={ ( { open } ) => (
								<div>
									{ imageUrl && (
										<img src={ imageUrl } alt="" onClick={ open } style={ { width: '100%', height: 120, objectFit: 'cover', borderRadius: 6, cursor: 'pointer', marginBottom: 8, display: 'block' } } />
									) }
									<Button variant="secondary" onClick={ open }>{ imageUrl ? 'Bild ersetzen' : 'Bild wählen' }</Button>
								</div>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="error-404__inner">
					<div className="error-404__text">
						<h1 className="error-404__heading">{ headingMain }<br /><em>{ headingItalic }</em></h1>
						<p className="error-404__lead" dangerouslySetInnerHTML={ { __html: lead } } />
						<span className="error-404__button">{ buttonText }</span>
					</div>
				</div>
				<div className="error-404__banner">
					{ imageUrl && <img className="error-404__photo" src={ imageUrl } alt="" /> }
					<span className="error-404__num" aria-hidden="true">404</span>
				</div>
			</section>
		</>
	);
}
