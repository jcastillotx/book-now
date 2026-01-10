/**
 * List View JavaScript
 *
 * @package BookNow
 * @since   1.2.0
 */

(function($) {
    'use strict';

    let selectedTypeId = null;
    let selectedDate = null;
    let selectedTime = null;
    let days = 7;

    $(document).ready(function() {
        initListView();
    });

    function initListView() {
        // Get initial settings
        selectedTypeId = $('.booknow-list-wrapper').data('type-id') || null;
        days = $('.booknow-list-wrapper').data('days') || 7;

        // Type selector change
        $('.list-type-select').on('change', function() {
            selectedTypeId = $(this).val();
            if (selectedTypeId) {
                loadAvailableSlots();
            } else {
                $('#list-container').html('<p class="list-placeholder">Please select a consultation type to view available times.</p>');
            }
        });

        // Slot click
        $(document).on('click', '.list-slot-button', handleSlotClick);

        // Modal actions
        $('.modal-close, .modal-cancel, .modal-overlay').on('click', closeModal);
        $('.modal-close-success').on('click', closeModal);
        $('#list-booking-form').on('submit', handleBookingSubmit);

        // Initial load if type is specified
        if (selectedTypeId) {
            loadAvailableSlots();
        }
    }

    function loadAvailableSlots() {
        if (!selectedTypeId) return;

        $('.list-loading').show();
        $('#list-container').html('');

        // Calculate date range
        const startDate = new Date();
        const endDate = new Date();
        endDate.setDate(endDate.getDate() + days);

        const promises = [];
        const currentDate = new Date(startDate);

        // Load slots for each day
        while (currentDate <= endDate) {
            const dateStr = formatDate(currentDate);
            promises.push(loadSlotsForDate(selectedTypeId, dateStr));
            currentDate.setDate(currentDate.getDate() + 1);
        }

        Promise.all(promises).then(function(results) {
            $('.list-loading').hide();
            renderSlotsList(results);
        }).catch(function() {
            $('.list-loading').hide();
            $('#list-container').html('<p class="error">Failed to load available slots. Please try again.</p>');
        });
    }

    function loadSlotsForDate(typeId, date) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: bookNowPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'booknow_get_time_slots',
                    nonce: bookNowPublic.nonce,
                    consultation_type_id: typeId,
                    date: date
                },
                success: function(response) {
                    if (response.success && response.data.slots) {
                        resolve({
                            date: date,
                            slots: response.data.slots
                        });
                    } else {
                        resolve({
                            date: date,
                            slots: []
                        });
                    }
                },
                error: function() {
                    reject();
                }
            });
        });
    }

    function renderSlotsList(results) {
        let html = '';
        let hasSlots = false;

        results.forEach(function(dayData) {
            if (dayData.slots && dayData.slots.length > 0) {
                hasSlots = true;
                html += '<div class="list-day-group">';
                html += '<h4 class="list-day-header">' + formatDateDisplay(dayData.date) + '</h4>';
                html += '<div class="list-slots-grid">';
                
                dayData.slots.forEach(function(slot) {
                    html += '<button type="button" class="list-slot-button" data-date="' + dayData.date + '" data-time="' + slot.time + '">';
                    html += slot.formatted || slot.time;
                    html += '</button>';
                });
                
                html += '</div>';
                html += '</div>';
            }
        });

        if (!hasSlots) {
            html = '<p class="no-slots">No available time slots found for the next ' + days + ' days.</p>';
        }

        $('#list-container').html(html);
    }

    function handleSlotClick() {
        selectedDate = $(this).data('date');
        selectedTime = $(this).data('time');
        openBookingModal();
    }

    function openBookingModal() {
        // Get type name
        let typeName = '';
        if ($('.list-type-select').length) {
            typeName = $('.list-type-select option:selected').text();
        }

        // Populate summary
        $('.summary-date').text(formatDateDisplay(selectedDate));
        $('.summary-time').text(selectedTime);
        $('.summary-type').text(typeName);

        // Show modal
        $('.booknow-list-modal').fadeIn(300);
        $('body').addClass('modal-open');
    }

    function closeModal() {
        $('.booknow-list-modal').fadeOut(300);
        $('body').removeClass('modal-open');
        
        // Reset form
        $('#list-booking-form')[0].reset();
        $('.booking-form').show();
        $('.booking-success').hide();
    }

    function handleBookingSubmit(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('.modal-submit');

        $submitBtn.prop('disabled', true).text('Processing...');

        const formData = {
            action: 'booknow_create_booking',
            nonce: bookNowPublic.nonce,
            consultation_type_id: selectedTypeId,
            booking_date: selectedDate,
            booking_time: selectedTime,
            customer_name: $('#list-customer-name').val(),
            customer_email: $('#list-customer-email').val(),
            customer_phone: $('#list-customer-phone').val(),
            notes: $('#list-customer-notes').val()
        };

        $.ajax({
            url: bookNowPublic.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data);
                } else {
                    alert(response.data.message || 'Booking failed. Please try again.');
                    $submitBtn.prop('disabled', false).text('Confirm Booking');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $submitBtn.prop('disabled', false).text('Confirm Booking');
            }
        });
    }

    function showSuccess(data) {
        $('.booking-form').hide();
        $('.success-reference').html('Your booking reference is: <strong>' + data.reference + '</strong>');
        $('.booking-success').show();

        // Reload slots
        loadAvailableSlots();
    }

    // Helper functions
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function formatDateDisplay(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString(undefined, options);
    }

})(jQuery);
