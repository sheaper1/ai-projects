import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'wp-block-library-region-properties region-properties' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Objekte">
					<SelectControl
						label="Quelle"
						value={ attributes.source }
						options={ [
							{ label: 'Aktuelle Objekte (property)', value: 'property' },
							{ label: 'Verkauft / Referenzen (reference)', value: 'reference' },
						] }
						onChange={ ( source ) => setAttributes( { source } ) }
					/>
					<TextControl label="Überschrift" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
					<ToggleControl label="Ortsname anhängen" checked={ attributes.appendCity } onChange={ ( appendCity ) => setAttributes( { appendCity } ) } />
					<TextControl label="Untertitel" value={ attributes.subtitle } onChange={ ( subtitle ) => setAttributes( { subtitle } ) } />
					<TextControl label="Button-Text" value={ attributes.buttonText } onChange={ ( buttonText ) => setAttributes( { buttonText } ) } />
					<TextControl label="Button-Link" value={ attributes.buttonUrl } onChange={ ( buttonUrl ) => setAttributes( { buttonUrl } ) } />
					<RangeControl label="Max. Objekte" min={ 1 } max={ 12 } value={ attributes.limit } onChange={ ( limit ) => setAttributes( { limit } ) } />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="region-properties__inner">
					<div className="region-properties__head">
						<h2 className="region-properties__heading">{ attributes.heading }{ attributes.appendCity ? ' Ort' : '' }</h2>
						{ attributes.subtitle && <p className="region-properties__subtitle">{ attributes.subtitle }</p> }
					</div>
					<p style={ { color: 'var(--wp--preset--color--muted)' } }>Objekte werden nach Ort gefiltert ({ attributes.source }).</p>
				</div>
			</section>
		</>
	);
}
