import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, Flex, FlexItem, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

const EMPTY = { title: '', text: '', iconId: 0, iconUrl: '' };

export default function Edit( { attributes, setAttributes } ) {
	const items = Array.isArray( attributes.items ) ? attributes.items : [];
	const patchItem = ( index, patch ) =>
		setAttributes( { items: items.map( ( item, i ) => ( i === index ? { ...item, ...patch } : item ) ) } );
	const addItem = () => setAttributes( { items: [ ...items, { ...EMPTY } ] } );
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
				<PanelBody title="Heading">
					<TextControl label="Heading" value={ attributes.titleMain } onChange={ ( titleMain ) => setAttributes( { titleMain } ) } />
					<TextControl label="Italic" value={ attributes.titleAccent } onChange={ ( titleAccent ) => setAttributes( { titleAccent } ) } />
				</PanelBody>
				<PanelBody title="Items">
					{ items.map( ( item, index ) => (
						<div key={ index } className="pain-points__control">
							<TextControl label={ `Item ${ index + 1 }` } value={ item.title } onChange={ ( title ) => patchItem( index, { title } ) } />
							<TextareaControl label="Description" value={ item.text } onChange={ ( text ) => patchItem( index, { text } ) } />
							<MediaUploadCheck>
								<MediaUpload
									allowedTypes={ [ 'image' ] }
									value={ item.iconId }
									onSelect={ ( media ) => patchItem( index, { iconId: media.id, iconUrl: media.url } ) }
									render={ ( { open } ) => (
										<div>
											{ item.iconUrl && (
												<img src={ item.iconUrl } alt="" onClick={ open } style={ { width: 80, height: 80, objectFit: 'contain', padding: 12, borderRadius: 6, display: 'block', cursor: 'pointer', background: '#f0f0f0', border: '1px solid #ddd', marginBottom: 8 } } />
											) }
											<Button variant="secondary" onClick={ open }>{ item.iconUrl ? 'Replace SVG' : 'Select SVG' }</Button>
										</div>
									) }
								/>
							</MediaUploadCheck>
							<Flex justify="flex-start" gap={ 2 } style={ { marginTop: 8 } }>
								<FlexItem><Button size="small" onClick={ () => moveItem( index, -1 ) } disabled={ index === 0 }>↑</Button></FlexItem>
								<FlexItem><Button size="small" onClick={ () => moveItem( index, 1 ) } disabled={ index === items.length - 1 }>↓</Button></FlexItem>
								<FlexItem><Button size="small" isDestructive onClick={ () => removeItem( index ) }>Delete</Button></FlexItem>
							</Flex>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addItem }>+ Add item</Button>
				</PanelBody>
			</InspectorControls>
			<section { ...useBlockProps() }>
				<div className="pain-points__inner">
					<h2 className="pain-points__heading">{ attributes.titleMain } <em>{ attributes.titleAccent }</em></h2>
					<div className="pain-points__list">{ items.map( ( item, index ) => <article className="pain-points__item" key={ index }><div className="pain-points__icon">{ item.iconUrl && <img src={ item.iconUrl } alt="" /> }</div><div><h3>{ item.title }</h3><p>{ item.text }</p></div></article> ) }</div>
				</div>
			</section>
		</>
	);
}
