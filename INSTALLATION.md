# Book Now - Installation & Setup Guide

Complete guide for installing and configuring the Book Now WordPress plugin.

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Composer Dependencies](#composer-dependencies)
4. [Stripe Setup](#stripe-setup)
5. [Google Calendar Setup](#google-calendar-setup)
6. [Microsoft Calendar Setup](#microsoft-calendar-setup)
7. [Email Configuration](#email-configuration)
8. [Testing](#testing)

---

## Requirements

### Server Requirements
- **PHP:** 8.0 or higher
- **WordPress:** 6.0 or higher
- **MySQL:** 5.7 or higher / MariaDB 10.3 or higher
- **HTTPS:** Required for payment processing
- **Composer:** For installing dependencies

### PHP Extensions
- `curl`
- `json`
- `mbstring`
- `openssl`

---

## Installation

### Step 1: Upload Plugin

1. Download the plugin ZIP file or clone from GitHub
2. Upload to `/wp-content/plugins/book-now/`
3. Or install via WordPress admin: Plugins → Add New → Upload Plugin

### Step 2: Install Composer Dependencies

**IMPORTANT:** You must install Composer dependencies before activating the plugin.

```bash
cd /path/to/wordpress/wp-content/plugins/book-now
composer install --no-dev
```

This will install:
- `stripe/stripe-php` - Stripe payment processing
- `google/apiclient` - Google Calendar integration
- `microsoft/microsoft-graph` - Microsoft Calendar integration

### Step 3: Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "Book Now" in the list
3. Click "Activate"

### Step 4: Run Setup Wizard

After activation, you'll be redirected to the setup wizard. Complete all steps:

1. **Account Type** - Choose single provider or team
2. **Business Info** - Enter business name, timezone, currency
3. **Payment Setup** - Configure Stripe (can skip and do later)
4. **Availability** - Set your weekly schedule
5. **First Service** - Create your first consultation type

---

## Composer Dependencies

### Installing Dependencies

```bash
# Production (recommended)
composer install --no-dev --optimize-autoloader

# Development (includes testing tools)
composer install
```

### Updating Dependencies

```bash
composer update
```

### Verifying Installation

Check that the `vendor/` directory exists with these folders:
- `vendor/stripe/`
- `vendor/google/`
- `vendor/microsoft/`

---

## Stripe Setup

### Step 1: Create Stripe Account

1. Go to [https://stripe.com](https://stripe.com)
2. Sign up for an account
3. Complete business verification

### Step 2: Get API Keys

1. Log in to Stripe Dashboard
2. Go to Developers → API Keys
3. Copy your keys:
   - **Test Mode:**
     - Publishable key: `pk_test_...`
     - Secret key: `sk_test_...`
   - **Live Mode:**
     - Publishable key: `pk_live_...`
     - Secret key: `sk_live_...`

### Step 3: Configure in WordPress

1. Go to Book Now → Settings → Payment
2. Enter your Stripe keys:
   - Test Publishable Key
   - Test Secret Key
   - Live Publishable Key (when ready for production)
   - Live Secret Key (when ready for production)
3. Select mode: Test or Live
4. Click "Test Connection" to verify
5. Save settings

### Step 4: Set Up Webhooks

Webhooks allow Stripe to notify your site about payment events.

1. In Stripe Dashboard, go to Developers → Webhooks
2. Click "Add endpoint"
3. Enter endpoint URL:
   ```
   https://yoursite.com/wp-content/plugins/book-now/includes/webhook-handler.php
   ```
4. Select events to listen for:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
   - `charge.dispute.created`
5. Copy the "Signing secret" (starts with `whsec_`)
6. In WordPress: Book Now → Settings → Payment
7. Paste the webhook secret
8. Save settings

### Testing Stripe

1. Use test mode
2. Test card numbers:
   - Success: `4242 4242 4242 4242`
   - Decline: `4000 0000 0000 0002`
   - 3D Secure: `4000 0025 0000 3155`
3. Use any future expiry date
4. Use any 3-digit CVC
5. Use any ZIP code

---

## Google Calendar Setup

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create a new project or select existing
3. Name it "Book Now Calendar"

### Step 2: Enable Calendar API

1. In your project, go to APIs & Services → Library
2. Search for "Google Calendar API"
3. Click on it and click "Enable"

### Step 3: Create OAuth Credentials

1. Go to APIs & Services → Credentials
2. Click "Create Credentials" → "OAuth client ID"
3. Configure consent screen if prompted:
   - User Type: External
   - App name: Book Now
   - Support email: Your email
   - Scopes: Add `../auth/calendar`
4. Application type: Web application
5. Name: Book Now WordPress
6. Authorized redirect URIs:
   ```
   https://yoursite.com/wp-admin/admin.php?page=book-now-settings&tab=integrations&action=google_callback
   ```
7. Click "Create"
8. Copy Client ID and Client Secret

### Step 4: Configure in WordPress

1. Go to Book Now → Settings → Integrations
2. Enable Google Calendar
3. Enter:
   - Client ID
   - Client Secret
4. Save settings
5. Click "Connect to Google Calendar"
6. Authorize the app
7. Select which calendar to use
8. Click "Test Connection"

---

## Microsoft Calendar Setup

### Step 1: Register Azure AD App

1. Go to [Azure Portal](https://portal.azure.com)
2. Navigate to Azure Active Directory → App registrations
3. Click "New registration"
4. Name: Book Now Calendar
5. Supported account types: Accounts in any organizational directory and personal Microsoft accounts
6. Redirect URI:
   - Platform: Web
   - URI: `https://yoursite.com/wp-admin/admin.php?page=book-now-settings&tab=integrations&action=microsoft_callback`
7. Click "Register"

### Step 2: Configure API Permissions

1. In your app, go to API permissions
2. Click "Add a permission"
3. Select "Microsoft Graph"
4. Select "Delegated permissions"
5. Add these permissions:
   - `Calendars.ReadWrite`
   - `offline_access`
6. Click "Add permissions"
7. Click "Grant admin consent" (if you're admin)

### Step 3: Create Client Secret

1. Go to Certificates & secrets
2. Click "New client secret"
3. Description: Book Now
4. Expires: 24 months (or as needed)
5. Click "Add"
6. **IMPORTANT:** Copy the secret value immediately (you can't see it again)

### Step 4: Get Application ID

1. Go to Overview
2. Copy the "Application (client) ID"

### Step 5: Configure in WordPress

1. Go to Book Now → Settings → Integrations
2. Enable Microsoft Calendar
3. Enter:
   - Client ID (Application ID)
   - Client Secret
4. Save settings
5. Click "Connect to Microsoft Calendar"
6. Sign in with Microsoft account
7. Grant permissions
8. Click "Test Connection"

---

## Email Configuration

### Step 1: Configure Email Settings

1. Go to Book Now → Settings → Email
2. Configure:
   - **From Name:** Your business name
   - **From Email:** noreply@yourdomain.com
   - **Reply-To Email:** support@yourdomain.com
   - **Admin Notifications:** Enable/disable
   - **Admin Email:** Where to receive booking notifications
   - **Reminder Hours:** Hours before appointment to send reminder (default: 24)

### Step 2: Customize Email Templates

Email templates are located in:
```
/wp-content/plugins/book-now/includes/email-templates/
```

Available templates:
- `booking-confirmation.php` - Sent when booking is confirmed
- `booking-reminder.php` - Sent before appointment
- `cancellation-notification.php` - Sent when booking is cancelled
- `refund-notification.php` - Sent when refund is processed
- `admin-new-booking.php` - Sent to admin for new bookings
- `admin-cancellation.php` - Sent to admin for cancellations

To customize:
1. Copy template to your theme:
   ```
   /wp-content/themes/your-theme/book-now/email-templates/
   ```
2. Edit the copied file
3. Plugin will use your custom template

### Step 3: Test Email Delivery

1. Go to Book Now → Settings → Email
2. Click "Send Test Email"
3. Check your inbox
4. If not received, check:
   - WordPress email settings
   - Server email configuration
   - Spam folder
   - Consider using SMTP plugin (WP Mail SMTP, etc.)

### Recommended: Use SMTP

For reliable email delivery, use an SMTP service:

1. Install WP Mail SMTP plugin
2. Configure with:
   - SendGrid
   - Mailgun
   - Amazon SES
   - Gmail SMTP
3. Test email delivery

---

## Testing

### Test Checklist

- [ ] Plugin activates without errors
- [ ] Database tables created
- [ ] Setup wizard completes
- [ ] Can create consultation types
- [ ] Can set availability
- [ ] Booking form displays on frontend
- [ ] Can select date and time
- [ ] Stripe payment processes (test mode)
- [ ] Confirmation email received
- [ ] Admin notification received
- [ ] Booking appears in admin
- [ ] Google Calendar event created (if enabled)
- [ ] Microsoft Calendar event created (if enabled)
- [ ] Can cancel booking
- [ ] Refund processes correctly
- [ ] Reminder emails sent

### Test Booking Flow

1. Add shortcode to a page:
   ```
   [book_now_form]
   ```
2. Visit the page
3. Select a consultation type
4. Choose date and time
5. Enter customer details
6. Complete payment (use test card)
7. Verify confirmation email
8. Check admin for booking
9. Check calendar for event

### Debug Mode

Enable WordPress debug mode for troubleshooting:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `/wp-content/debug.log`

---

## Troubleshooting

### Composer Dependencies Not Found

**Error:** "Class 'Stripe\Stripe' not found"

**Solution:**
```bash
cd /path/to/plugin
composer install --no-dev
```

### Stripe Connection Failed

**Possible causes:**
- Wrong API keys
- Keys from different modes (test vs live)
- Network/firewall blocking Stripe API

**Solution:**
- Verify keys in Stripe Dashboard
- Ensure using correct mode
- Test with curl:
  ```bash
  curl https://api.stripe.com/v1/charges -u sk_test_YOUR_KEY:
  ```

### Calendar Not Syncing

**Google Calendar:**
- Verify OAuth credentials
- Check redirect URI matches exactly
- Ensure Calendar API is enabled
- Re-authorize connection

**Microsoft Calendar:**
- Verify app permissions granted
- Check client secret hasn't expired
- Ensure redirect URI matches exactly
- Re-authorize connection

### Emails Not Sending

**Solutions:**
- Install WP Mail SMTP plugin
- Check server email configuration
- Verify from email is valid
- Check spam folder
- Test with different email provider

### Webhook Not Working

**Solutions:**
- Verify webhook URL is accessible
- Check webhook secret is correct
- Ensure HTTPS is enabled
- Check server logs for errors
- Test webhook in Stripe Dashboard

---

## Support

For issues or questions:

1. Check documentation: `/docs/` folder
2. Review error logs
3. Visit GitHub issues
4. Contact support: support@kre8ivtech.com

---

## Next Steps

After installation:

1. ✅ Complete setup wizard
2. ✅ Configure Stripe
3. ✅ Set up calendar integration
4. ✅ Configure email settings
5. ✅ Create consultation types
6. ✅ Set availability
7. ✅ Test booking flow
8. ✅ Add booking form to website
9. ✅ Go live!

---

**Version:** 1.0.0  
**Last Updated:** 2024-01-08
