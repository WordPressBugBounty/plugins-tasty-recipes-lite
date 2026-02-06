import { useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Modal, useModal } from '@library';

export const UsageModal = ( { closeUsageModal } ) => {
	const { openModal, modalData, closeModal } = useModal();

	const updateUsageConsent = useCallback( ( consent ) => {
		try {
			apiFetch( {
				path: '/tasty-recipes-lite/v1/usage-consent',
				method: 'POST',
				data: {
					consent,
				},
			} );
		} catch ( error ) {
			// Fail silently.
		}
	}, [] );

	const handleAllowTracking = useCallback( () => {
		updateUsageConsent( 'yes' );
		closeModal();
	}, [ closeModal, updateUsageConsent ] );

	useEffect( () => {
		openModal( {
			title: __( 'Help Us to Improve WP Tasty', 'tasty-recipes-lite' ),
			xClickCallback: () => updateUsageConsent( 'no' ),
			closeCallback: () => closeUsageModal(),
		} );
	}, [ closeUsageModal, openModal, updateUsageConsent ] );

	return (
		<Modal modalData={ modalData } closeModal={ closeModal }>
			<p>
				{ __(
					'Allow us to collect anonymized usage data to improve features and crush bugs. You can change this setting anytime',
					'tasty-recipes-lite'
				) }
			</p>
			<div className="tasty-recipes-modal-actions">
				<button
					className="tasty-button-primary"
					onClick={ handleAllowTracking }
				>
					{ __( 'Allow Tracking', 'tasty-recipes-lite' ) }
				</button>
			</div>
		</Modal>
	);
};
