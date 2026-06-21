import { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const items = Array.isArray( attributes.items ) ? attributes.items : [];
	const patchItem = ( index, patch ) => setAttributes( { items: items.map( ( item, i ) => i === index ? { ...item, ...patch } : item ) } );
	return <>
		<InspectorControls><PanelBody title="Контент">
			<TextControl label="Заголовок" value={ attributes.titleMain } onChange={ ( titleMain ) => setAttributes( { titleMain } ) } />
			<TextControl label="Курсив" value={ attributes.titleAccent } onChange={ ( titleAccent ) => setAttributes( { titleAccent } ) } />
			{ items.map( ( item, index ) => <div key={ index } className="pain-points__control">
				<TextControl label={ `Пункт ${ index + 1 }` } value={ item.title } onChange={ ( title ) => patchItem( index, { title } ) } />
				<TextareaControl label="Описание" value={ item.text } onChange={ ( text ) => patchItem( index, { text } ) } />
				<MediaUploadCheck>
					<MediaUpload
						allowedTypes={ [ 'image' ] }
						value={ item.iconId }
						onSelect={ ( media ) => patchItem( index, { iconId: media.id, iconUrl: media.url } ) }
						render={ ( { open } ) => ( <Button variant="secondary" onClick={ open }>{ item.iconUrl ? 'Заменить SVG' : 'Выбрать SVG' }</Button> ) }
					/>
				</MediaUploadCheck>
			</div> ) }
		</PanelBody></InspectorControls>
		<section { ...useBlockProps() }>
			<h2 className="pain-points__heading">{ attributes.titleMain } <em>{ attributes.titleAccent }</em></h2>
			<div className="pain-points__list">{ items.map( ( item, index ) => <article className="pain-points__item" key={ index }><div className="pain-points__icon">{ item.iconUrl && <img src={ item.iconUrl } alt="" /> }</div><div><h3>{ item.title }</h3><p>{ item.text }</p></div></article> ) }</div>
		</section>
	</>;
}
