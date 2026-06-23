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
					<TextControl label="Heading" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
					<TextControl label="Italic" value={ attributes.headingItalic } onChange={ ( headingItalic ) => setAttributes( { headingItalic } ) } />
					<TextareaControl label="Intro" value={ attributes.intro } onChange={ ( intro ) => setAttributes( { intro } ) } />
				</PanelBody>
				<PanelBody title="Cards">
					{ items.map( ( item, index ) => (
						<div key={ index } className="problem-cards__control" style={ { marginBottom: 16, paddingBottom: 16, borderBottom: '1px solid var(--wp--preset--color--border)' } }>
							<TextControl label={ `Card ${ index + 1 }` } value={ item.title } onChange={ ( title ) => patchItem( index, { title } ) } />
							<TextareaControl label="Description" value={ item.text } onChange={ ( text ) => patchItem( index, { text } ) } />
							<MediaUploadCheck>
								<MediaUpload
									allowedTypes={ [ 'image' ] }
									value={ item.iconId }
									onSelect={ ( media ) => patchItem( index, { iconId: media.id, iconUrl: media.url } ) }
									render={ ( { open } ) => (
										<div>
											{ item.iconUrl && (
												<img src={ item.iconUrl } alt="" onClick={ open } style={ { width: 56, height: 56, objectFit: 'contain', display: 'block', cursor: 'pointer', marginBottom: 8 } } />
											) }
											<Button variant="secondary" onClick={ open }>{ item.iconUrl ? 'Replace icon' : 'Select icon' }</Button>
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
					<Button variant="secondary" onClick={ addItem }>+ Add card</Button>
				</PanelBody>
			</InspectorControls>
			<section { ...useBlockProps( { className: 'problem-cards' } ) }>
				<div className="problem-cards__inner">
					<div className="problem-cards__head">
						<h2 className="problem-cards__heading">{ attributes.heading } <em>{ attributes.headingItalic }</em></h2>
						{ attributes.intro && <p className="problem-cards__intro">{ attributes.intro }</p> }
					</div>
					<div className="problem-cards__row">
						{ items.map( ( item, index ) => (
							<article className="problem-cards__card" key={ index }>
								<div className="problem-cards__icon">{ item.iconUrl && <img src={ item.iconUrl } alt="" /> }</div>
								<div className="problem-cards__text">
									<h3 className="problem-cards__title">{ item.title }</h3>
									<p className="problem-cards__desc">{ item.text }</p>
								</div>
							</article>
						) ) }
					</div>
				</div>
			</section>
		</>
	);
}
