import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { TextInput } from './TextInput';

export const ShareASaleInput = ( {
	id,
	name,
	value,
	setValue,
	variant = 'side-label',
	disabled = false,
	...props
} ) => {
	return (
		<TextInput
			label={ __( 'WP Tasty Affiliate Link', 'tasty' ) }
			id={ id }
			name={ name }
			value={ value }
			setValue={ setValue }
			disabled={ disabled }
			variant={ variant }
			{...props}
			helper={ createInterpolateElement(
				__(
					'<affiliateLink>Apply for the affiliate program</affiliateLink>, or <findIdLink>find your affiliate link</findIdLink>.',
					'tasty'
				),
				{
					affiliateLink: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							href="https://www.wptasty.com/affiliate"
							target="_blank"
							rel="noreferrer"
						/>
					),
					findIdLink: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							href="https://www.wptasty.com/knowledge-base/how-to-find-your-affiliate-id"
							target="_blank"
							rel="noreferrer"
						/>
					),
				}
			) }
		/>
	);
};
