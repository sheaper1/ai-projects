import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, Flex, FlexItem, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

const EMPTY = { iconId: 0, iconUrl: '', title: '', text: '' };

export default function Edit( { attributes, setAttributes } ) {
	const { headingItalic, headingRest, lead, buttonText, buttonUrl } = attributes;
	const cards = Array.isArray( attributes.cards ) ? attributes.cards : [];
	const blockProps = useBlockProps( { className: 'wp-block-library-thank-you thank-you' } );

	const patchCard = ( index, patch ) =>
		setAttributes( { cards: cards.map( ( c, i ) => ( i === index ? { ...c, ...patch } : c ) ) } );
	const addCard = () => setAttributes( { cards: [ ...cards, { ...EMPTY } ] } );
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
				<PanelBody title="Texte">
					<TextControl label="Überschrift (kursiv)" value={ headingItalic } onChange={ ( v ) => setAttributes( { headingItalic: v } ) } />
					<TextControl label="Überschrift (rest)" value={ headingRest } onChange={ ( v ) => setAttributes( { headingRest: v } ) } />
					<TextareaControl label="Lead" value={ lead } onChange={ ( v ) => setAttributes( { lead: v } ) } />
					<TextControl label="Button-Text" value={ buttonText } onChange={ ( v ) => setAttributes( { buttonText: v } ) } />
					<TextControl label="Button-Link" value={ buttonUrl } onChange={ ( v ) => setAttributes( { buttonUrl: v } ) } />
				</PanelBody>
				<PanelBody title="Karten">
					{ cards.map( ( card, index ) => (
						<div key={ index } className="thank-you__control">
							<TextControl label={ `Karte ${ index + 1 } – Titel` } value={ card.title } onChange={ ( v ) => patchCard( index, { title: v } ) } />
							<TextareaControl label="Text" value={ card.text } onChange={ ( v ) => patchCard( index, { text: v } ) } />
							<MediaUploadCheck>
								<MediaUpload
									allowedTypes={ [ 'image' ] }
									value={ card.iconId }
									onSelect={ ( media ) => patchCard( index, { iconId: media.id, iconUrl: media.url } ) }
									render={ ( { open } ) => (
										<div>
											{ card.iconUrl && (
												<img src={ card.iconUrl } alt="" onClick={ open } style={ { width: 64, height: 64, objectFit: 'contain', cursor: 'pointer', marginBottom: 8, display: 'block' } } />
											) }
											<Button variant="secondary" onClick={ open }>{ card.iconUrl ? 'Icon ersetzen' : 'Icon wählen' }</Button>
										</div>
									) }
								/>
							</MediaUploadCheck>
							<Flex justify="flex-start" gap={ 2 } style={ { marginTop: 8 } }>
								<FlexItem><Button size="small" onClick={ () => moveCard( index, -1 ) } disabled={ index === 0 }>↑</Button></FlexItem>
								<FlexItem><Button size="small" onClick={ () => moveCard( index, 1 ) } disabled={ index === cards.length - 1 }>↓</Button></FlexItem>
								<FlexItem><Button size="small" isDestructive onClick={ () => removeCard( index ) }>Löschen</Button></FlexItem>
							</Flex>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addCard }>+ Karte hinzufügen</Button>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="thank-you__hero">
					<div className="thank-you__head">
						<h1 className="thank-you__heading"><em>{ headingItalic }</em><br />{ headingRest }</h1>
						<p className="thank-you__lead" dangerouslySetInnerHTML={ { __html: lead } } />
						<span className="thank-you__button">{ buttonText }</span>
					</div>
				</div>
				{ cards.length > 0 && (
					<div className="thank-you__cards-band">
						<div className="thank-you__cards">
							{ cards.map( ( card, index ) => (
								<article className="ty-card" key={ index }>
									<div className="ty-card__icon">{ card.iconUrl && <img src={ card.iconUrl } alt="" width="64" height="64" /> }</div>
									<div className="ty-card__body">
										<h3 className="ty-card__title">{ card.title }</h3>
										<p className="ty-card__text">{ card.text }</p>
									</div>
								</article>
							) ) }
						</div>
					</div>
				) }
			</section>
		</>
	);
}
