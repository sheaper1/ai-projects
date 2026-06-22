import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextareaControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'founder-story' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Content">
					<TextareaControl
						label="Heading"
						value={ attributes.heading }
						onChange={ ( heading ) => setAttributes( { heading } ) }
					/>
					<TextareaControl
						label="Lead text (large)"
						value={ attributes.lead }
						onChange={ ( lead ) => setAttributes( { lead } ) }
					/>
					<TextareaControl
						label="Body text (small)"
						value={ attributes.body }
						onChange={ ( body ) => setAttributes( { body } ) }
					/>
					<TextareaControl
						label="Blockquote"
						value={ attributes.quote }
						onChange={ ( quote ) => setAttributes( { quote } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="founder-story__inner">
					<div className="founder-story__heading-col">
						{ attributes.heading && <h2 className="founder-story__heading">{ attributes.heading }</h2> }
					</div>
					<div className="founder-story__body-col">
						{ attributes.lead && <p className="founder-story__lead">{ attributes.lead }</p> }
						{ attributes.body && <p className="founder-story__body">{ attributes.body }</p> }
						{ attributes.quote && (
							<blockquote className="founder-story__quote">
								<p>{ attributes.quote }</p>
							</blockquote>
						) }
					</div>
				</div>
			</section>
		</>
	);
}
