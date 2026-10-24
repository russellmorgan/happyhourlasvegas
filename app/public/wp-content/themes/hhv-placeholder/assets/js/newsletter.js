// Newsletter signup: submit to BigMailer in a hidden iframe and swap the form for a thank-you line.
// Without JS the form still works (target=_blank opens BigMailer's own page).
document.addEventListener( 'submit', function ( e ) {
	var form = e.target.closest( '.hhv-newsletter form' );
	if ( ! form ) {
		return;
	}
	var frame = document.createElement( 'iframe' );
	frame.name = 'hhv-nl-' + Date.now();
	frame.hidden = true;
	document.body.appendChild( frame );
	form.target = frame.name;
	setTimeout( function () {
		var msg = document.createElement( 'p' );
		msg.className = 'hhv-newsletter__done';
		msg.setAttribute( 'role', 'status' );
		msg.textContent = 'Welcome to the club, check your email.';
		form.replaceWith( msg );
	} );
} );
