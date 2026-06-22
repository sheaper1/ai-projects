import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button, Flex, FlexItem, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

const EMPTY_ITEM = { number: '', title: '', text: '' };

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'promise-list' } );
	const items = Array.isArray( attributes.items ) ? attributes.items : [];

	const patchItem = ( index, patch ) =>
		setAttributes( { items: items.map( ( item, i ) => ( i === index ? { ...item, ...patch } : item ) ) } );
	const addItem = () => setAttributes( { items: [ ...items, { ...EMPTY_ITEM } ] } );
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
					<TextControl
						label="Heading"
						value={ attributes.heading }
						onChange={ ( heading ) => setAttributes( { heading } ) }
					/>
				</PanelBody>
				<PanelBody title="Items" initialOpen={ false }>
					{ items.map( ( item, index ) => (
						<div key={ index } style={ { marginBottom: 16, paddingBottom: 16, borderBottom: '1px solid #ddd' } }>
							<TextControl
								label="Number (e.g. 01)"
								value={ item.number }
								onChange={ ( number ) => patchItem( index, { number } ) }
							/>
							<TextControl
								label="Title"
								value={ item.title }
								onChange={ ( title ) => patchItem( index, { title } ) }
							/>
							<TextareaControl
								label="Description"
								value={ item.text }
								onChange={ ( text ) => patchItem( index, { text } ) }
							/>
							<Flex justify="flex-start" gap={ 2 }>
								<FlexItem><Button size="small" onClick={ () => moveItem( index, -1 ) } disabled={ index === 0 }>↑</Button></FlexItem>
								<FlexItem><Button size="small" onClick={ () => moveItem( index, 1 ) } disabled={ index === items.length - 1 }>↓</Button></FlexItem>
								<FlexItem><Button size="small" isDestructive onClick={ () => removeItem( index ) }>Delete</Button></FlexItem>
							</Flex>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addItem }>+ Add item</Button>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="promise-list__inner">
					<div className="promise-list__heading-col">
						{ attributes.heading && <h2 className="promise-list__heading">{ attributes.heading }</h2> }
					</div>
					<div className="promise-list__items-col">
						{ items.map( ( item, index ) => (
							<>
								{ index > 0 && <hr key={ `divider-${ index }` } className="promise-list__divider" aria-hidden="true" /> }
								<div className="promise-list__item" key={ index }>
									<span className="promise-list__number" aria-hidden="true">{ item.number || String( index + 1 ).padStart( 2, '0' ) }</span>
									<div className="promise-list__item-body">
										{ item.title && <h3 className="promise-list__item-title">{ item.title }</h3> }
										{ item.text && <p className="promise-list__item-text">{ item.text }</p> }
									</div>
								</div>
							</>
						) ) }
					</div>
				</div>
			</section>
		</>
	);
}
