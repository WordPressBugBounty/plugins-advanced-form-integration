/**
 * AFI UTM Grabber — first-touch attribution capture.
 *
 * Reads marketing/click-ID parameters from the current URL, stores them in
 * cookies on FIRST encounter only (first-touch is what most CRMs want for
 * attribution), and auto-fills hidden fields on any form on the page. Also
 * decorates outbound links carrying the `.utm-out` class with the stored
 * params so the trail survives navigation.
 *
 * Notes for future-readers:
 * - We do NOT capture `email`/`username` from the URL. Doing so opens a
 *   phishing vector: `?email=victim@x.com` could silently pre-fill a form's
 *   email input on the victim's machine for the cookie lifetime.
 * - We do NOT match form fields by CSS class. `.email`/`.username` collide
 *   with theme styling classes and clobber visible user input. Use the
 *   field's `name` or `id` attribute instead.
 */
jQuery(function ($) {
    var TRACKED_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_term',
        'utm_content',
        'utm_campaign',
        'gclid',
        'gbraid',
        'wbraid',
        'fbclid',
        'msclkid',
        'ttclid',
        'li_fat_id',
        'dclid'
    ];

    var COOKIE_OPTS = {
        expires: 30,
        sameSite: 'lax',
        secure: window.location.protocol === 'https:'
    };

    var urlParams = new URLSearchParams(window.location.search);

    TRACKED_PARAMS.forEach(function (param) {
        var fromUrl = urlParams.get(param);
        var fromCookie = Cookies.get(param);

        // First-touch: only write the cookie if this URL has the param AND
        // no cookie value already exists. Returning visitors with a fresh
        // ad click do NOT overwrite their original attribution.
        if (fromUrl && !fromCookie) {
            Cookies.set(param, fromUrl, COOKIE_OPTS);
        }

        var value = Cookies.get(param);
        if (value === undefined || value === '') {
            return;
        }

        // js-cookie already URL-decodes on read; do not decode again.
        // Match by name or id only (class-based matching clobbered visible
        // inputs that happened to share a class name with a tracking param).
        $('input[name="' + param + '"]').val(value);
        $('input#' + param).val(value);
    });

    // Decorate outbound links carrying ".utm-out" with the stored params so
    // attribution survives off-site navigation. Existing query params on the
    // link are preserved; stored UTMs only fill in gaps.
    $('.utm-out').each(function () {
        var href = this.href;
        if (!href) {
            return;
        }

        var anchor = document.createElement('a');
        anchor.href = href;
        var linkParams = new URLSearchParams(anchor.search);
        var changed = false;

        TRACKED_PARAMS.forEach(function (param) {
            if (linkParams.has(param)) {
                return;
            }
            var stored = Cookies.get(param);
            if (stored) {
                linkParams.set(param, stored);
                changed = true;
            }
        });

        if (changed) {
            anchor.search = linkParams.toString();
            this.href = anchor.href;
        }
    });
});
