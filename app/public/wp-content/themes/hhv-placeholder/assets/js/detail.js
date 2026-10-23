// Venue detail page: photo dialog opens at the clicked photo, mobile "Read more", Share button (where the browser can share).
document.querySelectorAll( '[data-hhv-gallery]' ).forEach( ( gallery ) => {
	const dialog = gallery.querySelector( 'dialog' );
	gallery.querySelectorAll( '[data-photo]' ).forEach( ( button ) => button.addEventListener( 'click', () => {
		if ( ! dialog.open ) {
			dialog.showModal(); // Browsers without invoker commands (command="show-modal").
		}
		dialog.querySelectorAll( 'li' )[ button.dataset.photo ]?.scrollIntoView( { block: 'start' } );
	} ) );
	dialog.addEventListener( 'click', ( e ) => e.target === dialog && dialog.close() ); // Backdrop click where closedby="any" is unsupported.
} );

document.querySelectorAll( '[data-hhv-more]' ).forEach( ( text ) => {
	const button = document.querySelector( '[aria-controls="' + text.id + '"]' );
	text.classList.add( 'is-clamped' ); // Only clamps on mobile (detail.css).
	if ( text.scrollHeight <= text.clientHeight + 4 ) {
		text.classList.remove( 'is-clamped' );
		return;
	}
	button.hidden = false;
	button.addEventListener( 'click', () => {
		const open = ! text.classList.toggle( 'is-clamped' );
		button.setAttribute( 'aria-expanded', open );
		button.textContent = open ? 'Show less' : 'Read more';
	} );
} );

document.querySelectorAll( '[data-hhv-share]' ).forEach( ( button ) => {
	if ( navigator.share ) {
		button.hidden = false;
		button.addEventListener( 'click', () => navigator.share( { title: document.title, url: location.href } ).catch( () => {} ) );
	}
} );
