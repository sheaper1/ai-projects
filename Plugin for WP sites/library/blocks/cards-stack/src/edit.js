import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, Flex, FlexItem, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

const EMPTY = { title: '', text: '', buttonText: 'Erfahren Sie mehr', buttonUrl: '#', imageId: 0, imageUrl: '' };

const Arrow = () => (
	<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
		<path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
	</svg>
);

export default function Edit( { attributes, setAttributes } ) {
	const cards = Array.isArray( attributes.cards ) ? attributes.cards : [];
	const patchCard = ( index, patch ) =>
		setAttributes( { cards: cards.map( ( card, i ) => ( i === index ? { ...card, ...patch } : card ) ) } );
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
				<PanelBody title="Заголовок">
					<TextControl label="Заголовок" value={ attributes.titleMain } onChange={ ( titleMain ) => setAttributes( { titleMain } ) } />
					<TextControl label="Курсив" value={ attributes.titleAccent } onChange={ ( titleAccent ) => setAttributes( { titleAccent } ) } />
					<TextareaControl label="Подзаголовок" value={ attributes.subtitle } onChange={ ( subtitle ) => setAttributes( { subtitle } ) } />
				</PanelBody>
				<PanelBody title="Карточки">
					{ cards.map( ( card, index ) => (
						<div key={ index } className="cards-stack__control">
							<TextControl label={ `Карточка ${ index + 1 } — заголовок` } value={ card.title } onChange={ ( title ) => patchCard( index, { title } ) } />
							<TextareaControl label="Описание" value={ card.text } onChange={ ( text ) => patchCard( index, { text } ) } />
							<TextControl label="Текст кнопки" value={ card.buttonText } onChange={ ( buttonText ) => patchCard( index, { buttonText } ) } />
							<TextControl label="Ссылка кнопки" value={ card.buttonUrl } onChange={ ( buttonUrl ) => patchCard( index, { buttonUrl } ) } />
							<MediaUploadCheck>
								<MediaUpload
									allowedTypes={ [ 'image' ] }
									value={ card.imageId }
									onSelect={ ( media ) => patchCard( index, { imageId: media.id, imageUrl: media.url } ) }
									render={ ( { open } ) => (
										<Button variant="secondary" onClick={ open }>{ card.imageUrl ? 'Заменить фото' : 'Выбрать фото' }</Button>
									) }
								/>
							</MediaUploadCheck>
							<Flex justify="flex-start" gap={ 2 } style={ { marginTop: 8 } }>
								<FlexItem><Button size="small" onClick={ () => moveCard( index, -1 ) } disabled={ index === 0 }>↑</Button></FlexItem>
								<FlexItem><Button size="small" onClick={ () => moveCard( index, 1 ) } disabled={ index === cards.length - 1 }>↓</Button></FlexItem>
								<FlexItem><Button size="small" isDestructive onClick={ () => removeCard( index ) }>Удалить</Button></FlexItem>
							</Flex>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addCard }>+ Добавить карточку</Button>
				</PanelBody>
				<PanelBody title="Кнопка снизу">
					<TextControl label="Текст" value={ attributes.ctaText } onChange={ ( ctaText ) => setAttributes( { ctaText } ) } />
					<TextControl label="Ссылка" value={ attributes.ctaUrl } onChange={ ( ctaUrl ) => setAttributes( { ctaUrl } ) } />
				</PanelBody>
			</InspectorControls>

			<section { ...useBlockProps() }>
				<header className="cards-stack__head">
					<h2 className="cards-stack__heading"><span className="cards-stack__lead">{ attributes.titleMain }</span> <em>{ attributes.titleAccent }</em></h2>
					<p className="cards-stack__subtitle">{ attributes.subtitle }</p>
				</header>
				<div className="cards-stack__stage">
					<div className="cards-stack__cards">
						{ cards.map( ( card, index ) => (
							<article className="cards-stack__card" key={ index } style={ { '--card-index': index + 1 } } data-index={ index }>
								<div className="cards-stack__body">
									<div className="cards-stack__text">
										<h3>{ card.title }</h3>
										<p>{ card.text }</p>
									</div>
									{ card.buttonText && (
										<span className="cards-stack__button">{ card.buttonText } <Arrow /></span>
									) }
								</div>
								<div className="cards-stack__media">{ card.imageUrl && <img src={ card.imageUrl } alt="" /> }</div>
							</article>
						) ) }
					</div>
					{ cards.length > 0 && (
						<aside className="cards-stack__counter" aria-hidden="true">
							<div className="cards-stack__window">
								<div className="cards-stack__track">
									{ cards.map( ( _, i ) => (
										<span key={ i }>{ String( i + 1 ).padStart( 2, '0' ) }</span>
									) ) }
								</div>
							</div>
							<div className="cards-stack__line"><span></span></div>
							<div className="cards-stack__total">{ String( cards.length ).padStart( 2, '0' ) }</div>
						</aside>
					) }
				</div>
				{ attributes.ctaText && (
					<div className="cards-stack__cta"><span className="cards-stack__cta-button">{ attributes.ctaText }</span></div>
				) }
			</section>
		</>
	);
}
