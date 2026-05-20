/**
 * Settings page — General tab behaviors.
 *
 * Independent IIFEs: each one no-ops when its target elements aren't present,
 * so loading on any tab of the Settings screen is safe.
 */

( function () {
    var ALLOWED_FILTERS = [ 'all', 'active', 'inactive' ];

    function readFilterFromUrl() {
        try {
            var params = new URLSearchParams( window.location.search );
            var f = params.get( 'filter' );
            return f && ALLOWED_FILTERS.indexOf( f ) !== -1 ? f : 'all';
        } catch ( e ) {
            return 'all';
        }
    }

    function syncFilterToUrl( filter ) {
        if ( ! window.history || typeof window.history.replaceState !== 'function' ) { return; }
        try {
            var url = new URL( window.location.href );
            if ( filter === 'all' ) {
                url.searchParams.delete( 'filter' );
            } else {
                url.searchParams.set( 'filter', filter );
            }
            window.history.replaceState( null, '', url.toString() );
        } catch ( e ) { /* no-op */ }
    }

    function adfoinInitPlatformFilters() {
        var searchInput = document.getElementById( 'adfoin-platform-search' );
        var filterBtns  = document.querySelectorAll( '.afi-filter-btn' );
        var container   = document.querySelector( '.afi-checkbox-container[data-platform-list]' );
        var noResults   = document.getElementById( 'afi-no-results' );

        if ( ! container || ! searchInput ) { return; }

        var items         = Array.prototype.slice.call( container.querySelectorAll( '.afi-checkbox' ) );
        var currentFilter = readFilterFromUrl();

        // Reflect the URL-derived filter on the button row.
        filterBtns.forEach( function ( b ) {
            b.classList.toggle( 'active', b.getAttribute( 'data-filter' ) === currentFilter );
        } );

        function filterItems() {
            var term         = searchInput.value.toLowerCase();
            var visibleCount = 0;

            items.forEach( function ( item ) {
                var label        = item.querySelector( '.afi-el-title label' );
                var text         = label ? label.textContent.toLowerCase() : '';
                var checkbox     = item.querySelector( 'input[type="checkbox"]' );
                var dynStatus    = checkbox && checkbox.checked ? 'active' : 'inactive';
                var matchSearch  = ! term || text.indexOf( term ) !== -1;
                var matchFilter  = currentFilter === 'all' || dynStatus === currentFilter;

                if ( matchSearch && matchFilter ) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            } );

            if ( noResults ) {
                noResults.hidden = ( visibleCount !== 0 );
            }
        }

        searchInput.addEventListener( 'input', filterItems );

        filterBtns.forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                filterBtns.forEach( function ( b ) { b.classList.remove( 'active' ); } );
                this.classList.add( 'active' );
                currentFilter = this.getAttribute( 'data-filter' );
                syncFilterToUrl( currentFilter );
                filterItems();
            } );
        } );

        items.forEach( function ( item ) {
            var cb = item.querySelector( 'input[type="checkbox"]' );
            if ( cb ) {
                cb.addEventListener( 'change', function () {
                    if ( currentFilter !== 'all' ) { filterItems(); }
                } );
            }
        } );

        // Apply the URL filter on first render.
        filterItems();
    }

    if ( document.readyState !== 'loading' ) {
        adfoinInitPlatformFilters();
    } else {
        document.addEventListener( 'DOMContentLoaded', adfoinInitPlatformFilters );
    }
}() );

( function () {
    function init() {
        var btn    = document.getElementById( 'adfoin-test-email-btn' );
        var result = document.getElementById( 'adfoin-test-email-result' );
        var cfg    = window.adfoinSettingsGeneral;
        if ( ! btn || ! cfg ) { return; }

        btn.addEventListener( 'click', function () {
            btn.disabled = true;
            if ( result ) {
                result.textContent = cfg.i18n && cfg.i18n.sending ? cfg.i18n.sending : '';
                result.className = 'afi-test-email-result is-pending';
            }

            var body = new URLSearchParams();
            body.append( 'action', 'adfoin_send_test_email' );
            body.append( '_nonce', cfg.testEmailNonce );

            fetch( cfg.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            } )
                .then( function ( r ) { return r.json().catch( function () { return null; } ); } )
                .then( function ( payload ) {
                    var ok      = payload && payload.success;
                    var message = payload && payload.data && payload.data.message
                        ? payload.data.message
                        : ( cfg.i18n && cfg.i18n.testEmailError ? cfg.i18n.testEmailError : '' );
                    if ( result ) {
                        result.textContent = message;
                        result.className   = 'afi-test-email-result ' + ( ok ? 'is-success' : 'is-error' );
                    }
                } )
                .catch( function () {
                    if ( result ) {
                        result.textContent = cfg.i18n && cfg.i18n.testEmailError ? cfg.i18n.testEmailError : '';
                        result.className   = 'afi-test-email-result is-error';
                    }
                } )
                .then( function () { btn.disabled = false; } );
        } );
    }

    if ( document.readyState !== 'loading' ) {
        init();
    } else {
        document.addEventListener( 'DOMContentLoaded', init );
    }
}() );

( function () {
    function init() {
        var form = document.getElementById( 'adfoin-reset-general-form' );
        var cfg  = window.adfoinSettingsGeneral;
        if ( ! form ) { return; }

        form.addEventListener( 'submit', function ( e ) {
            var msg = cfg && cfg.i18n && cfg.i18n.resetConfirm ? cfg.i18n.resetConfirm : 'Reset?';
            if ( ! window.confirm( msg ) ) {
                e.preventDefault();
            }
        } );
    }

    if ( document.readyState !== 'loading' ) {
        init();
    } else {
        document.addEventListener( 'DOMContentLoaded', init );
    }
}() );

( function () {
    function updateCheckboxBackground( checkbox ) {
        var container = checkbox.closest( '.afi-checkbox' );
        if ( container ) {
            container.classList.toggle( 'active', checkbox.checked );
        }
    }

    function init() {
        document.querySelectorAll( '.afi-checkbox input[type="checkbox"]' ).forEach( function ( checkbox ) {
            updateCheckboxBackground( checkbox );
            checkbox.addEventListener( 'change', function () {
                updateCheckboxBackground( this );
            } );
        } );
    }

    if ( document.readyState !== 'loading' ) {
        init();
    } else {
        document.addEventListener( 'DOMContentLoaded', init );
    }
}() );
