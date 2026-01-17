<?php
/**
 * Email Settings Page
 *
 * @package BookNow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Security check - verify user has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'book-now-kre8iv'));
}

// Handle form submission
if (isset($_POST['booknow_save_email_settings'])) {
    check_admin_referer('booknow_email_settings_nonce', 'nonce');

    $settings = array(
        'from_name' => sanitize_text_field($_POST['from_name']),
        'from_email' => sanitize_email($_POST['from_email']),
        'confirmation_enabled' => isset($_POST['confirmation_enabled']),
        'reminder_enabled' => isset($_POST['reminder_enabled']),
        'admin_notification_enabled' => isset($_POST['admin_notification_enabled']),
        'reminder_hours' => absint($_POST['reminder_hours']),
    );

    update_option('booknow_email_settings', $settings);
    
    echo '<div class="notice notice-success"><p>' . esc_html__('Email settings saved successfully.', 'book-now-kre8iv') . '</p></div>';
}

// Handle test email
if (isset($_POST['booknow_send_test_email'])) {
    check_admin_referer('booknow_test_email_nonce', 'test_nonce');
    
    $test_email = sanitize_email($_POST['test_email']);
    $email_handler = new Book_Now_Email();
    
    if ($email_handler->send_test_email($test_email)) {
        echo '<div class="notice notice-success"><p>' . esc_html__('Test email sent successfully!', 'book-now-kre8iv') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__('Failed to send test email. Please check your settings.', 'book-now-kre8iv') . '</p></div>';
    }
}

$settings = get_option('booknow_email_settings', array(
    'from_name' => get_bloginfo('name'),
    'from_email' => get_bloginfo('admin_email'),
    'confirmation_enabled' => true,
    'reminder_enabled' => true,
    'admin_notification_enabled' => true,
    'reminder_hours' => 24,
));
?>

<div class="wrap">
    <h1><?php _e('Email Settings', 'book-now-kre8iv'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('booknow_email_settings_nonce', 'nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="from_name"><?php _e('From Name', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="from_name" 
                           name="from_name" 
                           value="<?php echo esc_attr($settings['from_name']); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('The name that appears in the "From" field of emails.', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="from_email"><?php _e('From Email', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="email" 
                           id="from_email" 
                           name="from_email" 
                           value="<?php echo esc_attr($settings['from_email']); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('The email address that appears in the "From" field.', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Email Notifications', 'book-now-kre8iv'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" 
                                   name="confirmation_enabled" 
                                   value="1" 
                                   <?php checked(!empty($settings['confirmation_enabled'])); ?>>
                            <?php _e('Send confirmation emails to customers', 'book-now-kre8iv'); ?>
                        </label>
                        <br><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="reminder_enabled" 
                                   value="1" 
                                   <?php checked(!empty($settings['reminder_enabled'])); ?>>
                            <?php _e('Send reminder emails to customers', 'book-now-kre8iv'); ?>
                        </label>
                        <br><br>
                        
                        <label>
                            <input type="checkbox" 
                                   name="admin_notification_enabled" 
                                   value="1" 
                                   <?php checked(!empty($settings['admin_notification_enabled'])); ?>>
                            <?php _e('Send notifications to admin on new bookings', 'book-now-kre8iv'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="reminder_hours"><?php _e('Reminder Timing', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="reminder_hours" 
                           name="reminder_hours" 
                           value="<?php echo esc_attr($settings['reminder_hours']); ?>" 
                           min="1" 
                           max="168" 
                           class="small-text">
                    <span><?php _e('hours before appointment', 'book-now-kre8iv'); ?></span>
                    <p class="description"><?php _e('How many hours before the appointment should the reminder be sent?', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Email Settings', 'book-now-kre8iv'), 'primary', 'booknow_save_email_settings'); ?>
    </form>

    <hr>

    <h2><?php _e('Test Email', 'book-now-kre8iv'); ?></h2>
    <p><?php _e('Send a test email to verify your settings are working correctly.', 'book-now-kre8iv'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('booknow_test_email_nonce', 'test_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="test_email"><?php _e('Test Email Address', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="email" 
                           id="test_email" 
                           name="test_email" 
                           value="<?php echo esc_attr(get_option('admin_email')); ?>" 
                           class="regular-text" 
                           required>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Send Test Email', 'book-now-kre8iv'), 'secondary', 'booknow_send_test_email'); ?>
    </form>

    <hr>

    <h2><?php _e('Email Templates', 'book-now-kre8iv'); ?></h2>
    <p><?php _e('The following email templates are automatically sent:', 'book-now-kre8iv'); ?></p>

    <ul>
        <li><strong><?php _e('Booking Confirmation', 'book-now-kre8iv'); ?></strong> - <?php _e('Sent when a booking is confirmed', 'book-now-kre8iv'); ?></li>
        <li><strong><?php _e('Booking Reminder', 'book-now-kre8iv'); ?></strong> - <?php _e('Sent before the appointment', 'book-now-kre8iv'); ?></li>
        <li><strong><?php _e('Booking Cancellation', 'book-now-kre8iv'); ?></strong> - <?php _e('Sent when a booking is cancelled', 'book-now-kre8iv'); ?></li>
        <li><strong><?php _e('Admin Notification', 'book-now-kre8iv'); ?></strong> - <?php _e('Sent to admin on new bookings', 'book-now-kre8iv'); ?></li>
    </ul>

    <p class="description">
        <?php _e('Email templates use HTML formatting and include booking details, customer information, and branding from your site.', 'book-now-kre8iv'); ?>
    </p>
</div>

<style>
.form-table th {
    width: 200px;
}
</style>
