import domReady from '@wordpress/dom-ready';
import { __, sprintf } from '@wordpress/i18n';
import '../../scss/migration-notice/index.scss';

domReady( () => {
	const notice = document.getElementById(
		'tasty-recipes-db-migration-notice'
	);
	const button = document.getElementById( 'tasty-recipes-run-migration' );

	if ( ! notice || ! button ) {
		return;
	}

	const { nonce, ajaxUrl } = window.tastyRecipesDatabaseMigration || {};

	if ( ! nonce || ! ajaxUrl ) {
		return;
	}

	const message = notice.querySelector( '.tasty-recipes-migration-message' );
	const loadingText = button.querySelector( '.tasty-recipes-loading-text' );

	const handleComplete = () => {
		button.classList.remove( 'is-loading' );
		notice.classList.remove( 'notice-warning' );
		notice.classList.add( 'is-success' );

		const successLabel = __( 'Success!', 'tasty-recipes-lite' );
		const successMessage = __(
			'Database update completed successfully.',
			'tasty-recipes-lite'
		);

		message.innerHTML = `<strong>${ successLabel }</strong> ${ successMessage }`;
	};

	const handleError = ( errorMessage ) => {
		button.classList.remove( 'is-loading' );

		const errorLabel = __( 'Error:', 'tasty-recipes-lite' );

		message.innerHTML = `<strong>${ errorLabel }</strong> ${ errorMessage }`;
		notice.style.borderLeftColor = '#d63638';
	};

	const startMigration = async ( offset = 0 ) => {
		button.classList.add( 'is-loading' );

		const formData = new FormData();
		formData.append( 'action', 'tasty_recipes_run_db_migration' );
		formData.append( '_ajax_nonce', nonce );
		formData.append( 'offset', offset );

		try {
			const response = await fetch( ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} );

			const data = await response.json();

			if ( ! data.success ) {
				const defaultError = __(
					'An error occurred during migration.',
					'tasty-recipes-lite'
				);
				handleError( data.data?.message || defaultError );
				return;
			}

			if ( ! data.data.complete ) {
				loadingText.textContent = sprintf(
					/* translators: %d: number of remaining recipes */
					__( 'Updating… (%d remaining)', 'tasty-recipes-lite' ),
					data.data.remaining
				);
				await startMigration( data.data.offset );

				return;
			}

			handleComplete();
		} catch ( error ) {
			handleError(
				__(
					'An error occurred during migration.',
					'tasty-recipes-lite'
				)
			);
		}
	};

	button.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		startMigration( 0 );
	} );
} );
