import { useState } from '@wordpress/element';
import classNames from 'classnames';
import { FormField } from './FormField';

export const FormFieldNotice = ( props ) => {
	const {
		children,
		className = '',
		isDismissible = true,
		onDismiss = () => {},
	} = props;

	const [ isVisible, setIsVisible ] = useState( true );

	const handleDismiss = () => {
		setIsVisible( false );
		onDismiss();
	};

	if ( ! isVisible ) {
		return null;
	}

	return (
		<FormField
			label=""
			className={ classNames(
				'tasty-form-field-notice-wrapper',
				'tasty-form-field',
				className
			) }
		>
			<div className="tasty-form-field-notice">
				<div className="tasty-form-field-notice-content">
					{ children }
				</div>
				{ isDismissible && (
					<button
						type="button"
						className="tasty-form-field-notice-dismiss"
						onClick={ handleDismiss }
						aria-label="Dismiss notice"
					>
						×
					</button>
				) }
			</div>
		</FormField>
	);
};
