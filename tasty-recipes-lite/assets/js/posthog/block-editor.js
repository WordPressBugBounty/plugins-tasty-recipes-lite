/**
 * Initialize PostHog in the block editor when a Tasty Recipes block is present.
 *
 * @since x.x
 */

( () => {
	const BLOCK_PREFIX = 'wp-tasty/';

	const hasTastyBlock = blocks => {
		if ( ! Array.isArray( blocks ) ) {
			return false;
		}

		return blocks.some( block => {
			if ( block.name && block.name.startsWith( BLOCK_PREFIX ) ) {
				return true;
			}

			return hasTastyBlock( block.innerBlocks );
		} );
	};

	const maybeInit = () => {
		if ( 'undefined' === typeof wp || ! wp.data ) {
			return false;
		}

		const blockEditor = wp.data.select( 'core/block-editor' );
		const blocks = blockEditor && 'function' === typeof blockEditor.getBlocks
			? blockEditor.getBlocks()
			: [];

		if ( ! hasTastyBlock( blocks ) ) {
			return false;
		}

		if ( 'function' === typeof window.tastyRecipesInitPosthog ) {
			window.tastyRecipesInitPosthog();
		}

		return true;
	};

	const start = () => {
		if ( maybeInit() ) {
			return;
		}

		if ( 'undefined' === typeof wp || ! wp.data || 'function' !== typeof wp.data.subscribe ) {
			return;
		}

		const unsubscribe = wp.data.subscribe( () => {
			if ( maybeInit() ) {
				unsubscribe();
			}
		} );
	};

	if ( 'complete' === document.readyState ) {
		start();
	} else {
		window.addEventListener( 'load', start );
	}
} )();
