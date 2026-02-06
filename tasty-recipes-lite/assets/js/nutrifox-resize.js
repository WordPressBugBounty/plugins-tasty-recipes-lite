(function(){
	window.addEventListener( 'message', function( event ){
		if ( ( 'https://nutrifox.com' !== event.origin && 'https://nutrifox.test' !== event.origin ) ||
			typeof event.data !== 'string' ) {
			return;
		}
		var payload = JSON.parse( event.data );
		switch ( payload.event ) {
			case 'setHeight':
				var iframe = document.getElementById( 'nutrifox-label-' + payload.recipeId );
				iframe.style.height = payload.height + 'px';
				break;
		}
	} );
}());
