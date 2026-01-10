/**
 * Stripe Payment Integration
 *
 * @package BookNow
 * @since   1.4.0
 */

(function($) {
    'use strict';

    let stripe = null;
    let elements = null;
    let paymentElement = null;

    $(document).ready(function() {
        if (typeof bookNowStripe !== 'undefined' && bookNowStripe.publishableKey) {
            initStripe();
        }
    });

    function initStripe() {
        stripe = Stripe(bookNowStripe.publishableKey);
    }

    /**
     * Show payment modal
     *
     * @param {object} bookingData Booking data including payment intent
     */
    window.showPaymentModal = function(bookingData) {
        if (!bookingData.payment_intent || !bookingData.payment_intent.client_secret) {
            return;
        }

        // Create payment modal
        const modalHtml = `
            <div class="booknow-payment-modal">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <button type="button" class="modal-close">×</button>
                    <h3>Complete Payment</h3>
                    
                    <div class="payment-summary">
                        <p><strong>Booking Reference:</strong> ${bookingData.reference}</p>
                        <p><strong>Amount:</strong> $${bookingData.payment_intent.amount.toFixed(2)}</p>
                    </div>

                    <form id="payment-form">
                        <div id="payment-element"></div>
                        <div id="payment-errors" class="error-message"></div>
                        <button type="submit" id="submit-payment" class="button button-primary">
                            Pay Now
                        </button>
                    </form>

                    <div id="payment-success" style="display:none;">
                        <div class="success-icon">✓</div>
                        <h4>Payment Successful!</h4>
                        <p>Your booking is confirmed.</p>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $('.booknow-payment-modal').fadeIn(300);

        // Initialize Stripe Elements
        initPaymentElement(bookingData.payment_intent.client_secret);

        // Handle form submission
        $('#payment-form').on('submit', function(e) {
            e.preventDefault();
            handlePaymentSubmit(bookingData);
        });

        // Close modal
        $('.modal-close, .modal-overlay').on('click', closePaymentModal);
    };

    function initPaymentElement(clientSecret) {
        elements = stripe.elements({
            clientSecret: clientSecret
        });

        paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');
    }

    async function handlePaymentSubmit(bookingData) {
        const submitButton = $('#submit-payment');
        submitButton.prop('disabled', true).text('Processing...');

        const {error} = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: window.location.href,
                receipt_email: bookingData.booking.customer_email,
            },
            redirect: 'if_required'
        });

        if (error) {
            $('#payment-errors').text(error.message);
            submitButton.prop('disabled', false).text('Pay Now');
        } else {
            // Payment succeeded
            $('#payment-form').hide();
            $('#payment-success').show();
            
            setTimeout(function() {
                closePaymentModal();
                location.reload();
            }, 3000);
        }
    }

    function closePaymentModal() {
        $('.booknow-payment-modal').fadeOut(300, function() {
            $(this).remove();
        });
    }

})(jQuery);
