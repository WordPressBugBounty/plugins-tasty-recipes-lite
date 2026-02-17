import { useRef, useState, useEffect } from '@wordpress/element';
import { FormField } from './FormField';

export const EditorInput = ( {
	id,
	label,
	name,
	value,
	defaultValue = '',
	variant = 'side-label',
	setValue,
	editorOptions = {
		toolbar: 'bold italic underline link',
		mediaButtons: false,
		quicktags: true,
	},
	disabled = false,
	helper = '',
	onClick = () => {},
	...props
} ) => {
	const editorRef = useRef( null );
	const [ error, setError ] = useState( null );

	const { oldEditor } = window.wp;

	useEffect( () => {
		if ( ! editorRef.current || ! oldEditor ) {
			return;
		}

		const settings = {
			tinymce: {
				toolbar1: editorOptions.toolbar,
				statusbar: false,
				setup: ( editor ) => {
					editor.on( 'change', () => {
						setValue( editor.getContent() );
					} );
				},
			},
			quicktags: editorOptions.quicktags,
			mediaButtons: editorOptions.mediaButtons,
		};

		const initEditor = async () => {
			try {
				await oldEditor.initialize( id, settings );
			} catch ( err ) {
				setError( err );
			}
		};

		initEditor();

		return () => {
			oldEditor.remove( id );
		};
		// We disable the exhaustive-deps rule because we want to ensure the editor is initialized only once.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [
		editorOptions.mediaButtons,
		editorOptions.quicktags,
		editorOptions.toolbar,
		id,
	] );

	if ( error ) {
		return <div style={ { color: 'red' } }>{ error }</div>;
	}

	return (
		<FormField
			id={ id }
			label={ label }
			helper={ helper }
			className="tasty-editor-input"
			onClick={ onClick }
			variant={ variant }
			{...props}
		>
			<div ref={ editorRef } className="tasty-editor-input-wrapper">
				<textarea
					id={ id }
					name={ name }
					defaultValue={ value }
					disabled={ disabled }
					{ ...props }
				></textarea>
			</div>
		</FormField>
	);
};
