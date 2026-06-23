import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, Button } from '@wordpress/components';

const EMPTY = { imageId: 0, imageUrl: '', title: '', text: '' };

export default function Edit( { attributes, setAttributes } ) {
	const { headingStart, headingItalic, headingLine2, lead, items } = attributes;
	const blockProps = useBlockProps( { className: 'tipper-types' } );

	const setItem = ( i, key, val ) => setAttributes( { items: items.map( ( it, j ) => j === i ? { ...it, [key]: val } : it ) } );
	const addItem    = () => setAttributes( { items: [ ...items, { ...EMPTY } ] } );
	const removeItem = i  => setAttributes( { items: items.filter( ( _, j ) => j !== i ) } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Heading', 'rosenberger' ) }>
					<TextControl label="Start (normal)" value={ headingStart } onChange={ v => setAttributes( { headingStart: v } ) } />
					<TextControl label="Italic" value={ headingItalic } onChange={ v => setAttributes( { headingItalic: v } ) } />
					<TextControl label="Line 2" value={ headingLine2 } onChange={ v => setAttributes( { headingLine2: v } ) } />
					<TextControl label="Lead" value={ lead } onChange={ v => setAttributes( { lead: v } ) } />
				</PanelBody>
				{ items.map( ( item, i ) => (
					<PanelBody key={ i } title={ `Item ${ i + 1 }` } initialOpen={ false }>
						{ item.imageUrl && <img src={ item.imageUrl } style={ { width: '100%', height: 100, objectFit: 'cover', marginBottom: 8 } } /> }
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ m => { setItem( i, 'imageId', m.id ); setItem( i, 'imageUrl', m.url ); } }
								allowedTypes={ [ 'image' ] }
								value={ item.imageId }
								render={ ( { open } ) => (
									<Button variant="secondary" onClick={ open } style={ { marginBottom: 8 } }>{ item.imageUrl ? 'Replace photo' : 'Select photo' }</Button>
								) }
							/>
						</MediaUploadCheck>
						<TextControl label="Title" value={ item.title } onChange={ v => setItem( i, 'title', v ) } />
						<TextareaControl label="Text" value={ item.text } onChange={ v => setItem( i, 'text', v ) } />
						<Button isDestructive onClick={ () => removeItem( i ) }>Remove</Button>
					</PanelBody>
				) ) }
				<PanelBody>
					<Button variant="primary" onClick={ addItem }>+ Add item</Button>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="tipper-types__inner">
					<div className="tipper-types__header">
						<h2 className="tipper-types__heading">
							{ headingStart }{ headingItalic && <em>{ headingItalic } </em> }
							{ headingLine2 && <><br />{ headingLine2 }</> }
						</h2>
						{ lead && <p className="tipper-types__lead">{ lead }</p> }
					</div>
					<div className="tipper-types__cards">
						{ items.map( ( item, i ) => (
							<div key={ i } className="tipper-types__card">
								{ item.imageUrl && <div className="tipper-types__card-image"><img src={ item.imageUrl } alt="" /></div> }
								<div className="tipper-types__card-body">
									<h3 className="tipper-types__card-title">{ item.title }</h3>
									<p className="tipper-types__card-text">{ item.text }</p>
								</div>
							</div>
						) ) }
					</div>
				</div>
			</section>
		</>
	);
}
