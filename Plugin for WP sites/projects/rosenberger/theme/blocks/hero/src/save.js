import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Сохраняем только содержимое вложенных блоков. Обёртку и классы добавляет
 * render.php на сервере (динамический блок).
 */
export default function save() {
	return <InnerBlocks.Content />;
}
