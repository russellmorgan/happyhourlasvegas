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
