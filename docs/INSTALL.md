# Book Now - Installation Guide

Complete installation and setup guide for the Book Now WordPress plugin.

---

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Initial Configuration](#initial-configuration)
4. [Stripe Payment Setup](#stripe-payment-setup)
5. [Google Calendar Setup](#google-calendar-setup)
6. [Microsoft Calendar Setup](#microsoft-calendar-setup)
7. [Creating Your First Consultation Type](#creating-your-first-consultation-type)
8. [Setting Up Availability](#setting-up-availability)
9. [Adding Shortcodes to Pages](#adding-shortcodes-to-pages)
10. [Testing Your Setup](#testing-your-setup)
11. [Going Live Checklist](#going-live-checklist)
12. [Troubleshooting](#troubleshooting)

---

## Requirements

Before installing, ensure your environment meets these requirements:

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| WordPress | 6.0 | Latest |
| PHP | 8.0 | 8.1+ |
| MySQL | 5.7 | 8.0 |
| Memory Limit | 128MB | 256MB |
| SSL Certificate | Required | Required |

### Required PHP Extensions
- cURL
- JSON
- OpenSSL
- mbstring

### Check Your PHP Version
1. Go to **Tools > Site Health** in WordPress admin
2. Click **Info** tab
3. Expand **Server** section
4. Find "PHP version"

---

## Installation

### Method 1: WordPress Admin Upload

1. Download the plugin ZIP file
2. Go to **Plugins > Add New** in WordPress admin
3. Click **Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Click **Activate Plugin**

### Method 2: FTP/File Manager

1. Extract the plugin ZIP file
2. Upload the `book-now-kre8iv` folder to `/wp-content/plugins/`
3. Go to **Plugins** in WordPress admin
4. Find "Book Now by Kre8iv Tech" and click **Activate**

### Method 3: WP-CLI

```bash
wp plugin install /path/to/book-now-kre8iv.zip --activate
```

### Post-Installation

After activation:
- Database tables are created automatically
- Default settings are configured
- Admin menu "Book Now" appears in sidebar

---

## Initial Configuration

### Step 1: Access Settings

1. Go to **Book Now > Settings** in WordPress admin
2. You'll see tabs for different settings areas

### Step 2: General Settings

Configure your business basics:

| Setting | Description | Example |
|---------|-------------|---------|
| Business Name | Displayed in emails | "Acme Consulting" |
| Timezone | Your business timezone | "America/New_York" |
| Date Format | How dates are displayed | "F j, Y" (January 1, 2026) |
| Time Format | How times are displayed | "g:i A" (9:00 AM) |
| Currency | Default currency | "USD" |
| Booking Page | Page with booking form | Select from dropdown |

### Step 3: Email Settings

Configure notification emails:

| Setting | Description |
|---------|-------------|
| From Name | Sender name for emails |
| From Email | Sender email address |
| Reply-To | Where replies go |

---

## Stripe Payment Setup

### Create a Stripe Account

1. Go to [stripe.com](https://stripe.com)
2. Click **Start now** and create account
3. Complete business verification

### Get API Keys

1. Log in to [Stripe Dashboard](https://dashboard.stripe.com)
2. Click **Developers** in sidebar
3. Click **API keys**
4. You'll see:
   - **Publishable key** (starts with `pk_`)
   - **Secret key** (starts with `sk_`)

> **Important:** Use **Test keys** during setup, **Live keys** for production.

### Configure in Book Now

1. Go to **Book Now > Settings > Payments**
2. Enter your keys:

| Field | Value |
|-------|-------|
| API Mode | Test (for setup) or Live |
| Publishable Key | `pk_test_...` or `pk_live_...` |
| Secret Key | `sk_test_...` or `sk_live_...` |

3. Click **Save Settings**
4. Click **Test Connection** button

### Expected Result
- Green checkmark: "Connection successful!"
- Shows Stripe account name

### Set Up Webhooks

1. In Stripe Dashboard, go to **Developers > Webhooks**
2. Click **Add endpoint**
3. Enter your webhook URL:
   ```
   https://yourdomain.com/wp-json/book-now/v1/payment/webhook
   ```
4. Select events to listen for:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
   - `charge.dispute.created`
5. Click **Add endpoint**
6. Copy the **Signing secret** (starts with `whsec_`)
7. Enter in **Book Now > Settings > Payments > Webhook Secret**

---

## Google Calendar Setup

### Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Click **Select a project** > **New Project**
3. Name it (e.g., "Book Now Integration")
4. Click **Create**

### Enable Calendar API

1. Go to **APIs & Services > Library**
2. Search for "Google Calendar API"
3. Click on it and click **Enable**

### Create OAuth Credentials

1. Go to **APIs & Services > Credentials**
2. Click **Create Credentials > OAuth client ID**
3. If prompted, configure consent screen first:
   - User Type: External
   - App name: Your business name
   - Support email: Your email
   - Scopes: Add `calendar` and `calendar.events`
4. Application type: **Web application**
5. Name: "Book Now"
6. Authorized redirect URIs:
   ```
   https://yourdomain.com/wp-admin/admin.php?page=booknow-settings&tab=integrations
   ```
7. Click **Create**
8. Copy **Client ID** and **Client Secret**

### Configure in Book Now

1. Go to **Book Now > Settings > Integrations**
2. In Google Calendar section:

| Field | Value |
|-------|-------|
| Client ID | Your OAuth client ID |
| Client Secret | Your OAuth client secret |

3. Click **Save Settings**
4. Click **Connect Google Calendar**
5. Sign in with your Google account
6. Grant calendar permissions
7. Select which calendar to sync with
8. Click **Test Connection** to verify

### Expected Result
- Shows connected Google account
- Calendar dropdown populated
- Test creates a test event (then deletes it)

---

## Microsoft Calendar Setup

### Register Azure AD Application

1. Go to [Azure Portal](https://portal.azure.com)
2. Search for "App registrations"
3. Click **New registration**
4. Configure:

| Field | Value |
|-------|-------|
| Name | Book Now Integration |
| Supported account types | Accounts in any organizational directory and personal Microsoft accounts |
| Redirect URI | Web: `https://yourdomain.com/wp-admin/admin.php?page=booknow-settings&tab=integrations` |

5. Click **Register**
6. Copy **Application (client) ID**

### Create Client Secret

1. Go to **Certificates & secrets**
2. Click **New client secret**
3. Description: "Book Now"
4. Expiration: Choose duration
5. Click **Add**
6. **Copy the Value immediately** (shown only once)

### Configure API Permissions

1. Go to **API permissions**
2. Click **Add a permission**
3. Choose **Microsoft Graph**
4. Select **Delegated permissions**
5. Add:
   - `Calendars.ReadWrite`
   - `User.Read`
6. Click **Add permissions**
7. Click **Grant admin consent** (if you're admin)

### Configure in Book Now

1. Go to **Book Now > Settings > Integrations**
2. In Microsoft Calendar section:

| Field | Value |
|-------|-------|
| Application ID | Your Azure client ID |
| Client Secret | Your Azure client secret |

3. Click **Save Settings**
4. Click **Connect Microsoft Calendar**
5. Sign in with your Microsoft account
6. Grant permissions
7. Select calendar
8. Click **Test Connection**

---

## Creating Your First Consultation Type

### Step 1: Add New Type

1. Go to **Book Now > Consultation Types**
2. Click **Add New**

### Step 2: Configure Details

| Field | Example | Notes |
|-------|---------|-------|
| Name | Strategy Session | Displayed to customers |
| Slug | strategy-session | Auto-generated, URL-safe |
| Description | 60-minute strategy session... | Supports formatting |
| Duration | 60 | In minutes |
| Price | 150.00 | In your currency |
| Deposit | 50.00 | Optional, leave blank for full payment |

### Step 3: Advanced Settings

| Setting | Purpose |
|---------|---------|
| Buffer Before | Minutes before appointment to block |
| Buffer After | Minutes after appointment to block |
| Max Advance Days | How far ahead can book |
| Min Lead Time | Minimum hours notice required |
| Category | Group similar types |

### Step 4: Save

Click **Publish** or **Save**

---

## Setting Up Availability

### Weekly Schedule

1. Go to **Book Now > Availability**
2. For each day:
   - Toggle day on/off
   - Set start time
   - Set end time
   - Add breaks (lunch, etc.)

**Example Schedule:**
| Day | Hours |
|-----|-------|
| Monday | 9:00 AM - 5:00 PM |
| Tuesday | 9:00 AM - 5:00 PM |
| Wednesday | 9:00 AM - 12:00 PM |
| Thursday | 9:00 AM - 5:00 PM |
| Friday | 9:00 AM - 3:00 PM |
| Saturday | Off |
| Sunday | Off |

### Block Specific Dates

To mark dates as unavailable (holidays, vacations):

1. Go to **Availability > Date Overrides**
2. Click **Add Date Block**
3. Select date or date range
4. Save

### Custom Date Hours

To set different hours for specific dates:

1. Click **Add Custom Hours**
2. Select date
3. Set custom start/end times
4. Save

---

## Adding Shortcodes to Pages

### Create Booking Page

1. Go to **Pages > Add New**
2. Title: "Book a Consultation"
3. Add shortcode block or paste:

```
[book_now_form]
```

4. Publish page
5. Set this as your Booking Page in **Book Now > Settings > General**

### Shortcode Options

**Full Booking Form:**
```
[book_now_form]
```

**Specific Consultation Type:**
```
[book_now_form type="strategy-session"]
```

**Filter by Category:**
```
[book_now_form category="coaching"]
```

**Calendar View:**
```
[book_now_calendar]
```

**List View:**
```
[book_now_list days="14"]
```

**Consultation Cards:**
```
[book_now_types columns="3"]
```

---

## Testing Your Setup

### Test Checklist

Use this checklist to verify everything works:

- [ ] **View Consultation Types**
  - Visit booking page
  - Types display correctly with prices

- [ ] **Check Calendar**
  - Available dates show correctly
  - Unavailable dates are blocked

- [ ] **Test Booking Flow**
  - Select a consultation type
  - Pick a date and time
  - Enter test customer details
  - Complete payment (use Stripe test cards)

- [ ] **Verify Payment**
  - Check Stripe Dashboard for test payment
  - Booking shows in Book Now > Bookings

- [ ] **Check Calendar Sync**
  - Event appears in Google/Microsoft Calendar
  - Details are correct

- [ ] **Test Emails**
  - Confirmation email received
  - Admin notification received

### Stripe Test Cards

Use these for testing:

| Card Number | Result |
|-------------|--------|
| 4242 4242 4242 4242 | Success |
| 4000 0000 0000 0002 | Declined |
| 4000 0025 0000 3155 | Requires 3D Secure |

All test cards use:
- Any future expiry date
- Any 3-digit CVC
- Any postal code

---

## Going Live Checklist

Before accepting real bookings:

### Stripe
- [ ] Switch API Mode to "Live"
- [ ] Enter live Publishable Key
- [ ] Enter live Secret Key
- [ ] Update webhook to use live endpoint
- [ ] Enter live Webhook Secret
- [ ] **Test Connection** shows success

### Calendar
- [ ] Connected to production calendar
- [ ] Test event created successfully
- [ ] Sync direction configured correctly

### Content
- [ ] All consultation types reviewed
- [ ] Prices are correct
- [ ] Descriptions are complete
- [ ] Availability schedule is accurate

### Legal
- [ ] Terms and conditions page linked
- [ ] Privacy policy page linked
- [ ] Refund policy clear

### Testing
- [ ] Complete one real test booking
- [ ] Process one real test refund
- [ ] Verify all emails work

---

## Troubleshooting

### Common Issues

#### "Connection Failed" for Stripe
- Verify API keys are correct (no extra spaces)
- Ensure using correct mode (Test vs Live)
- Check SSL certificate is valid

#### "Connection Failed" for Google Calendar
- Verify redirect URI matches exactly
- Check OAuth consent screen is configured
- Ensure Calendar API is enabled

#### "Connection Failed" for Microsoft
- Verify redirect URI matches exactly
- Check API permissions are granted
- Ensure admin consent given (if required)

#### Bookings Not Showing in Calendar
- Check sync is enabled
- Verify calendar is selected
- Test connection works
- Check error logs

#### Emails Not Sending
- Verify From Email is valid
- Check WordPress can send emails
- Try different email plugin (WP Mail SMTP)
- Check spam folder

#### Times Showing Wrong
- Verify timezone in Settings
- Check server timezone
- Ensure customer timezone detection works

### Debug Mode

Enable debug logging:

1. Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('BOOKNOW_DEBUG', true);
```

2. Check `/wp-content/debug.log` for errors

### Getting Help

- Check [FAQ section](HELP.md#faq)
- Review [GitHub Issues](https://github.com/jcastillotx/book-now/issues)
- Contact support with debug log

---

## Uninstallation

### Keep Data
1. Go to **Plugins**
2. Deactivate "Book Now by Kre8iv Tech"
3. Data remains in database for reactivation

### Remove Everything
1. Go to **Book Now > Settings > General**
2. Enable "Delete data on uninstall"
3. Deactivate plugin
4. Delete plugin
5. All data and tables are removed

---

**Need more help?** See the [User Guide](HELP.md) for detailed feature documentation.
