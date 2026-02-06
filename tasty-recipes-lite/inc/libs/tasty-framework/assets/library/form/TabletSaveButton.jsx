import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
export const TabletSaveButton = () => {
	const [ isButtonDisabled, setIsButtonDisabled ] = useState(
		window.innerWidth > 800
	);

	useEffect( () => {
		const handleResize = () => {
			setIsButtonDisabled( window.innerWidth > 800 );
		};

		window.addEventListener( 'resize', handleResize );

		return () => window.removeEventListener( 'resize', handleResize );
	}, [] );

	if ( isButtonDisabled ) {
		return null;
	}

	return (
		<div style={ { width: '100%', padding: '24px' } }>
			<input
				type="submit"
				name="submit"
				value={ __( 'Save Changes', 'tasty' ) }
				className="button button-primary tasty-tablet-save-button"
			/>
		</div>
	);
};
