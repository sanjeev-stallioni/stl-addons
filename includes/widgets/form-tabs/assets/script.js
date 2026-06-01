/* Stl Addons — Form Tabs frontend script.
 *
 * Wires up tab buttons inside each .stl-ft-root so clicking a card reveals
 * its paired form panel. Safe to load multiple times: instances are tagged
 * via dataset.stlFtBound so we never bind the same root twice (the script
 * may be re-enqueued by Elementor on editor refresh).
 */
( function () {
	'use strict';

	function init( root ) {
		if ( ! root || root.dataset.stlFtBound === '1' ) return;
		root.dataset.stlFtBound = '1';

		var cards  = root.querySelectorAll( '.stl-ft-card' );
		var panels = root.querySelectorAll( '.stl-ft-panel' );
		if ( ! cards.length || ! panels.length ) return;

		var smooth = root.dataset.smooth === '1';

		function show( index ) {
			cards.forEach( function ( c ) {
				var on = parseInt( c.dataset.index, 10 ) === index;
				c.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
			var target = null;
			panels.forEach( function ( p ) {
				var on = parseInt( p.dataset.index, 10 ) === index;
				if ( on ) {
					p.removeAttribute( 'hidden' );
					target = p;
				} else {
					p.setAttribute( 'hidden', '' );
				}
			} );
			if ( smooth && target && target.getBoundingClientRect ) {
				requestAnimationFrame( function () {
					var top = target.getBoundingClientRect().top + window.scrollY - 80;
					window.scrollTo( { top: top, behavior: 'smooth' } );
				} );
			}
		}

		cards.forEach( function ( card ) {
			card.addEventListener( 'click', function () {
				var i = parseInt( card.dataset.index, 10 );
				if ( isNaN( i ) ) return;
				// Toggle: clicking the currently-open tab collapses it.
				if ( card.getAttribute( 'aria-pressed' ) === 'true' ) {
					card.setAttribute( 'aria-pressed', 'false' );
					panels.forEach( function ( p ) { p.setAttribute( 'hidden', '' ); } );
					return;
				}
				show( i );
			} );

			card.addEventListener( 'keydown', function ( e ) {
				if ( e.key !== 'ArrowRight' && e.key !== 'ArrowLeft' ) return;
				e.preventDefault();
				var list = Array.prototype.slice.call( cards );
				var idx  = list.indexOf( card );
				var next = e.key === 'ArrowRight' ? ( idx + 1 ) % list.length : ( idx - 1 + list.length ) % list.length;
				list[ next ].focus();
			} );
		} );
	}

	function boot( scope ) {
		( scope || document ).querySelectorAll( '.stl-ft-root' ).forEach( init );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () { boot(); } );
	} else {
		boot();
	}

	// Re-init when Elementor injects/refreshes the widget inside the editor.
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) return;
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/stl_form_tabs.default', function ( $scope ) {
				boot( $scope.get( 0 ) );
			} );
		} );
	}
} )();
