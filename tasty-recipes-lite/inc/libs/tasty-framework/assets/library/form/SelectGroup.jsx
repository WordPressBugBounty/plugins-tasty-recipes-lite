import { FormField } from './FormField';
import { SelectInput } from './SelectInput';

export const SelectGroup = ( {
	groupId,
	groupLabel,
	groupHelper = '',
	selects,
	onClick = () => {},
} ) => {
	return (
		<FormField
			id={ groupId }
			label={ groupLabel }
			helper={ groupHelper }
			className="tasty-select-group"
			onClick={ onClick }
		>
			{ selects.map( ( select ) => (
				<SelectInput
					id={ select.id }
					label={ select.label }
					name={ select.name }
					handleChange={ select.handleChange }
					disabled={ select.disabled }
					helper={ select.helper }
					isMulti={ select.isMulti }
					isSearchable={ select.isSearchable }
					isClearable={ select.isClearable }
					options={ select.options }
					key={ 'tasty-select-group-' + select.id }
					grouped={ true }
				/>
			) ) }
		</FormField>
	);
};
