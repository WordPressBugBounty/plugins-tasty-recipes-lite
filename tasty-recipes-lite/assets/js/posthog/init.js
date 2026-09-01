/**
 * Initialize PostHog once using localized config.
 *
 * @since x.x
 */

window.tastyRecipesInitPosthog = () => {
	const config = window.tastyRecipesPosthog;

	if ( window.tastyRecipesPosthogLoaded || 'undefined' === typeof posthog || ! config ) {
		return;
	}

	window.tastyRecipesPosthogLoaded = true;

	posthog.init( config.apiKey, {
		api_host: config.apiHost,
		defaults: config.defaults,
		person_profiles: 'identified_only',
	} );

	posthog.identify( config.siteId );
	posthog.group( 'site', config.siteId );
};

if ( window.tastyRecipesPosthog && window.tastyRecipesPosthog.autoInit ) {
	window.tastyRecipesInitPosthog();
}
