// Mobile overlay menu: core shows every submenu expanded; collapse them and let the toggle open one at a time.
( function () {
	document.addEventListener( 'click', function ( e ) {
		var open = e.target.closest( '.hhv-header .wp-block-navigation__responsive-container-open' );
		if ( open ) {
			// Core marks all submenus expanded once the overlay opens; reset after it renders.
			setTimeout( function () {
				document.querySelectorAll( '.hhv-header .is-menu-open .has-child' ).forEach( function ( li ) {
					li.classList.remove( 'is-open' );
					li.querySelector( '.wp-block-navigation-submenu__toggle' ).setAttribute( 'aria-expanded', 'false' );
				} );
			} );
			return;
		}
		var t = e.target.closest( '.hhv-header .is-menu-open .wp-block-navigation-submenu__toggle' );
		if ( ! t ) {
			return;
		}
		e.stopPropagation();
		var li = t.parentElement;
		li.classList.toggle( 'is-open' );
		t.setAttribute( 'aria-expanded', li.classList.contains( 'is-open' ) ? 'true' : 'false' );
	}, true );
} )();
