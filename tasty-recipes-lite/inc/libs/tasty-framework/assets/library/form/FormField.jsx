/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */

import classNames from 'classnames';

export const FormField = ( {
	id,
	label,
	className = '',
	children,
	helper = '',
	onClick = () => {},
	variant = 'side-label',
} ) => {
	return helper ? (
		<div
			className={ classNames( 'tasty-form-field', className, {
				'tasty-form-field-top-label': variant === 'top-label',
			} ) }
			onClick={ onClick }
		>
			<label htmlFor={ id }>{ label }</label>
			<div className="tasty-form-field-input-wrapper">
				{ children }
				<p className="tasty-form-helper">
					{ typeof helper === 'function' ? helper() : helper }
				</p>
			</div>
		</div>
	) : (
		<div
			className={ classNames( 'tasty-form-field', className, {
				'tasty-form-field-top-label': variant === 'top-label',
			} ) }
			onClick={ onClick }
		>
			<label htmlFor={ id }>{ label }</label>
			{ children }
		</div>
	);
};
