<?php
/**
 * Calendar view template
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
?>

<div class="booknow-calendar-wrapper" data-type-id="<?php echo esc_attr($type_id); ?>" data-category-id="<?php echo esc_attr($category_id); ?>">
    <?php wp_nonce_field('booknow_calendar', 'booknow_calendar_nonce'); ?>
    
    <!-- Calendar Header -->
    <div class="booknow-calendar-header">
        <button type="button" class="calendar-nav calendar-prev" aria-label="Previous Month">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>
        
        <h3 class="calendar-month-year"></h3>
        
        <button type="button" class="calendar-nav calendar-next" aria-label="Next Month">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>
    </div>

    <!-- Type Selector (if no type specified) -->
    <?php if (!$type_id) : ?>
        <div class="booknow-calendar-type-selector">
            <label for="calendar-type-select"><?php esc_html_e('Select Consultation Type:', 'book-now-kre8iv'); ?></label>
            <select id="calendar-type-select" class="calendar-type-select">
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

    <!-- Calendar Grid -->
    <div class="booknow-calendar-grid">
        <div class="calendar-weekdays">
            <div class="calendar-weekday"><?php esc_html_e('Sun', 'book-now-kre8iv'); ?></div>
            <div class="calendar-weekday"><?php esc_html_e('Mon', 'book-now-kre8iv'); ?></div>
            <div class="calendar-weekday"><?php esc_html_e('Tue', 'book-now-kre8iv'); ?></div>
            <div class="calendar-weekday"><?php esc_html_e('Wed', 'book-now-kre8iv'); ?></div>
            <div class="calendar-weekday"><?php esc_html_e('Thu', 'book-now-kre8iv'); ?></div>
            <div class="calendar-weekday"><?php esc_html_e('Fri', 'book-now-kre8iv'); ?></div>
            <div class="calendar-weekday"><?php esc_html_e('Sat', 'book-now-kre8iv'); ?></div>
        </div>
        
        <div class="calendar-days" id="calendar-days-container">
            <!-- Days will be populated by JavaScript -->
        </div>
    </div>

    <!-- Loading Indicator -->
    <div class="calendar-loading" style="display:none;">
        <span class="spinner is-active"></span>
        <p><?php esc_html_e('Loading availability...', 'book-now-kre8iv'); ?></p>
    </div>

    <!-- Time Slots Panel -->
    <div class="booknow-timeslots-panel" style="display:none;">
        <div class="timeslots-header">
            <h4><?php esc_html_e('Available Times', 'book-now-kre8iv'); ?></h4>
            <span class="selected-date"></span>
            <button type="button" class="close-timeslots" aria-label="Close">×</button>
        </div>
        
        <div class="timeslots-container" id="timeslots-container">
            <!-- Time slots will be populated by JavaScript -->
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="booknow-booking-modal" style="display:none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button type="button" class="modal-close" aria-label="Close">×</button>
            
            <h3><?php esc_html_e('Complete Your Booking', 'book-now-kre8iv'); ?></h3>
            
            <div class="booking-summary">
                <p><strong><?php esc_html_e('Date:', 'book-now-kre8iv'); ?></strong> <span class="summary-date"></span></p>
                <p><strong><?php esc_html_e('Time:', 'book-now-kre8iv'); ?></strong> <span class="summary-time"></span></p>
                <p><strong><?php esc_html_e('Type:', 'book-now-kre8iv'); ?></strong> <span class="summary-type"></span></p>
            </div>

            <form id="calendar-booking-form" class="booking-form">
                <p>
                    <label for="modal-customer-name"><?php esc_html_e('Name', 'book-now-kre8iv'); ?> *</label>
                    <input type="text" id="modal-customer-name" name="customer_name" required>
                </p>

                <p>
                    <label for="modal-customer-email"><?php esc_html_e('Email', 'book-now-kre8iv'); ?> *</label>
                    <input type="email" id="modal-customer-email" name="customer_email" required>
                </p>

                <p>
                    <label for="modal-customer-phone"><?php esc_html_e('Phone', 'book-now-kre8iv'); ?></label>
                    <input type="tel" id="modal-customer-phone" name="customer_phone">
                </p>

                <p>
                    <label for="modal-customer-notes"><?php esc_html_e('Notes', 'book-now-kre8iv'); ?></label>
                    <textarea id="modal-customer-notes" name="notes" rows="3"></textarea>
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
