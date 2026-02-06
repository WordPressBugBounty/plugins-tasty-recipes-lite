import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

export const PremiumLock = ( { upgradeUrl } ) => {
	const isPro = applyFilters( 'tasty_lock_is_pro', false );

	if ( isPro ) {
		return null;
	}

	return (
		<span>
			<span className="tasty-framework-pro-badge">
				<a
					href={ upgradeUrl }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Premium', 'tasty' ) }
				</a>
			</span>
		</span>
	);
};
