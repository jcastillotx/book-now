<?php
/**
 * Email notifications system
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Notifications {

    /**
     * Send booking confirmation email to customer.
     *
     * @param object $booking Booking object.
     * @return bool
     */
    public static function send_booking_confirmation($booking) {
        $consultation_type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
        
        $variables = array(
            'customer_name' => $booking->customer_name,
            'booking_reference' => $booking->reference_number,
            'booking_date' => booknow_format_date($booking->booking_date),
            'booking_time' => booknow_format_time($booking->booking_time),
            'consultation_type' => $consultation_type ? $consultation_type->name : '',
            'duration' => $booking->duration,
            'business_name' => booknow_get_setting('general', 'business_name'),
            'amount' => booknow_format_price($booking->payment_amount),
            'cancel_url' => self::get_cancel_url($booking->reference_number),
        );

        $subject = sprintf(
            __('Booking Confirmation - %s', 'book-now-kre8iv'),
            $variables['booking_reference']
        );

        $message = self::get_template('booking-confirmation', $variables);

        $result = self::send_email($booking->customer_email, $subject, $message);

        // Log email
        self::log_email($booking->id, 'confirmation', $booking->customer_email, $subject, $result);

        return $result;
    }

    /**
     * Send booking reminder email to customer.
     *
     * @param object $booking Booking object.
     * @return bool
     */
    public static function send_booking_reminder($booking) {
        $consultation_type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
        
        $variables = array(
            'customer_name' => $booking->customer_name,
            'booking_reference' => $booking->reference_number,
            'booking_date' => booknow_format_date($booking->booking_date),
            'booking_time' => booknow_format_time($booking->booking_time),
            'consultation_type' => $consultation_type ? $consultation_type->name : '',
            'duration' => $booking->duration,
            'business_name' => booknow_get_setting('general', 'business_name'),
            'cancel_url' => self::get_cancel_url($booking->reference_number),
        );

        $subject = sprintf(
            __('Reminder: Upcoming Booking - %s', 'book-now-kre8iv'),
            $variables['booking_reference']
        );

        $message = self::get_template('booking-reminder', $variables);

        $result = self::send_email($booking->customer_email, $subject, $message);

        // Log email
        self::log_email($booking->id, 'reminder', $booking->customer_email, $subject, $result);

        return $result;
    }

    /**
     * Send cancellation notification to customer.
     *
     * @param object $booking Booking object.
     * @return bool
     */
    public static function send_cancellation_notification($booking) {
        $consultation_type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
        
        $variables = array(
            'customer_name' => $booking->customer_name,
            'booking_reference' => $booking->reference_number,
            'booking_date' => booknow_format_date($booking->booking_date),
            'booking_time' => booknow_format_time($booking->booking_time),
            'consultation_type' => $consultation_type ? $consultation_type->name : '',
            'business_name' => booknow_get_setting('general', 'business_name'),
        );

        $subject = sprintf(
            __('Booking Cancelled - %s', 'book-now-kre8iv'),
            $variables['booking_reference']
        );

        $message = self::get_template('cancellation-notification', $variables);

        $result = self::send_email($booking->customer_email, $subject, $message);

        // Log email
        self::log_email($booking->id, 'cancellation', $booking->customer_email, $subject, $result);

        return $result;
    }

    /**
     * Send new booking notification to admin.
     *
     * @param object $booking Booking object.
     * @return bool
     */
    public static function send_admin_notification($booking) {
        $email_settings = booknow_get_setting('email');
        
        if (empty($email_settings['admin_notification'])) {
            return false;
        }

        $admin_email = $email_settings['admin_email'] ?? get_option('admin_email');
        $consultation_type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
        
        $variables = array(
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'customer_phone' => $booking->customer_phone,
            'booking_reference' => $booking->reference_number,
            'booking_date' => booknow_format_date($booking->booking_date),
            'booking_time' => booknow_format_time($booking->booking_time),
            'consultation_type' => $consultation_type ? $consultation_type->name : '',
            'duration' => $booking->duration,
            'amount' => booknow_format_price($booking->payment_amount),
            'customer_notes' => $booking->customer_notes,
            'admin_url' => admin_url('admin.php?page=book-now-bookings&id=' . $booking->id),
        );

        $subject = sprintf(
            __('New Booking: %s - %s', 'book-now-kre8iv'),
            $variables['consultation_type'],
            $variables['booking_reference']
        );

        $message = self::get_template('admin-new-booking', $variables);

        $result = self::send_email($admin_email, $subject, $message);

        // Log email
        self::log_email($booking->id, 'admin_notification', $admin_email, $subject, $result);

        return $result;
    }

    /**
     * Send cancellation alert to admin.
     *
     * @param object $booking Booking object.
     * @return bool
     */
    public static function send_admin_cancellation_alert($booking) {
        $email_settings = booknow_get_setting('email');
        $admin_email = $email_settings['admin_email'] ?? get_option('admin_email');
        $consultation_type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
        
        $variables = array(
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'booking_reference' => $booking->reference_number,
            'booking_date' => booknow_format_date($booking->booking_date),
            'booking_time' => booknow_format_time($booking->booking_time),
            'consultation_type' => $consultation_type ? $consultation_type->name : '',
            'admin_url' => admin_url('admin.php?page=book-now-bookings&id=' . $booking->id),
        );

        $subject = sprintf(
            __('Booking Cancelled: %s', 'book-now-kre8iv'),
            $variables['booking_reference']
        );

        $message = self::get_template('admin-cancellation', $variables);

        $result = self::send_email($admin_email, $subject, $message);

        // Log email
        self::log_email($booking->id, 'admin_notification', $admin_email, $subject, $result);

        return $result;
    }

    /**
     * Send refund notification to customer.
     *
     * @param object $booking Booking object.
     * @return bool
     */
    public static function send_refund_notification($booking) {
        $consultation_type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
        
        $variables = array(
            'customer_name' => $booking->customer_name,
            'booking_reference' => $booking->reference_number,
            'booking_date' => booknow_format_date($booking->booking_date),
            'booking_time' => booknow_format_time($booking->booking_time),
            'consultation_type' => $consultation_type ? $consultation_type->name : '',
            'amount' => booknow_format_price($booking->payment_amount),
            'business_name' => booknow_get_setting('general', 'business_name'),
        );

        $subject = sprintf(
            __('Refund Processed - %s', 'book-now-kre8iv'),
            $variables['booking_reference']
        );

        $message = self::get_template('refund-notification', $variables);

        $result = self::send_email($booking->customer_email, $subject, $message);

        // Log email
        self::log_email($booking->id, 'refund', $booking->customer_email, $subject, $result);

        return $result;
    }

    /**
     * Send email.
     *
     * @param string $to      Recipient email.
     * @param string $subject Email subject.
     * @param string $message Email message.
     * @return bool
     */
    private static function send_email($to, $subject, $message) {
        $email_settings = booknow_get_setting('email');
        
        $from_name = $email_settings['from_name'] ?? get_bloginfo('name');
        $from_email = $email_settings['from_email'] ?? get_option('admin_email');
        $reply_to = $email_settings['reply_to'] ?? $from_email;

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $from_name, $from_email),
            sprintf('Reply-To: %s', $reply_to),
        );

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Get email template.
     *
     * @param string $template_name Template name.
     * @param array  $variables     Template variables.
     * @return string
     */
    private static function get_template($template_name, $variables) {
        $template_file = BOOK_NOW_PLUGIN_DIR . 'includes/email-templates/' . $template_name . '.php';

        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            $content = ob_get_clean();
        } else {
            $content = self::get_default_template($template_name, $variables);
        }

        return self::replace_variables($content, $variables);
    }

    /**
     * Get default email template.
     *
     * @param string $template_name Template name.
     * @param array  $variables     Template variables.
     * @return string
     */
    private static function get_default_template($template_name, $variables) {
        $templates = array(
            'booking-confirmation' => '
                <h2>Booking Confirmed!</h2>
                <p>Dear {customer_name},</p>
                <p>Your booking has been confirmed. Here are the details:</p>
                <ul>
                    <li><strong>Reference Number:</strong> {booking_reference}</li>
                    <li><strong>Service:</strong> {consultation_type}</li>
                    <li><strong>Date:</strong> {booking_date}</li>
                    <li><strong>Time:</strong> {booking_time}</li>
                    <li><strong>Duration:</strong> {duration} minutes</li>
                    <li><strong>Amount:</strong> {amount}</li>
                </ul>
                <p>If you need to cancel, please use this link: <a href="{cancel_url}">Cancel Booking</a></p>
                <p>Thank you,<br>{business_name}</p>
            ',
            'booking-reminder' => '
                <h2>Upcoming Booking Reminder</h2>
                <p>Dear {customer_name},</p>
                <p>This is a reminder about your upcoming booking:</p>
                <ul>
                    <li><strong>Reference Number:</strong> {booking_reference}</li>
                    <li><strong>Service:</strong> {consultation_type}</li>
                    <li><strong>Date:</strong> {booking_date}</li>
                    <li><strong>Time:</strong> {booking_time}</li>
                    <li><strong>Duration:</strong> {duration} minutes</li>
                </ul>
                <p>We look forward to seeing you!</p>
                <p>If you need to cancel, please use this link: <a href="{cancel_url}">Cancel Booking</a></p>
                <p>Thank you,<br>{business_name}</p>
            ',
            'cancellation-notification' => '
                <h2>Booking Cancelled</h2>
                <p>Dear {customer_name},</p>
                <p>Your booking has been cancelled:</p>
                <ul>
                    <li><strong>Reference Number:</strong> {booking_reference}</li>
                    <li><strong>Service:</strong> {consultation_type}</li>
                    <li><strong>Date:</strong> {booking_date}</li>
                    <li><strong>Time:</strong> {booking_time}</li>
                </ul>
                <p>If you did not request this cancellation, please contact us immediately.</p>
                <p>Thank you,<br>{business_name}</p>
            ',
            'admin-new-booking' => '
                <h2>New Booking Received</h2>
                <p>A new booking has been made:</p>
                <ul>
                    <li><strong>Reference:</strong> {booking_reference}</li>
                    <li><strong>Customer:</strong> {customer_name}</li>
                    <li><strong>Email:</strong> {customer_email}</li>
                    <li><strong>Phone:</strong> {customer_phone}</li>
                    <li><strong>Service:</strong> {consultation_type}</li>
                    <li><strong>Date:</strong> {booking_date}</li>
                    <li><strong>Time:</strong> {booking_time}</li>
                    <li><strong>Duration:</strong> {duration} minutes</li>
                    <li><strong>Amount:</strong> {amount}</li>
                </ul>
                <p><strong>Customer Notes:</strong><br>{customer_notes}</p>
                <p><a href="{admin_url}">View in Admin</a></p>
            ',
            'admin-cancellation' => '
                <h2>Booking Cancelled</h2>
                <p>A booking has been cancelled:</p>
                <ul>
                    <li><strong>Reference:</strong> {booking_reference}</li>
                    <li><strong>Customer:</strong> {customer_name} ({customer_email})</li>
                    <li><strong>Service:</strong> {consultation_type}</li>
                    <li><strong>Date:</strong> {booking_date}</li>
                    <li><strong>Time:</strong> {booking_time}</li>
                </ul>
                <p><a href="{admin_url}">View in Admin</a></p>
            ',
            'refund-notification' => '
                <h2>Refund Processed</h2>
                <p>Dear {customer_name},</p>
                <p>A refund has been processed for your booking:</p>
                <ul>
                    <li><strong>Reference Number:</strong> {booking_reference}</li>
                    <li><strong>Service:</strong> {consultation_type}</li>
                    <li><strong>Original Date:</strong> {booking_date}</li>
                    <li><strong>Refund Amount:</strong> {amount}</li>
                </ul>
                <p>The refund will appear in your account within 5-10 business days.</p>
                <p>Thank you,<br>{business_name}</p>
            ',
        );

        $html_wrapper = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    h2 { color: #2271b1; }
                    ul { list-style: none; padding: 0; }
                    ul li { padding: 5px 0; }
                    a { color: #2271b1; }
                </style>
            </head>
            <body>
                %s
            </body>
            </html>
        ';

        $content = $templates[$template_name] ?? '<p>Email template not found.</p>';
        
        return sprintf($html_wrapper, $content);
    }

    /**
     * Replace variables in template.
     *
     * @param string $content   Template content.
     * @param array  $variables Variables to replace.
     * @return string
     */
    private static function replace_variables($content, $variables) {
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }

    /**
     * Get cancel URL.
     *
     * @param string $reference_number Booking reference number.
     * @return string
     */
    private static function get_cancel_url($reference_number) {
        return add_query_arg(
            array(
                'booknow_action' => 'cancel',
                'ref' => $reference_number,
            ),
            home_url()
        );
    }

    /**
     * Log email.
     *
     * @param int    $booking_id Booking ID.
     * @param string $email_type Email type.
     * @param string $recipient  Recipient email.
     * @param string $subject    Email subject.
     * @param bool   $success    Whether email was sent successfully.
     */
    private static function log_email($booking_id, $email_type, $recipient, $subject, $success) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_email_log';

        $wpdb->insert(
            $table,
            array(
                'booking_id' => $booking_id,
                'email_type' => $email_type,
                'recipient_email' => $recipient,
                'subject' => $subject,
                'status' => $success ? 'sent' : 'failed',
                'sent_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
}
