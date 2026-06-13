/* Stl Addons — Post Grid frontend script.
 *
 * Powers the optional category filter bar inside each .stl-pg. Filtering is
 * purely client-side and additive: every post is already in the DOM (crawlable
 * for SEO), and clicking a chip just hides the cards that don't match via the
 * .is-hidden class. Safe to load multiple times — each grid is tagged via
 * dataset.stlPgBound so we never bind the same instance twice (Elementor may
 * re-enqueue the script on editor refresh).
 */
( function () {
	'use strict';

	function init( root ) {
		if ( ! root || root.dataset.stlPgBound === '1' ) return;

		var filter = root.querySelector( '.stl-pg-filter' );
		var posts  = root.querySelectorAll( '.stl-pg-post' );
		if ( ! filter || ! posts.length ) return;

		root.dataset.stlPgBound = '1';
		var chips = filter.querySelectorAll( '.stl-pg-chip' );

		function apply( value ) {
			posts.forEach( function ( post ) {
				var cats = ( post.getAttribute( 'data-cats' ) || '' ).split( /\s+/ );
				var show = value === '*' || cats.indexOf( value ) !== -1;
				post.classList.toggle( 'is-hidden', ! show );
			} );
			chips.forEach( function ( chip ) {
				var on = chip.getAttribute( 'data-filter' ) === value;
				chip.classList.toggle( 'is-active', on );
				chip.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
		}

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				apply( chip.getAttribute( 'data-filter' ) || '*' );
			} );
		} );
	}

	function boot( scope ) {
		( scope || document ).querySelectorAll( '.stl-pg' ).forEach( init );
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
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/stl_post_grid.default', function ( $scope ) {
				boot( $scope.get( 0 ) );
			} );
		} );
	}
} )();
