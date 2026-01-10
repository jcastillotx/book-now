<?php
/**
 * SMTP Handler for Email Services
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 */

class Book_Now_SMTP {

    /**
     * SMTP settings
     */
    private $settings;

    /**
     * Initialize SMTP handler
     */
    public function __construct() {
        $this->load_settings();
        $this->setup_phpmailer();
    }

    /**
     * Load SMTP settings
     */
    private function load_settings() {
        $this->settings = get_option('booknow_smtp_settings', array(
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
    }

    /**
     * Setup PHPMailer configuration
     */
    private function setup_phpmailer() {
        if (!$this->settings['enabled']) {
            return;
        }

        add_action('phpmailer_init', array($this, 'configure_phpmailer'));
    }

    /**
     * Configure PHPMailer with SMTP settings
     *
     * @param PHPMailer $phpmailer PHPMailer instance
     */
    public function configure_phpmailer($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host = $this->get_smtp_host();
        $phpmailer->Port = $this->get_smtp_port();
        $phpmailer->SMTPSecure = $this->settings['encryption'];
        $phpmailer->SMTPAuth = $this->settings['auth'];
        
        if ($this->settings['auth']) {
            $phpmailer->Username = $this->get_smtp_username();
            $phpmailer->Password = $this->get_smtp_password();
        }

        if (!empty($this->settings['from_email'])) {
            $phpmailer->From = $this->settings['from_email'];
        }

        if (!empty($this->settings['from_name'])) {
            $phpmailer->FromName = $this->settings['from_name'];
        }

        $phpmailer->SMTPDebug = 0;
    }

    /**
     * Get SMTP host based on provider
     *
     * @return string SMTP host
     */
    private function get_smtp_host() {
        if ($this->settings['provider'] !== 'custom' && empty($this->settings['host'])) {
            return $this->get_provider_host($this->settings['provider']);
        }

        return $this->settings['host'];
    }

    /**
     * Get SMTP port based on provider
     *
     * @return int SMTP port
     */
    private function get_smtp_port() {
        if ($this->settings['provider'] !== 'custom' && empty($this->settings['port'])) {
            return $this->get_provider_port($this->settings['provider']);
        }

        return $this->settings['port'];
    }

    /**
     * Get SMTP username
     *
     * @return string Username
     */
    private function get_smtp_username() {
        return $this->settings['username'];
    }

    /**
     * Get SMTP password
     *
     * @return string Password
     */
    private function get_smtp_password() {
        return $this->settings['password'];
    }

    /**
     * Get provider-specific SMTP host
     *
     * @param string $provider Provider name
     * @return string SMTP host
     */
    private function get_provider_host($provider) {
        $hosts = array(
            'brevo' => 'smtp-relay.brevo.com',
            'sendgrid' => 'smtp.sendgrid.net',
            'mailgun' => 'smtp.mailgun.org',
            'amazon_ses' => 'email-smtp.us-east-1.amazonaws.com',
            'sparkpost' => 'smtp.sparkpostmail.com',
            'postmark' => 'smtp.postmarkapp.com',
            'gmail' => 'smtp.gmail.com',
            'outlook' => 'smtp-mail.outlook.com',
        );

        return isset($hosts[$provider]) ? $hosts[$provider] : '';
    }

    /**
     * Get provider-specific SMTP port
     *
     * @param string $provider Provider name
     * @return int SMTP port
     */
    private function get_provider_port($provider) {
        $ports = array(
            'brevo' => 587,
            'sendgrid' => 587,
            'mailgun' => 587,
            'amazon_ses' => 587,
            'sparkpost' => 587,
            'postmark' => 587,
            'gmail' => 587,
            'outlook' => 587,
        );

        return isset($ports[$provider]) ? $ports[$provider] : 587;
    }

    /**
     * Get provider-specific encryption
     *
     * @param string $provider Provider name
     * @return string Encryption type
     */
    private function get_provider_encryption($provider) {
        $encryption = array(
            'brevo' => 'tls',
            'sendgrid' => 'tls',
            'mailgun' => 'tls',
            'amazon_ses' => 'tls',
            'sparkpost' => 'tls',
            'postmark' => 'tls',
            'gmail' => 'tls',
            'outlook' => 'tls',
        );

        return isset($encryption[$provider]) ? $encryption[$provider] : 'tls';
    }

    /**
     * Test SMTP connection
     *
     * @return array|WP_Error Test result
     */
    public function test_connection() {
        if (!$this->settings['enabled']) {
            return new WP_Error('smtp_disabled', __('SMTP is not enabled.', 'book-now-kre8iv'));
        }

        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

        $phpmailer = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $phpmailer->isSMTP();
            $phpmailer->Host = $this->get_smtp_host();
            $phpmailer->Port = $this->get_smtp_port();
            $phpmailer->SMTPSecure = $this->settings['encryption'];
            $phpmailer->SMTPAuth = $this->settings['auth'];
            
            if ($this->settings['auth']) {
                $phpmailer->Username = $this->get_smtp_username();
                $phpmailer->Password = $this->get_smtp_password();
            }

            $phpmailer->SMTPDebug = 0;
            $phpmailer->Timeout = 10;

            // Test connection
            if (!$phpmailer->smtpConnect()) {
                return new WP_Error('connection_failed', __('Could not connect to SMTP server.', 'book-now-kre8iv'));
            }

            $phpmailer->smtpClose();

            return array(
                'success' => true,
                'message' => __('SMTP connection successful!', 'book-now-kre8iv'),
                'host' => $this->get_smtp_host(),
                'port' => $this->get_smtp_port(),
            );

        } catch (Exception $e) {
            return new WP_Error('smtp_error', $e->getMessage());
        }
    }

    /**
     * Send email via Brevo API (alternative to SMTP)
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message (HTML)
     * @param array  $headers Email headers
     * @return bool Success
     */
    public function send_via_brevo_api($to, $subject, $message, $headers = array()) {
        $api_key = get_option('booknow_brevo_api_key');
        
        if (empty($api_key)) {
            return false;
        }

        $from_email = $this->settings['from_email'] ?: get_bloginfo('admin_email');
        $from_name = $this->settings['from_name'] ?: get_bloginfo('name');

        $data = array(
            'sender' => array(
                'name' => $from_name,
                'email' => $from_email,
            ),
            'to' => array(
                array(
                    'email' => $to,
                ),
            ),
            'subject' => $subject,
            'htmlContent' => $message,
        );

        $response = wp_remote_post('https://api.brevo.com/v3/smtp/email', array(
            'headers' => array(
                'accept' => 'application/json',
                'api-key' => $api_key,
                'content-type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code === 201;
    }

    /**
     * Get list of supported providers
     *
     * @return array Provider list
     */
    public static function get_providers() {
        return array(
            'custom' => __('Custom SMTP', 'book-now-kre8iv'),
            'brevo' => __('Brevo (Sendinblue)', 'book-now-kre8iv'),
            'sendgrid' => __('SendGrid', 'book-now-kre8iv'),
            'mailgun' => __('Mailgun', 'book-now-kre8iv'),
            'amazon_ses' => __('Amazon SES', 'book-now-kre8iv'),
            'sparkpost' => __('SparkPost', 'book-now-kre8iv'),
            'postmark' => __('Postmark', 'book-now-kre8iv'),
            'gmail' => __('Gmail', 'book-now-kre8iv'),
            'outlook' => __('Outlook/Office 365', 'book-now-kre8iv'),
        );
    }

    /**
     * Get provider setup instructions
     *
     * @param string $provider Provider name
     * @return string Instructions
     */
    public static function get_provider_instructions($provider) {
        $instructions = array(
            'brevo' => __('1. Sign up at brevo.com<br>2. Go to SMTP & API settings<br>3. Generate SMTP credentials<br>4. Use your email as username and SMTP key as password', 'book-now-kre8iv'),
            'sendgrid' => __('1. Sign up at sendgrid.com<br>2. Create an API Key<br>3. Use "apikey" as username and your API key as password', 'book-now-kre8iv'),
            'mailgun' => __('1. Sign up at mailgun.com<br>2. Go to Sending > Domain Settings > SMTP credentials<br>3. Use the provided username and password', 'book-now-kre8iv'),
            'amazon_ses' => __('1. Sign up for AWS<br>2. Verify your domain/email in SES<br>3. Create SMTP credentials in IAM<br>4. Use the provided credentials', 'book-now-kre8iv'),
            'gmail' => __('1. Enable 2-factor authentication on your Google account<br>2. Generate an App Password<br>3. Use your Gmail address as username and App Password as password', 'book-now-kre8iv'),
            'outlook' => __('1. Use your Outlook/Office 365 email address<br>2. Use your account password<br>3. Enable "Allow less secure apps" if needed', 'book-now-kre8iv'),
        );

        return isset($instructions[$provider]) ? $instructions[$provider] : '';
    }
}
