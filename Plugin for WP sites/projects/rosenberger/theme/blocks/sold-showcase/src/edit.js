import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Notice, PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'sold-showcase' } );
	const set = ( key ) => ( value ) => setAttributes( { [ key ]: value } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Überschrift">
					<TextControl label="Heading" value={ attributes.heading } onChange={ set( 'heading' ) } />
					<TextControl label="Italic heading" value={ attributes.headingItalic } onChange={ set( 'headingItalic' ) } />
				</PanelBody>
				<PanelBody title="CTA-Button">
					<TextControl label="Button-Text" value={ attributes.ctaText } onChange={ set( 'ctaText' ) } />
					<TextControl label="Button-URL" value={ attributes.ctaUrl } onChange={ set( 'ctaUrl' ) } />
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="sold-showcase__inner">
					<header className="sold-showcase__header">
						<h2 className="sold-showcase__heading">
							{ attributes.heading } <em>{ attributes.headingItalic }</em>
						</h2>
					</header>
					<Notice status="info" isDismissible={ false }>
						Карусель автоматически показывает объекты с&nbsp;CPT&nbsp;<strong>Objekte</strong> со статусом <strong>Verkauft</strong>.
						Добавьте объекты через <em>Objekte → Новый объект</em> и&nbsp;установите статус «Verkauft».
					</Notice>
				</div>
			</section>
		</>
	);
}
