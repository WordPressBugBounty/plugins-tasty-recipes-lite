export const SettingsGroup = ( { label, children } ) => {
	return (
		<div className="tasty-settings-group">
			<div className="tasty-settings-group-label">
				<p>{ label }</p>
			</div>
			<div className="tasty-settings-group-content">{ children }</div>
		</div>
	);
};
