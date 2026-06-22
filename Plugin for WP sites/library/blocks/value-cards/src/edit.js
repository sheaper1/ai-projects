import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, Flex, FlexItem, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

const EMPTY_CARD = { iconId: 0, iconUrl: '', title: '', text: '' };

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'value-cards' } );
	const cards = Array.isArray( attributes.cards ) ? attributes.cards : [];

	const patchCard = ( index, patch ) =>
		setAttributes( { cards: cards.map( ( card, i ) => ( i === index ? { ...card, ...patch } : card ) ) } );
	const addCard = () => setAttributes( { cards: [ ...cards, { ...EMPTY_CARD } ] } );
	const removeCard = ( index ) => setAttributes( { cards: cards.filter( ( _, i ) => i !== index ) } );
	const moveCard = ( index, dir ) => {
		const j = index + dir;
		if ( j < 0 || j >= cards.length ) return;
		const next = [ ...cards ];
		[ next[ index ], next[ j ] ] = [ next[ j ], next[ index ] ];
		setAttributes( { cards: next } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title="Cards">
					{ cards.map( ( card, index ) => (
						<div key={ index } style={ { marginBottom: 16, paddingBottom: 16, borderBottom: '1px solid #e0e0e0' } }>
							<MediaUploadCheck>
								<MediaUpload
									allowedTypes={ [ 'image' ] }
									value={ card.iconId }
									onSelect={ ( media ) => patchCard( index, { iconId: media.id, iconUrl: media.url } ) }
									render={ ( { open } ) => (
										<div style={ { marginBottom: 8 } }>
											{ card.iconUrl && (
												<img
													src={ card.iconUrl }
													alt=""
													onClick={ open }
													style={ { width: 64, height: 64, objectFit: 'contain', padding: 8, background: '#f0f0f0', display: 'block', cursor: 'pointer', borderRadius: 6, marginBottom: 8 } }
												/>
											) }
											<Button variant="secondary" onClick={ open }>
												{ card.iconUrl ? 'Replace SVG' : 'Select SVG' }
											</Button>
										</div>
									) }
								/>
							</MediaUploadCheck>
							<TextControl
								label={ `Card ${ index + 1 } — title` }
								value={ card.title }
								onChange={ ( title ) => patchCard( index, { title } ) }
							/>
							<TextareaControl
								label="Description"
								value={ card.text }
								onChange={ ( text ) => patchCard( index, { text } ) }
							/>
							<Flex justify="flex-start" gap={ 2 }>
								<FlexItem><Button size="small" onClick={ () => moveCard( index, -1 ) } disabled={ index === 0 }>↑</Button></FlexItem>
								<FlexItem><Button size="small" onClick={ () => moveCard( index, 1 ) } disabled={ index === cards.length - 1 }>↓</Button></FlexItem>
								<FlexItem><Button size="small" isDestructive onClick={ () => removeCard( index ) }>Delete</Button></FlexItem>
							</Flex>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addCard }>+ Add card</Button>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="value-cards__row">
					{ cards.map( ( card, index ) => (
						<article className="value-cards__card" key={ index }>
							{ card.iconUrl && (
								<div className="value-cards__icon">
									<img src={ card.iconUrl } alt="" />
								</div>
							) }
							<div className="value-cards__body">
								{ card.title && <h3 className="value-cards__title">{ card.title }</h3> }
								{ card.text && <p className="value-cards__text">{ card.text }</p> }
							</div>
						</article>
					) ) }
				</div>
			</section>
		</>
	);
}
