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

// Security check - verify user has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'book-now-kre8iv'));
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

// Handle OAuth callbacks and disconnections for calendar integrations
$oauth_message = '';
$oauth_error = '';

if ($current_tab === 'integration') {
    // Handle Google OAuth callback (google_oauth=callback from redirect URI)
    if (isset($_GET['code']) && isset($_GET['google_oauth']) && $_GET['google_oauth'] === 'callback') {
        // Load dependencies required by calendar classes
        if (!class_exists('Book_Now_Encryption')) {
            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-encryption.php';
        }
        if (!class_exists('Book_Now_Logger')) {
            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-logger.php';
        }
        if (!class_exists('Book_Now_Google_Calendar')) {
            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-google-calendar.php';
        }
        $google_calendar = new Book_Now_Google_Calendar();
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        $result = $google_calendar->handle_oauth_callback(sanitize_text_field(wp_unslash($_GET['code'])), $state);

        if (is_wp_error($result)) {
            $oauth_error = $result->get_error_message();
        } else {
            // Redirect to clean URL to prevent resubmission
            wp_safe_redirect(admin_url('admin.php?page=book-now-settings&tab=integration&google_connected=1'));
            exit;
        }
    }

    // Handle Microsoft OAuth callback (microsoft_oauth=callback from redirect URI)
    if (isset($_GET['code']) && isset($_GET['microsoft_oauth']) && $_GET['microsoft_oauth'] === 'callback') {
        // Load dependencies required by calendar classes
        if (!class_exists('Book_Now_Encryption')) {
            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-encryption.php';
        }
        if (!class_exists('Book_Now_Logger')) {
            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-logger.php';
        }
        if (!class_exists('Book_Now_Microsoft_Calendar')) {
            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-microsoft-calendar.php';
        }
        $microsoft_calendar = new Book_Now_Microsoft_Calendar();
        $result = $microsoft_calendar->handle_oauth_callback(sanitize_text_field(wp_unslash($_GET['code'])));

        if (is_wp_error($result)) {
            $oauth_error = $result->get_error_message();
        } else {
            // Redirect to clean URL to prevent resubmission
            wp_safe_redirect(admin_url('admin.php?page=book-now-settings&tab=integration&microsoft_connected=1'));
            exit;
        }
    }

    // Handle Google disconnect using the disconnect() method
    if (isset($_GET['disconnect_google']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'booknow_disconnect_google')) {
            // Load dependencies required by calendar classes
            if (!class_exists('Book_Now_Encryption')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-encryption.php';
            }
            if (!class_exists('Book_Now_Logger')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-logger.php';
            }
            if (!class_exists('Book_Now_Google_Calendar')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-google-calendar.php';
            }
            $google_calendar = new Book_Now_Google_Calendar();
            $result = $google_calendar->disconnect();

            if (is_wp_error($result)) {
                $oauth_error = $result->get_error_message();
            } else {
                wp_safe_redirect(admin_url('admin.php?page=book-now-settings&tab=integration&google_disconnected=1'));
                exit;
            }
        }
    }

    // Handle Microsoft disconnect
    if (isset($_GET['disconnect_microsoft']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'booknow_disconnect_microsoft')) {
            // Load dependencies required by calendar classes
            if (!class_exists('Book_Now_Encryption')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-encryption.php';
            }
            if (!class_exists('Book_Now_Logger')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-logger.php';
            }
            if (!class_exists('Book_Now_Microsoft_Calendar')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-microsoft-calendar.php';
            }
            $microsoft_calendar = new Book_Now_Microsoft_Calendar();
            // Use disconnect method if available, otherwise fall back to manual cleanup
            if (method_exists($microsoft_calendar, 'disconnect')) {
                $result = $microsoft_calendar->disconnect();
            } else {
                $calendar_settings = get_option('booknow_calendar_settings', array());
                unset($calendar_settings['microsoft_access_token']);
                unset($calendar_settings['microsoft_refresh_token']);
                unset($calendar_settings['microsoft_token_expires']);
                update_option('booknow_calendar_settings', $calendar_settings);
            }
            wp_safe_redirect(admin_url('admin.php?page=book-now-settings&tab=integration&microsoft_disconnected=1'));
            exit;
        }
    }

    // Show success/error messages from redirects
    if (isset($_GET['google_connected'])) {
        $oauth_message = __('Google Calendar connected successfully.', 'book-now-kre8iv');
    }
    if (isset($_GET['microsoft_connected'])) {
        $oauth_message = __('Microsoft 365 Calendar connected successfully.', 'book-now-kre8iv');
    }
    if (isset($_GET['google_disconnected'])) {
        $oauth_message = __('Google Calendar disconnected.', 'book-now-kre8iv');
    }
    if (isset($_GET['microsoft_disconnected'])) {
        $oauth_message = __('Microsoft 365 Calendar disconnected.', 'book-now-kre8iv');
    }

    // Check for OAuth messages stored in transients (set by the calendar classes)
    $microsoft_transient_success = get_transient('booknow_microsoft_oauth_success');
    $microsoft_transient_error = get_transient('booknow_microsoft_oauth_error');

    if ($microsoft_transient_success) {
        $oauth_message = $microsoft_transient_success;
        delete_transient('booknow_microsoft_oauth_success');
    }
    if ($microsoft_transient_error) {
        $oauth_error = $microsoft_transient_error;
        delete_transient('booknow_microsoft_oauth_error');
    }
}

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
        <a href="?page=book-now-settings&tab=diagnostics" class="nav-tab <?php echo $current_tab === 'diagnostics' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Diagnostics', 'book-now-kre8iv'); ?>
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
            <?php
            // Initialize calendar classes and get connection status
            if (!class_exists('Book_Now_Google_Calendar')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-google-calendar.php';
            }
            if (!class_exists('Book_Now_Microsoft_Calendar')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-microsoft-calendar.php';
            }

            $google_calendar = new Book_Now_Google_Calendar();
            $microsoft_calendar = new Book_Now_Microsoft_Calendar();

            // Get Google Calendar connection status using new method
            $google_connection_status = $google_calendar->get_connection_status();
            $google_is_configured = $google_connection_status['configured'];
            $google_is_connected = $google_connection_status['connected'];
            $google_auth_url = $google_is_configured ? $google_calendar->get_auth_url() : false;

            // Build the redirect URI for display in setup instructions
            $google_redirect_uri = $google_calendar->get_redirect_uri();

            $microsoft_is_configured = $microsoft_calendar->is_configured();
            $microsoft_is_connected = $microsoft_calendar->is_connected(); // Use is_connected() which handles token refresh
            $microsoft_auth_url = $microsoft_is_configured ? $microsoft_calendar->get_auth_url() : false;
            $microsoft_connection_status = $microsoft_calendar->get_connection_status(); // Get detailed status
            $microsoft_connection_info = $microsoft_is_connected ? $microsoft_calendar->test_connection() : null;
            ?>

            <h2><?php esc_html_e('Calendar Integrations', 'book-now-kre8iv'); ?></h2>

            <?php if (!empty($oauth_message)) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($oauth_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($oauth_error)) : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html($oauth_error); ?></p>
                </div>
            <?php endif; ?>

            <p class="description" style="margin-bottom: 20px;">
                <?php esc_html_e('Connect your calendar accounts to sync bookings automatically. New bookings will be added to your calendar and busy times will be checked for conflicts.', 'book-now-kre8iv'); ?>
            </p>

            <!-- Calendar Connection Cards -->
            <div class="booknow-calendar-cards">

                <!-- Google Calendar Connection Card -->
                <div class="booknow-calendar-card <?php echo $google_is_connected ? 'connected' : ''; ?>">
                    <div class="booknow-calendar-card-header">
                        <span class="dashicons dashicons-google" style="color: #4285f4; font-size: 24px;"></span>
                        <h3><?php esc_html_e('Google Calendar', 'book-now-kre8iv'); ?></h3>
                        <span class="booknow-connection-status <?php echo $google_is_connected ? 'status-connected' : 'status-disconnected'; ?>">
                            <?php echo $google_is_connected ? esc_html__('Connected', 'book-now-kre8iv') : esc_html__('Not Connected', 'book-now-kre8iv'); ?>
                        </span>
                    </div>
                    <div class="booknow-calendar-card-body">
                        <?php if ($google_is_connected) : ?>
                            <div class="booknow-connection-details">
                                <?php if (!empty($google_connection_status['email'])) : ?>
                                <p>
                                    <span class="dashicons dashicons-email"></span>
                                    <strong><?php esc_html_e('Account:', 'book-now-kre8iv'); ?></strong>
                                    <?php echo esc_html($google_connection_status['email']); ?>
                                </p>
                                <?php endif; ?>
                                <p>
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <strong><?php esc_html_e('Calendar:', 'book-now-kre8iv'); ?></strong>
                                    <?php echo esc_html($google_connection_status['calendar_name'] ?: __('Primary Calendar', 'book-now-kre8iv')); ?>
                                </p>
                                <p>
                                    <span class="dashicons dashicons-admin-generic"></span>
                                    <strong><?php esc_html_e('Calendar ID:', 'book-now-kre8iv'); ?></strong>
                                    <?php echo esc_html($google_connection_status['calendar_id'] ?: 'primary'); ?>
                                </p>
                                <?php if (!empty($google_connection_status['connected_at'])) : ?>
                                <p>
                                    <span class="dashicons dashicons-clock"></span>
                                    <strong><?php esc_html_e('Connected:', 'book-now-kre8iv'); ?></strong>
                                    <?php
                                    $connected_time = strtotime($google_connection_status['connected_at']);
                                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $connected_time));
                                    ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="booknow-calendar-card-actions">
                                <?php
                                $google_disconnect_url = wp_nonce_url(
                                    admin_url('admin.php?page=book-now-settings&tab=integration&disconnect_google=1'),
                                    'booknow_disconnect_google'
                                );
                                ?>
                                <a href="<?php echo esc_url($google_disconnect_url); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to disconnect Google Calendar? This will revoke access and remove stored tokens.', 'book-now-kre8iv'); ?>');">
                                    <span class="dashicons dashicons-no" style="vertical-align: middle; margin-top: -2px;"></span>
                                    <?php esc_html_e('Disconnect', 'book-now-kre8iv'); ?>
                                </a>
                            </div>
                        <?php elseif ($google_is_configured && $google_auth_url) : ?>
                            <p class="description">
                                <?php esc_html_e('Click the button below to authorize access to your Google Calendar.', 'book-now-kre8iv'); ?>
                            </p>
                            <div class="booknow-calendar-card-actions">
                                <a href="<?php echo esc_url($google_auth_url); ?>" class="button button-primary">
                                    <span class="dashicons dashicons-yes-alt" style="vertical-align: middle; margin-top: -2px;"></span>
                                    <?php esc_html_e('Connect Google Calendar', 'book-now-kre8iv'); ?>
                                </a>
                            </div>
                        <?php else : ?>
                            <p class="description">
                                <?php esc_html_e('Enter your Google OAuth credentials below and save settings before connecting.', 'book-now-kre8iv'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Microsoft 365 Connection Card -->
                <div class="booknow-calendar-card <?php echo $microsoft_is_connected ? 'connected' : ''; ?>">
                    <div class="booknow-calendar-card-header">
                        <span class="dashicons dashicons-cloud" style="color: #0078d4; font-size: 24px;"></span>
                        <h3><?php esc_html_e('Microsoft 365 / Outlook', 'book-now-kre8iv'); ?></h3>
                        <span class="booknow-connection-status <?php echo $microsoft_is_connected ? 'status-connected' : 'status-disconnected'; ?>">
                            <?php echo $microsoft_is_connected ? esc_html__('Connected', 'book-now-kre8iv') : esc_html__('Not Connected', 'book-now-kre8iv'); ?>
                        </span>
                    </div>
                    <div class="booknow-calendar-card-body">
                        <?php if ($microsoft_is_connected && !is_wp_error($microsoft_connection_info)) : ?>
                            <div class="booknow-connection-details">
                                <p>
                                    <span class="dashicons dashicons-businessperson"></span>
                                    <strong><?php esc_html_e('Account:', 'book-now-kre8iv'); ?></strong>
                                    <?php echo esc_html($microsoft_connection_info['user_name'] ?? $microsoft_connection_status['calendar_name'] ?? __('Unknown', 'book-now-kre8iv')); ?>
                                </p>
                                <p>
                                    <span class="dashicons dashicons-email"></span>
                                    <strong><?php esc_html_e('Email:', 'book-now-kre8iv'); ?></strong>
                                    <?php echo esc_html($microsoft_connection_info['user_email'] ?? $microsoft_connection_status['email'] ?? ''); ?>
                                </p>
                                <?php if (!empty($microsoft_connection_status['expires_at'])) : ?>
                                <p>
                                    <span class="dashicons dashicons-clock"></span>
                                    <strong><?php esc_html_e('Token Expires:', 'book-now-kre8iv'); ?></strong>
                                    <?php echo esc_html($microsoft_connection_status['expires_at']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="booknow-calendar-card-actions">
                                <?php
                                $microsoft_disconnect_url = wp_nonce_url(
                                    admin_url('admin.php?page=book-now-settings&tab=integration&disconnect_microsoft=1'),
                                    'booknow_disconnect_microsoft'
                                );
                                ?>
                                <a href="<?php echo esc_url($microsoft_disconnect_url); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to disconnect Microsoft 365?', 'book-now-kre8iv'); ?>');">
                                    <span class="dashicons dashicons-no" style="vertical-align: middle; margin-top: -2px;"></span>
                                    <?php esc_html_e('Disconnect', 'book-now-kre8iv'); ?>
                                </a>
                            </div>
                        <?php elseif (!empty($microsoft_connection_status['error']) && $microsoft_is_configured) : ?>
                            <p class="description" style="color: #d63638;">
                                <span class="dashicons dashicons-warning"></span>
                                <?php echo esc_html($microsoft_connection_status['error']); ?>
                            </p>
                            <div class="booknow-calendar-card-actions">
                                <?php if ($microsoft_auth_url) : ?>
                                <a href="<?php echo esc_url($microsoft_auth_url); ?>" class="button button-primary">
                                    <span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -2px;"></span>
                                    <?php esc_html_e('Reconnect Microsoft 365', 'book-now-kre8iv'); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($microsoft_is_configured && $microsoft_auth_url) : ?>
                            <p class="description">
                                <?php esc_html_e('Click the button below to authorize access to your Microsoft 365 Calendar.', 'book-now-kre8iv'); ?>
                            </p>
                            <div class="booknow-calendar-card-actions">
                                <a href="<?php echo esc_url($microsoft_auth_url); ?>" class="button button-primary">
                                    <span class="dashicons dashicons-yes-alt" style="vertical-align: middle; margin-top: -2px;"></span>
                                    <?php esc_html_e('Connect Microsoft 365', 'book-now-kre8iv'); ?>
                                </a>
                            </div>
                        <?php else : ?>
                            <p class="description">
                                <?php esc_html_e('Enter your Microsoft Azure AD credentials below and save settings before connecting.', 'book-now-kre8iv'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- API Credentials Section -->
            <h3 style="margin-top: 30px;"><?php esc_html_e('API Credentials', 'book-now-kre8iv'); ?></h3>
            <p class="description" style="margin-bottom: 15px;">
                <?php esc_html_e('Enter your OAuth client credentials. These are required to enable calendar connections.', 'book-now-kre8iv'); ?>
            </p>

            <h4><?php esc_html_e('Google Calendar', 'book-now-kre8iv'); ?></h4>
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
                    <th><?php esc_html_e('Redirect URI', 'book-now-kre8iv'); ?></th>
                    <td>
                        <code style="display: inline-block; padding: 8px 12px; background: #f0f0f1; border-radius: 4px; user-select: all;"><?php echo esc_html($google_redirect_uri); ?></code>
                        <p class="description">
                            <?php esc_html_e('Add this URL as an authorized redirect URI in your Google Cloud Console OAuth 2.0 Client configuration.', 'book-now-kre8iv'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="google_client_id"><?php esc_html_e('Client ID', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="google_client_id" id="google_client_id" value="<?php echo esc_attr($integration_settings['google_client_id'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('From Google Cloud Console > APIs & Services > Credentials', 'book-now-kre8iv'); ?></p>
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
                        <p class="description"><?php esc_html_e('Usually "primary" for your main calendar. Leave empty or use "primary" for default.', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
            </table>

            <h4><?php esc_html_e('Microsoft 365 Calendar', 'book-now-kre8iv'); ?></h4>
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
                    <th><label for="microsoft_client_id"><?php esc_html_e('Application (Client) ID', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="microsoft_client_id" id="microsoft_client_id" value="<?php echo esc_attr($integration_settings['microsoft_client_id'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('From Azure Portal > App registrations', 'book-now-kre8iv'); ?></p>
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
                    <th><label for="microsoft_tenant_id"><?php esc_html_e('Directory (Tenant) ID', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <input type="text" name="microsoft_tenant_id" id="microsoft_tenant_id" value="<?php echo esc_attr($integration_settings['microsoft_tenant_id'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Your Azure AD tenant ID from the app overview page', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
            </table>

        <?php elseif ($current_tab === 'diagnostics') : ?>
            <?php
            // Calendar Diagnostics Page
            // Load required classes
            if (!class_exists('Book_Now_Encryption')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-encryption.php';
            }
            if (!class_exists('Book_Now_Logger')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-logger.php';
            }
            if (!class_exists('Book_Now_Google_Calendar')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-google-calendar.php';
            }
            if (!class_exists('Book_Now_Microsoft_Calendar')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-microsoft-calendar.php';
            }

            // Get calendar settings
            $calendar_settings = get_option('booknow_calendar_settings', array());
            $integration_settings = Book_Now_Encryption::get_integration_settings();

            // Initialize calendar classes
            $google_calendar = null;
            $microsoft_calendar = null;
            $google_error = null;
            $microsoft_error = null;

            try {
                $google_calendar = new Book_Now_Google_Calendar();
            } catch (Exception $e) {
                $google_error = $e->getMessage();
            }

            try {
                $microsoft_calendar = new Book_Now_Microsoft_Calendar();
            } catch (Exception $e) {
                $microsoft_error = $e->getMessage();
            }

            // Get connection status
            $google_status = $google_calendar ? $google_calendar->get_connection_status() : array('connected' => false, 'error' => $google_error);
            $microsoft_status = $microsoft_calendar ? $microsoft_calendar->get_connection_status() : array('connected' => false, 'error' => $microsoft_error);

            // Check if auto-sync is enabled
            $google_sync_enabled = !empty($integration_settings['google_calendar_enabled']);
            $microsoft_sync_enabled = !empty($integration_settings['microsoft_calendar_enabled']);

            // Get recent bookings that might need sync
            if (!class_exists('Book_Now_Booking')) {
                require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-booking.php';
            }
            $recent_confirmed = Book_Now_Booking::get_all(array(
                'status' => 'confirmed',
                'limit' => 10,
                'orderby' => 'created_at',
                'order' => 'DESC'
            ));

            // Count bookings without calendar events
            $bookings_need_sync = array();
            foreach ($recent_confirmed as $booking) {
                $needs_google = $google_sync_enabled && $google_status['connected'] && empty($booking->google_event_id);
                $needs_microsoft = $microsoft_sync_enabled && $microsoft_status['connected'] && empty($booking->microsoft_event_id);
                if ($needs_google || $needs_microsoft) {
                    $bookings_need_sync[] = $booking;
                }
            }
            ?>

            <h2><?php esc_html_e('Calendar Diagnostics', 'book-now-kre8iv'); ?></h2>
            <p class="description"><?php esc_html_e('Check the status of your calendar integrations and troubleshoot sync issues.', 'book-now-kre8iv'); ?></p>

            <!-- System Status -->
            <div class="booknow-diagnostic-section" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3 style="margin-top: 0;"><span class="dashicons dashicons-admin-tools" style="margin-right: 8px;"></span><?php esc_html_e('System Status', 'book-now-kre8iv'); ?></h3>
                <table class="widefat striped" style="margin-top: 10px;">
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e('PHP Version', 'book-now-kre8iv'); ?></strong></td>
                            <td><?php echo esc_html(PHP_VERSION); ?> <?php echo version_compare(PHP_VERSION, '8.0', '>=') ? '<span style="color:green;">✓</span>' : '<span style="color:orange;">⚠ PHP 8.0+ recommended</span>'; ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('WordPress Version', 'book-now-kre8iv'); ?></strong></td>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Plugin Version', 'book-now-kre8iv'); ?></strong></td>
                            <td><?php echo esc_html(defined('BOOK_NOW_VERSION') ? BOOK_NOW_VERSION : '1.0.0'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Debug Mode', 'book-now-kre8iv'); ?></strong></td>
                            <td><?php echo (defined('WP_DEBUG') && WP_DEBUG) ? '<span style="color:orange;">Enabled</span>' : '<span style="color:green;">Disabled</span>'; ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Timezone', 'book-now-kre8iv'); ?></strong></td>
                            <td><?php echo esc_html(booknow_get_setting('general', 'timezone') ?: get_option('timezone_string', 'UTC')); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Google Calendar Status -->
            <div class="booknow-diagnostic-section" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3 style="margin-top: 0;"><span class="dashicons dashicons-google" style="color: #4285f4; margin-right: 8px;"></span><?php esc_html_e('Google Calendar Status', 'book-now-kre8iv'); ?></h3>
                <table class="widefat striped" style="margin-top: 10px;">
                    <tbody>
                        <tr>
                            <td style="width: 200px;"><strong><?php esc_html_e('Sync Enabled', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php if ($google_sync_enabled) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Yes', 'book-now-kre8iv'); ?></span>
                                <?php else : ?>
                                    <span style="color: gray;">✗ <?php esc_html_e('No', 'book-now-kre8iv'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Credentials Configured', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php if (!empty($integration_settings['google_client_id']) && !empty($integration_settings['google_client_secret'])) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Yes', 'book-now-kre8iv'); ?></span>
                                <?php else : ?>
                                    <span style="color: red;">✗ <?php esc_html_e('Missing credentials', 'book-now-kre8iv'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Connection Status', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php if ($google_status['connected']) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Connected', 'book-now-kre8iv'); ?></span>
                                    <?php if (!empty($google_status['email'])) : ?>
                                        <br><small><?php echo esc_html($google_status['email']); ?></small>
                                    <?php endif; ?>
                                <?php elseif (!empty($google_status['error'])) : ?>
                                    <span style="color: red;">✗ <?php echo esc_html($google_status['error']); ?></span>
                                <?php else : ?>
                                    <span style="color: orange;">⚠ <?php esc_html_e('Not connected - authorization required', 'book-now-kre8iv'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($google_calendar && $google_status['connected']) : ?>
                        <tr>
                            <td><strong><?php esc_html_e('API Test', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php
                                $test_result = $google_calendar->test_connection();
                                if (is_wp_error($test_result)) {
                                    echo '<span style="color: red;">✗ ' . esc_html($test_result->get_error_message()) . '</span>';
                                } else {
                                    echo '<span style="color: green;">✓ ' . esc_html__('API responding correctly', 'book-now-kre8iv') . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Microsoft Calendar Status -->
            <div class="booknow-diagnostic-section" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3 style="margin-top: 0;"><span class="dashicons dashicons-cloud" style="color: #0078d4; margin-right: 8px;"></span><?php esc_html_e('Microsoft 365 Calendar Status', 'book-now-kre8iv'); ?></h3>
                <table class="widefat striped" style="margin-top: 10px;">
                    <tbody>
                        <tr>
                            <td style="width: 200px;"><strong><?php esc_html_e('Sync Enabled', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php if ($microsoft_sync_enabled) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Yes', 'book-now-kre8iv'); ?></span>
                                <?php else : ?>
                                    <span style="color: gray;">✗ <?php esc_html_e('No', 'book-now-kre8iv'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Credentials Configured', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php if (!empty($integration_settings['microsoft_client_id']) && !empty($integration_settings['microsoft_client_secret'])) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Yes', 'book-now-kre8iv'); ?></span>
                                <?php else : ?>
                                    <span style="color: red;">✗ <?php esc_html_e('Missing credentials', 'book-now-kre8iv'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Connection Status', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php if ($microsoft_status['connected']) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Connected', 'book-now-kre8iv'); ?></span>
                                    <?php if (!empty($microsoft_status['email'])) : ?>
                                        <br><small><?php echo esc_html($microsoft_status['email']); ?></small>
                                    <?php endif; ?>
                                <?php elseif (!empty($microsoft_status['error'])) : ?>
                                    <span style="color: red;">✗ <?php echo esc_html($microsoft_status['error']); ?></span>
                                <?php else : ?>
                                    <span style="color: orange;">⚠ <?php esc_html_e('Not connected - authorization required', 'book-now-kre8iv'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($microsoft_status['expires_at'])) : ?>
                        <tr>
                            <td><strong><?php esc_html_e('Token Expires', 'book-now-kre8iv'); ?></strong></td>
                            <td><?php echo esc_html($microsoft_status['expires_at']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($microsoft_calendar && $microsoft_status['connected']) : ?>
                        <tr>
                            <td><strong><?php esc_html_e('API Test', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php
                                $test_result = $microsoft_calendar->test_connection();
                                if (is_wp_error($test_result)) {
                                    echo '<span style="color: red;">✗ ' . esc_html($test_result->get_error_message()) . '</span>';
                                } else {
                                    echo '<span style="color: green;">✓ ' . esc_html__('API responding correctly', 'book-now-kre8iv') . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sync Status -->
            <div class="booknow-diagnostic-section" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3 style="margin-top: 0;"><span class="dashicons dashicons-update" style="margin-right: 8px;"></span><?php esc_html_e('Auto-Sync Status', 'book-now-kre8iv'); ?></h3>

                <p class="description"><?php esc_html_e('Auto-sync automatically creates calendar events when bookings are confirmed.', 'book-now-kre8iv'); ?></p>

                <table class="widefat striped" style="margin-top: 10px;">
                    <tbody>
                        <tr>
                            <td style="width: 200px;"><strong><?php esc_html_e('Auto-Sync Hooks', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php
                                // Check if Calendar_Sync hooks are registered
                                $hooks_registered = has_action('booknow_booking_confirmed');
                                if ($hooks_registered) {
                                    echo '<span style="color: green;">✓ ' . esc_html__('Registered', 'book-now-kre8iv') . '</span>';
                                } else {
                                    echo '<span style="color: orange;">⚠ ' . esc_html__('Not registered - Calendar_Sync may not be initialized', 'book-now-kre8iv') . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Bookings Needing Sync', 'book-now-kre8iv'); ?></strong></td>
                            <td>
                                <?php if (empty($bookings_need_sync)) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e('All recent bookings are synced', 'book-now-kre8iv'); ?></span>
                                <?php else : ?>
                                    <span style="color: orange;">⚠ <?php echo esc_html(sprintf(_n('%d booking needs sync', '%d bookings need sync', count($bookings_need_sync), 'book-now-kre8iv'), count($bookings_need_sync))); ?></span>
                                    <ul style="margin: 10px 0 0 20px; list-style: disc;">
                                        <?php foreach ($bookings_need_sync as $booking) : ?>
                                            <li>
                                                <?php echo esc_html(sprintf(
                                                    __('Booking #%s (%s) - %s', 'book-now-kre8iv'),
                                                    $booking->reference_number,
                                                    $booking->customer_name,
                                                    $booking->booking_date
                                                )); ?>
                                                <a href="<?php echo esc_url(wp_nonce_url(
                                                    admin_url('admin.php?page=book-now-bookings&id=' . $booking->id . '&action=sync_calendar'),
                                                    'booknow_booking_action_' . $booking->id
                                                )); ?>" class="button button-small" style="margin-left: 10px;">
                                                    <?php esc_html_e('Sync Now', 'book-now-kre8iv'); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Troubleshooting Tips -->
            <div class="booknow-diagnostic-section" style="margin-top: 20px; padding: 20px; background: #fff9e5; border: 1px solid #f0c36d; border-radius: 4px;">
                <h3 style="margin-top: 0;"><span class="dashicons dashicons-lightbulb" style="margin-right: 8px;"></span><?php esc_html_e('Troubleshooting Tips', 'book-now-kre8iv'); ?></h3>
                <ul style="margin: 10px 0 0 20px; list-style: disc;">
                    <li><strong><?php esc_html_e('Blank screen on sync:', 'book-now-kre8iv'); ?></strong> <?php esc_html_e('Check that your calendar credentials are valid and the calendar is still connected. Try disconnecting and reconnecting.', 'book-now-kre8iv'); ?></li>
                    <li><strong><?php esc_html_e('Auto-sync not working:', 'book-now-kre8iv'); ?></strong> <?php esc_html_e('Ensure the calendar sync is enabled in Integrations settings AND the calendar is connected/authenticated.', 'book-now-kre8iv'); ?></li>
                    <li><strong><?php esc_html_e('Token expired:', 'book-now-kre8iv'); ?></strong> <?php esc_html_e('Microsoft tokens expire periodically. If you see token errors, disconnect and reconnect your calendar.', 'book-now-kre8iv'); ?></li>
                    <li><strong><?php esc_html_e('Calendar not showing availability:', 'book-now-kre8iv'); ?></strong> <?php esc_html_e('The system checks calendar busy times when calculating available slots. Ensure the correct calendar is selected and connected.', 'book-now-kre8iv'); ?></li>
                </ul>
            </div>

        <?php endif; ?>

        <?php if ($current_tab !== 'diagnostics') : ?>
        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'book-now-kre8iv'); ?></button>
        </p>
        <?php endif; ?>
    </form>
</div>
