import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, TextareaControl, ToggleControl } from '@wordpress/components';

const EMPTY_ITEM = { question: '', answer: '', open: false };

export default function Edit( { attributes, setAttributes } ) {
	const items = Array.isArray( attributes.items ) ? attributes.items : [];
	const patch = ( index, nextPatch ) => setAttributes( { items: items.map( ( item, i ) => ( i === index ? { ...item, ...nextPatch } : item ) ) } );
	const addItem = () => setAttributes( { items: [ ...items, { ...EMPTY_ITEM } ] } );
	const removeItem = ( index ) => setAttributes( { items: items.filter( ( _, i ) => i !== index ) } );
	const moveItem = ( index, dir ) => {
		const target = index + dir;
		if ( target < 0 || target >= items.length ) return;
		const next = [ ...items ];
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		setAttributes( { items: next } );
	};
	const blockProps = useBlockProps( { className: 'faq-section' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Heading">
					<TextControl label="Heading" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
				</PanelBody>
				<PanelBody title="Items" initialOpen={ false }>
					{ items.map( ( item, index ) => (
						<div key={ index } style={ { marginBottom: 16, paddingBottom: 16, borderBottom: '1px solid #ddd' } }>
							<TextControl label="Question" value={ item.question } onChange={ ( question ) => patch( index, { question } ) } />
							<TextareaControl label="Answer" value={ item.answer } onChange={ ( answer ) => patch( index, { answer } ) } />
							<ToggleControl label="Open by default" checked={ !! item.open } onChange={ ( open ) => patch( index, { open } ) } />
							<div style={ { display: 'flex', gap: 8 } }>
								<Button size="small" onClick={ () => moveItem( index, -1 ) } disabled={ index === 0 }>↑</Button>
								<Button size="small" onClick={ () => moveItem( index, 1 ) } disabled={ index === items.length - 1 }>↓</Button>
								<Button size="small" isDestructive onClick={ () => removeItem( index ) }>Delete</Button>
							</div>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addItem }>Add item</Button>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="faq-section__inner">
					<h2 className="faq-section__heading">{ attributes.heading }</h2>
					<div className="faq-section__items">
						{ items.map( ( item, index ) => (
							<details className="faq-section__item" key={ index } open={ !! item.open }>
								<summary><span>{ item.question }</span><i aria-hidden="true"></i></summary>
								<div className="faq-section__answer">{ item.answer }</div>
							</details>
						) ) }
					</div>
				</div>
			</section>
		</>
	);
}
