// Landing pages: auto-submit the vibe/menu filter selects; keep one area tab open on desktop and size the panel area.
( function () {
	document.querySelectorAll( '.hhv-vgrid__filters' ).forEach( function ( form ) {
		form.classList.add( 'is-enhanced' );
		form.addEventListener( 'change', function () {
			form.querySelectorAll( 'select' ).forEach( function ( s ) {
				s.disabled = ! s.value; // Keep "All ..." out of the URL.
			} );
			form.submit();
		} );
	} );

	var desktop = window.matchMedia( '(min-width: 700px)' );
	document.querySelectorAll( '.hhv-areadir__list' ).forEach( function ( list ) {
		var items = list.querySelectorAll( '.hhv-areadir__item' );
		function size() {
			var open = list.querySelector( '.hhv-areadir__item[open] .hhv-areadir__panel' );
			var tabs = 0;
			items.forEach( function ( d ) { tabs += d.offsetHeight + 4; } );
			list.style.setProperty( '--hhv-areadir-h', Math.max( tabs, open ? open.offsetHeight : 0 ) + 'px' );
		}
		items.forEach( function ( d ) {
			d.querySelector( 'summary' ).addEventListener( 'click', function ( e ) {
				if ( desktop.matches && d.open ) {
					e.preventDefault(); // Tabs: clicking the open tab keeps it open.
				}
			} );
			d.addEventListener( 'toggle', size );
		} );
		size();
		desktop.addEventListener( 'change', size );
	} );
} )();
