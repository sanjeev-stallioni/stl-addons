/* Stl Addons — Social Links frontend script.
 *
 * Wires the floating radial FAB: clicking the trigger toggles .is-active on
 * the .stl-sl-radial container so the CSS arc animation runs. Each .stl-sl-radial
 * is bound once via dataset.stlSlBound so the script is safe to load twice
 * (Elementor editor reload, etc.).
 *
 * The sliding-rail variant is pure CSS and needs no JS.
 */
( function () {
	'use strict';

	function init( root ) {
		if ( ! root || root.dataset.stlSlBound === '1' ) return;
		root.dataset.stlSlBound = '1';

		var trigger = root.querySelector( '.stl-sl-radial-trigger' );
		if ( ! trigger ) return;

		trigger.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			var isActive = root.classList.toggle( 'is-active' );
			trigger.setAttribute( 'aria-expanded', isActive ? 'true' : 'false' );

			// Close every other open radial on the page — only one expanded at a time.
			if ( isActive ) {
				document.querySelectorAll( '.stl-sl-radial.is-active' ).forEach( function ( other ) {
					if ( other !== root ) {
						other.classList.remove( 'is-active' );
						var t = other.querySelector( '.stl-sl-radial-trigger' );
						if ( t ) t.setAttribute( 'aria-expanded', 'false' );
					}
				} );
			}
		} );

		// Close on outside click.
		document.addEventListener( 'click', function ( e ) {
			if ( ! root.classList.contains( 'is-active' ) ) return;
			if ( root.contains( e.target ) ) return;
			root.classList.remove( 'is-active' );
			trigger.setAttribute( 'aria-expanded', 'false' );
		} );

		// Close on Escape.
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'Escape' ) return;
			if ( ! root.classList.contains( 'is-active' ) ) return;
			root.classList.remove( 'is-active' );
			trigger.setAttribute( 'aria-expanded', 'false' );
			trigger.focus();
		} );
	}

	function boot( scope ) {
		( scope || document ).querySelectorAll( '.stl-sl-radial' ).forEach( init );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () { boot(); } );
	} else {
		boot();
	}

	// Re-init when Elementor injects/refreshes the widget in the editor.
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) return;
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/stl_social_links.default', function ( $scope ) {
				boot( $scope.get( 0 ) );
			} );
		} );
	}
} )();
