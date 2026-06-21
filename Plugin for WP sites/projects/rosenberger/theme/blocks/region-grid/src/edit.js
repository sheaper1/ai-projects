import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	PanelBody, TextControl, Button,
} from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { heading, headingItalic, subtext, regions } = attributes;
	const blockProps = useBlockProps( { className: 'region-grid' } );

	const setRegion = ( i, key, val ) => {
		const next = regions.map( ( r, j ) => j === i ? { ...r, [ key ]: val } : r );
		setAttributes( { regions: next } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Заголовок', 'library' ) }>
					<TextControl
						label={ __( 'Текст (обычный)', 'library' ) }
						value={ heading }
						onChange={ ( v ) => setAttributes( { heading: v } ) }
					/>
					<TextControl
						label={ __( 'Текст (курсив)', 'library' ) }
						value={ headingItalic }
						onChange={ ( v ) => setAttributes( { headingItalic: v } ) }
					/>
					<TextControl
						label={ __( 'Подзаголовок', 'library' ) }
						value={ subtext }
						onChange={ ( v ) => setAttributes( { subtext: v } ) }
					/>
				</PanelBody>

				{ regions.map( ( region, i ) => (
					<PanelBody
						key={ i }
						title={ region.label || `${ __( 'Регион', 'library' ) } ${ i + 1 }` }
						initialOpen={ i === 0 }
					>
						<TextControl
							label={ __( 'Название', 'library' ) }
							value={ region.label }
							onChange={ ( v ) => setRegion( i, 'label', v ) }
						/>
						<TextControl
							label={ __( 'Ссылка (URL)', 'library' ) }
							value={ region.url }
							onChange={ ( v ) => setRegion( i, 'url', v ) }
						/>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( m ) => {
									setRegion( i, 'mediaId', m.id );
									setRegion( i, 'mediaUrl', m.url );
								} }
								allowedTypes={ [ 'image' ] }
								value={ region.mediaId }
								render={ ( { open } ) => (
									<>
										{ region.mediaUrl && (
											<img
												src={ region.mediaUrl }
												alt=""
												style={ { width: '100%', height: 80, objectFit: 'cover', marginBottom: 8, display: 'block' } }
											/>
										) }
										<Button variant="secondary" onClick={ open } style={ { width: '100%' } }>
											{ region.mediaUrl
												? __( 'Заменить фото', 'library' )
												: __( 'Выбрать фото', 'library' ) }
										</Button>
									</>
								) }
							/>
						</MediaUploadCheck>
					</PanelBody>
				) ) }
			</InspectorControls>

			<div { ...blockProps }>
				<div className="region-grid__header">
					<h2 className="region-grid__title">
						{ heading }{ headingItalic && <em>{ headingItalic }</em> }
					</h2>
					{ subtext && <p className="region-grid__subtext">{ subtext }</p> }
				</div>

				<div className="region-grid__grid">
					{ regions.map( ( region, i ) => (
						<div key={ i } className="region-grid__card">
							{ region.mediaUrl
								? <img className="region-grid__img" src={ region.mediaUrl } alt={ region.label } />
								: <div className="region-grid__placeholder" aria-hidden="true" />
							}
							<div className="region-grid__overlay" aria-hidden="true" />
							{ region.label && (
								<span className="region-grid__pill">{ region.label } →</span>
							) }
						</div>
					) ) }
				</div>
			</div>
		</>
	);
}
