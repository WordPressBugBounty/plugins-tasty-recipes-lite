export const TastyBanner = ( {
	heading,
	description,
	buttonText,
	buttonUrl = false,
	buttonType = 'link',
	buttonOnClick = () => {},
} ) => {
	return (
		<div className="tasty-settings-banner">
			<div className="tasty-settings-banner-left">
				<h2>{ heading }</h2>
				<p>{ description }</p>
			</div>
			<div className="tasty-settings-banner-right">
				{ buttonType === 'link' && (
					<a
						className="tasty-banner-button"
						href={ buttonUrl }
						target="_blank"
						rel="noreferrer"
					>
						{ buttonText }
					</a>
				) }
				{ buttonType === 'button' && (
					<button
						className="tasty-banner-button"
						onClick={ buttonOnClick }
					>
						{ buttonText }
					</button>
				) }
			</div>
		</div>
	);
};
