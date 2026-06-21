import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Flex, FlexItem, PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const items = Array.isArray( attributes.items ) ? attributes.items : [];
	const setItem = ( index, value ) => setAttributes( { items: items.map( ( item, i ) => ( i === index ? value : item ) ) } );
	const addItem = () => setAttributes( { items: [ ...items, '' ] } );
	const removeItem = ( index ) => setAttributes( { items: items.filter( ( _, i ) => i !== index ) } );
	const moveItem = ( index, dir ) => {
		const j = index + dir;
		if ( j < 0 || j >= items.length ) return;
		const next = [ ...items ];
		[ next[ index ], next[ j ] ] = [ next[ j ], next[ index ] ];
		setAttributes( { items: next } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title="Trust Bar">
					<TextControl label="Рейтинг" value={ attributes.rating } onChange={ ( rating ) => setAttributes( { rating } ) } />
					<MediaUploadCheck>
						<MediaUpload allowedTypes={ [ 'image' ] } value={ attributes.badgeId } onSelect={ ( media ) => setAttributes( { badgeId: media.id, badgeUrl: media.url } ) } render={ ( { open } ) => (
							<div>
								{ attributes.badgeUrl && (
									<img src={ attributes.badgeUrl } alt="" onClick={ open } style={ { width: '100%', height: 64, objectFit: 'contain', padding: 8, borderRadius: 6, display: 'block', cursor: 'pointer', background: '#f0f0f0', border: '1px solid #ddd', marginBottom: 8 } } />
								) }
								<Button variant="secondary" onClick={ open }>{ attributes.badgeUrl ? 'Заменить SVG рейтинга' : 'Выбрать SVG рейтинга' }</Button>
							</div>
						) } />
					</MediaUploadCheck>
				</PanelBody>
				<PanelBody title="Преимущества">
					{ items.map( ( item, index ) => (
						<div key={ index } className="cards-stack__control">
							<TextControl label={ `Преимущество ${ index + 1 }` } value={ item } onChange={ ( value ) => setItem( index, value ) } />
							<Flex justify="flex-start" gap={ 2 }>
								<FlexItem><Button size="small" onClick={ () => moveItem( index, -1 ) } disabled={ index === 0 }>↑</Button></FlexItem>
								<FlexItem><Button size="small" onClick={ () => moveItem( index, 1 ) } disabled={ index === items.length - 1 }>↓</Button></FlexItem>
								<FlexItem><Button size="small" isDestructive onClick={ () => removeItem( index ) }>Удалить</Button></FlexItem>
							</Flex>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addItem }>+ Добавить преимущество</Button>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<div className="trust-bar__inner">
					<div className="trust-bar__rating">{ attributes.badgeUrl && <img src={ attributes.badgeUrl } alt={ `Google Bewertung ${ attributes.rating } von 5` } /> }</div>
					<div className="trust-bar__items">{ items.map( ( item, index ) => <span key={ index }>{ item }</span> ) }</div>
				</div>
			</div>
		</>
	);
}
