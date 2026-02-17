import { FormField } from './FormField';

export const UploadInput = ( {
	id,
	label,
	name,
	value,
	setValue,
	disabled = false,
	variant = 'side-label',
	buttonText,
	helper = '',
	onClick = () => {},
	...props
} ) => {
	const handleMediaUpload = () => {
		// We use the global wp object because the available NPM packages work only within the block editor context.
		const uploader = wp.media( {
			title: 'Select or Upload Image',
			button: {
				text: 'Select Image',
			},
			multiple: false,
			library: {
				type: 'image',
			},
		} );

		uploader.on( 'select', () => {
			const attachment = uploader
				.state()
				.get( 'selection' )
				.first()
				.toJSON();
			setValue( attachment.url );
		} );

		uploader.open();
	};

	return (
		<FormField
			id={ id }
			label={ label }
			helper={ helper }
			onClick={ onClick }
			variant={ variant }
			{...props}
		>
			<input
				type="hidden"
				name={ name }
				value={ value || '' }
				disabled={ disabled }
			/>
			<button
				onClick={ ( e ) => {
					e.preventDefault();
					handleMediaUpload();
				} }
				id={ id }
				disabled={ disabled }
				className="tasty-secondary-button"
			>
				{ buttonText }
			</button>
		</FormField>
	);
};
