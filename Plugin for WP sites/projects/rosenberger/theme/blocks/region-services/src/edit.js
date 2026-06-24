import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	const blockProps = useBlockProps( { className: 'wp-block-library-region-services region-services' } );
	return (
		<section { ...blockProps }>
			<div className="region-services__inner">
				<div className="region-services__head">
					<h2 className="region-services__heading">Womit ich Sie in <em>Ort</em> unterstütze</h2>
					<span className="region-services__button">Kostenlos beraten lassen</span>
				</div>
				<div className="region-services__grid">
					{ [ 0, 1, 2 ].map( ( i ) => (
						<div className="rs-card" key={ i }>
							<div className="rs-card__icon" />
							<div className="rs-card__bottom">
								<div className="rs-card__textgroup">
									<h3 className="rs-card__title">Leistung</h3>
									<p className="rs-card__desc">Beschreibung</p>
								</div>
								<span className="rs-card__more">Erfahren Sie mehr →</span>
							</div>
						</div>
					) ) }
				</div>
			</div>
		</section>
	);
}
