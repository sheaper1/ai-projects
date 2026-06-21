import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const cards = Array.isArray( attributes.cards ) ? attributes.cards : [];
	const patchCard = ( index, patch ) =>
		setAttributes( { cards: cards.map( ( card, i ) => ( i === index ? { ...card, ...patch } : card ) ) } );

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
						</div>
					) ) }
				</PanelBody>
				<PanelBody title="Кнопка снизу">
					<TextControl label="Текст" value={ attributes.ctaText } onChange={ ( ctaText ) => setAttributes( { ctaText } ) } />
					<TextControl label="Ссылка" value={ attributes.ctaUrl } onChange={ ( ctaUrl ) => setAttributes( { ctaUrl } ) } />
				</PanelBody>
			</InspectorControls>

			<section { ...useBlockProps() }>
				<header className="cards-stack__head">
					<h2 className="cards-stack__heading">{ attributes.titleMain } <em>{ attributes.titleAccent }</em></h2>
					<p className="cards-stack__subtitle">{ attributes.subtitle }</p>
				</header>
				<div className="cards-stack__layout">
					<div className="cards-stack__cards">
						{ cards.map( ( card, index ) => (
							<article className="cards-stack__card" key={ index } style={ { '--i': index } } data-index={ index + 1 }>
								<div className="cards-stack__text">
									<h3>{ card.title }</h3>
									<p>{ card.text }</p>
									{ card.buttonText && (
										<span className="cards-stack__button">{ card.buttonText } <span aria-hidden="true">&rarr;</span></span>
									) }
								</div>
								<div className="cards-stack__media">{ card.imageUrl && <img src={ card.imageUrl } alt="" /> }</div>
							</article>
						) ) }
					</div>
					{ cards.length > 0 && (
						<aside className="cards-stack__counter" aria-hidden="true">
							<span className="cards-stack__current">01</span>
							<span className="cards-stack__total">{ String( cards.length ).padStart( 2, '0' ) }</span>
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
