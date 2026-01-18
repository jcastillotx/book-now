<?php
/**
 * Email Handler
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 */

class Book_Now_Email {

    /**
     * Email settings
     */
    private $settings;

    /**
     * Initialize email handler
     */
    public function __construct() {
        $this->load_settings();
        $this->setup_hooks();
    }

    /**
     * Load email settings
     */
    private function load_settings() {
        $this->settings = get_option('booknow_email_settings', array(
            'from_name' => get_bloginfo('name'),
            'from_email' => get_bloginfo('admin_email'),
            'confirmation_enabled' => true,
            'reminder_enabled' => true,
            'admin_notification_enabled' => true,
            'reminder_hours' => 24,
        ));
    }

    /**
     * Setup email hooks
     */
    private function setup_hooks() {
        add_action('booknow_booking_created', array($this, 'send_confirmation_email'), 10, 1);
        add_action('booknow_booking_confirmed', array($this, 'send_confirmation_email'), 10, 1);
        add_action('booknow_booking_cancelled', array($this, 'send_cancellation_email'), 10, 1);
        add_action('booknow_send_reminder', array($this, 'send_reminder_email'), 10, 1);
    }

    /**
     * Send booking confirmation email
     *
     * @param int $booking_id Booking ID
     */
    public function send_confirmation_email($booking_id) {
        if (empty($this->settings['confirmation_enabled'])) {
            return;
        }

        $booking = Book_Now_Booking::get($booking_id);
        if (!$booking) {
            return;
        }

        $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);
        if (!$type) {
            return;
        }

        $to = $booking->customer_email;
        $subject = sprintf(__('Booking Confirmation - %s', 'book-now-kre8iv'), $type->name);
        $message = $this->get_confirmation_template($booking, $type);
        $headers = $this->get_email_headers();

        $sent = wp_mail($to, $subject, $message, $headers);

        // Log email with encrypted body
        $this->log_email($booking_id, 'confirmation', $to, $subject, $sent, $message);

        // Send admin notification
        if (!empty($this->settings['admin_notification_enabled'])) {
            $this->send_admin_notification($booking, $type);
        }

        // Schedule reminder
        if (!empty($this->settings['reminder_enabled'])) {
            $this->schedule_reminder($booking);
        }

