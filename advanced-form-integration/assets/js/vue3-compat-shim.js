/**
 * Advanced Form Integration — Vue 2 → Vue 3 compatibility shim.
 *
 * Loaded immediately after Vue 3's global build and BEFORE any plugin
 * script. It re-creates the two Vue-2 globals this codebase relies on, so
 * the two app bootstraps (app.js, core.js) and all ~348 lazy-loaded
 * platform `*-component.js` files keep working unchanged on Vue 3:
 *
 *   - `new Vue({ el, data: {...}, ... })`  — Vue-2 root-instance style
 *   - `Vue.component('name', {...})`        — Vue-2 global registration
 *
 * Component *options* (props/data/methods/watch/computed/lifecycle) are
 * API-compatible between Vue 2 and Vue 3, so individual components need no
 * changes. The remaining Vue-3 breakers — $children, $scopedSlots,
 * $listeners, render(h) signature, transition class names — are handled in
 * the migration's Phase 2; they surface as console warnings/errors while
 * this shim is active, which is exactly what the Phase 0 spike measures.
 *
 * @since vue3-migration branch
 */
( function ( global ) {
	'use strict';

	var Vue3 = global.Vue;

	if ( ! Vue3 || typeof Vue3.createApp !== 'function' ) {
		// Vue 3 global build not present — leave window.Vue untouched so a
		// fallback to vue.min.js (Vue 2) still works during the migration.
		return;
	}

	var realCreateApp = Vue3.createApp;
	var apps          = []; // every app created through the shim
	var registry      = {}; // component name -> definition (global components)

	/**
	 * Vue-2-style constructor: `new Vue({ el, data, ... })`.
	 * Returns the mounted root proxy, mirroring Vue 2's `new Vue()`.
	 */
	function VueShim( options ) {
		options = options || {};

		var el = options.el;
		delete options.el;

		// Vue 3 requires a root component's data to be a function.
		if ( options.data && typeof options.data !== 'function' ) {
			var dataObject = options.data;
			options.data   = function () { return dataObject; };
		}

		var app = realCreateApp( options );
		apps.push( app );

		// Vue 3 removed the vm.$set / vm.$delete instance methods, but ~250
		// component call sites still use `this.$set(...)`. Re-expose them via
		// globalProperties so every component keeps working untouched. Vue 3's
		// Proxy reactivity makes plain property assignment reactive, so these
		// are thin wrappers (array index handled via splice for Vue-2 parity).
		var gp = app.config.globalProperties;
		if ( ! gp.$set ) {
			gp.$set = function ( target, key, value ) {
				if ( Array.isArray( target ) && typeof key === 'number' ) {
					target.splice( key, 1, value );
				} else if ( target && typeof target === 'object' ) {
					target[ key ] = value;
				}
				return value;
			};
		}
		if ( ! gp.$delete ) {
			gp.$delete = function ( target, key ) {
				if ( Array.isArray( target ) && typeof key === 'number' ) {
					target.splice( key, 1 );
				} else if ( target && typeof target === 'object' ) {
					delete target[ key ];
				}
			};
		}

		// Hand the new app every component already registered globally.
		Object.keys( registry ).forEach( function ( name ) {
			app.component( name, registry[ name ] );
		} );

		return el ? app.mount( el ) : app;
	}

	/**
	 * Vue-2-style global component registration. Records the definition and
	 * registers it on every app (existing and future) — matching Vue 2's
	 * global `Vue.component()` semantics. Lazy-loaded platform components
	 * call this after their app has mounted; Vue 3 resolves them at the next
	 * render of the dynamic <component :is="..."> picker.
	 */
	VueShim.component = function ( name, definition ) {
		if ( typeof definition === 'undefined' ) {
			return registry[ name ]; // Vue-2 getter form
		}
		registry[ name ] = definition;
		apps.forEach( function ( app ) {
			app.component( name, definition );
		} );
		return definition;
	};

	/**
	 * Vue-2 `Vue.set` / `Vue.delete`. Vue 3's Proxy reactivity tracks plain
	 * property add/delete, so these are simple pass-throughs.
	 */
	VueShim.set = function ( target, key, value ) {
		target[ key ] = value;
		return value;
	};
	VueShim['delete'] = function ( target, key ) {
		delete target[ key ];
	};

	// Copy EVERY Vue 3 export onto the shim. Vue 3's runtime template
	// compiler (the full "global" build) emits render functions that pull
	// helpers — resolveComponent, createVNode, toDisplayString, openBlock,
	// createElementBlock, … — off the global `Vue`. Because we replace that
	// global just below, the shim must carry Vue 3's complete export surface
	// or every compiled `template:` throws "_resolveComponent is not a
	// function". Runs after the Vue-2 overrides above; Vue 3 has no
	// `component`/`set`/`delete` exports, so nothing is clobbered.
	Object.keys( Vue3 ).forEach( function ( key ) {
		if ( ! VueShim.hasOwnProperty( key ) ) {
			VueShim[ key ] = Vue3[ key ];
		}
	} );

	VueShim.createApp = realCreateApp;
	VueShim.__vue3__  = Vue3; // escape hatch for code that needs the real object

	global.Vue = VueShim;
}( window ) );
