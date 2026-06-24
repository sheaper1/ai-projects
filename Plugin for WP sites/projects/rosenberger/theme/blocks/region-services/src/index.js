import metadata from '../block.json';
import Edit from './edit';
import './style.scss';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
