// Click-to-play YouTube (inc/video.php): swap the thumbnail link for the player on click.
// Ctrl/Cmd/Shift click still opens YouTube in a new tab.
document.addEventListener( 'click', function ( e ) {
	var link = e.target.closest && e.target.closest( '.hhv-yt__play' );
	if ( ! link || e.metaKey || e.ctrlKey || e.shiftKey || e.button ) {
		return;
	}
	var box = link.closest( '.hhv-yt' );
	e.preventDefault();
	var f = document.createElement( 'iframe' );
	f.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent( box.dataset.video ) + '?autoplay=1&rel=0&playsinline=1';
	f.title = box.dataset.title || 'Happy Hour Vegas video';
	f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
	f.allowFullscreen = true;
	box.replaceChildren( f );
} );
