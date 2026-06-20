import { registerBlockType } from '@wordpress/blocks';

import metadata from '../block.json';
import Edit from './edit';
import './style.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	// Динамический блок: разметку строит render.php из атрибутов.
	save: () => null,
} );
