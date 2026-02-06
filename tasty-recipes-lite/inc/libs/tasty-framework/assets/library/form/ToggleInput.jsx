import { FormField } from './FormField';
import { useEffect, useState } from '@wordpress/element';

export const ToggleInput = ( {
	id,
	label,
	name,
	disabled = false,
	variant = 'side-label',
	value,
	setValue,
	onValue = 'yes',
	offValue = 'no',
	helper = '',
	onClick = () => {},
} ) => {
	const [ isChecked, setIsChecked ] = useState( () => {
		return disabled ? false : value === onValue;
	} );

	useEffect( () => {
		setIsChecked( () => {
			return disabled ? false : value === onValue;
		} );
	}, [ disabled, onValue, value ] );

	return (
		<FormField
			id={ id }
			label={ label }
			helper={ helper }
			onClick={ onClick }
			variant={ variant }
		>
			{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
			<label className="tasty-toggle-container">
				<input
					type="checkbox"
					name={ name }
					id={ id }
					disabled={ disabled }
					checked={ isChecked }
					value={ value }
					onChange={ ( e ) => {
						setIsChecked( ( prev ) => ! prev );
						setValue( e.target.checked ? onValue : offValue );
					} }
				/>
				<span className="tasty-slider"></span>
			</label>
			{ /* We keep this hidden checkbox because WordPress won't process unchecked boxes */ }
			<input
				type="checkbox"
				style={ { display: 'none' } }
				name={ name }
				value={ value }
				checked={ disabled ? false : ! isChecked }
				disabled={ disabled }
				onChange={ () => {
					// Prevent read-only error
				} }
			/>
		</FormField>
	);
};
