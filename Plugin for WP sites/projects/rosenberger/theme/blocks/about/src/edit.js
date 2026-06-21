import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, Flex, FlexItem, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

const EMPTY = { title: '', text: '' };

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
				<PanelBody title="Контент">
					<TextControl label="Заголовок" value={ attributes.titleMain } onChange={ ( titleMain ) => setAttributes( { titleMain } ) } />
					<TextareaControl label="Текст" value={ attributes.text } onChange={ ( text ) => setAttributes( { text } ) } />
					<TextControl label="Текст кнопки" value={ attributes.buttonText } onChange={ ( buttonText ) => setAttributes( { buttonText } ) } />
					<TextControl label="Ссылка кнопки" value={ attributes.buttonUrl } onChange={ ( buttonUrl ) => setAttributes( { buttonUrl } ) } />
				</PanelBody>
				<PanelBody title="Фоновое фото">
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ attributes.backgroundId }
							onSelect={ ( media ) => setAttributes( { backgroundId: media.id, backgroundUrl: media.url } ) }
							render={ ( { open } ) => (
								<div>
									{ attributes.backgroundUrl && (
										<img src={ attributes.backgroundUrl } alt="" onClick={ open } style={ { width: '100%', height: 150, objectFit: 'cover', borderRadius: 6, display: 'block', cursor: 'pointer', border: '1px solid #ddd', marginBottom: 8 } } />
									) }
									<Button variant="secondary" onClick={ open }>{ attributes.backgroundUrl ? 'Заменить фото' : 'Выбрать фото' }</Button>
								</div>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
				<PanelBody title="Карточки">
					{ items.map( ( item, index ) => (
						<div key={ index } className="cards-stack__control">
							<TextControl label={ `Карточка ${ index + 1 } — заголовок` } value={ item.title } onChange={ ( title ) => patchItem( index, { title } ) } />
							<TextareaControl label="Описание" value={ item.text } onChange={ ( text ) => patchItem( index, { text } ) } />
							<Flex justify="flex-start" gap={ 2 }>
								<FlexItem><Button size="small" onClick={ () => moveItem( index, -1 ) } disabled={ index === 0 }>↑</Button></FlexItem>
								<FlexItem><Button size="small" onClick={ () => moveItem( index, 1 ) } disabled={ index === items.length - 1 }>↓</Button></FlexItem>
								<FlexItem><Button size="small" isDestructive onClick={ () => removeItem( index ) }>Удалить</Button></FlexItem>
							</Flex>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addItem }>+ Добавить карточку</Button>
				</PanelBody>
			</InspectorControls>

			<section { ...useBlockProps() }>
				<div className="about__bg" aria-hidden="true">{ attributes.backgroundUrl && <img src={ attributes.backgroundUrl } alt="" /> }</div>
				<div className="about__inner">
					<div className="about__intro">
						<div className="about__intro-text">
							<h2 className="about__heading">{ attributes.titleMain }</h2>
							<p className="about__lead">{ attributes.text }</p>
						</div>
						{ attributes.buttonText && <span className="about__button">{ attributes.buttonText }</span> }
					</div>
					<div className="about__cards">
						{ items.map( ( item, index ) => (
							<article className="about__card" key={ index }>
								<h3>{ item.title }</h3>
								<p>{ item.text }</p>
							</article>
						) ) }
					</div>
				</div>
			</section>
		</>
	);
}
