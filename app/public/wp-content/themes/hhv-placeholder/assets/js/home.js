// Homepage: sections below the fold fade + rise in as they scroll into view (styles in motion.css).
// Sections already on screen at load are left alone so nothing flashes.
if ( 'IntersectionObserver' in window && ! matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
	var revealIO = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( e ) {
			if ( e.isIntersecting ) {
				e.target.classList.add( 'is-in' );
				revealIO.unobserve( e.target );
			}
		} );
	}, { rootMargin: '0px 0px -10% 0px' } );
	document.querySelectorAll( '.hhv-home .hhv-browse, .hhv-home-video, .hhv-home-faq' ).forEach( function ( el ) {
		if ( el.getBoundingClientRect().top > innerHeight ) {
			el.classList.add( 'hhv-reveal' );
			revealIO.observe( el );
		}
	} );
}
