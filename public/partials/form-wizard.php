<?php
/**
 * Booking form wizard template
 *
 * @package BookNow
 * @since   1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get consultation types
$show_types = isset($atts['show_types']) && $atts['show_types'] === 'false' ? false : true;
$type_id = !empty($atts['type']) ? absint($atts['type']) : 0;

$types = array();
if ($show_types) {
    $args = array('status' => 'active');
    if (!empty($atts['category'])) {
        $args['category'] = absint($atts['category']);
    }
    $types = Book_Now_Consultation_Type::get_all($args);
}
?>

<div class="booknow-form-wrapper">
    <?php wp_nonce_field('booknow_booking', 'booknow_nonce'); ?>

    <!-- Step 1: Select Consultation Type -->
    <?php if ($show_types && !$type_id) : ?>
        <div class="booknow-form-step" data-step="1">
            <h3><?php esc_html_e('Select Consultation Type', 'book-now-kre8iv'); ?></h3>

            <?php if (!empty($types)) : ?>
                <div class="booknow-type-options">
                    <?php foreach ($types as $type) : ?>
                        <label class="booknow-type-option">
                            <input type="radio" name="consultation_type_id" value="<?php echo esc_attr($type->id); ?>" required>
                            <div class="type-option-content">
                                <strong><?php echo esc_html($type->name); ?></strong>
                                <span class="type-duration"><?php echo esc_html($type->duration); ?> <?php esc_html_e('mins', 'book-now-kre8iv'); ?></span>
                                <span class="type-price"><?php echo esc_html(booknow_format_price($type->price)); ?></span>
                                <?php if ($type->description) : ?>
                                    <p class="type-desc"><?php echo esc_html(wp_trim_words($type->description, 20)); ?></p>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="button booknow-next-step">
                    <?php esc_html_e('Next', 'book-now-kre8iv'); ?>
                </button>
            <?php else : ?>
                <p><?php esc_html_e('No consultation types available at this time.', 'book-now-kre8iv'); ?></p>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <input type="hidden" name="consultation_type_id" value="<?php echo esc_attr($type_id); ?>">
    <?php endif; ?>

    <!-- Step 2: Select Date & Time -->
    <div class="booknow-form-step" data-step="2" style="display:none;">
        <h3><?php esc_html_e('Select Date & Time', 'book-now-kre8iv'); ?></h3>

        <div class="booknow-datetime-selector">
            <label for="booking_date"><?php esc_html_e('Date', 'book-now-kre8iv'); ?></label>
            <input type="date" name="booking_date" id="booking_date" required min="<?php echo esc_attr(date('Y-m-d', strtotime('+24 hours'))); ?>">

            <div id="available-slots" class="booknow-time-slots" style="display:none;">
                <label><?php esc_html_e('Available Times', 'book-now-kre8iv'); ?></label>
                <div id="slots-container"></div>
            </div>
        </div>

        <div class="booknow-form-nav">
            <button type="button" class="button booknow-prev-step">
                <?php esc_html_e('Previous', 'book-now-kre8iv'); ?>
            </button>
            <button type="button" class="button booknow-next-step" disabled>
                <?php esc_html_e('Next', 'book-now-kre8iv'); ?>
            </button>
        </div>
    </div>

    <!-- Step 3: Customer Details -->
    <div class="booknow-form-step" data-step="3" style="display:none;">
        <h3><?php esc_html_e('Your Information', 'book-now-kre8iv'); ?></h3>

        <div class="booknow-form-fields">
            <p>
                <label for="customer_name"><?php esc_html_e('Name', 'book-now-kre8iv'); ?> *</label>
                <input type="text" name="customer_name" id="customer_name" required class="regular-text">
            </p>

            <p>
                <label for="customer_email"><?php esc_html_e('Email', 'book-now-kre8iv'); ?> *</label>
                <input type="email" name="customer_email" id="customer_email" required class="regular-text">
            </p>

            <p>
                <label for="customer_phone"><?php esc_html_e('Phone', 'book-now-kre8iv'); ?></label>
                <input type="tel" name="customer_phone" id="customer_phone" class="regular-text">
            </p>

            <p>
                <label for="customer_notes"><?php esc_html_e('Additional Notes', 'book-now-kre8iv'); ?></label>
                <textarea name="customer_notes" id="customer_notes" rows="4" class="large-text"></textarea>
            </p>
        </div>

        <div class="booknow-form-nav">
            <button type="button" class="button booknow-prev-step">
                <?php esc_html_e('Previous', 'book-now-kre8iv'); ?>
            </button>
            <button type="submit" class="button button-primary booknow-submit">
                <?php esc_html_e('Complete Booking', 'book-now-kre8iv'); ?>
            </button>
        </div>
    </div>

    <!-- Confirmation Message -->
    <div class="booknow-confirmation" style="display:none;">
        <div class="booknow-success-icon">✓</div>
        <h3><?php esc_html_e('Booking Confirmed!', 'book-now-kre8iv'); ?></h3>
        <p class="confirmation-message"></p>
        <p><?php esc_html_e('You will receive a confirmation email shortly.', 'book-now-kre8iv'); ?></p>
    </div>
</div>
