import { FormField } from './FormField';
import { useRef, useEffect } from '@wordpress/element';

export const LongTextInput = ( {
	id,
	label,
	name,
	value,
	setValue,
	disabled,
	variant = 'side-label',
	rows = 2,
	helper,
	onClick = () => {},
	...props
} ) => {
	const textAreaRef = useRef( null );

	const adjustHeight = () => {
		const textarea = textAreaRef.current;

		if ( textarea ) {
			textarea.style.height = 'auto';
			textarea.style.height = `${ textarea.scrollHeight }px`;
		}
	};

	useEffect( () => {
		adjustHeight();
	}, [ value ] );

	return (
		<FormField
			id={ id }
			label={ label }
			helper={ helper }
			onClick={ onClick }
			variant={ variant }
		>
			<textarea
				id={ id }
				name={ name }
				onChange={ ( e ) => setValue( e.target.value ) }
				disabled={ disabled }
				value={ value }
				rows={ rows }
				ref={ textAreaRef }
				{ ...props }
			>
				{ value || '' }
			</textarea>
		</FormField>
	);
};
