import { __ } from '@wordpress/i18n';
import { Modal } from './Modal';

export const PremiumModal = ( { modalData, closeModal } ) => {
	const { previewImage, title, upgradeUrl } = modalData;

	return (
		<Modal modalData={ modalData } closeModal={ closeModal }>
			{ previewImage && (
				<div className="tasty-framework-modal-preview">
					<img src={ previewImage } alt={ title } />
				</div>
			) }

			{ __(
				'This is a premium feature. Consider upgrading to a paid plan to get this and more great features.',
				'tasty'
			) }

			<div className="tasty-framework-spacer"></div>

			<a
				href={ upgradeUrl }
				target="_blank"
				rel="noopener noreferrer"
				className="tasty-button tasty-button-pink tasty-button-large tasty-button-full"
			>
				{ __( 'Upgrade Now', 'tasty' ) }
			</a>
		</Modal>
	);
};
