/**
 * Calendar View JavaScript
 *
 * @package BookNow
 * @since   1.2.0
 */

(function($) {
    'use strict';

    let currentDate = new Date();
    let selectedTypeId = null;
    let selectedDate = null;
    let selectedTime = null;
    let availableDates = [];

    $(document).ready(function() {
        initCalendar();
    });

    function initCalendar() {
        // Get initial type ID from data attribute
        selectedTypeId = $('.booknow-calendar-wrapper').data('type-id') || null;

        // Type selector change
        $('.calendar-type-select').on('change', function() {
            selectedTypeId = $(this).val();
            if (selectedTypeId) {
                loadAvailableDates();
            }
        });

        // Navigation
        $('.calendar-prev').on('click', function() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
            if (selectedTypeId) {
                loadAvailableDates();
            }
        });

        $('.calendar-next').on('click', function() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
            if (selectedTypeId) {
                loadAvailableDates();
            }
        });

        // Day click
        $(document).on('click', '.calendar-day.available', handleDayClick);

        // Time slot click
        $(document).on('click', '.timeslot-button', handleTimeslotClick);

        // Close timeslots panel
        $('.close-timeslots').on('click', function() {
            $('.booknow-timeslots-panel').hide();
        });

        // Modal actions
        $('.modal-close, .modal-cancel, .modal-overlay').on('click', closeModal);
        $('.modal-close-success').on('click', closeModal);
        $('#calendar-booking-form').on('submit', handleBookingSubmit);

        // Initial render
        renderCalendar();
        
        if (selectedTypeId) {
            loadAvailableDates();
        }
    }

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        // Update header
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
        $('.calendar-month-year').text(monthNames[month] + ' ' + year);

        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Build calendar grid
        let html = '';
        let dayCount = 1;

        // Add empty cells for days before month starts
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-day empty"></div>';
        }

        // Add days of month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = formatDate(year, month, day);
            const isAvailable = availableDates.includes(dateStr);
            const isPast = isPastDate(year, month, day);
            const isToday = isCurrentDate(year, month, day);

            let classes = 'calendar-day';
            if (isToday) classes += ' today';
            if (isPast) classes += ' past';
            if (isAvailable && !isPast) classes += ' available';

            html += '<div class="' + classes + '" data-date="' + dateStr + '">';
            html += '<span class="day-number">' + day + '</span>';
            if (isAvailable && !isPast) {
                html += '<span class="day-indicator"></span>';
            }
            html += '</div>';
        }

        $('#calendar-days-container').html(html);
    }

    function loadAvailableDates() {
        if (!selectedTypeId) return;

        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0');
        const monthStr = year + '-' + month;

        $('.calendar-loading').show();

        $.ajax({
            url: bookNowPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'booknow_get_available_dates',
                nonce: bookNowPublic.nonce,
                consultation_type_id: selectedTypeId,
                month: monthStr
            },
            success: function(response) {
                $('.calendar-loading').hide();
                if (response.success && response.data.dates) {
                    availableDates = response.data.dates;
                    renderCalendar();
                }
            },
            error: function() {
                $('.calendar-loading').hide();
                alert('Failed to load availability. Please try again.');
            }
        });
    }

    function handleDayClick() {
        const date = $(this).data('date');
        selectedDate = date;

        // Highlight selected day
        $('.calendar-day').removeClass('selected');
        $(this).addClass('selected');

        // Load time slots
        loadTimeSlots(selectedTypeId, date);
    }

    function loadTimeSlots(typeId, date) {
        $('.booknow-timeslots-panel').show();
        $('.selected-date').text(formatDateDisplay(date));
        $('#timeslots-container').html('<div class="loading">Loading...</div>');

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
                    renderTimeSlots(response.data.slots);
                } else {
                    $('#timeslots-container').html('<p class="no-slots">No available times for this date.</p>');
                }
            },
            error: function() {
                $('#timeslots-container').html('<p class="error">Failed to load time slots.</p>');
            }
        });
    }

    function renderTimeSlots(slots) {
        if (!slots || slots.length === 0) {
            $('#timeslots-container').html('<p class="no-slots">No available times.</p>');
            return;
        }

        let html = '<div class="timeslots-grid">';
        slots.forEach(function(slot) {
            html += '<button type="button" class="timeslot-button" data-time="' + slot.time + '">';
            html += slot.formatted || slot.time;
            html += '</button>';
        });
        html += '</div>';

        $('#timeslots-container').html(html);
    }

    function handleTimeslotClick() {
        selectedTime = $(this).data('time');
        openBookingModal();
    }

    function openBookingModal() {
        // Get type name
        let typeName = '';
        if ($('.calendar-type-select').length) {
            typeName = $('.calendar-type-select option:selected').text();
        }

        // Populate summary
        $('.summary-date').text(formatDateDisplay(selectedDate));
        $('.summary-time').text(selectedTime);
        $('.summary-type').text(typeName);

        // Show modal
        $('.booknow-booking-modal').fadeIn(300);
        $('body').addClass('modal-open');
    }

    function closeModal() {
        $('.booknow-booking-modal').fadeOut(300);
        $('body').removeClass('modal-open');
        
        // Reset form
        $('#calendar-booking-form')[0].reset();
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
            customer_name: $('#modal-customer-name').val(),
            customer_email: $('#modal-customer-email').val(),
            customer_phone: $('#modal-customer-phone').val(),
            notes: $('#modal-customer-notes').val()
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

        // Reload available dates
        loadAvailableDates();
    }

    // Helper functions
    function formatDate(year, month, day) {
        return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    }

    function formatDateDisplay(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString(undefined, options);
    }

    function isPastDate(year, month, day) {
        const date = new Date(year, month, day);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return date < today;
    }

    function isCurrentDate(year, month, day) {
        const date = new Date(year, month, day);
        const today = new Date();
        return date.toDateString() === today.toDateString();
    }

})(jQuery);
