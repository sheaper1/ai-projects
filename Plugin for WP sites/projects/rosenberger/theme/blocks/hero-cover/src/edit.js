import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	PanelBody, TextControl, TextareaControl, Button, Flex, FlexItem,
} from '@wordpress/components';

// Дефолтный фон, проброшенный с сервера (см. functions.php → wp_add_inline_script).
const defaultBg =
	( typeof window !== 'undefined' && window.libraryBlockDefaults
		&& window.libraryBlockDefaults[ 'hero-cover' ]
		&& window.libraryBlockDefaults[ 'hero-cover' ].bg ) || '';

export default function Edit( { attributes, setAttributes } ) {
	const { titleMain, titleAccent, subtitle, columns, backgroundUrl } = attributes;

	const cols = Array.isArray( columns ) ? columns : [];
	const bgUrl = backgroundUrl || defaultBg;
	const blockProps = useBlockProps( { className: 'wp-block-library-hero-cover' } );

	// --- репитер колонок ---
	const setCol = ( i, v ) => {
		const next = [ ...cols ];
		next[ i ] = v;
		setAttributes( { columns: next } );
	};
	const addCol = () => setAttributes( { columns: [ ...cols, '' ] } );
	const removeCol = ( i ) => setAttributes( { columns: cols.filter( ( _, j ) => j !== i ) } );
	const moveCol = ( i, dir ) => {
		const j = i + dir;
		if ( j < 0 || j >= cols.length ) return;
		const next = [ ...cols ];
		[ next[ i ], next[ j ] ] = [ next[ j ], next[ i ] ];
		setAttributes( { columns: next } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Контент', 'library' ) }>
					<TextControl label={ __( 'Заголовок', 'library' ) } value={ titleMain }
						onChange={ ( v ) => setAttributes( { titleMain: v } ) } />
					<TextControl label={ __( 'Акцентная строка (курсив)', 'library' ) } value={ titleAccent }
						onChange={ ( v ) => setAttributes( { titleAccent: v } ) } />
					<TextareaControl label={ __( 'Подзаголовок', 'library' ) } value={ subtitle }
						onChange={ ( v ) => setAttributes( { subtitle: v } ) } />
				</PanelBody>

				<PanelBody title={ __( 'Колонки текста', 'library' ) }>
					{ cols.map( ( text, i ) => (
						<div key={ i } style={ { marginBottom: 16, paddingBottom: 12, borderBottom: '1px solid #e0e0e0' } }>
							<TextareaControl label={ `${ __( 'Колонка', 'library' ) } ${ i + 1 }` } value={ text }
								onChange={ ( v ) => setCol( i, v ) } />
							<Flex justify="flex-start" gap={ 2 }>
								<FlexItem><Button size="small" onClick={ () => moveCol( i, -1 ) } disabled={ i === 0 }>↑</Button></FlexItem>
								<FlexItem><Button size="small" onClick={ () => moveCol( i, 1 ) } disabled={ i === cols.length - 1 }>↓</Button></FlexItem>
								<FlexItem><Button size="small" isDestructive onClick={ () => removeCol( i ) }>{ __( 'Удалить', 'library' ) }</Button></FlexItem>
							</Flex>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addCol }>{ __( '+ Добавить колонку', 'library' ) }</Button>
				</PanelBody>

				<PanelBody title={ __( 'Фоновое изображение', 'library' ) } initialOpen={ false }>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( m ) => setAttributes( { backgroundUrl: m.url, backgroundId: m.id } ) }
							allowedTypes={ [ 'image' ] }
							value={ attributes.backgroundId }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ backgroundUrl ? __( 'Заменить фото', 'library' ) : __( 'Выбрать фото', 'library' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
					{ backgroundUrl && (
						<Button variant="link" isDestructive style={ { marginTop: 8 } }
							onClick={ () => setAttributes( { backgroundUrl: '', backgroundId: undefined } ) }>
							{ __( 'Сбросить (вернуть дефолт)', 'library' ) }
						</Button>
					) }
				</PanelBody>
			</InspectorControls>

			{ /* Превью в канвасе (правка — в панели справа) */ }
			<div { ...blockProps }>
				{ bgUrl && <img className="hero-cover__bg" src={ bgUrl } alt="" /> }
				<div className="hero-cover__overlay" aria-hidden="true" />
				<div className="hero-cover__inner">
					<div className="hero-cover__content">
						<h1 className="hero-cover__title">
							{ titleMain }
							{ titleAccent && <em>{ titleAccent }</em> }
						</h1>
						<p className="hero-cover__subtitle">{ subtitle }</p>
					</div>

					<div className="hero-cover__cols">
						{ cols.map( ( text, i ) => (
							<p className="hero-cover__col" key={ i }>{ text }</p>
						) ) }
					</div>
				</div>
			</div>
		</>
	);
}
