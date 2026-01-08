/**
 * Book Now Public JavaScript
 *
 * @package BookNow
 * @since   1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        let currentStep = 1;
        let selectedType = null;
        let selectedDate = null;
        let selectedTime = null;

        // Show specific step
        function showStep(step) {
            $('.booknow-form-step').hide();
            $('.booknow-form-step[data-step="' + step + '"]').show();
            currentStep = step;
        }

        // Next step button
        $('.booknow-next-step').on('click', function() {
            if (currentStep === 1) {
                selectedType = $('input[name="consultation_type_id"]:checked').val();
                if (!selectedType) {
                    alert(bookNowPublic.strings.selectType);
                    return;
                }
                showStep(2);
            } else if (currentStep === 2) {
                if (!selectedDate || !selectedTime) {
                    alert(bookNowPublic.strings.selectDateTime);
                    return;
                }
                showStep(3);
            }
        });

        // Previous step button
        $('.booknow-prev-step').on('click', function() {
            showStep(currentStep - 1);
        });

        // Handle date selection
        $('#booking_date').on('change', function() {
            selectedDate = $(this).val();
            if (!selectedDate) {
                return;
            }

            // Get selected consultation type
            if (!selectedType) {
                selectedType = $('input[name="consultation_type_id"]').val();
            }

            if (!selectedType) {
                alert(bookNowPublic.strings.selectType);
                return;
            }

            // Load available time slots
            loadAvailableSlots(selectedType, selectedDate);
        });

        // Load available time slots via AJAX
        function loadAvailableSlots(typeId, date) {
            const slotsContainer = $('#slots-container');
            const availableSlots = $('#available-slots');

            // Show loading
            slotsContainer.html('<p>' + bookNowPublic.strings.loading + '</p>');
            availableSlots.show();

            $.ajax({
                url: bookNowPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'booknow_get_availability',
                    nonce: bookNowPublic.nonce,
                    consultation_type_id: typeId,
                    date: date
                },
                success: function(response) {
                    if (response.success) {
                        renderTimeSlots(response.data.slots);
                    } else {
                        slotsContainer.html('<p>' + (response.data.message || bookNowPublic.strings.error) + '</p>');
                    }
                },
                error: function() {
                    slotsContainer.html('<p>' + bookNowPublic.strings.error + '</p>');
                }
            });
        }

        // Render time slots
        function renderTimeSlots(slots) {
            const slotsContainer = $('#slots-container');

            if (!slots || slots.length === 0) {
                slotsContainer.html('<p>No available time slots for this date.</p>');
                return;
            }

            let html = '';
            slots.forEach(function(slot) {
                html += '<div class="time-slot" data-time="' + slot.time + '">' + slot.display + '</div>';
            });

            slotsContainer.html(html);

            // Enable next button when time slot is selected
            $('.time-slot').on('click', function() {
                $('.time-slot').removeClass('selected');
                $(this).addClass('selected');
                selectedTime = $(this).data('time');
                $('.booknow-form-step[data-step="2"] .booknow-next-step').prop('disabled', false);
            });
        }

        // Handle form submission
        $('.booknow-form-wrapper').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('.booknow-submit');

            // Validate required fields
            const name = $('#customer_name').val();
            const email = $('#customer_email').val();

            if (!name || !email) {
                alert(bookNowPublic.strings.fillFields);
                return;
            }

            // Disable submit button
            submitBtn.prop('disabled', true).text('Processing...');

            // Prepare form data
            const formData = {
                action: 'booknow_create_booking',
                nonce: bookNowPublic.nonce,
                consultation_type_id: selectedType || $('input[name="consultation_type_id"]').val(),
                booking_date: selectedDate,
                booking_time: selectedTime,
                customer_name: name,
                customer_email: email,
                customer_phone: $('#customer_phone').val(),
                customer_notes: $('#customer_notes').val(),
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
            };

            // Submit booking
            $.ajax({
                url: bookNowPublic.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Show confirmation
                        $('.booknow-form-step').hide();
                        $('.booknow-confirmation').show();
                        $('.confirmation-message').html(
                            'Your booking reference number is <strong>' + response.data.reference_number + '</strong>'
                        );

                        // If payment required, redirect to payment
                        if (response.data.payment_required) {
                            // Payment handling will be implemented in Phase 4
                        }
                    } else {
                        alert(response.data.message || bookNowPublic.strings.error);
                        submitBtn.prop('disabled', false).text('Complete Booking');
                    }
                },
                error: function() {
                    alert(bookNowPublic.strings.error);
                    submitBtn.prop('disabled', false).text('Complete Booking');
                }
            });
        });

        // Handle "Book Now" button clicks on consultation type cards
        $('.booknow-select-type').on('click', function(e) {
            e.preventDefault();
            const typeId = $(this).data('type-id');

            // Store selected type and redirect to booking page or trigger form
            // This is a simplified version - in a full implementation,
            // you might redirect to a booking page with the type ID as a parameter
            console.log('Selected consultation type:', typeId);
        });
    });

})(jQuery);