        return $sent;
    }

    /**
     * Send cancellation email
     *
     * @param int $booking_id Booking ID
     */
    public function send_cancellation_email($booking_id) {
        $booking = Book_Now_Booking::get($booking_id);
        if (!$booking) {
            return;
        }

        $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);
        if (!$type) {
            return;
        }

        $to = $booking->customer_email;
        $subject = sprintf(__('Booking Cancelled - %s', 'book-now-kre8iv'), $type->name);
        $message = $this->get_cancellation_template($booking, $type);
        $headers = $this->get_email_headers();

        $sent = wp_mail($to, $subject, $message, $headers);

        $this->log_email($booking_id, 'cancellation', $to, $subject, $sent, $message);

        return $sent;
    }

    /**
     * Send reminder email
     *
     * @param int $booking_id Booking ID
     */
    public function send_reminder_email($booking_id) {
        $booking = Book_Now_Booking::get($booking_id);
        if (!$booking || $booking->status !== 'confirmed') {
            return;
        }

        $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);
        if (!$type) {
            return;
        }

        $to = $booking->customer_email;
        $subject = sprintf(__('Reminder: Upcoming Appointment - %s', 'book-now-kre8iv'), $type->name);
        $message = $this->get_reminder_template($booking, $type);
        $headers = $this->get_email_headers();

        $sent = wp_mail($to, $subject, $message, $headers);

        $this->log_email($booking_id, 'reminder', $to, $subject, $sent, $message);

        return $sent;
    }

    /**
     * Send admin notification
     *
     * @param object $booking Booking object
     * @param object $type Consultation type object
     */
    private function send_admin_notification($booking, $type) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('New Booking: %s', 'book-now-kre8iv'), $type->name);
        $message = $this->get_admin_notification_template($booking, $type);
        $headers = $this->get_email_headers();

        $sent = wp_mail($admin_email, $subject, $message, $headers);

        $this->log_email($booking->id, 'admin_notification', $admin_email, $subject, $sent, $message);

        return $sent;
    }

    /**
     * Get confirmation email template
     *
     * @param object $booking Booking object
     * @param object $type Consultation type object
     * @return string HTML email content
     */
    private function get_confirmation_template($booking, $type) {
        $booking_datetime = booknow_format_date($booking->booking_date) . ' at ' . booknow_format_time($booking->booking_time);
        $price = booknow_format_price($booking->total_amount);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .booking-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #4CAF50; }
                .detail-row { margin: 10px 0; }
                .label { font-weight: bold; color: #666; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .button { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Booking Confirmed!', 'book-now-kre8iv'); ?></h1>
                </div>
                
                <div class="content">
                    <p><?php printf(__('Hi %s,', 'book-now-kre8iv'), esc_html($booking->customer_name)); ?></p>
                    
                    <p><?php _e('Your booking has been confirmed. Here are the details:', 'book-now-kre8iv'); ?></p>
                    
                    <div class="booking-details">
                        <div class="detail-row">
                            <span class="label"><?php _e('Reference Number:', 'book-now-kre8iv'); ?></span>
                            <strong><?php echo esc_html($booking->reference_number); ?></strong>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label"><?php _e('Consultation Type:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($type->name); ?>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label"><?php _e('Date & Time:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($booking_datetime); ?>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label"><?php _e('Duration:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($type->duration); ?> <?php _e('minutes', 'book-now-kre8iv'); ?>
                        </div>
                        
                        <?php if ($booking->total_amount > 0): ?>
                        <div class="detail-row">
                            <span class="label"><?php _e('Amount Paid:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($price); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($booking->notes): ?>
                        <div class="detail-row">
                            <span class="label"><?php _e('Notes:', 'book-now-kre8iv'); ?></span>
                            <?php echo nl2br(esc_html($booking->notes)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <p><?php _e('We look forward to meeting with you!', 'book-now-kre8iv'); ?></p>
                    
                    <p><?php _e('If you need to cancel or reschedule, please contact us as soon as possible.', 'book-now-kre8iv'); ?></p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html(get_bloginfo('name')); ?></p>
                    <p><?php _e('This is an automated message. Please do not reply to this email.', 'book-now-kre8iv'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get cancellation email template
     *
     * @param object $booking Booking object
     * @param object $type Consultation type object
     * @return string HTML email content
     */
    private function get_cancellation_template($booking, $type) {
        $booking_datetime = booknow_format_date($booking->booking_date) . ' at ' . booknow_format_time($booking->booking_time);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f44336; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .booking-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #f44336; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Booking Cancelled', 'book-now-kre8iv'); ?></h1>
                </div>
                
                <div class="content">
                    <p><?php printf(__('Hi %s,', 'book-now-kre8iv'), esc_html($booking->customer_name)); ?></p>
                    
                    <p><?php _e('Your booking has been cancelled:', 'book-now-kre8iv'); ?></p>
                    
                    <div class="booking-details">
                        <p><strong><?php _e('Reference:', 'book-now-kre8iv'); ?></strong> <?php echo esc_html($booking->reference_number); ?></p>
                        <p><strong><?php _e('Consultation:', 'book-now-kre8iv'); ?></strong> <?php echo esc_html($type->name); ?></p>
                        <p><strong><?php _e('Date & Time:', 'book-now-kre8iv'); ?></strong> <?php echo esc_html($booking_datetime); ?></p>
                    </div>
                    
                    <p><?php _e('If you would like to book another appointment, please visit our website.', 'book-now-kre8iv'); ?></p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html(get_bloginfo('name')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get reminder email template
     *
     * @param object $booking Booking object
     * @param object $type Consultation type object
     * @return string HTML email content
     */
    private function get_reminder_template($booking, $type) {
        $booking_datetime = booknow_format_date($booking->booking_date) . ' at ' . booknow_format_time($booking->booking_time);
        $hours = $this->settings['reminder_hours'];

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .booking-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #2196F3; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Appointment Reminder', 'book-now-kre8iv'); ?></h1>
                </div>
                
                <div class="content">
                    <p><?php printf(__('Hi %s,', 'book-now-kre8iv'), esc_html($booking->customer_name)); ?></p>
                    
                    <p><?php printf(__('This is a reminder that you have an upcoming appointment in %d hours:', 'book-now-kre8iv'), $hours); ?></p>
                    
                    <div class="booking-details">
                        <p><strong><?php _e('Consultation:', 'book-now-kre8iv'); ?></strong> <?php echo esc_html($type->name); ?></p>
                        <p><strong><?php _e('Date & Time:', 'book-now-kre8iv'); ?></strong> <?php echo esc_html($booking_datetime); ?></p>
                        <p><strong><?php _e('Duration:', 'book-now-kre8iv'); ?></strong> <?php echo esc_html($type->duration); ?> <?php _e('minutes', 'book-now-kre8iv'); ?></p>
                        <p><strong><?php _e('Reference:', 'book-now-kre8iv'); ?></strong> <?php echo esc_html($booking->reference_number); ?></p>
                    </div>
                    
                    <p><?php _e('We look forward to seeing you!', 'book-now-kre8iv'); ?></p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html(get_bloginfo('name')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get admin notification template
     *
     * @param object $booking Booking object
     * @param object $type Consultation type object
     * @return string HTML email content
     */
    private function get_admin_notification_template($booking, $type) {
        $booking_datetime = booknow_format_date($booking->booking_date) . ' at ' . booknow_format_time($booking->booking_time);
        $price = booknow_format_price($booking->total_amount);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #673AB7; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .booking-details { background: white; padding: 15px; margin: 20px 0; }
                .detail-row { margin: 8px 0; padding: 8px; border-bottom: 1px solid #eee; }
                .label { font-weight: bold; color: #666; display: inline-block; width: 150px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('New Booking Received', 'book-now-kre8iv'); ?></h1>
                </div>
                
                <div class="content">
                    <p><?php _e('A new booking has been received:', 'book-now-kre8iv'); ?></p>
                    
                    <div class="booking-details">
                        <div class="detail-row">
                            <span class="label"><?php _e('Reference:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($booking->reference_number); ?>
                        </div>
                        <div class="detail-row">
                            <span class="label"><?php _e('Customer:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($booking->customer_name); ?>
                        </div>
                        <div class="detail-row">
                            <span class="label"><?php _e('Email:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($booking->customer_email); ?>
                        </div>
                        <?php if ($booking->customer_phone): ?>
                        <div class="detail-row">
                            <span class="label"><?php _e('Phone:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($booking->customer_phone); ?>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="label"><?php _e('Consultation:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($type->name); ?>
                        </div>
                        <div class="detail-row">
                            <span class="label"><?php _e('Date & Time:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($booking_datetime); ?>
                        </div>
                        <div class="detail-row">
                            <span class="label"><?php _e('Amount:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html($price); ?>
                        </div>
                        <div class="detail-row">
                            <span class="label"><?php _e('Status:', 'book-now-kre8iv'); ?></span>
                            <?php echo esc_html(booknow_get_status_label($booking->status)); ?>
                        </div>
                        <?php if ($booking->notes): ?>
                        <div class="detail-row">
                            <span class="label"><?php _e('Notes:', 'book-now-kre8iv'); ?></span>
                            <?php echo nl2br(esc_html($booking->notes)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <p><a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings')); ?>"><?php esc_html_e('View in Admin', 'book-now-kre8iv'); ?></a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get email headers
     *
     * @return array Email headers
     */
    private function get_email_headers() {
        $from_name = $this->settings['from_name'];
        $from_email = $this->settings['from_email'];

        return array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $from_name, $from_email),
        );
    }

    /**
     * Schedule reminder email
     *
     * @param object $booking Booking object
     */
    private function schedule_reminder($booking) {
        $reminder_hours = $this->settings['reminder_hours'];
        $booking_timestamp = strtotime($booking->booking_date . ' ' . $booking->booking_time);
        $reminder_timestamp = $booking_timestamp - ($reminder_hours * 3600);

        // Only schedule if reminder time is in the future
        if ($reminder_timestamp > time()) {
            wp_schedule_single_event($reminder_timestamp, 'booknow_send_reminder', array($booking->id));
        }
    }

    /**
     * Log email
     *
     * @param int    $booking_id Booking ID
     * @param string $type Email type
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param bool   $sent Whether email was sent successfully
     * @param string $body Optional. Email body content to encrypt and store
     */
    private function log_email($booking_id, $type, $to, $subject, $sent, $body = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_email_log';

        // Encrypt email body if provided and encryption is available
        $encrypted_body = '';
        if (!empty($body) && class_exists('Book_Now_Encryption') && Book_Now_Encryption::is_available()) {
            $encrypted_body = Book_Now_Encryption::encrypt($body);
        } elseif (!empty($body)) {
            // Fallback: store as-is if encryption not available
            $encrypted_body = $body;
        }

        $wpdb->insert($table, array(
            'booking_id' => $booking_id,
            'email_type' => $type,
            'recipient_email' => $to,
            'subject' => $subject,
            'email_body' => $encrypted_body,
            'status' => $sent ? 'sent' : 'failed',
            'sent_at' => current_time('mysql'),
        ));
    }

    /**
     * Send test email
     *
     * @param string $to Recipient email
     * @return bool Success
     */
    public function send_test_email($to) {
        $subject = __('Test Email from Book Now Plugin', 'book-now-kre8iv');
        $message = '<p>' . __('This is a test email to verify your email settings are working correctly.', 'book-now-kre8iv') . '</p>';
        $headers = $this->get_email_headers();

        return wp_mail($to, $subject, $message, $headers);
    }
}
