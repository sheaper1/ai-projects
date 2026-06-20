import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

/**
 * Заблокированный шаблон: клиент редактирует текст и кнопки, но не может
 * добавлять, удалять или переставлять блоки (templateLock: 'all').
 */
const TEMPLATE = [
	[ 'core/heading', { level: 1, placeholder: __( 'Заголовок первого экрана', 'library' ) } ],
	[ 'core/paragraph', { placeholder: __( 'Короткое описание под заголовком…', 'library' ) } ],
	[
		'core/buttons',
		{},
		[ [ 'core/button', { text: __( 'Кнопка', 'library' ) } ] ],
	],
];

export default function Edit() {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		templateLock: 'all',
	} );

	return <div { ...innerBlocksProps } />;
}
