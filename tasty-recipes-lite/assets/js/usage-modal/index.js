import { createRoot } from '@wordpress/element';
import { UsageModal } from './components/UsageModal';
import domReady from '@wordpress/dom-ready';
import '../../scss/usage-modal/index.scss';

domReady( () => {
	const modal = document.getElementById( 'tasty-recipes-usage-modal' );

	if ( modal ) {
		createRoot( modal ).render(
			<UsageModal closeUsageModal={ () => modal.remove() } />
		);
	}
} );
