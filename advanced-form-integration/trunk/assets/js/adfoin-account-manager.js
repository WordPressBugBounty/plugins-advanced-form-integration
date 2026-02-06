/**
 * Centralized Account Manager JavaScript
 * 
 * Handles all account management interactions using jQuery
 * Replaces Vue.js modals with simpler PHP/jQuery approach
 * 
 * @package Advanced_Form_Integration
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Account Manager Class
     * 
     * @param {string} platform Platform slug (e.g., 'klaviyo', 'brevo')
     * @param {string} title    Platform title (e.g., 'Klaviyo', 'Brevo')
     */
    window.ADFOIN_AccountManager = function(platform, title) {
        this.platform = platform;
        this.title = title;
        this.modal = $('#adfoin-' + platform + '-modal');
        this.overlay = $('#adfoin-modal-overlay-' + platform);
        this.form = $('#adfoin-' + platform + '-form');
        this.table = $('#adfoin-' + platform + '-table');
        this.modalTitle = $('#adfoin-' + platform + '-modal-title');
        
        this.init();
    };

    ADFOIN_AccountManager.prototype = {
        
        /**
         * Initialize event handlers
         */
        init: function() {
            var self = this;
            
            // Add account button
            $('#adfoin-add-' + this.platform + '-account').on('click', function(e) {
                e.preventDefault();
                self.openModal('add');
            });
            
            // Edit account buttons
            $(document).on('click', '.adfoin-edit-account-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                
                // Only handle if it's for this platform's table
                if ($btn.closest('#adfoin-' + self.platform + '-table').length) {
                    self.openModal('edit', $btn);
                }
            });
            
            // Delete account buttons
            $(document).on('click', '.adfoin-delete-account-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                
                // Only handle if it's for this platform's table
                if ($btn.closest('#adfoin-' + self.platform + '-table').length) {
                    self.deleteAccount($btn);
                }
            });
            
            // Close modal buttons
            $('.adfoin-modal-close[data-platform="' + this.platform + '"]').on('click', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            // Close on overlay click
            this.overlay.on('click', function() {
                self.closeModal();
            });
            
            // Close on ESC key
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape' && self.modal.is(':visible')) {
                    self.closeModal();
                }
            });
            
            // Form submission
            this.form.on('submit', function(e) {
                e.preventDefault();
                self.saveAccount();
            });
        },
        
        /**
         * Open modal for add or edit
         * 
         * @param {string} mode 'add' or 'edit'
         * @param {jQuery} $btn Button element (for edit mode)
         */
        openModal: function(mode, $btn) {
            if (mode === 'add') {
                this.modalTitle.text('Add ' + this.title + ' Account');
                this.form[0].reset();
                $('#adfoin_' + this.platform + '_id').val('');
            } else if (mode === 'edit' && $btn) {
                this.modalTitle.text('Edit ' + this.title + ' Account');
                
                // Populate form with existing data
                var data = $btn.data();
                $('#adfoin_' + this.platform + '_id').val(data.id);
                $('#adfoin_' + this.platform + '_title').val(data.title);
                
                // Get credential data from JSON
                var credData = {};
                try {
                    credData = JSON.parse($btn.attr('data-cred') || '{}');
                } catch (e) {
                    console.warn('Failed to parse credential data:', e);
                    credData = {};
                }
                
                // Populate all field values from credential data
                var self = this;
                this.form.find('input[name], select[name], textarea[name]').each(function() {
                    var $field = $(this);
                    var fieldName = $field.attr('name');
                    
                    if (fieldName && fieldName !== 'action' && fieldName !== '_nonce' && 
                        fieldName !== 'id' && fieldName !== 'platform' && fieldName !== 'title') {
                        if (typeof credData[fieldName] !== 'undefined') {
                            $field.val(credData[fieldName]);
                        }
                    }
                });
            }
            
            this.overlay.fadeIn(200);
            this.modal.fadeIn(300);
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            this.modal.fadeOut(200);
            this.overlay.fadeOut(300);
            this.form[0].reset();
        },
        
        /**
         * Save account via AJAX
         */
        saveAccount: function() {
            var self = this;
            var $submitBtn = $('#adfoin-' + this.platform + '-submit-btn');
            var $spinner = this.form.find('.spinner');
            
            // Disable submit button and show spinner
            $submitBtn.prop('disabled', true);
            $spinner.addClass('is-active');
            
            var formData = this.form.serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $submitBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    
                    if (response.success) {
                        self.showNotice('success', response.data.message || 'Account saved successfully!');
                        self.closeModal();
                        
                        // Reload page to show updated table
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    } else {
                        self.showNotice('error', response.data.message || 'Failed to save account.');
                    }
                },
                error: function(xhr, status, error) {
                    $submitBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    self.showNotice('error', 'An error occurred: ' + error);
                }
            });
        },
        
        /**
         * Delete account
         * 
         * @param {jQuery} $btn Delete button element
         */
        deleteAccount: function($btn) {
            var self = this;
            
            if (!confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
                return;
            }
            
            var index = $btn.data('index');
            var id = $btn.data('id');
            
            // Show loading state
            $btn.prop('disabled', true);
            $btn.find('.dashicons').addClass('dashicons-update').css('animation', 'rotation 1s infinite linear');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'adfoin_save_' + this.platform + '_credentials',
                    _nonce: this.form.find('input[name="_nonce"]').val(),
                    delete_index: index
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('success', response.data.message || 'Account deleted successfully!');
                        
                        // Remove row from table with animation
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Show "no accounts" message if table is empty
                            if (self.table.find('tbody tr').length === 0) {
                                var colspan = self.table.find('thead th').length;
                                self.table.find('tbody').html(
                                    '<tr class="adfoin-no-accounts">' +
                                    '<td colspan="' + colspan + '" style="text-align: center; padding: 40px 20px; color: #666;">' +
                                    '<span class="dashicons dashicons-info" style="font-size: 24px; opacity: 0.5;"></span>' +
                                    '<p style="margin: 10px 0 0 0;">No accounts found. Click "Add Account" to get started.</p>' +
                                    '</td>' +
                                    '</tr>'
                                );
                            }
                        });
                    } else {
                        $btn.prop('disabled', false);
                        $btn.find('.dashicons').removeClass('dashicons-update').css('animation', '');
                        self.showNotice('error', response.data.message || 'Failed to delete account.');
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    $btn.find('.dashicons').removeClass('dashicons-update').css('animation', '');
                    self.showNotice('error', 'An error occurred: ' + error);
                }
            });
        },
        
        /**
         * Show admin notice
         * 
         * @param {string} type    'success' or 'error'
         * @param {string} message Notice message
         */
        showNotice: function(type, message) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Insert after page title
            $('.wrap > h1').first().after($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Make dismissible
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);
        }
    };

    // Add CSS for rotation animation
    $('<style>')
        .text('@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }')
        .appendTo('head');

})(jQuery);
