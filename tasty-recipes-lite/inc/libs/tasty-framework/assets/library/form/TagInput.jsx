import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { FormField } from './FormField';
import { __, sprintf } from '@wordpress/i18n';

export const TagInput = ( {
	id,
	label,
	name,
	value = '',
	setValue,
	disabled = false,
	helper = '',
	variant = 'side-label',
	placeholder = '',
	onClick = () => {},
	...props
} ) => {
	const [ tags, setTags ] = useState( [] );
	const [ inputValue, setInputValue ] = useState( '' );
	const inputRef = useRef( null );
	const containerRef = useRef( null );
	const memoizedSetValue = useCallback( setValue, [ setValue ] );

	useEffect( () => {
		if ( value && typeof value === 'string' ) {
			const tagArray = value
				.split( ',' )
				.map( ( tag ) => tag.trim() )
				.filter( ( tag ) => tag );
			const currentTagString = tags.join( ', ' );
			if ( currentTagString !== value ) {
				setTags( tagArray );
			}
		}
	}, [ value, tags ] );

	useEffect( () => {
		const tagString = tags.join( ', ' );
		if ( tagString !== value ) {
			memoizedSetValue( tagString );
		}
	}, [ tags, value, memoizedSetValue ] );

	const addTag = ( tag ) => {
		const trimmedTag = tag.trim();

		if ( trimmedTag && ! tags.includes( trimmedTag ) ) {
			const newTags = [ ...tags, trimmedTag ];
			setTags( newTags );
			const newValue = newTags.join( ', ' );
			setValue( newValue );
		}
		setInputValue( '' );
	};

	const removeTag = ( tagToRemove ) => {
		const newTags = tags.filter( ( tag ) => tag !== tagToRemove );
		setTags( newTags );
		const newValue = newTags.join( ', ' );
		setValue( newValue );
	};

	const handleKeyDown = ( e ) => {
		if ( e.key === 'Enter' || e.key === ',' ) {
			e.preventDefault();
			if ( inputValue.trim() ) {
				addTag( inputValue );
			}
		} else if ( e.key === 'Backspace' && ! inputValue && tags.length > 0 ) {
			removeTag( tags[ tags.length - 1 ] );
		}
	};

	const handleInputChange = ( e ) => {
		setInputValue( e.target.value );
	};

	const handleContainerClick = () => {
		if ( ! disabled && inputRef.current ) {
			inputRef.current.focus();
		}
	};

	const handleKeyPress = ( e ) => {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			handleContainerClick();
		}
	};

	const handleBlur = () => {
		if ( inputValue.trim() ) {
			addTag( inputValue );
		}
	};

	return (
		<FormField
			id={ id }
			label={ label }
			helper={ helper }
			onClick={ onClick }
			variant={ variant }
		>
			<div
				ref={ containerRef }
				className="tasty-tag-input-container"
				onClick={ handleContainerClick }
				onKeyDown={ handleKeyPress }
				role="textbox"
				tabIndex={ disabled ? -1 : 0 }
				aria-label={ label }
				style={ { cursor: disabled ? 'not-allowed' : 'text' } }
			>
				<div className="tasty-tag-input-content">
					{ tags.map( ( tag, index ) => (
						<>
							<span key={ index } className="tasty-tag">
								{ tag }
								{ ! disabled && (
									<button
										type="button"
										className="tasty-tag-remove"
										onClick={ ( e ) => {
											e.stopPropagation();
											removeTag( tag );
										} }
										aria-label={ sprintf(
											// translators: %s is the tag name
											__( 'Remove %s tag', 'tasty-pins' ),
											tag
										) }
									>
										×
									</button>
								) }
							</span>
							{ index < tags.length - 1 && (
								<span className="tasty-tag-comma">, </span>
							) }
						</>
					) ) }
					<input
						ref={ inputRef }
						type="text"
						value={ inputValue }
						onChange={ handleInputChange }
						onKeyDown={ handleKeyDown }
						onBlur={ handleBlur }
						disabled={ disabled }
						placeholder={ tags.length === 0 ? placeholder : '' }
						className="tasty-tag-input-field"
						{ ...props }
					/>
					<input
						type="hidden"
						name={ name }
						value={ tags.join( ', ' ) }
					/>
				</div>
			</div>
		</FormField>
	);
};
