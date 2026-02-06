import { FormField } from './FormField';

export const InputField = ( {
	id,
	label,
	name,
	value,
	type = 'text',
	setValue,
	disabled = false,
	variant = 'side-label',
	helper = '',
	onClick = () => undefined,
	...props
} ) => {
	return (
		<FormField
			id={ id }
			label={ label }
			helper={ helper }
			onClick={ onClick }
			variant={ variant }
		>
			<input
				id={ id }
				name={ name }
				type={ type }
				onChange={ ( e ) => setValue( e.target.value ) }
				disabled={ disabled }
				value={ value }
				style={
					disabled ? { cursor: 'pointer', pointerEvents: 'none' } : {}
				}
				{ ...props }
			/>
		</FormField>
	);
};
