export const CheckBox = ( {
	id,
	label,
	name,
	disabled = false,
	isChecked = false,
	value,
	onChange,
} ) => {
	return (
		<label className="tasty-checkbox-container" htmlFor={ id }>
			<input
				type="checkbox"
				name={ name }
				id={ id }
				disabled={ disabled }
				checked={ Boolean( isChecked ) }
				value={ value }
				onChange={ onChange }
			/>
			{ label }
			<span className="tasty-checkmark"></span>
		</label>
	);
};
