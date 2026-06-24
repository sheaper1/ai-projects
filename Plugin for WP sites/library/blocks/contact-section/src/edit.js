import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { headingItalic, headingRest, lead, cardTitle, formId, lat, lng } = attributes;
	const blockProps = useBlockProps( { className: 'wp-block-library-contact-section contact-section' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Texte">
					<TextControl label="Überschrift (kursiv)" value={ headingItalic } onChange={ ( v ) => setAttributes( { headingItalic: v } ) } />
					<TextControl label="Überschrift (rest)" value={ headingRest } onChange={ ( v ) => setAttributes( { headingRest: v } ) } />
					<TextareaControl label="Lead (HTML <br> erlaubt)" value={ lead } onChange={ ( v ) => setAttributes( { lead: v } ) } />
					<TextControl label="Karten-Titel" value={ cardTitle } onChange={ ( v ) => setAttributes( { cardTitle: v } ) } />
				</PanelBody>
				<PanelBody title="Formular & Karte">
					<TextControl label="WPForms-ID (0 = automatisch)" type="number" value={ formId } onChange={ ( v ) => setAttributes( { formId: parseInt( v, 10 ) || 0 } ) } />
					<TextControl label="Breitengrad (lat)" type="number" value={ lat } onChange={ ( v ) => setAttributes( { lat: parseFloat( v ) || 0 } ) } />
					<TextControl label="Längengrad (lng)" type="number" value={ lng } onChange={ ( v ) => setAttributes( { lng: parseFloat( v ) || 0 } ) } />
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="contact-section__inner">
					<div className="contact-section__form-col">
						<div className="contact-section__head">
							<h1 className="contact-section__heading"><em>{ headingItalic }</em>{ headingRest }</h1>
							<p className="contact-section__lead" dangerouslySetInnerHTML={ { __html: lead } } />
						</div>
						<div className="contact-section__form">
							{ [ 'Name', 'Email', 'Phone', 'Subject of the request', 'Nachricht' ].map( ( label ) => (
								<div className="cs-field" key={ label }>
									<label className="cs-field__label">{ label }</label>
									<div className="cs-field__input" style={ { display: 'flex', alignItems: 'center' } } />
								</div>
							) ) }
							<button type="button" className="cs-field__submit">JETZT ANFRAGEN</button>
						</div>
					</div>
					<div className="contact-section__map-col">
						<div className="contact-section__map" style={ { background: '#e9eef1' } } />
						<div className="contact-section__card">
							<p className="contact-section__card-title">{ cardTitle }</p>
							<div className="contact-section__card-body">
								<p>Telefon: +43 699 11 777 505</p>
								<p>Email: office@rosenberger.immo</p>
								<p>ROSENBERGER Immobilien GmbH, Drevesstraße 2/1, 6800 Feldkirch</p>
							</div>
						</div>
					</div>
				</div>
			</section>
		</>
	);
}
