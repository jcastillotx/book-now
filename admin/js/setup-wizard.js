/**
 * Setup Wizard JavaScript
 *
 * @package BookNow
 * @since   1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Account type selection
        $('.booknow-account-type-option').on('click', function() {
            $('.booknow-account-type-option').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
        });

        // Payment settings toggle
        $('input[name="payment_required"]').on('change', function() {
            if ($(this).is(':checked')) {
                $('#stripe-settings').slideDown();
            } else {
                $('#stripe-settings').slideUp();
            }
        });

        // Availability toggle
        $('.availability-toggle').on('change', function() {
            var row = $(this).closest('tr');
            var timeInputs = row.find('.availability-time');
            
            if ($(this).is(':checked')) {
                timeInputs.prop('disabled', false);
            } else {
                timeInputs.prop('disabled', true);
            }
        });

        // Form validation
        $('.booknow-setup-form').on('submit', function(e) {
            var form = $(this);
            var requiredFields = form.find('[required]');
            var isValid = true;

            requiredFields.each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).css('border-color', '#dc3232');
                } else {
                    $(this).css('border-color', '#ddd');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
        });

        // Remove error styling on input
        $('input, select, textarea').on('focus', function() {
            $(this).css('border-color', '#ddd');
        });

        // Stripe key validation
        $('input[name="stripe_test_publishable_key"]').on('blur', function() {
            var value = $(this).val();
            if (value && !value.startsWith('pk_test_')) {
                $(this).css('border-color', '#dc3232');
                alert('Test publishable key should start with "pk_test_"');
            }
        });

        $('input[name="stripe_test_secret_key"]').on('blur', function() {
            var value = $(this).val();
            if (value && !value.startsWith('sk_test_')) {
                $(this).css('border-color', '#dc3232');
                alert('Test secret key should start with "sk_test_"');
            }
        });

        // Price validation
        $('input[name="consultation_price"]').on('input', function() {
            var value = parseFloat($(this).val());
            if (value < 0) {
                $(this).val(0);
            }
        });

        // Duration validation
        $('input[name="consultation_duration"]').on('input', function() {
            var value = parseInt($(this).val());
            if (value < 15) {
                $(this).val(15);
            }
        });

        // Smooth scroll to errors
        function scrollToError() {
            var firstError = $('.booknow-setup-form input[style*="border-color: rgb(220, 50, 50)"]').first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }
        }

        // Initialize
        if ($('input[name="payment_required"]').is(':checked')) {
            $('#stripe-settings').show();
        } else {
            $('#stripe-settings').hide();
        }
    });

})(jQuery);
