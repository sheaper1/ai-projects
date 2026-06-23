import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl, Button } from '@wordpress/components';

const EMPTY_ITEM = { iconId: 0, iconUrl: '', title: '', text: '' };

export default function Edit( { attributes, setAttributes } ) {
	const { heading, lead, items } = attributes;
	const blockProps = useBlockProps( { className: 'how-it-works' } );

	const setItem = ( i, key, val ) => {
		const next = items.map( ( it, j ) => j === i ? { ...it, [key]: val } : it );
		setAttributes( { items: next } );
	};
	const addItem    = () => setAttributes( { items: [ ...items, { ...EMPTY_ITEM } ] } );
	const removeItem = i => setAttributes( { items: items.filter( ( _, j ) => j !== i ) } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Header', 'rosenberger' ) }>
					<TextControl label="Heading" value={ heading } onChange={ v => setAttributes( { heading: v } ) } />
					<TextControl label="Lead" value={ lead } onChange={ v => setAttributes( { lead: v } ) } />
				</PanelBody>
				{ items.map( ( item, i ) => (
					<PanelBody key={ i } title={ `Step ${ i + 1 }` } initialOpen={ false }>
						{ item.iconUrl && <img src={ item.iconUrl } style={ { width: 64, height: 64, objectFit: 'contain', marginBottom: 8 } } /> }
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ m => { setItem( i, 'iconId', m.id ); setItem( i, 'iconUrl', m.url ); } }
								allowedTypes={ [ 'image' ] }
								value={ item.iconId }
								render={ ( { open } ) => (
									<Button variant="secondary" onClick={ open } style={ { marginBottom: 8 } }>{ item.iconUrl ? 'Replace icon' : 'Select icon' }</Button>
								) }
							/>
						</MediaUploadCheck>
						<TextareaControl label="Title (HTML allowed for <br>)" value={ item.title } onChange={ v => setItem( i, 'title', v ) } />
						<TextareaControl label="Text" value={ item.text } onChange={ v => setItem( i, 'text', v ) } />
						<Button isDestructive onClick={ () => removeItem( i ) }>Remove</Button>
					</PanelBody>
				) ) }
				<PanelBody>
					<Button variant="primary" onClick={ addItem }>+ Add step</Button>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="how-it-works__inner">
					<div className="how-it-works__header">
						<h2 className="how-it-works__heading">{ heading }</h2>
						{ lead && <p className="how-it-works__lead">{ lead }</p> }
					</div>
					<div className="how-it-works__cards">
						{ items.map( ( item, i ) => (
							<div key={ i } className="how-it-works__card">
								{ item.iconUrl && <div className="how-it-works__icon"><img src={ item.iconUrl } alt="" /></div> }
								<div className="how-it-works__card-body">
									<h3 className="how-it-works__card-title">{ item.title }</h3>
									<p className="how-it-works__card-text">{ item.text }</p>
								</div>
							</div>
						) ) }
					</div>
				</div>
			</section>
		</>
	);
}
