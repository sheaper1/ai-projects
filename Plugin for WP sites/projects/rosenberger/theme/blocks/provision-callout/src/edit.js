import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, Button } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { label, value, subtitle, finePrint, imageUrl } = attributes;
	const blockProps = useBlockProps( { className: 'provision-callout' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Content', 'rosenberger' ) }>
					<TextControl label="Label (italic)" value={ label } onChange={ v => setAttributes( { label: v } ) } />
					<TextControl label="Value (big)" value={ value } onChange={ v => setAttributes( { value: v } ) } />
					<TextareaControl label="Subtitle" value={ subtitle } onChange={ v => setAttributes( { subtitle: v } ) } />
					<TextControl label="Fine print" value={ finePrint } onChange={ v => setAttributes( { finePrint: v } ) } />
				</PanelBody>
				<PanelBody title={ __( 'Background image', 'rosenberger' ) }>
					{ imageUrl && <img src={ imageUrl } style={ { width: '100%', height: 120, objectFit: 'cover', marginBottom: 8, borderRadius: 4 } } /> }
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ m => setAttributes( { imageId: m.id, imageUrl: m.url } ) }
							allowedTypes={ [ 'image' ] }
							value={ attributes.imageId }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>{ imageUrl ? 'Replace' : 'Select background' }</Button>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps } style={ { background: '#222', padding: '60px', textAlign: 'center', color: '#fff', position: 'relative', overflow: 'hidden' } }>
				{ imageUrl && <img src={ imageUrl } alt="" style={ { position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover', opacity: 0.5 } } /> }
				<div style={ { position: 'relative', zIndex: 1 } }>
					<p style={ { margin: 0, fontStyle: 'italic', fontSize: 40 } }>{ label }</p>
					<p style={ { margin: 0, fontSize: 80, fontWeight: 700 } }>{ value }</p>
					<p style={ { margin: 0, fontSize: 18 } }>{ subtitle }</p>
				</div>
			</section>
		</>
	);
}
