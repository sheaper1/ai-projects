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

export default function Edit( { attributes, setAttributes } ) {
	const {
		titleMain, titleAccent, subtitle, colLeft, colRight,
		menuText, logoText, contactText, backgroundUrl,
	} = attributes;

	// В редакторе фон показываем, если выбран; иначе блок подставит дефолт на фронте.
	const blockProps = useBlockProps( {
		className: 'wp-block-library-hero-cover',
		style: backgroundUrl ? { '--hc-bg': `url(${ backgroundUrl })` } : undefined,
	} );

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
						<Button
							variant="link"
							isDestructive
							onClick={ () => setAttributes( { backgroundUrl: '', backgroundId: undefined } ) }
							style={ { marginTop: 8 } }
						>
							{ __( 'Сбросить (вернуть дефолт)', 'library' ) }
						</Button>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="hero-cover__overlay" aria-hidden="true" />
				<div className="hero-cover__inner">
					<header className="hero-cover__bar">
						<div className="hero-cover__menu">
							<MenuIcon />
							<RichText
								tagName="span"
								value={ menuText }
								onChange={ ( v ) => setAttributes( { menuText: v } ) }
								allowedFormats={ [] }
							/>
						</div>
						<div className="hero-cover__logo">
							<span className="hero-cover__logo-box" aria-hidden="true" />
							<RichText
								tagName="span"
								className="hero-cover__logo-text"
								value={ logoText }
								onChange={ ( v ) => setAttributes( { logoText: v } ) }
								allowedFormats={ [] }
							/>
						</div>
						<RichText
							tagName="span"
							className="hero-cover__contact"
							value={ contactText }
							onChange={ ( v ) => setAttributes( { contactText: v } ) }
							allowedFormats={ [] }
						/>
					</header>

					<div className="hero-cover__content">
						<h1 className="hero-cover__title">
							<RichText
								tagName="span"
								className="hero-cover__title-main"
								value={ titleMain }
								onChange={ ( v ) => setAttributes( { titleMain: v } ) }
								allowedFormats={ [] }
								placeholder={ __( 'Заголовок…', 'library' ) }
							/>
							<RichText
								tagName="em"
								className="hero-cover__title-accent"
								value={ titleAccent }
								onChange={ ( v ) => setAttributes( { titleAccent: v } ) }
								allowedFormats={ [] }
								placeholder={ __( 'Акцент…', 'library' ) }
							/>
						</h1>
						<RichText
							tagName="p"
							className="hero-cover__subtitle"
							value={ subtitle }
							onChange={ ( v ) => setAttributes( { subtitle: v } ) }
							allowedFormats={ [] }
							placeholder={ __( 'Подзаголовок…', 'library' ) }
						/>
					</div>

					<div className="hero-cover__cols">
						<RichText
							tagName="p"
							className="hero-cover__col"
							value={ colLeft }
							onChange={ ( v ) => setAttributes( { colLeft: v } ) }
							allowedFormats={ [] }
						/>
						<RichText
							tagName="p"
							className="hero-cover__col"
							value={ colRight }
							onChange={ ( v ) => setAttributes( { colRight: v } ) }
							allowedFormats={ [] }
						/>
					</div>
				</div>
			</div>
		</>
	);
}
