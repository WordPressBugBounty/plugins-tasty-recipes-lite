/* eslint-disable jsx-a11y/anchor-is-valid */
/* eslint-disable jsx-a11y/anchor-has-content */
import { useRef, useEffect, useCallback } from '@wordpress/element';

export const Modal = ( { modalData, closeModal, children } ) => {
	const { open, title, subtitle, id, xClickCallback } = modalData;
	const modalRef = useRef( null );

	useEffect( () => {
		document.body.style.overflow = open ? 'hidden' : '';

		return () => {
			document.body.style.overflow = '';
		};
	}, [ open ] );

	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if (
				modalRef.current &&
				! modalRef.current.contains( event.target )
			) {
				closeModal();
			}
		};

		const handleEscKey = ( event ) => {
			if ( event.key === 'Escape' ) {
				closeModal();
			}
		};

		if ( open ) {
			document.addEventListener( 'mousedown', handleClickOutside );
			document.addEventListener( 'keydown', handleEscKey );
		}

		// Cleanup listener when component unmounts or modal closes
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
			document.removeEventListener( 'keydown', handleEscKey );
		};
	}, [ open, closeModal ] );

	const handleXClick = useCallback( () => {
		if ( xClickCallback ) {
			xClickCallback();
		}

		closeModal();
	}, [ xClickCallback, closeModal ] );

	if ( open ) {
		return (
			<section className="tasty-framework-modal tasty-open" id={ id }>
				<div
					className="tasty-framework-modal-container"
					ref={ modalRef }
				>
					{ ! modalData.disableClosing && (
						<a
							href="#"
							className="tasty-hide-modal tasty-framework-close-modal tasty-framework-modal-x"
							onClick={ handleXClick }
						></a>
					) }
					<div className="tasty-framework-modal-header">
						<h2>{ title }</h2>
						{ subtitle && (
							<p className="tasty-framework-modal-header-subtitle">
								{ subtitle }
							</p>
						) }
					</div>

					<div className="tasty-framework-modal-body">
						{ children }
					</div>
				</div>
			</section>
		);
	}

	return null;
};
