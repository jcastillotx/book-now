/**
 * Booking Form Wizard JavaScript
 *
 * @package BookNow
 * @since   1.2.0
 */

(function($) {
    'use strict';

    let currentStep = 1;
    let selectedTypeId = null;
    let selectedDate = null;
    let selectedTime = null;

    $(document).ready(function() {
        initWizard();
    });

    function initWizard() {
        // Step navigation
        $('.booknow-next-step').on('click', handleNextStep);
        $('.booknow-prev-step').on('click', handlePrevStep);

        // Type selection
        $('input[name="consultation_type_id"]').on('change', handleTypeSelection);

        // Date selection
        $('#booking_date').on('change', handleDateSelection);

        // Time slot selection
        $(document).on('click', '.time-slot', handleTimeSelection);

        // Form submission
        $('.booknow-form-wrapper').on('submit', handleFormSubmit);

        // Initialize first step
        showStep(1);
    }

    function handleTypeSelection() {
        selectedTypeId = $(this).val();
        $('.booknow-next-step').prop('disabled', false);
    }

    function handleDateSelection() {
        const date = $(this).val();
        
        if (!date || !selectedTypeId) {
            return;
        }

        selectedDate = date;
        loadTimeSlots(selectedTypeId, date);
    }

    function loadTimeSlots(typeId, date) {
        const $slotsContainer = $('#slots-container');
        const $availableSlots = $('#available-slots');

        // Show loading
        $slotsContainer.html('<div class="loading">' + bookNowPublic.strings.loading + '</div>');
        $availableSlots.show();

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
                    $slotsContainer.html('<p class="no-slots">No available time slots for this date.</p>');
                }
            },
            error: function() {
                $slotsContainer.html('<p class="error">Failed to load time slots. Please try again.</p>');
            }
        });
    }

    function renderTimeSlots(slots) {
        const $container = $('#slots-container');
        
        if (!slots || slots.length === 0) {
            $container.html('<p class="no-slots">No available time slots for this date.</p>');
            return;
        }

        let html = '<div class="time-slots-grid">';
        slots.forEach(function(slot) {
            html += '<button type="button" class="time-slot" data-time="' + slot.time + '">';
            html += slot.formatted || slot.time;
            html += '</button>';
        });
        html += '</div>';

        $container.html(html);
    }

    function handleTimeSelection(e) {
        e.preventDefault();
        
        // Remove previous selection
        $('.time-slot').removeClass('selected');
        
        // Mark as selected
        $(this).addClass('selected');
        
        // Store selected time
        selectedTime = $(this).data('time');
        
        // Enable next button
        $('.booknow-form-step[data-step="2"] .booknow-next-step').prop('disabled', false);
    }

    function handleNextStep() {
        if (!validateStep(currentStep)) {
            return;
        }

        currentStep++;
        showStep(currentStep);
    }

    function handlePrevStep() {
        currentStep--;
        showStep(currentStep);
    }

    function showStep(step) {
        $('.booknow-form-step').hide();
        $('.booknow-form-step[data-step="' + step + '"]').show();
        currentStep = step;

        // Auto-detect type if hidden input exists
        const hiddenTypeId = $('input[name="consultation_type_id"][type="hidden"]').val();
        if (hiddenTypeId && !selectedTypeId) {
            selectedTypeId = hiddenTypeId;
            currentStep = 2;
            showStep(2);
        }
    }

    function validateStep(step) {
        switch(step) {
            case 1:
                if (!selectedTypeId) {
                    alert(bookNowPublic.strings.selectType || 'Please select a consultation type');
                    return false;
                }
                return true;

            case 2:
                if (!selectedDate || !selectedTime) {
                    alert(bookNowPublic.strings.selectDateTime || 'Please select date and time');
                    return false;
                }
                return true;

            case 3:
                const name = $('#customer_name').val();
                const email = $('#customer_email').val();
                
                if (!name || !email) {
                    alert(bookNowPublic.strings.fillFields || 'Please fill in all required fields');
                    return false;
                }
                
                if (!isValidEmail(email)) {
                    alert('Please enter a valid email address');
                    return false;
                }
                return true;

            default:
                return true;
        }
    }

    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function handleFormSubmit(e) {
        e.preventDefault();

        if (!validateStep(3)) {
            return;
        }

        const $form = $(this);
        const $submitBtn = $form.find('.booknow-submit');

        // Disable submit button
        $submitBtn.prop('disabled', true).text('Processing...');

        const formData = {
            action: 'booknow_create_booking',
            nonce: bookNowPublic.nonce,
            consultation_type_id: selectedTypeId,
            booking_date: selectedDate,
            booking_time: selectedTime,
            customer_name: $('#customer_name').val(),
            customer_email: $('#customer_email').val(),
            customer_phone: $('#customer_phone').val(),
            notes: $('#customer_notes').val()
        };

        $.ajax({
            url: bookNowPublic.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showConfirmation(response.data);
                } else {
                    alert(response.data.message || bookNowPublic.strings.error);
                    $submitBtn.prop('disabled', false).text('Complete Booking');
                }
            },
            error: function() {
                alert(bookNowPublic.strings.error || 'An error occurred. Please try again.');
                $submitBtn.prop('disabled', false).text('Complete Booking');
            }
        });
    }

    function showConfirmation(data) {
        // Check if payment is needed
        if (data.needs_payment && data.payment_intent && typeof showPaymentModal === 'function') {
            showPaymentModal(data);
            return;
        }

        // Hide all steps
        $('.booknow-form-step').hide();

        // Show confirmation
        const $confirmation = $('.booknow-confirmation');
        const message = 'Your booking reference is: <strong>' + data.reference + '</strong>';
        $confirmation.find('.confirmation-message').html(message);
        $confirmation.show();

        // Scroll to confirmation
        $('html, body').animate({
            scrollTop: $confirmation.offset().top - 100
        }, 500);
    }

})(jQuery);
