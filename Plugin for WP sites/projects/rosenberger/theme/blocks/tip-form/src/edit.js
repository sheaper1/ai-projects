import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, lead, formSlug } = attributes;
	const blockProps = useBlockProps( { className: 'tip-form' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Section', 'rosenberger' ) }>
					<TextControl label="Heading" value={ heading } onChange={ ( v ) => setAttributes( { heading: v } ) } />
					<TextareaControl label="Lead" value={ lead } onChange={ ( v ) => setAttributes( { lead: v } ) } />
					<TextControl label="WPForms Slug" help="Slug der WPForms-Form (Standard: tippgeber)." value={ formSlug } onChange={ ( v ) => setAttributes( { formSlug: v } ) } />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="tip-form__header">
					<h2 className="tip-form__heading">{ heading }</h2>
					{ lead && <p className="tip-form__lead">{ lead }</p> }
				</div>
				<div className="tip-form__card" style={ { maxWidth: 820, margin: '0 auto', border: '1px solid #e2e2e2', padding: 40, textAlign: 'center', color: '#666' } }>
					<p>3-Schritte-Formular: Das Objekt → Die Situation → Ihre Daten (rendert im Frontend).</p>
				</div>
			</section>
		</>
	);
}
