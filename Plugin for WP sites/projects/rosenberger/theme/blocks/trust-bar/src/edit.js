import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const items = Array.isArray( attributes.items ) ? attributes.items : [];
	const setItem = ( index, value ) => setAttributes( { items: items.map( ( item, i ) => i === index ? value : item ) } );
	return <>
		<InspectorControls><PanelBody title="Trust Bar">
			<TextControl label="Рейтинг" value={ attributes.rating } onChange={ ( rating ) => setAttributes( { rating } ) } />
			{ items.map( ( item, index ) => <TextControl key={ index } label={ `Преимущество ${ index + 1 }` } value={ item } onChange={ ( value ) => setItem( index, value ) } /> ) }
		</PanelBody></InspectorControls>
		<div { ...useBlockProps() }>
			<div className="trust-bar__rating"><span className="trust-bar__google">G</span><span><strong>Google bewertet</strong><small>{ attributes.rating } <span className="trust-bar__stars">★★★★★</span></small></span></div>
			<div className="trust-bar__items">{ items.map( ( item, index ) => <span key={ index }>{ item }</span> ) }</div>
		</div>
	</>;
}
