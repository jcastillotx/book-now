<?php
/**
 * SMTP Settings Page
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
if (isset($_POST['booknow_save_smtp_settings'])) {
    check_admin_referer('booknow_smtp_settings_nonce', 'nonce');

    // Encrypt sensitive credentials before storing
    $smtp_password = sanitize_text_field($_POST['smtp_password']);
    $encrypted_password = !empty($smtp_password) ? Book_Now_Encryption::encrypt($smtp_password) : '';

    $settings = array(
        'enabled' => isset($_POST['smtp_enabled']),
        'provider' => sanitize_text_field($_POST['provider']),
        'host' => sanitize_text_field($_POST['smtp_host']),
        'port' => absint($_POST['smtp_port']),
        'encryption' => sanitize_text_field($_POST['smtp_encryption']),
        'auth' => isset($_POST['smtp_auth']),
        'username' => sanitize_text_field($_POST['smtp_username']),
        'password' => $encrypted_password,
        'from_name' => sanitize_text_field($_POST['smtp_from_name']),
        'from_email' => sanitize_email($_POST['smtp_from_email']),
    );

    update_option('booknow_smtp_settings', $settings);

    // Save Brevo API key separately if provided (encrypted)
    if (!empty($_POST['brevo_api_key'])) {
        $brevo_key = sanitize_text_field($_POST['brevo_api_key']);
        update_option('booknow_brevo_api_key', Book_Now_Encryption::encrypt($brevo_key));
    }

    echo '<div class="notice notice-success"><p>' . esc_html__('SMTP settings saved successfully.', 'book-now-kre8iv') . '</p></div>';
}

// Handle test connection
if (isset($_POST['booknow_test_smtp'])) {
    check_admin_referer('booknow_test_smtp_nonce', 'test_nonce');
    
    $smtp = new Book_Now_SMTP();
    $result = $smtp->test_connection();
    
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p><strong>' . esc_html__('Connection Failed:', 'book-now-kre8iv') . '</strong> ' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
    }
}

$settings = get_option('booknow_smtp_settings', array(
    'enabled' => false,
    'provider' => 'custom',
    'host' => '',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => '',
    'password' => '',
    'from_name' => '',
    'from_email' => '',
));

$brevo_api_key = get_option('booknow_brevo_api_key', '');
$providers = Book_Now_SMTP::get_providers();
?>

<div class="wrap">
    <h1><?php _e('SMTP Settings', 'book-now-kre8iv'); ?></h1>
    
    <p><?php _e('Configure SMTP to ensure reliable email delivery. Choose from popular email service providers or use custom SMTP settings.', 'book-now-kre8iv'); ?></p>

    <form method="post" action="" id="smtp-settings-form">
        <?php wp_nonce_field('booknow_smtp_settings_nonce', 'nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="smtp_enabled"><?php _e('Enable SMTP', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               id="smtp_enabled" 
                               name="smtp_enabled" 
                               value="1" 
                               <?php checked(!empty($settings['enabled'])); ?>>
                        <?php _e('Use SMTP for sending emails', 'book-now-kre8iv'); ?>
                    </label>
                    <p class="description"><?php _e('Enable this to use SMTP instead of the default PHP mail() function.', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="provider"><?php _e('Email Provider', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <select id="provider" name="provider" class="regular-text">
                        <?php foreach ($providers as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['provider'], $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Select your email service provider for automatic configuration.', 'book-now-kre8iv'); ?></p>
                    
                    <div id="provider-instructions" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1; display: none;">
                        <!-- Instructions will be populated via JavaScript -->
                    </div>
                </td>
            </tr>

            <tr class="smtp-field">
                <th scope="row">
                    <label for="smtp_host"><?php _e('SMTP Host', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="smtp_host" 
                           name="smtp_host" 
                           value="<?php echo esc_attr($settings['host']); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('Your SMTP server address (e.g., smtp.gmail.com)', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr class="smtp-field">
                <th scope="row">
                    <label for="smtp_port"><?php _e('SMTP Port', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="smtp_port" 
                           name="smtp_port" 
                           value="<?php echo esc_attr($settings['port']); ?>" 
                           class="small-text">
                    <p class="description"><?php _e('Common ports: 587 (TLS), 465 (SSL), 25 (no encryption)', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr class="smtp-field">
                <th scope="row">
                    <label for="smtp_encryption"><?php _e('Encryption', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <select id="smtp_encryption" name="smtp_encryption">
                        <option value="tls" <?php selected($settings['encryption'], 'tls'); ?>>TLS</option>
                        <option value="ssl" <?php selected($settings['encryption'], 'ssl'); ?>>SSL</option>
                        <option value="" <?php selected($settings['encryption'], ''); ?>><?php _e('None', 'book-now-kre8iv'); ?></option>
                    </select>
                    <p class="description"><?php _e('TLS is recommended for most providers.', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr class="smtp-field">
                <th scope="row">
                    <label for="smtp_auth"><?php _e('Authentication', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               id="smtp_auth" 
                               name="smtp_auth" 
                               value="1" 
                               <?php checked(!empty($settings['auth'])); ?>>
                        <?php _e('Use SMTP authentication', 'book-now-kre8iv'); ?>
                    </label>
                    <p class="description"><?php _e('Most SMTP servers require authentication.', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr class="smtp-field">
                <th scope="row">
                    <label for="smtp_username"><?php _e('Username', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="smtp_username" 
                           name="smtp_username" 
                           value="<?php echo esc_attr($settings['username']); ?>" 
                           class="regular-text" 
                           autocomplete="off">
                    <p class="description"><?php _e('Your SMTP username (usually your email address)', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr class="smtp-field">
                <th scope="row">
                    <label for="smtp_password"><?php _e('Password', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="password" 
                           id="smtp_password" 
                           name="smtp_password" 
                           value="<?php echo esc_attr($settings['password']); ?>" 
                           class="regular-text" 
                           autocomplete="new-password">
                    <p class="description"><?php _e('Your SMTP password or API key', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr class="brevo-api-field" style="display: none;">
                <th scope="row">
                    <label for="brevo_api_key"><?php _e('Brevo API Key', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="brevo_api_key" 
                           name="brevo_api_key" 
                           value="<?php echo esc_attr($brevo_api_key); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('Optional: Use Brevo API instead of SMTP for better deliverability', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr class="smtp-field">
                <th scope="row">
                    <label for="smtp_from_name"><?php _e('From Name', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="smtp_from_name" 
                           name="smtp_from_name" 
                           value="<?php echo esc_attr($settings['from_name']); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('The name that appears in the "From" field', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>

            <tr class="smtp-field">
                <th scope="row">
                    <label for="smtp_from_email"><?php _e('From Email', 'book-now-kre8iv'); ?></label>
                </th>
                <td>
                    <input type="email" 
                           id="smtp_from_email" 
                           name="smtp_from_email" 
                           value="<?php echo esc_attr($settings['from_email']); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('The email address that appears in the "From" field', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save SMTP Settings', 'book-now-kre8iv'), 'primary', 'booknow_save_smtp_settings'); ?>
    </form>

    <hr>

    <h2><?php _e('Test SMTP Connection', 'book-now-kre8iv'); ?></h2>
    <p><?php _e('Test your SMTP configuration to ensure it\'s working correctly.', 'book-now-kre8iv'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('booknow_test_smtp_nonce', 'test_nonce'); ?>
        <?php submit_button(__('Test Connection', 'book-now-kre8iv'), 'secondary', 'booknow_test_smtp'); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    const providerInstructions = <?php echo json_encode(array(
        'brevo' => Book_Now_SMTP::get_provider_instructions('brevo'),
        'sendgrid' => Book_Now_SMTP::get_provider_instructions('sendgrid'),
        'mailgun' => Book_Now_SMTP::get_provider_instructions('mailgun'),
        'amazon_ses' => Book_Now_SMTP::get_provider_instructions('amazon_ses'),
        'gmail' => Book_Now_SMTP::get_provider_instructions('gmail'),
        'outlook' => Book_Now_SMTP::get_provider_instructions('outlook'),
    )); ?>;

    function updateProviderFields() {
        const provider = $('#provider').val();
        const $instructions = $('#provider-instructions');
        
        // Show/hide Brevo API field
        if (provider === 'brevo') {
            $('.brevo-api-field').show();
        } else {
            $('.brevo-api-field').hide();
        }
        
        // Show instructions for selected provider
        if (providerInstructions[provider]) {
            $instructions.html('<strong><?php _e('Setup Instructions:', 'book-now-kre8iv'); ?></strong><br>' + providerInstructions[provider]).show();
        } else {
            $instructions.hide();
        }
    }

    $('#provider').on('change', updateProviderFields);
    updateProviderFields();
});
</script>

<style>
.form-table th {
    width: 200px;
}
#provider-instructions {
    line-height: 1.6;
}
</style>
