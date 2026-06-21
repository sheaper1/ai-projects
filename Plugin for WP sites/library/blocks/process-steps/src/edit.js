import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, TextareaControl } from '@wordpress/components';

const EMPTY_STEP = { number: '', title: '', text: '' };

export default function Edit( { attributes, setAttributes } ) {
	const steps = Array.isArray( attributes.steps ) ? attributes.steps : [];
	const patchStep = ( index, patch ) =>
		setAttributes( { steps: steps.map( ( step, i ) => ( i === index ? { ...step, ...patch } : step ) ) } );
	const addStep = () => setAttributes( { steps: [ ...steps, { ...EMPTY_STEP } ] } );
	const removeStep = ( index ) => setAttributes( { steps: steps.filter( ( _, i ) => i !== index ) } );
	const moveStep = ( index, dir ) => {
		const target = index + dir;
		if ( target < 0 || target >= steps.length ) return;
		const next = [ ...steps ];
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		setAttributes( { steps: next } );
	};
	const blockProps = useBlockProps( { className: 'process-steps' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Intro">
					<TextControl label="Heading" value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } />
					<TextControl label="Italic heading" value={ attributes.headingItalic } onChange={ ( headingItalic ) => setAttributes( { headingItalic } ) } />
					<TextareaControl label="Subtext" value={ attributes.subtext } onChange={ ( subtext ) => setAttributes( { subtext } ) } />
					<TextControl label="Button text" value={ attributes.buttonText } onChange={ ( buttonText ) => setAttributes( { buttonText } ) } />
					<TextControl label="Button URL" value={ attributes.buttonUrl } onChange={ ( buttonUrl ) => setAttributes( { buttonUrl } ) } />
				</PanelBody>
				<PanelBody title="Steps" initialOpen={ false }>
					{ steps.map( ( step, index ) => (
						<div key={ index } style={ { marginBottom: 16, paddingBottom: 16, borderBottom: '1px solid #ddd' } }>
							<TextControl label="Number" value={ step.number } onChange={ ( number ) => patchStep( index, { number } ) } />
							<TextControl label="Title" value={ step.title } onChange={ ( title ) => patchStep( index, { title } ) } />
							<TextareaControl label="Text" value={ step.text } onChange={ ( text ) => patchStep( index, { text } ) } />
							<div style={ { display: 'flex', gap: 8 } }>
								<Button size="small" onClick={ () => moveStep( index, -1 ) } disabled={ index === 0 }>↑</Button>
								<Button size="small" onClick={ () => moveStep( index, 1 ) } disabled={ index === steps.length - 1 }>↓</Button>
								<Button size="small" isDestructive onClick={ () => removeStep( index ) }>Delete</Button>
							</div>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addStep }>Add step</Button>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div className="process-steps__inner">
					<div className="process-steps__intro">
						<h2 className="process-steps__heading">
							{ attributes.heading } <em>{ attributes.headingItalic }</em>
						</h2>
						<p className="process-steps__subtext">{ attributes.subtext }</p>
						<span className="process-steps__button">{ attributes.buttonText }</span>
					</div>
					<div className="process-steps__list">
						{ steps.map( ( step, index ) => (
							<article className="process-steps__item" key={ index }>
								<div className="process-steps__number">{ step.number }</div>
								<div className="process-steps__content">
									<h3>{ step.title }</h3>
									<p>{ step.text }</p>
								</div>
							</article>
						) ) }
					</div>
				</div>
			</section>
		</>
	);
}
