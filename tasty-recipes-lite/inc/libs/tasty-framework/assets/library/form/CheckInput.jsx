import { CheckBox } from './base/CheckBox';
import { FormField } from './FormField';

export const CheckInput = ( {
	id,
	label,
	variant = 'side-label',
	setValue,
	helper = '',
	onClick = () => {},
	...props
} ) => {
	return (
		<FormField
			id={ id }
			helper={ helper }
			onClick={ onClick }
			variant={ variant }
			{...props}
		>
			<CheckBox
				id={ id }
				label={ label }
				onChange={ ( e ) => {
					setValue( e.target.checked );
				} }
				{ ...props }
			/>
		</FormField>
	);
};
