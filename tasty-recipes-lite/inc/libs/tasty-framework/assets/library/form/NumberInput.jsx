import { InputField } from './InputField';

export const NumberInput = ( {
	id,
	label,
	name,
	value,
	setValue,
	disabled = false,
	variant = 'side-label',
	helper = '',
	onClick = () => {},
	...props
} ) => {
	return (
		<InputField
			id={ id }
			label={ label }
			name={ name }
			value={ value }
			setValue={ setValue }
			type="number"
			disabled={ disabled }
			helper={ helper }
			onClick={ onClick }
			variant={ variant }
			{ ...props }
		/>
	);
};
