/**
 * Book Now Admin JavaScript
 *
 * @package BookNow
 * @since   1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Modal handling
        const modal = $('#consultation-type-modal');
        const modalClose = $('.booknow-modal-close');

        // Open modal for adding new consultation type
        $('#add-consultation-type').on('click', function(e) {
            e.preventDefault();
            $('#consultation-type-form')[0].reset();
            $('#consultation-type-id').val('');
            $('#modal-title').text(bookNowAdmin.strings.addNew || 'Add Consultation Type');
            modal.show();
        });

        // Open modal for editing consultation type
        $('.edit-consultation-type').on('click', function(e) {
            e.preventDefault();
            const typeId = $(this).data('id');
            // In a full implementation, you would load the consultation type data via AJAX
            $('#consultation-type-id').val(typeId);
            $('#modal-title').text('Edit Consultation Type');
            modal.show();
        });

        // Close modal
        modalClose.on('click', function() {
            modal.hide();
        });

        // Close modal on outside click
        $(window).on('click', function(e) {
            if ($(e.target).is('.booknow-modal')) {
                modal.hide();
            }
        });

        // Handle consultation type form submission
        $('#consultation-type-form').on('submit', function(e) {
            e.preventDefault();

            const formData = $(this).serialize();
            const data = {
                action: 'booknow_save_consultation_type',
                nonce: bookNowAdmin.nonce
            };

            // Parse form data and add to data object
            $(this).serializeArray().forEach(function(field) {
                data[field.name] = field.value;
            });

            $.ajax({
                url: bookNowAdmin.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || bookNowAdmin.strings.success);
                        location.reload();
                    } else {
                        alert(response.data.message || bookNowAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(bookNowAdmin.strings.error);
                }
            });
        });

        // Handle consultation type deletion
        $('.delete-consultation-type').on('click', function(e) {
            e.preventDefault();

            if (!confirm(bookNowAdmin.strings.confirmDelete)) {
                return;
            }

            const typeId = $(this).data('id');
            const row = $(this).closest('tr');

            $.ajax({
                url: bookNowAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'booknow_delete_consultation_type',
                    nonce: bookNowAdmin.nonce,
                    id: typeId
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(400, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || bookNowAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(bookNowAdmin.strings.error);
                }
            });
        });

        // Handle booking status updates
        $('.update-booking-status').on('change', function() {
            const bookingId = $(this).data('id');
            const newStatus = $(this).val();

            $.ajax({
                url: bookNowAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'booknow_update_booking_status',
                    nonce: bookNowAdmin.nonce,
                    id: bookingId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        // Optionally show a success message
                        console.log('Status updated');
                    } else {
                        alert(response.data.message || bookNowAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(bookNowAdmin.strings.error);
                }
            });
        });
    });

})(jQuery);
