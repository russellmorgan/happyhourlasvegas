// "On this page": collapsed on mobile, open rail on desktop; highlights the section in view.
( function () {
	var toc = document.querySelector( '.hhv-toc' );
	if ( ! toc ) {
		return;
	}
	var details = toc.querySelector( 'details' );
	var desktop = window.matchMedia( '(min-width: 900px)' );
	function sync() {
		details.open = desktop.matches;
	}
	sync();
	desktop.addEventListener( 'change', sync );

	var links = [].slice.call( toc.querySelectorAll( 'a' ) );
	if ( ! ( 'IntersectionObserver' in window ) ) {
		return;
	}
	var spy = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( e ) {
			if ( e.isIntersecting ) {
				links.forEach( function ( a ) {
					a.classList.toggle( 'is-active', a.getAttribute( 'href' ) === '#' + e.target.id );
				} );
			}
		} );
	}, { rootMargin: '0px 0px -70% 0px' } );
	links.forEach( function ( a ) {
		var h = document.getElementById( a.getAttribute( 'href' ).slice( 1 ) );
		if ( h ) {
			spy.observe( h );
		}
	} );
} )();
