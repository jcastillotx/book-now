<?php
/**
 * Admin settings page with tabs
 *
 * @package BookNow
 * @since   1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booknow_settings_nonce'])) {
    if (wp_verify_nonce($_POST['booknow_settings_nonce'], 'booknow_save_settings_' . $current_tab)) {
        
        switch ($current_tab) {
            case 'general':
                $settings = array(
                    'business_name'       => sanitize_text_field($_POST['business_name'] ?? ''),
                    'timezone'            => sanitize_text_field($_POST['timezone'] ?? 'UTC'),
                    'currency'            => sanitize_text_field($_POST['currency'] ?? 'USD'),
                    'date_format'         => sanitize_text_field($_POST['date_format'] ?? 'F j, Y'),
                    'time_format'         => sanitize_text_field($_POST['time_format'] ?? 'g:i a'),
                    'slot_interval'       => absint($_POST['slot_interval'] ?? 30),
                    'min_booking_notice'  => absint($_POST['min_booking_notice'] ?? 24),
                    'max_booking_advance' => absint($_POST['max_booking_advance'] ?? 90),
                    'account_type'        => sanitize_text_field($_POST['account_type'] ?? 'single'),
                    'enable_team_members' => isset($_POST['enable_team_members']),
                );
                update_option('booknow_general_settings', $settings);
                break;

            case 'payment':
                // Get existing settings to preserve unchanged masked values
                $existing_settings = Book_Now_Encryption::get_payment_settings();

                // Helper to check if a masked value was submitted unchanged
                $get_key_value = function($field, $existing) {
                    $submitted = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
                    // If submitted value is all asterisks (masked), keep existing
                    if (preg_match('/^\*+.{0,4}$/', $submitted) && !empty($existing[$field])) {
                        return $existing[$field];
                    }
                    return $submitted;
                };

                $settings = array(
                    'stripe_mode'                 => sanitize_text_field($_POST['stripe_mode'] ?? 'test'),
                    'stripe_test_publishable_key' => sanitize_text_field($_POST['stripe_test_publishable_key'] ?? ''),
                    'stripe_test_secret_key'      => $get_key_value('stripe_test_secret_key', $existing_settings),
                    'stripe_live_publishable_key' => sanitize_text_field($_POST['stripe_live_publishable_key'] ?? ''),
                    'stripe_live_secret_key'      => $get_key_value('stripe_live_secret_key', $existing_settings),
                    'payment_required'            => isset($_POST['payment_required']),
                    'allow_deposit'               => isset($_POST['allow_deposit']),
                );
                // Use encryption class to save with automatic encryption
                Book_Now_Encryption::save_payment_settings($settings);
                break;

            case 'email':
                $settings = array(
                    'from_name'              => sanitize_text_field($_POST['from_name'] ?? ''),
                    'from_email'             => sanitize_email($_POST['from_email'] ?? ''),
                    'admin_email'            => sanitize_email($_POST['admin_email'] ?? ''),
                    'send_confirmation'      => isset($_POST['send_confirmation']),
                    'send_reminder'          => isset($_POST['send_reminder']),
                    'reminder_hours'         => absint($_POST['reminder_hours'] ?? 24),
                    'send_admin_notification' => isset($_POST['send_admin_notification']),
                );
                update_option('booknow_email_settings', $settings);
                break;

            case 'integration':
                // Get existing settings to preserve unchanged masked values
                $existing_integration = Book_Now_Encryption::get_integration_settings();

                // Helper to check if a masked value was submitted unchanged
                $get_secret_value = function($field, $existing) {
                    $submitted = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
                    // If submitted value is all asterisks (masked), keep existing
                    if (preg_match('/^\*+.{0,4}$/', $submitted) && !empty($existing[$field])) {
                        return $existing[$field];
                    }
                    return $submitted;
                };

                $settings = array(
                    'google_calendar_enabled'    => isset($_POST['google_calendar_enabled']),
                    'google_client_id'           => sanitize_text_field($_POST['google_client_id'] ?? ''),
                    'google_client_secret'       => $get_secret_value('google_client_secret', $existing_integration),
                    'google_calendar_id'         => sanitize_text_field($_POST['google_calendar_id'] ?? ''),
                    'microsoft_calendar_enabled' => isset($_POST['microsoft_calendar_enabled']),
                    'microsoft_client_id'        => sanitize_text_field($_POST['microsoft_client_id'] ?? ''),
                    'microsoft_client_secret'    => $get_secret_value('microsoft_client_secret', $existing_integration),
                    'microsoft_tenant_id'        => sanitize_text_field($_POST['microsoft_tenant_id'] ?? ''),
                );
                // Use encryption class to save with automatic encryption
                Book_Now_Encryption::save_integration_settings($settings);
                break;
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'book-now-kre8iv') . '</p></div>';
    }
}

// Get settings - use encryption class for settings with sensitive data
$general_settings = get_option('booknow_general_settings', array());
$payment_settings = Book_Now_Encryption::get_payment_settings();
$email_settings = get_option('booknow_email_settings', array());
$integration_settings = Book_Now_Encryption::get_integration_settings();

// Prepare masked values for display in forms
$masked_stripe_test_secret = Book_Now_Encryption::mask($payment_settings['stripe_test_secret_key'] ?? '');
$masked_stripe_live_secret = Book_Now_Encryption::mask($payment_settings['stripe_live_secret_key'] ?? '');
$masked_google_secret = Book_Now_Encryption::mask($integration_settings['google_client_secret'] ?? '');
$masked_microsoft_secret = Book_Now_Encryption::mask($integration_settings['microsoft_client_secret'] ?? '');
?>

<div class="wrap">
    <h1><?php esc_html_e('Book Now Settings', 'book-now-kre8iv'); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="?page=book-now-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('General', 'book-now-kre8iv'); ?>
        </a>
        <a href="?page=book-now-settings&tab=payment" class="nav-tab <?php echo $current_tab === 'payment' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Payment', 'book-now-kre8iv'); ?>
        </a>
        <a href="?page=book-now-settings&tab=email" class="nav-tab <?php echo $current_tab === 'email' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Email', 'book-now-kre8iv'); ?>
        </a>
        <a href="?page=book-now-settings&tab=integration" class="nav-tab <?php echo $current_tab === 'integration' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Integrations', 'book-now-kre8iv'); ?>
        </a>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field('booknow_save_settings_' . $current_tab, 'booknow_settings_nonce'); ?>

        <?php if ($current_tab === 'general') : ?>
            <h2><?php esc_html_e('General Settings', 'book-now-kre8iv'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="business_name"><?php esc_html_e('Business Name', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="business_name" id="business_name" value="<?php echo esc_attr($general_settings['business_name'] ?? get_bloginfo('name')); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Your business or organization name', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="account_type"><?php esc_html_e('Account Type', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <select name="account_type" id="account_type">
                            <option value="single" <?php selected($general_settings['account_type'] ?? 'single', 'single'); ?>><?php esc_html_e('Single Person', 'book-now-kre8iv'); ?></option>
                            <option value="agency" <?php selected($general_settings['account_type'] ?? 'single', 'agency'); ?>><?php esc_html_e('Agency / Team', 'book-now-kre8iv'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Choose single person for solo consultants or agency for multiple team members', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="timezone"><?php esc_html_e('Timezone', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <select name="timezone" id="timezone" class="regular-text">
                            <?php
                            $timezones = timezone_identifiers_list();
                            $current = $general_settings['timezone'] ?? get_option('timezone_string', 'UTC');
                            foreach ($timezones as $tz) {
                                printf(
                                    '<option value="%s"%s>%s</option>',
                                    esc_attr($tz),
                                    selected($current, $tz, false),
                                    esc_html($tz)
                                );
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('All booking times will be displayed in this timezone', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="currency"><?php esc_html_e('Currency', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <select name="currency" id="currency">
                            <?php
                            $currencies = array(
                                'USD' => 'US Dollar ($)',
                                'EUR' => 'Euro (€)',
                                'GBP' => 'British Pound (£)',
                                'CAD' => 'Canadian Dollar (C$)',
                                'AUD' => 'Australian Dollar (A$)',
                                'JPY' => 'Japanese Yen (¥)',
                                'INR' => 'Indian Rupee (₹)',
                                'MXN' => 'Mexican Peso ($)',
                            );
                            $current = $general_settings['currency'] ?? 'USD';
                            foreach ($currencies as $code => $label) {
                                printf(
                                    '<option value="%s"%s>%s</option>',
                                    esc_attr($code),
                                    selected($current, $code, false),
                                    esc_html($label)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="slot_interval"><?php esc_html_e('Slot Interval (minutes)', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="number" name="slot_interval" id="slot_interval" value="<?php echo esc_attr($general_settings['slot_interval'] ?? 30); ?>" min="5" max="120" class="small-text">
                        <p class="description"><?php esc_html_e('Time between available booking slots', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="min_booking_notice"><?php esc_html_e('Minimum Booking Notice (hours)', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="number" name="min_booking_notice" id="min_booking_notice" value="<?php echo esc_attr($general_settings['min_booking_notice'] ?? 24); ?>" min="1" class="small-text">
                        <p class="description"><?php esc_html_e('How far in advance bookings must be made', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="max_booking_advance"><?php esc_html_e('Maximum Booking Advance (days)', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="number" name="max_booking_advance" id="max_booking_advance" value="<?php echo esc_attr($general_settings['max_booking_advance'] ?? 90); ?>" min="1" class="small-text">
                        <p class="description"><?php esc_html_e('How far in the future customers can book', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
            </table>

        <?php elseif ($current_tab === 'payment') : ?>
            <h2><?php esc_html_e('Payment Settings', 'book-now-kre8iv'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Payment Required', 'book-now-kre8iv'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="payment_required" value="1" <?php checked(!empty($payment_settings['payment_required'])); ?>>
                            <?php esc_html_e('Require payment for bookings', 'book-now-kre8iv'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Uncheck for free consultations', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="stripe_mode"><?php esc_html_e('Stripe Mode', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <select name="stripe_mode" id="stripe_mode">
                            <option value="test" <?php selected($payment_settings['stripe_mode'] ?? 'test', 'test'); ?>><?php esc_html_e('Test Mode', 'book-now-kre8iv'); ?></option>
                            <option value="live" <?php selected($payment_settings['stripe_mode'] ?? 'test', 'live'); ?>><?php esc_html_e('Live Mode', 'book-now-kre8iv'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Use test mode for development', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th colspan="2"><h3><?php esc_html_e('Test Mode Keys', 'book-now-kre8iv'); ?></h3></th>
                </tr>
                <tr>
                    <th><label for="stripe_test_publishable_key"><?php esc_html_e('Test Publishable Key', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="stripe_test_publishable_key" id="stripe_test_publishable_key" value="<?php echo esc_attr($payment_settings['stripe_test_publishable_key'] ?? ''); ?>" class="regular-text code" placeholder="pk_test_...">
                    </td>
                </tr>
                <tr>
                    <th><label for="stripe_test_secret_key"><?php esc_html_e('Test Secret Key', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="password" name="stripe_test_secret_key" id="stripe_test_secret_key" value="<?php echo esc_attr($masked_stripe_test_secret); ?>" class="regular-text code" placeholder="sk_test_..." autocomplete="new-password">
                        <?php if (!empty($masked_stripe_test_secret)) : ?>
                            <p class="description"><?php esc_html_e('Key is stored encrypted. Enter a new key to replace it.', 'book-now-kre8iv'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th colspan="2"><h3><?php esc_html_e('Live Mode Keys', 'book-now-kre8iv'); ?></h3></th>
                </tr>
                <tr>
                    <th><label for="stripe_live_publishable_key"><?php esc_html_e('Live Publishable Key', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="stripe_live_publishable_key" id="stripe_live_publishable_key" value="<?php echo esc_attr($payment_settings['stripe_live_publishable_key'] ?? ''); ?>" class="regular-text code" placeholder="pk_live_...">
                    </td>
                </tr>
                <tr>
                    <th><label for="stripe_live_secret_key"><?php esc_html_e('Live Secret Key', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="password" name="stripe_live_secret_key" id="stripe_live_secret_key" value="<?php echo esc_attr($masked_stripe_live_secret); ?>" class="regular-text code" placeholder="sk_live_..." autocomplete="new-password">
                        <?php if (!empty($masked_stripe_live_secret)) : ?>
                            <p class="description"><?php esc_html_e('Key is stored encrypted. Enter a new key to replace it.', 'book-now-kre8iv'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Deposit Payments', 'book-now-kre8iv'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="allow_deposit" value="1" <?php checked(!empty($payment_settings['allow_deposit'])); ?>>
                            <?php esc_html_e('Allow deposit payments', 'book-now-kre8iv'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Enable partial payment options for consultation types', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
            </table>

        <?php elseif ($current_tab === 'email') : ?>
            <h2><?php esc_html_e('Email Settings', 'book-now-kre8iv'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="from_name"><?php esc_html_e('From Name', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="from_name" id="from_name" value="<?php echo esc_attr($email_settings['from_name'] ?? get_bloginfo('name')); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Name shown in outgoing emails', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="from_email"><?php esc_html_e('From Email', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="email" name="from_email" id="from_email" value="<?php echo esc_attr($email_settings['from_email'] ?? get_option('admin_email')); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Email address for outgoing emails', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="admin_email"><?php esc_html_e('Admin Email', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="email" name="admin_email" id="admin_email" value="<?php echo esc_attr($email_settings['admin_email'] ?? get_option('admin_email')); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Email address for admin notifications', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Customer Notifications', 'book-now-kre8iv'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="send_confirmation" value="1" <?php checked(!empty($email_settings['send_confirmation']) || !isset($email_settings['send_confirmation'])); ?>>
                            <?php esc_html_e('Send booking confirmation emails', 'book-now-kre8iv'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="send_reminder" value="1" <?php checked(!empty($email_settings['send_reminder'])); ?>>
                            <?php esc_html_e('Send reminder emails', 'book-now-kre8iv'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="reminder_hours"><?php esc_html_e('Reminder Time (hours before)', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="number" name="reminder_hours" id="reminder_hours" value="<?php echo esc_attr($email_settings['reminder_hours'] ?? 24); ?>" min="1" max="168" class="small-text">
                        <p class="description"><?php esc_html_e('How many hours before the appointment to send reminder', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Admin Notifications', 'book-now-kre8iv'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="send_admin_notification" value="1" <?php checked(!empty($email_settings['send_admin_notification']) || !isset($email_settings['send_admin_notification'])); ?>>
                            <?php esc_html_e('Notify admin of new bookings', 'book-now-kre8iv'); ?>
                        </label>
                    </td>
                </tr>
            </table>

        <?php elseif ($current_tab === 'integration') : ?>
            <h2><?php esc_html_e('Calendar Integrations', 'book-now-kre8iv'); ?></h2>
            
            <h3><?php esc_html_e('Google Calendar', 'book-now-kre8iv'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable Google Calendar', 'book-now-kre8iv'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="google_calendar_enabled" value="1" <?php checked(!empty($integration_settings['google_calendar_enabled'])); ?>>
                            <?php esc_html_e('Sync bookings with Google Calendar', 'book-now-kre8iv'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="google_client_id"><?php esc_html_e('Client ID', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="google_client_id" id="google_client_id" value="<?php echo esc_attr($integration_settings['google_client_id'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="google_client_secret"><?php esc_html_e('Client Secret', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="password" name="google_client_secret" id="google_client_secret" value="<?php echo esc_attr($masked_google_secret); ?>" class="regular-text" autocomplete="new-password">
                        <?php if (!empty($masked_google_secret)) : ?>
                            <p class="description"><?php esc_html_e('Secret is stored encrypted. Enter a new secret to replace it.', 'book-now-kre8iv'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="google_calendar_id"><?php esc_html_e('Calendar ID', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="google_calendar_id" id="google_calendar_id" value="<?php echo esc_attr($integration_settings['google_calendar_id'] ?? ''); ?>" class="regular-text" placeholder="primary">
                        <p class="description"><?php esc_html_e('Usually "primary" for your main calendar', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Microsoft Calendar', 'book-now-kre8iv'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Enable Microsoft Calendar', 'book-now-kre8iv'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="microsoft_calendar_enabled" value="1" <?php checked(!empty($integration_settings['microsoft_calendar_enabled'])); ?>>
                            <?php esc_html_e('Sync bookings with Microsoft 365/Outlook', 'book-now-kre8iv'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="microsoft_client_id"><?php esc_html_e('Client ID', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="microsoft_client_id" id="microsoft_client_id" value="<?php echo esc_attr($integration_settings['microsoft_client_id'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="microsoft_client_secret"><?php esc_html_e('Client Secret', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="password" name="microsoft_client_secret" id="microsoft_client_secret" value="<?php echo esc_attr($masked_microsoft_secret); ?>" class="regular-text" autocomplete="new-password">
                        <?php if (!empty($masked_microsoft_secret)) : ?>
                            <p class="description"><?php esc_html_e('Secret is stored encrypted. Enter a new secret to replace it.', 'book-now-kre8iv'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="microsoft_tenant_id"><?php esc_html_e('Tenant ID', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="microsoft_tenant_id" id="microsoft_tenant_id" value="<?php echo esc_attr($integration_settings['microsoft_tenant_id'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Your Azure AD tenant ID', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'book-now-kre8iv'); ?></button>
        </p>
    </form>
</div>
