import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { postsPerPage, archiveUrl } = attributes;
	const blockProps = useBlockProps( { className: 'property-catalog' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Einstellungen">
					<RangeControl
						label="Anzahl Objekte"
						value={ postsPerPage }
						onChange={ v => setAttributes( { postsPerPage: v } ) }
						min={ 1 }
						max={ 12 }
					/>
					<TextControl
						label="Link «Alle Objekte ansehen»"
						value={ archiveUrl }
						onChange={ v => setAttributes( { archiveUrl: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div style={ { padding: '48px', textAlign: 'center', color: '#747c86' } }>
					<strong style={ { display: 'block', fontSize: '18px', marginBottom: '8px' } }>
						Property Catalog
					</strong>
					<span style={ { fontSize: '14px' } }>
						{ postsPerPage } Objekte · serverseitig gerendert
					</span>
				</div>
			</section>
		</>
	);
}
