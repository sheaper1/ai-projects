import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { postsPerPage, archiveUrl, heading, headingItalic, subtext, layout } = attributes;
	const blockProps = useBlockProps( { className: 'property-catalog' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Einstellungen">
					<SelectControl
						label="Layout"
						value={ layout }
						options={ [
							{ label: 'Kompakt (Homepage)', value: 'compact' },
							{ label: 'Katalog (Alle Immobilien)', value: 'catalog' },
						] }
						onChange={ ( value ) => setAttributes( { layout: value } ) }
					/>
					<TextControl
						label="Haupttitel"
						value={ heading }
						onChange={ ( value ) => setAttributes( { heading: value } ) }
					/>
					<TextControl
						label="Kursiver Titelteil"
						value={ headingItalic }
						onChange={ ( value ) => setAttributes( { headingItalic: value } ) }
					/>
					<TextareaControl
						label="Beschreibung"
						value={ subtext }
						onChange={ ( value ) => setAttributes( { subtext: value } ) }
					/>
					<TextControl
						label="Link «Alle Objekte ansehen»"
						value={ archiveUrl }
						onChange={ ( value ) => setAttributes( { archiveUrl: value } ) }
					/>
					<RangeControl
						label="Anzahl Objekte"
						value={ postsPerPage }
						onChange={ ( value ) => setAttributes( { postsPerPage: value } ) }
						min={ 1 }
						max={ 12 }
					/>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div
					style={ {
						padding: '48px',
						textAlign: 'center',
						color: '#747c86',
						maxWidth: '760px',
						margin: '0 auto',
					} }
				>
					<h3
						style={ {
							margin: '0 0 16px',
							fontSize: '40px',
							lineHeight: '1.2',
							color: '#142335',
							fontWeight: 500,
						} }
					>
						{ heading }{' '}
						<em style={ { fontStyle: 'italic', fontWeight: 300 } }>{ headingItalic }</em>
					</h3>
					<p style={ { margin: '0 0 24px', fontSize: '16px', lineHeight: '1.5' } }>
						{ subtext }
					</p>
					<span style={ { fontSize: '14px' } }>
						{ postsPerPage } Objekte · serverseitig gerendert
					</span>
				</div>
			</section>
		</>
	);
}
