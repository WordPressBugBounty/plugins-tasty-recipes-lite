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
			label={ __( 'ShareASale Affiliate ID', 'tasty' ) }
			id={ id }
			name={ name }
			value={ value }
			setValue={ setValue }
			disabled={ disabled }
			variant={ variant }
			{...props}
			helper={ createInterpolateElement(
				__(
					'<affiliateLink>Apply for the affiliate program</affiliateLink>, or <findIdLink>find your affiliate ID</findIdLink>.',
					'tasty'
				),
				{
					affiliateLink: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							href="https://www.shareasale.com/r.cfm?b=122128&u=177486&m=41788&urllink=&afftrack="
							target="_blank"
							rel="noreferrer"
						/>
					),
					findIdLink: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a
							href="https://www.shareasale.com/info/"
							target="_blank"
							rel="noreferrer"
						/>
					),
				}
			) }
		/>
	);
};
