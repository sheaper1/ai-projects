import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, Notice } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, lead, wpformsId } = attributes;
	const blockProps = useBlockProps( { className: 'tipper-form' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Section', 'rosenberger' ) }>
					<TextControl label="Heading" value={ heading } onChange={ v => setAttributes( { heading: v } ) } />
					<TextareaControl label="Lead" value={ lead } onChange={ v => setAttributes( { lead: v } ) } />
				</PanelBody>
				<PanelBody title={ __( 'WPForms', 'rosenberger' ) }>
					<TextControl
						label="WPForms Form ID"
						help="Enter the WPForms form ID after building the form in WPForms. 0 = preview mode (no submission)."
						value={ String( wpformsId ) }
						onChange={ v => setAttributes( { wpformsId: parseInt( v ) || 0 } ) }
						type="number"
					/>
					{ ! wpformsId && (
						<Notice status="warning" isDismissible={ false }>
							Form ID not set — funnel runs in local preview mode.
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="tipper-form__header">
					<h2 className="tipper-form__heading">{ heading }</h2>
					{ lead && <p className="tipper-form__lead">{ lead }</p> }
				</div>
				<div className="tipper-form__funnel" style={ { background: '#fff', padding: 40, maxWidth: 820, margin: '0 auto', border: '2px dashed #ccc', textAlign: 'center', color: '#666' } }>
					<p>Multi-step Tippgeber funnel renders here on the front end.</p>
					{ ! wpformsId && <p style={ { fontSize: 12 } }>⚠ Set WPForms ID in sidebar to enable submission.</p> }
				</div>
			</section>
		</>
	);
}
