const tastyFrameworkPositionLicenseGraceNotice = () => {
	const notices = Array.from(
		document.querySelectorAll( '.tasty-license-grace-notice' )
	);

	if ( ! notices.length ) {
		return;
	}

	// WPT pages: place above the white card (tabs + content).
	const tabs = document.querySelector( '.tasty-tabs-container' );
	// Other admin screens: place below the page title.
	const headerEnd = document.querySelector(
		'.wrap .wp-header-end, .wrap > h1, .wrap > h2'
	);

	let reference = tabs || headerEnd;
	let position = tabs ? 'beforebegin' : 'afterend';

	notices.forEach( ( notice ) => {
		if ( reference ) {
			reference.insertAdjacentElement( position, notice );
			reference = notice;
			position = 'afterend';
		}

		notice.classList.add( 'tasty-license-grace-notice--positioned' );
	} );
};

tastyFrameworkPositionLicenseGraceNotice();
