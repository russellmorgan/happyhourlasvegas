// Homepage: swap the YouTube thumbnail link for the player on click.
document.querySelectorAll( '.hhv-yt' ).forEach( function ( box ) {
	box.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.hhv-yt__play' ) || e.metaKey || e.ctrlKey || e.shiftKey ) {
			return;
		}
		e.preventDefault();
		var f = document.createElement( 'iframe' );
		f.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent( box.dataset.video ) + '?autoplay=1&rel=0';
		f.title = 'Happy Hour Vegas video';
		f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
		f.allowFullscreen = true;
		box.replaceChildren( f );
	} );
} );

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
