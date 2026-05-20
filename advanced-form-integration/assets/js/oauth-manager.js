/**
 * Advanced Form Integration — OAuth Manager UI controller.
 *
 * Replaces the inline jQuery previously emitted by
 * `ADFOIN_OAuth_Manager::render_oauth_settings_view()`. Each call to
 * `render_oauth_settings_view` outputs a small init block that calls
 * `ADFOIN_OAuthManager.init({...})` with platform-specific config; the bulk
 * of the controller logic lives here so it loads once.
 *
 * Expected `opts` shape:
 *   {
 *     platform:    'googletasks',
 *     fields:      [ { name, label, type, mask, show_in_table, required, ... }, ... ],
 *     nonce:       '<wp-nonce>',
 *     showStatus:  true,
 *     i18n: {
 *       save, update, confirmDelete, deleteFailed, saveFailed, error,
 *       loading, untitled, connected, notConnected, connectionBroken,
 *       noAccounts, authFailed, edit, delete
 *     }
 *   }
 */
(function () {
    'use strict';

    window.ADFOIN_OAuthManager = window.ADFOIN_OAuthManager || {};

    /**
     * Mask first 6 characters; collapse very short values.
     */
    function maskValue(value) {
        if (!value) {
            return '';
        }
        if (value.length > 6) {
            return value.substring(0, 6) + '****';
        }
        if (value.length > 2) {
            return value.substring(0, 2) + '****';
        }
        return '****';
    }

    /**
     * Tiny HTML escaper. Only the values we interpolate into HTML strings
     * use this; static markup stays as-is.
     */
    function escapeHtml(s) {
        if (s === null || typeof s === 'undefined') {
            return '';
        }
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    ADFOIN_OAuthManager.init = function (opts) {
        var platform   = opts.platform;
        var fields     = opts.fields || [];
        var nonce      = opts.nonce;
        var showStatus = !!opts.showStatus;
        var enableTest = !!opts.enableTest;
        var i18n       = opts.i18n || {};

        jQuery(function ($) {
            var $modal     = $('#adfoin-modal-overlay');
            var $form      = $('#adfoin-' + platform + '-form');
            var $table     = $('#adfoin-' + platform + '-table');
            var $submitBtn = $('#adfoin-' + platform + '-submit-btn');
            var $spinner   = $submitBtn.siblings('.spinner');

            function openModal(reset) {
                if (reset !== false) {
                    $form[0].reset();
                    $('#adfoin_' + platform + '_id').val('');
                    $submitBtn.text(i18n.save || 'Save');
                }
                $modal.fadeIn(300);
            }

            function closeModal() {
                $modal.fadeOut(300);
            }

            $('#adfoin-add-' + platform + '-account').on('click', function (e) {
                e.preventDefault();
                openModal(true);
            });

            $('.adfoin-modal-close').on('click', function (e) {
                e.preventDefault();
                closeModal();
            });

            $modal.on('click', function (e) {
                if ($(e.target).hasClass('afi-modal')) {
                    closeModal();
                }
            });

            $(document).on('click', '.adfoin-edit-account-btn', function (e) {
                e.preventDefault();
                var $btn = $(this);
                if (!$btn.closest($table).length) {
                    return;
                }

                var data = $btn.data();
                $('#adfoin_' + platform + '_id').val(data.id);
                $('#adfoin_' + platform + '_title').val(data.title);

                $.each(fields, function (i, field) {
                    var $field = $('#adfoin_' + platform + '_' + field.name);
                    if ($field.length && typeof data[field.name] !== 'undefined') {
                        $field.val(data[field.name]);
                    }
                });

                $submitBtn.text(i18n.update || 'Update');
                openModal(false);
            });

            $(document).on('click', '.adfoin-test-account-btn', function (e) {
                e.preventDefault();
                var $btn = $(this);
                if (!$btn.closest($table).length) {
                    return;
                }
                var credId = $btn.data('id');
                var $icon  = $btn.find('.dashicons');
                $icon.removeClass('dashicons-admin-network').addClass('dashicons-update');
                $btn.prop('disabled', true).attr('title', i18n.testing || 'Testing…');

                $.ajax({
                    url: window.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adfoin_test_' + platform + '_connection',
                        _nonce: nonce,
                        credId: credId
                    },
                    complete: function () {
                        $icon.removeClass('dashicons-update').addClass('dashicons-admin-network');
                        $btn.prop('disabled', false).attr('title', i18n.test || 'Test connection');
                    },
                    success: function (response) {
                        if (response && response.success) {
                            alert(i18n.testOk || 'Connection OK');
                        } else {
                            var msg = response && response.data && response.data.message
                                ? response.data.message
                                : (i18n.error || 'An error occurred.');
                            alert((i18n.testFailed || 'Connection test failed:') + ' ' + msg);
                        }
                    },
                    error: function () {
                        alert(i18n.error || 'An error occurred.');
                    }
                });
            });

            $(document).on('click', '.adfoin-delete-account-btn', function (e) {
                e.preventDefault();
                var $btn = $(this);
                if (!$btn.closest($table).length) {
                    return;
                }
                if (!confirm(i18n.confirmDelete || 'Delete?')) {
                    return;
                }

                $.ajax({
                    url: window.ajaxurl,
                    type: 'POST',
                    data: {
                        action:       'adfoin_save_' + platform + '_credentials',
                        _nonce:       nonce,
                        delete_index: $btn.data('index')
                    },
                    success: function (response) {
                        if (response && response.success) {
                            refreshTable();
                        } else {
                            alert((response && response.data && response.data.message) || i18n.deleteFailed || 'Failed.');
                        }
                    }
                });
            });

            $form.on('submit', function (e) {
                e.preventDefault();

                $submitBtn.prop('disabled', true);
                $spinner.addClass('is-active');

                // Open the OAuth popup SYNCHRONOUSLY inside the submit handler
                // so it inherits the user-gesture context. Calling window.open()
                // later, inside the AJAX success callback, is blocked by
                // browsers' stricter popup heuristics — most visibly in
                // incognito/private windows but increasingly in normal mode
                // too. Symptom of the async path: popup closes immediately
                // or never appears.
                //
                // We don't have the auth URL yet, so the popup opens to
                // about:blank and we navigate it once save_credentials
                // returns. about:blank inherits the opener's origin, so the
                // later `oauthPopup.location.href = url` assignment works
                // without same-origin-policy issues.
                var width  = 600;
                var height = 700;
                var left   = (screen.width / 2) - (width / 2);
                var top    = (screen.height / 2) - (height / 2);
                var oauthPopup = window.open(
                    'about:blank',
                    'adfoin_oauth_popup',
                    'width=' + width + ',height=' + height + ',top=' + top + ',left=' + left
                );
                window.adfoin_oauth_popup = oauthPopup;

                if (!oauthPopup || oauthPopup.closed || typeof oauthPopup.closed === 'undefined') {
                    alert('A popup was blocked by your browser. Please allow popups for this site and try again.');
                    $submitBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    return;
                }

                var formData = {
                    action: 'adfoin_save_' + platform + '_credentials',
                    _nonce: nonce,
                    id:     $('#adfoin_' + platform + '_id').val(),
                    title:  $('#adfoin_' + platform + '_title').val()
                };

                $.each(fields, function (i, field) {
                    formData[field.name] = $('#adfoin_' + platform + '_' + field.name).val();
                });

                $.ajax({
                    url: window.ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        if (response && response.success) {
                            if (response.data && response.data.auth_url) {
                                // Navigate the pre-opened popup to the auth URL.
                                // Try location.href first; fall back to
                                // location.replace which is explicitly allowed
                                // cross-origin in stricter browsers.
                                try {
                                    oauthPopup.location.href = response.data.auth_url;
                                } catch (err) {
                                    try { oauthPopup.location.replace(response.data.auth_url); } catch (e2) {}
                                }
                                closeModal();
                            } else {
                                // Plain save (no auth needed) — close the placeholder popup.
                                if (oauthPopup && !oauthPopup.closed) { oauthPopup.close(); }
                                closeModal();
                                refreshTable();
                            }
                        } else {
                            if (oauthPopup && !oauthPopup.closed) { oauthPopup.close(); }
                            alert((response && response.data && response.data.message) || i18n.saveFailed || 'Failed.');
                        }
                        $submitBtn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    },
                    error: function () {
                        if (oauthPopup && !oauthPopup.closed) { oauthPopup.close(); }
                        alert(i18n.error || 'An error occurred.');
                        $submitBtn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                });
            });

            function renderStatusCell(row) {
                var connectionFailed = !!row.connection_failed;
                var isConnected      = (typeof row.connected !== 'undefined')
                    ? !!row.connected
                    : !!(row.access_token || row.accessToken);

                if (connectionFailed) {
                    return '<td><span style="color: #d63638; font-weight: 600;"><span class="dashicons dashicons-warning" style="font-size: 16px; vertical-align: middle;"></span> '
                        + escapeHtml(i18n.connectionBroken || 'Connection broken') + '</span></td>';
                }
                if (isConnected) {
                    return '<td><span style="color: #46b450; font-weight: 600;"><span class="dashicons dashicons-yes-alt" style="font-size: 16px; vertical-align: middle;"></span> '
                        + escapeHtml(i18n.connected || 'Connected') + '</span></td>';
                }
                return '<td><span style="color: #dc3232; font-weight: 600;"><span class="dashicons dashicons-dismiss" style="font-size: 16px; vertical-align: middle;"></span> '
                    + escapeHtml(i18n.notConnected || 'Not Connected') + '</span></td>';
            }

            function refreshTable() {
                var $tbody   = $table.find('tbody');
                var colCount = $table.find('thead th').length;

                $tbody.html('<tr><td colspan="' + colCount + '">' + escapeHtml(i18n.loading || 'Loading...') + '</td></tr>');

                $.ajax({
                    url: window.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adfoin_get_' + platform + '_credentials',
                        _nonce: nonce
                    },
                    success: function (response) {
                        if (!response || !response.success || !response.data || !response.data.length) {
                            $tbody.html(
                                '<tr><td colspan="' + colCount + '" style="text-align: center; padding: 40px 20px; color: #666;">'
                                + '<span class="dashicons dashicons-info" style="font-size: 24px; opacity: 0.5;"></span>'
                                + '<p style="margin: 10px 0 0 0;">' + escapeHtml(i18n.noAccounts || 'No accounts.') + '</p></td></tr>'
                            );
                            return;
                        }

                        var html = '';
                        $.each(response.data, function (index, row) {
                            html += '<tr>';
                            html += '<td>' + escapeHtml(row.title || i18n.untitled || 'Untitled') + '</td>';

                            $.each(fields, function (i, field) {
                                if (field.show_in_table === false) {
                                    return;
                                }
                                var value = row[field.name] || '';
                                if (field.mask && value) {
                                    value = maskValue(value);
                                }
                                html += '<td>' + escapeHtml(value) + '</td>';
                            });

                            if (showStatus) {
                                html += renderStatusCell(row);
                            }

                            // Actions column.
                            html += '<td>';
                            html += '<button class="button-link adfoin-edit-account-btn" '
                                  + 'data-index="' + index + '" '
                                  + 'data-id="'    + escapeHtml(row.id) + '" '
                                  + 'data-title="' + escapeHtml(row.title || '') + '" ';
                            $.each(fields, function (i, field) {
                                html += 'data-' + field.name + '="' + escapeHtml(row[field.name] || '') + '" ';
                            });
                            html += 'title="' + escapeHtml(i18n.edit || 'Edit') + '">'
                                  + '<span class="dashicons dashicons-edit"></span></button> ';

                            html += '<button class="button-link adfoin-delete-account-btn" '
                                  + 'data-index="' + index + '" '
                                  + 'data-id="'    + escapeHtml(row.id) + '" '
                                  + 'title="' + escapeHtml(i18n.delete || 'Delete') + '" '
                                  + 'style="color: #dc3232;">'
                                  + '<span class="dashicons dashicons-trash"></span></button>';

                            if (enableTest) {
                                html += ' <button class="button-link adfoin-test-account-btn" '
                                      + 'data-id="' + escapeHtml(row.id) + '" '
                                      + 'title="' + escapeHtml(i18n.test || 'Test connection') + '">'
                                      + '<span class="dashicons dashicons-admin-network"></span></button>';
                            }

                            html += '</td></tr>';
                        });

                        $tbody.html(html);
                    }
                });
            }

            // OAuth popup → parent window communication via localStorage.
            //
            // We previously used postMessage (window.opener.postMessage), but
            // that silently fails whenever `window.opener` has been severed —
            // which happens routinely with Cross-Origin-Opener-Policy:
            // same-origin headers, certain popup blockers, browser
            // tracking-protection, and a handful of WordPress security
            // plugins. Result: the parent never received the result, the
            // popup auto-closed, and the user saw "OAuth doesn't work" with
            // no feedback even when token storage actually succeeded.
            //
            // `storage` events fire across every tab/window of the same
            // origin regardless of opener state, so this listener picks up
            // the result no matter what severed the opener.
            window.addEventListener('storage', function (event) {
                if (event.key !== 'adfoin_oauth_response' || !event.newValue) {
                    return;
                }
                var data;
                try { data = JSON.parse(event.newValue); } catch (e) { return; }
                if (!data || data.type !== 'adfoin_oauth_response') {
                    return;
                }
                if (data.status === 'success') {
                    refreshTable();
                } else {
                    alert((i18n.authFailed || 'Authorization failed:') + ' ' + (data.message || ''));
                }
                // Clear so a later page load doesn't re-trigger.
                try { localStorage.removeItem('adfoin_oauth_response'); } catch (e) {}
                if (window.adfoin_oauth_popup && !window.adfoin_oauth_popup.closed) {
                    window.adfoin_oauth_popup.close();
                }
            });

            refreshTable();
        });
    };
}());
