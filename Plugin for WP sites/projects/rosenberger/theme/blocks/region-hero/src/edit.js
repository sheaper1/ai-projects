import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'wp-block-library-region-hero region-hero' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Region Hero">
					<TextControl label="Präfix" value={ attributes.headingPrefix } onChange={ ( headingPrefix ) => setAttributes( { headingPrefix } ) } help="Untertitel, Button und Foto kommen aus der Region (Meta-Box + Beitragsbild)." />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="region-hero__head">
					<h1 className="region-hero__title">{ attributes.headingPrefix } <em>Ort</em></h1>
					<p className="region-hero__subtitle">Untertitel der Region (Meta-Box).</p>
					<div className="region-hero__cta">
						<span className="region-hero__button">Kostenlos beraten lassen</span>
						<p className="region-hero__note">Unverbindlich und kostenlos</p>
					</div>
				</div>
				<div className="region-hero__image" />
			</section>
		</>
	);
}
