// eslint-disable-next-line import/no-extraneous-dependencies
import ChromePicker from '@uiw/react-color-chrome';
import { useState, useRef, useEffect } from '@wordpress/element';
import { FormField } from './FormField';

export const ColorInput = ( {
	id,
	label,
	name,
	disabled = false,
	variant = 'side-label',
	color,
	defaultColor = '',
	setColor,
	helper = '',
	onClick = () => {},
	...props
} ) => {
	const [ innerColor, setInnerColor ] = useState( () => {
		if ( ! color || color.length === 0 ) {
			return defaultColor;
		}

		return color;
	} );
	const [ isOpen, setIsOpen ] = useState( false );
	const pickerRef = useRef( null );

	// If the color changes to empty, set the inner color to the default color.
	useEffect( () => {
		setInnerColor( ! color || color.length === 0 ? defaultColor : color );
	}, [ color, defaultColor ] );

	// If the inner color is empty, set it to the default color.
	useEffect( () => {
		if ( ! innerColor || innerColor.length === 0 ) {
			setInnerColor( defaultColor );
		}
	}, [ innerColor, defaultColor ] );

	// Handle click outside of the picker.
	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if (
				pickerRef.current &&
				! pickerRef.current.contains( event.target )
			) {
				setIsOpen( false );
			}
		};

		document.addEventListener( 'mousedown', handleClickOutside );
		return () =>
			document.removeEventListener( 'mousedown', handleClickOutside );
	}, [] );

	const handleChange = ( changeInnerColor ) => {
		return changeInnerColor.rgba.a >= 1
			? setColor( changeInnerColor.hex )
			: setColor( changeInnerColor.hexa );
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
			<div className="tasty-color-wrap">
				<button
					className="tasty-swatch"
					style={ { background: innerColor } }
					onClick={ ( e ) => {
						e.preventDefault();

						if ( disabled ) {
							return;
						}
						setIsOpen( ! isOpen );
					} }
				></button>
				<input
					type="text"
					name={ name }
					value={ innerColor }
					disabled={ disabled }
					onChange={ ( e ) => {
						setInnerColor( e.target.value );

						if ( e.target.value !== defaultColor ) {
							setColor( e.target.value );
						}
					} }
				/>

				{ isOpen && (
					<div ref={ pickerRef } className="tasty-picker-popup">
						<ChromePicker
							color={ innerColor }
							onChange={ handleChange }
							showEyeDropper={ false }
							inputType="hexa"
						/>
					</div>
				) }
			</div>
		</FormField>
	);
};
