import { FormField } from './FormField';
import Select from 'react-select';

export const SelectInput = ( {
	id,
	label,
	options,
	name,
	handleChange,
	disabled = false,
	helper = '',
	isMulti = false,
	isSearchable = false,
	isClearable = false,
	grouped = false,
	onClick = () => {},
	variant = 'side-label',
	...props
} ) => {
	const customOptions = [
		...options.filter( ( option ) => option.selected ),
		...options.filter( ( option ) => ! option.selected ),
	];

	const value = options.filter( ( option ) => option.selected );

	const selectElement = (
		<Select
			id={ id }
			name={ name }
			options={ customOptions }
			value={ value }
			isMulti={ isMulti }
			isDisabled={ disabled }
			isSearchable={ isSearchable }
			isClearable={ isClearable }
			onChange={ ( selectedOption ) => {
				const val = isMulti
					? selectedOption.map( ( opt ) => opt.value )
					: selectedOption.value;
				handleChange( val );
			} }
			className="tasty-react-select-container"
			classNamePrefix="tasty-react-select"
			styles={ {
				valueContainer: ( base ) => ( {
					...base,
					justifyContent: 'flex-start',
					flexWrap: 'nowrap',
					overflow: 'hidden',
					padding: '2px 8px',
					display: 'flex',
					alignItems: 'center',
				} ),
				multiValue: ( base ) => ( {
					...base,
					flex: '0 0 auto',
				} ),
				singleValue: ( base ) => ( {
					...base,
					overflow: 'clip',
				} ),
				input: ( base ) => ( {
					...base,
					margin: 0,
					padding: 0,
				} ),
			} }
		/>
	);

	if ( ! grouped ) {
		return (
			<FormField
				id={ id }
				label={ label }
				helper={ helper }
				onClick={ onClick }
				variant={ variant }
				{...props}
			>
				{ selectElement }
			</FormField>
		);
	}

	return selectElement;
};
