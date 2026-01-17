# Implementation Guide for Remaining Features

This guide provides step-by-step instructions for implementing the remaining features of the Book Now plugin.

---

## Prerequisites

### 1. Install Composer Dependencies

```bash
cd /Users/jlaptop/Documents/GitHub/book-now
composer install
```

This will install:
- `stripe/stripe-php` - For payment processing
- `google/apiclient` - For Google Calendar integration
- `microsoft/microsoft-graph` - For Microsoft Calendar integration

---

## Feature 1: Stripe Payment Integration

### Files to Create:

#### 1. Complete `includes/class-book-now-stripe.php`

**Key Methods Needed:**
- `__construct()` - Initialize Stripe with API keys
- `create_payment_intent($amount, $consultation_type_id)` - Create payment intent
- `confirm_payment($payment_intent_id)` - Confirm payment
- `refund_payment($payment_intent_id, $amount = null)` - Process refund
- `handle_webhook($payload, $signature)` - Handle Stripe webhooks
- `test_connection()` - Test API keys validity

**Implementation Steps:**
1. Load Stripe PHP library
2. Get API keys from settings (test/live mode)
3. Create Stripe client instance
4. Implement payment intent creation
5. Implement webhook signature verification
6. Handle webhook events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
   - `charge.dispute.created`

**Integration Points:**
- REST API already has `/payment/create-intent` endpoint
- REST API already has `/payment/webhook` endpoint
- Booking creation already checks for payment requirement
- Admin already has refund button structure

### 2. Update Frontend for Payment

**File:** `public/js/book-now-public.js`

**Add Stripe Elements:**
```javascript
// After form validation, before booking creation
if (paymentRequired) {
    // Create payment intent via REST API
    // Mount Stripe Elements
    // Confirm payment
    // Then create booking with payment_intent_id
}
```

**Add to HTML:** `public/partials/form-wizard.php`
```html
<!-- Add between Step 3 and Confirmation -->
<div class="booknow-form-step" data-step="4" style="display:none;">
    <h3><?php esc_html_e('Payment', 'book-now-kre8iv'); ?></h3>
    <div id="stripe-card-element"></div>
    <div id="stripe-errors"></div>
</div>
```

---

## Feature 2: Email Notifications

### Files to Create:

#### 1. `includes/class-book-now-notifications.php`

**Key Methods:**
```php
class Book_Now_Notifications {
    public static function send_booking_confirmation($booking)
    public static function send_booking_reminder($booking)
    public static function send_cancellation_notification($booking)
    public static function send_admin_notification($booking)
    public static function send_admin_cancellation_alert($booking)
    public static function send_refund_notification($booking)
    private static function send_email($to, $subject, $message)
    private static function get_template($template_name, $variables)
    private static function replace_variables($content, $variables)
}
```

**Email Variables:**
- `{customer_name}`
- `{booking_reference}`
- `{booking_date}`
- `{booking_time}`
- `{consultation_type}`
- `{business_name}`
- `{amount}`
- `{cancel_url}`

#### 2. `includes/email-templates/` directory

Create template files:
- `booking-confirmation.php`
- `booking-reminder.php`
- `cancellation-notification.php`
- `admin-new-booking.php`
- `admin-cancellation.php`
- `refund-notification.php`

**Template Structure:**
```php
<?php
// Email: Booking Confirmation
// Variables: {customer_name}, {booking_reference}, etc.
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        /* Email styles */
    </style>
</head>
<body>
    <h1>Booking Confirmed!</h1>
    <p>Dear {customer_name},</p>
    <p>Your booking has been confirmed.</p>
    <p><strong>Reference:</strong> {booking_reference}</p>
    <!-- More content -->
</body>
</html>
```

#### 3. Reminder Cron Job

**Add to `includes/class-book-now.php`:**
```php
// In constructor
add_action('booknow_send_reminders', array($this, 'send_booking_reminders'));

// Add method
public function send_booking_reminders() {
    $reminder_hours = booknow_get_setting('email', 'reminder_hours') ?: 24;
    $bookings = Book_Now_Booking::get_upcoming_for_reminders($reminder_hours);
    
    foreach ($bookings as $booking) {
        Book_Now_Notifications::send_booking_reminder($booking);
        Book_Now_Booking::update($booking->id, array(
            'reminder_sent' => 1,
            'reminder_sent_at' => current_time('mysql')
        ));
    }
}
```

