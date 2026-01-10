<?php
/**
 * List view template
 *
 * @package BookNow
 * @since   1.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get consultation type if specified
$type_id = !empty($atts['type']) ? absint($atts['type']) : 0;
$category_id = !empty($atts['category']) ? absint($atts['category']) : 0;
$days = !empty($atts['days']) ? absint($atts['days']) : 7;
?>

<div class="booknow-list-wrapper" data-type-id="<?php echo esc_attr($type_id); ?>" data-category-id="<?php echo esc_attr($category_id); ?>" data-days="<?php echo esc_attr($days); ?>">
    <?php wp_nonce_field('booknow_list', 'booknow_list_nonce'); ?>
    
    <!-- Type Selector (if no type specified) -->
    <?php if (!$type_id) : ?>
        <div class="booknow-list-type-selector">
            <label for="list-type-select"><?php esc_html_e('Select Consultation Type:', 'book-now-kre8iv'); ?></label>
            <select id="list-type-select" class="list-type-select">
                <option value=""><?php esc_html_e('-- Select Type --', 'book-now-kre8iv'); ?></option>
                <?php
                $args = array('status' => 'active');
                if ($category_id) {
                    $args['category_id'] = $category_id;
                }
                $types = Book_Now_Consultation_Type::get_all($args);
                foreach ($types as $type) :
                ?>
                    <option value="<?php echo esc_attr($type->id); ?>">
                        <?php echo esc_html($type->name); ?> - <?php echo esc_html(booknow_format_price($type->price)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <!-- Loading Indicator -->
    <div class="list-loading" style="display:none;">
        <span class="spinner is-active"></span>
        <p><?php esc_html_e('Loading available slots...', 'book-now-kre8iv'); ?></p>
    </div>

    <!-- List Container -->
    <div class="booknow-list-container" id="list-container">
        <p class="list-placeholder"><?php esc_html_e('Please select a consultation type to view available times.', 'book-now-kre8iv'); ?></p>
    </div>

    <!-- Booking Modal -->
    <div class="booknow-list-modal" style="display:none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button type="button" class="modal-close" aria-label="Close">×</button>
            
            <h3><?php esc_html_e('Complete Your Booking', 'book-now-kre8iv'); ?></h3>
            
            <div class="booking-summary">
                <p><strong><?php esc_html_e('Date:', 'book-now-kre8iv'); ?></strong> <span class="summary-date"></span></p>
                <p><strong><?php esc_html_e('Time:', 'book-now-kre8iv'); ?></strong> <span class="summary-time"></span></p>
                <p><strong><?php esc_html_e('Type:', 'book-now-kre8iv'); ?></strong> <span class="summary-type"></span></p>
            </div>

            <form id="list-booking-form" class="booking-form">
                <p>
                    <label for="list-customer-name"><?php esc_html_e('Name', 'book-now-kre8iv'); ?> *</label>
                    <input type="text" id="list-customer-name" name="customer_name" required>
                </p>

                <p>
                    <label for="list-customer-email"><?php esc_html_e('Email', 'book-now-kre8iv'); ?> *</label>
                    <input type="email" id="list-customer-email" name="customer_email" required>
                </p>

                <p>
                    <label for="list-customer-phone"><?php esc_html_e('Phone', 'book-now-kre8iv'); ?></label>
                    <input type="tel" id="list-customer-phone" name="customer_phone">
                </p>

                <p>
                    <label for="list-customer-notes"><?php esc_html_e('Notes', 'book-now-kre8iv'); ?></label>
                    <textarea id="list-customer-notes" name="notes" rows="3"></textarea>
                </p>

                <div class="modal-actions">
                    <button type="button" class="button button-secondary modal-cancel">
                        <?php esc_html_e('Cancel', 'book-now-kre8iv'); ?>
                    </button>
                    <button type="submit" class="button button-primary modal-submit">
                        <?php esc_html_e('Confirm Booking', 'book-now-kre8iv'); ?>
                    </button>
                </div>
            </form>

            <!-- Success Message -->
            <div class="booking-success" style="display:none;">
                <div class="success-icon">✓</div>
                <h4><?php esc_html_e('Booking Confirmed!', 'book-now-kre8iv'); ?></h4>
                <p class="success-reference"></p>
                <button type="button" class="button button-primary modal-close-success">
                    <?php esc_html_e('Close', 'book-now-kre8iv'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
