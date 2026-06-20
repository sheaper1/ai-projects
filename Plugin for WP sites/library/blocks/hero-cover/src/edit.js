import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import { PanelBody, Button } from '@wordpress/components';

const MenuIcon = () => (
	<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
		<path d="M4 6h16v2H4zM4 11h16v2H4zM4 16h10v2H4z" />
	</svg>
);

// Дефолтный фон, проброшенный с сервера (см. deploy-block.mjs → wp_add_inline_script).
const defaultBg =
	( typeof window !== 'undefined' && window.libraryBlockDefaults
		&& window.libraryBlockDefaults[ 'hero-cover' ]
		&& window.libraryBlockDefaults[ 'hero-cover' ].bg ) || '';

export default function Edit( { attributes, setAttributes } ) {
	const {
		title, subtitle, colLeft, colRight,
		menuText, logoText, contactText, backgroundUrl,
	} = attributes;

	const bgUrl = backgroundUrl || defaultBg;
	const blockProps = useBlockProps( { className: 'wp-block-library-hero-cover' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Фоновое изображение', 'library' ) }>
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

			<div { ...blockProps }>
				{ bgUrl && <img className="hero-cover__bg" src={ bgUrl } alt="" /> }
				<div className="hero-cover__overlay" aria-hidden="true" />
				<div className="hero-cover__inner">
					<header className="hero-cover__bar">
						<div className="hero-cover__menu">
							<MenuIcon />
							<RichText tagName="span" value={ menuText }
								onChange={ ( v ) => setAttributes( { menuText: v } ) } allowedFormats={ [] } />
						</div>
						<div className="hero-cover__logo">
							<span className="hero-cover__logo-box" aria-hidden="true" />
							<RichText tagName="span" className="hero-cover__logo-text" value={ logoText }
								onChange={ ( v ) => setAttributes( { logoText: v } ) } allowedFormats={ [] } />
						</div>
						<RichText tagName="span" className="hero-cover__contact" value={ contactText }
							onChange={ ( v ) => setAttributes( { contactText: v } ) } allowedFormats={ [] } />
					</header>

					<div className="hero-cover__content">
						<RichText tagName="h1" className="hero-cover__title" value={ title }
							onChange={ ( v ) => setAttributes( { title: v } ) }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							placeholder={ __( 'Заголовок… (выдели строку и нажми курсив для акцента)', 'library' ) } />
						<RichText tagName="p" className="hero-cover__subtitle" value={ subtitle }
							onChange={ ( v ) => setAttributes( { subtitle: v } ) }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							placeholder={ __( 'Подзаголовок…', 'library' ) } />
					</div>

					<div className="hero-cover__cols">
						<RichText tagName="p" className="hero-cover__col" value={ colLeft }
							onChange={ ( v ) => setAttributes( { colLeft: v } ) }
							allowedFormats={ [ 'core/bold', 'core/italic' ] } />
						<RichText tagName="p" className="hero-cover__col" value={ colRight }
							onChange={ ( v ) => setAttributes( { colRight: v } ) }
							allowedFormats={ [ 'core/bold', 'core/italic' ] } />
					</div>
				</div>
			</div>
		</>
	);
}