**Activate Cron:**
```php
// In activator
if (!wp_next_scheduled('booknow_send_reminders')) {
    wp_schedule_event(time(), 'hourly', 'booknow_send_reminders');
}
```

### Integration Points:

**Update `public/class-book-now-public.php`:**
```php
// In ajax_create_booking(), after booking creation:
if ($booking_id) {
    $booking = Book_Now_Booking::get_by_id($booking_id);
    
    // Send emails
    Book_Now_Notifications::send_booking_confirmation($booking);
    Book_Now_Notifications::send_admin_notification($booking);
    
    // ... rest of code
}
```

**Update `includes/class-book-now-rest-api.php`:**
```php
// In cancel_booking(), after cancellation:
Book_Now_Notifications::send_cancellation_notification($booking);
Book_Now_Notifications::send_admin_cancellation_alert($booking);
```

---

## Feature 3: Google Calendar Integration

### Files to Create:

#### 1. `includes/class-book-now-google-calendar.php`

**Key Methods:**
```php
class Book_Now_Google_Calendar {
    private $client;
    private $service;
    
    public function __construct()
    public function get_auth_url()
    public function handle_oauth_callback($code)
    public function is_connected()
    public function disconnect()
    public function create_event($booking)
    public function update_event($event_id, $booking)
    public function delete_event($event_id)
    public function get_busy_times($date)
    public function test_connection()
}
```

**OAuth Flow:**
1. Admin clicks "Connect Google Calendar"
2. Redirect to Google OAuth consent screen
3. Handle callback with authorization code
4. Exchange code for access token
5. Store tokens securely (encrypted)
6. Implement token refresh

**Event Creation:**
```php
public function create_event($booking) {
    $consultation_type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
    
    $event = new Google_Service_Calendar_Event(array(
        'summary' => $consultation_type->name . ' - ' . $booking->customer_name,
        'description' => 'Booking Reference: ' . $booking->reference_number,
        'start' => array(
            'dateTime' => $booking->booking_date . 'T' . $booking->booking_time,
            'timeZone' => $booking->timezone,
        ),
        'end' => array(
            'dateTime' => // Calculate end time
            'timeZone' => $booking->timezone,
        ),
        'attendees' => array(
            array('email' => $booking->customer_email),
        ),
    ));
    
    $created_event = $this->service->events->insert('primary', $event);
    
    // Update booking with event ID
    Book_Now_Booking::update($booking->id, array(
        'google_event_id' => $created_event->getId()
    ));
    
    return $created_event;
}
```

#### 2. Admin Settings Page for Google Calendar

**Add to settings:**
```php
// OAuth connection button
// Calendar selection dropdown
// Test connection button
// Disconnect button
// Sync status display
```

---

## Feature 4: Microsoft Calendar Integration

### Files to Create:

#### 1. `includes/class-book-now-microsoft-calendar.php`

**Similar structure to Google Calendar:**
```php
class Book_Now_Microsoft_Calendar {
    private $graph;
    
    public function __construct()
    public function get_auth_url()
    public function handle_oauth_callback($code)
    public function is_connected()
    public function disconnect()
    public function create_event($booking)
    public function update_event($event_id, $booking)
    public function delete_event($event_id)
    public function get_busy_times($date)
    public function test_connection()
}
```

**Azure AD Setup Required:**
1. Register app in Azure Portal
2. Get Client ID and Client Secret
3. Set redirect URI
4. Request Calendar.ReadWrite permissions

---

## Feature 5: Enhanced Styling

### Admin CSS (`admin/css/book-now-admin.css`)

