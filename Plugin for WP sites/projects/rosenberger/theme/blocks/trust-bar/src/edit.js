import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const items = Array.isArray( attributes.items ) ? attributes.items : [];
	const setItem = ( index, value ) => setAttributes( { items: items.map( ( item, i ) => i === index ? value : item ) } );
	return <>
		<InspectorControls><PanelBody title="Trust Bar">
			<TextControl label="Рейтинг" value={ attributes.rating } onChange={ ( rating ) => setAttributes( { rating } ) } />
			<MediaUploadCheck><MediaUpload allowedTypes={ [ 'image' ] } value={ attributes.badgeId } onSelect={ ( media ) => setAttributes( { badgeId: media.id, badgeUrl: media.url } ) } render={ ( { open } ) => <Button variant="secondary" onClick={ open }>{ attributes.badgeUrl ? 'Заменить SVG рейтинга' : 'Выбрать SVG рейтинга' }</Button> } /></MediaUploadCheck>
			{ items.map( ( item, index ) => <TextControl key={ index } label={ `Преимущество ${ index + 1 }` } value={ item } onChange={ ( value ) => setItem( index, value ) } /> ) }
		</PanelBody></InspectorControls>
		<div { ...useBlockProps() }>
			<div className="trust-bar__rating">{ attributes.badgeUrl && <img src={ attributes.badgeUrl } alt={ `Google Bewertung ${ attributes.rating } von 5` } /> }</div>
			<div className="trust-bar__items">{ items.map( ( item, index ) => <span key={ index }>{ item }</span> ) }</div>
		</div>
	</>;
}
