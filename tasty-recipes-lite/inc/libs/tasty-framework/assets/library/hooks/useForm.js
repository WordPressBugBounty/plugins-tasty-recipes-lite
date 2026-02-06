import { useState } from '@wordpress/element';

export const useForm = ( initialValues ) => {
	const [ formData, setFormData ] = useState( initialValues );

	const handleChange = ( name, value ) => {
		setFormData( ( prev ) => {
			return {
				...prev,
				[ name ]: value,
			};
		} );
	};

	return { formData, handleChange };
};