**Add:**
```css
/* Dashboard Statistics */
.booknow-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.booknow-stat-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 32px;
    color: #2271b1;
}

/* Status Badges */
.booknow-status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending { background: #f0f0f1; color: #646970; }
.status-confirmed { background: #d5e8d4; color: #2c662d; }
.status-completed { background: #cfe2ff; color: #084298; }
.status-cancelled { background: #f8d7da; color: #842029; }

/* Payment Badges */
.booknow-payment-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.payment-pending { background: #fff3cd; color: #664d03; }
.payment-paid { background: #d1e7dd; color: #0f5132; }
.payment-refunded { background: #f8d7da; color: #842029; }
.payment-failed { background: #f8d7da; color: #842029; }

/* Modal Styles */
.booknow-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.booknow-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 4px;
}

.booknow-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
```

### Public CSS (`public/css/book-now-public.css`)

**Add:**
```css
/* Booking Form Wizard */
.booknow-form-wrapper {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.booknow-form-step {
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Type Selection */
.booknow-type-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.booknow-type-option {
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.booknow-type-option:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.booknow-type-option input[type="radio"]:checked + .type-option-content {
    border-left: 4px solid #2271b1;
    padding-left: 16px;
}

/* Time Slots */
.booknow-time-slots {
    margin: 20px 0;
}

#slots-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
}

.time-slot {
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.time-slot:hover {
    border-color: #2271b1;
    background: #f0f6fc;
}

.time-slot.selected {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

/* Form Navigation */
.booknow-form-nav {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

/* Confirmation */
.booknow-confirmation {
    text-align: center;
    padding: 40px;
}

.booknow-success-icon {
    font-size: 64px;
    color: #00a32a;
    margin-bottom: 20px;
}

/* Consultation Type Cards */
.booknow-types-grid {
    display: grid;
    gap: 20px;
    margin: 30px 0;
}

.booknow-columns-2 { grid-template-columns: repeat(2, 1fr); }
.booknow-columns-3 { grid-template-columns: repeat(3, 1fr); }
.booknow-columns-4 { grid-template-columns: repeat(4, 1fr); }

.booknow-type-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s;
}

.booknow-type-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .booknow-types-grid {
        grid-template-columns: 1fr !important;
    }
    
    .booknow-type-options {
        grid-template-columns: 1fr;
    }
    
    #slots-container {
        grid-template-columns: repeat(3, 1fr);
    }
}
```

---

## Testing Checklist

After implementing each feature:

### Stripe Payment:
- [ ] Test payment intent creation
- [ ] Test successful payment
- [ ] Test failed payment
- [ ] Test refund processing
- [ ] Test webhook handling
- [ ] Test with test cards

### Email Notifications:
- [ ] Test booking confirmation email
- [ ] Test admin notification email
- [ ] Test cancellation email
- [ ] Test reminder email (wait for cron)
- [ ] Test refund notification
- [ ] Verify email formatting

### Google Calendar:
- [ ] Test OAuth connection
- [ ] Test event creation
- [ ] Test event update
- [ ] Test event deletion
- [ ] Test busy time reading
- [ ] Test token refresh

### Microsoft Calendar:
- [ ] Test OAuth connection
- [ ] Test event creation
- [ ] Test event update
- [ ] Test event deletion
- [ ] Test busy time reading
- [ ] Test token refresh

---

## Deployment Steps

1. **Install Dependencies:**
   ```bash
   composer install --no-dev
   ```

2. **Configure API Keys:**
   - Add Stripe keys in settings
   - Set up Google OAuth credentials
   - Set up Microsoft Azure AD app

3. **Test Webhooks:**
   - Use Stripe CLI for local testing
   - Set up webhook endpoints in production

4. **Enable Cron:**
   - Verify WordPress cron is working
   - Test reminder emails

5. **Final Testing:**
   - Complete end-to-end booking flow
   - Test all payment scenarios
   - Verify calendar sync
   - Check email delivery

---

## Support Resources

- **Stripe Documentation:** https://stripe.com/docs/api
- **Google Calendar API:** https://developers.google.com/calendar
- **Microsoft Graph API:** https://docs.microsoft.com/en-us/graph/
- **WordPress Cron:** https://developer.wordpress.org/plugins/cron/

---

*Last Updated: 2026-01-08*
