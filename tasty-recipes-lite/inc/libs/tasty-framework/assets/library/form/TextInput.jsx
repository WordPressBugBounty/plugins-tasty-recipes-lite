import { InputField } from './InputField';

export const TextInput = ( {
	id,
	label,
	name,
	value,
	setValue,
	disabled = false,
	helper = '',
	variant = 'side-label',
	...props
} ) => {
	return (
		<InputField
			id={ id }
			label={ label }
			name={ name }
			value={ value }
			setValue={ setValue }
			type="text"
			disabled={ disabled }
			helper={ helper }
			variant={ variant }
			{ ...props }
		/>
	);
};
