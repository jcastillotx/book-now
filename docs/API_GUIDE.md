# Book Now - API Integration Guide

Complete guide for setting up and testing all API integrations.

---

## Table of Contents

1. [Overview](#overview)
2. [Stripe Payment Integration](#stripe-payment-integration)
3. [Google Calendar Integration](#google-calendar-integration)
4. [Microsoft Calendar Integration](#microsoft-calendar-integration)
5. [REST API Reference](#rest-api-reference)
6. [Webhooks](#webhooks)
7. [Test Connection Features](#test-connection-features)
8. [Troubleshooting](#troubleshooting)

---

## Overview

Book Now integrates with three external services:

| Service | Purpose | Required |
|---------|---------|----------|
| Stripe | Payment processing | Yes (for paid consultations) |
| Google Calendar | Calendar sync | Optional |
| Microsoft 365 | Calendar sync | Optional |

Each integration includes a **Test Connection** button to verify proper setup before going live.

---

## Stripe Payment Integration

### Overview

Stripe handles all payment processing using the Payment Intents API for SCA (Strong Customer Authentication) compliance.

### Setup Steps

#### 1. Create Stripe Account

1. Go to [stripe.com](https://stripe.com)
2. Click **Start now**
3. Complete registration
4. Verify your business (for live payments)

#### 2. Retrieve API Keys

1. Log in to [Stripe Dashboard](https://dashboard.stripe.com)
2. Navigate to **Developers > API keys**
3. Copy your keys:

**Test Mode Keys:**
- Publishable key: `pk_test_...`
- Secret key: `sk_test_...`

**Live Mode Keys:**
- Publishable key: `pk_live_...`
- Secret key: `sk_live_...`

#### 3. Configure in Book Now

1. Go to **Book Now > Settings > Payments**
2. Select API Mode (Test or Live)
3. Enter Publishable Key
4. Enter Secret Key
5. Click **Save Settings**

#### 4. Test Connection

Click **Test Connection** button.

**What it tests:**
- API key validity
- Account access
- API version compatibility

**Success Response:**
```
Connection successful!
Account: Your Business Name
Account ID: acct_xxxxx
```

**Failure Response:**
```
Connection failed: Invalid API key provided
```

#### 5. Configure Webhooks

Webhooks notify your site of payment events.

1. In Stripe Dashboard, go to **Developers > Webhooks**
2. Click **Add endpoint**
3. Enter URL:
   ```
   https://yourdomain.com/wp-json/book-now/v1/payment/webhook
   ```
4. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
   - `charge.dispute.created`
5. Click **Add endpoint**
6. Copy **Signing secret** (`whsec_...`)
7. Enter in Book Now settings

### Stripe Test Cards

Use these for testing (any future expiry, any CVC):

| Number | Result |
|--------|--------|
| 4242 4242 4242 4242 | Success |
| 4000 0000 0000 0002 | Declined |
| 4000 0025 0000 3155 | Requires 3D Secure |
| 4000 0000 0000 9995 | Insufficient funds |

### Payment Flow

```
Customer fills form
       ↓
Create Payment Intent (server-side)
       ↓
Stripe Elements collects card
       ↓
Confirm Payment Intent
       ↓
Webhook confirms payment
       ↓
Booking confirmed
```

### API Endpoints Used

| Endpoint | Purpose |
|----------|---------|
| `POST /v1/payment_intents` | Create payment intent |
| `POST /v1/payment_intents/:id/confirm` | Confirm payment |
| `POST /v1/refunds` | Process refunds |
| `GET /v1/balance` | Test connection |

---

## Google Calendar Integration

### Overview

Google Calendar integration allows automatic event creation when bookings are made, and can read busy times for availability calculation.

### Setup Steps

#### 1. Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Click project dropdown > **New Project**
3. Name: "Book Now Integration"
4. Click **Create**

#### 2. Enable Calendar API

1. Go to **APIs & Services > Library**
2. Search "Google Calendar API"
3. Click **Enable**

#### 3. Configure OAuth Consent Screen

1. Go to **APIs & Services > OAuth consent screen**
2. User Type: **External**
3. Fill required fields:
   - App name: Your business name
   - User support email: Your email
   - Developer contact: Your email
4. Add scopes:
   - `https://www.googleapis.com/auth/calendar`
   - `https://www.googleapis.com/auth/calendar.events`
5. Add test users (your email) during testing
6. Save

#### 4. Create OAuth Credentials

1. Go to **APIs & Services > Credentials**
2. Click **Create Credentials > OAuth client ID**
3. Application type: **Web application**
4. Name: "Book Now"
5. Authorized redirect URIs:
   ```
   https://yourdomain.com/wp-admin/admin.php?page=booknow-settings&tab=integrations&action=google-callback
   ```
6. Click **Create**
7. Copy **Client ID** and **Client Secret**

#### 5. Configure in Book Now

1. Go to **Book Now > Settings > Integrations**
2. In Google Calendar section:
   - Enter Client ID
   - Enter Client Secret
3. Click **Save Settings**
4. Click **Connect Google Calendar**
5. Sign in and authorize
6. Select calendar from dropdown
7. Click **Save**

#### 6. Test Connection

Click **Test Connection** button.

**What it tests:**
- OAuth token validity
- Calendar access permissions
- Creates and deletes a test event

**Success Response:**
```
Connection successful!
Account: your.email@gmail.com
Calendar: Your Calendar Name
Test event created and deleted successfully
```

**Failure Responses:**
```
Token expired - Click "Reconnect" to refresh
Invalid credentials - Check Client ID/Secret
Calendar not found - Select a different calendar
```

### Required Scopes

| Scope | Purpose |
|-------|---------|
| `calendar` | Full calendar access |
| `calendar.events` | Event CRUD operations |

### API Endpoints Used

| Endpoint | Purpose |
|----------|---------|
| `POST /calendar/v3/calendars/:id/events` | Create event |
| `PUT /calendar/v3/calendars/:id/events/:eventId` | Update event |
| `DELETE /calendar/v3/calendars/:id/events/:eventId` | Delete event |
| `POST /calendar/v3/freeBusy` | Query busy times |
| `GET /calendar/v3/users/me/calendarList` | List calendars |

### Event Format

Events created in Google Calendar:

```
Title: [Type Name] - [Customer Name]
Description:
  Customer: [Name]
  Email: [Email]
  Phone: [Phone]
  Reference: [Booking Ref]
  Notes: [Customer Notes]
Start: [Booking DateTime]
End: [Booking DateTime + Duration]
```

---

## Microsoft Calendar Integration

### Overview

Microsoft Calendar integration syncs with Outlook/Microsoft 365 calendars via the Microsoft Graph API.

### Setup Steps

#### 1. Register Azure AD Application

1. Go to [Azure Portal](https://portal.azure.com)
2. Search for **App registrations**
3. Click **New registration**
4. Configure:
   - Name: "Book Now Integration"
   - Supported account types: **Accounts in any organizational directory and personal Microsoft accounts**
   - Redirect URI: Web
     ```
     https://yourdomain.com/wp-admin/admin.php?page=booknow-settings&tab=integrations&action=microsoft-callback
     ```
5. Click **Register**
6. Copy **Application (client) ID**

#### 2. Create Client Secret

1. Go to **Certificates & secrets**
2. Click **New client secret**
3. Description: "Book Now"
4. Expiration: Choose (recommend 24 months)
5. Click **Add**
6. **Copy the Value immediately** (only shown once)

#### 3. Configure API Permissions

1. Go to **API permissions**
2. Click **Add a permission**
3. Select **Microsoft Graph**
4. Choose **Delegated permissions**
5. Add:
   - `Calendars.ReadWrite`
   - `User.Read`
6. Click **Add permissions**
7. If admin, click **Grant admin consent**

#### 4. Configure in Book Now

1. Go to **Book Now > Settings > Integrations**
2. In Microsoft Calendar section:
   - Enter Application (Client) ID
   - Enter Client Secret
3. Click **Save Settings**
4. Click **Connect Microsoft Calendar**
5. Sign in with Microsoft account
6. Grant permissions
7. Select calendar from dropdown
8. Click **Save**

#### 5. Test Connection

Click **Test Connection** button.

**What it tests:**
- OAuth token validity
- Graph API access
- Calendar permissions
- Creates and deletes a test event

**Success Response:**
```
Connection successful!
Account: your.email@outlook.com
Calendar: Calendar
Test event created and deleted successfully
```

**Failure Responses:**
```
Token expired - Click "Reconnect" to refresh
Insufficient permissions - Check API permissions
Calendar not accessible - Verify calendar selection
```

### Required Permissions

| Permission | Type | Purpose |
|------------|------|---------|
| Calendars.ReadWrite | Delegated | Calendar access |
| User.Read | Delegated | Get user profile |

### API Endpoints Used

| Endpoint | Purpose |
|----------|---------|
| `POST /me/calendar/events` | Create event |
| `PATCH /me/calendar/events/{id}` | Update event |
| `DELETE /me/calendar/events/{id}` | Delete event |
| `POST /me/calendar/getSchedule` | Free/busy query |
| `GET /me/calendars` | List calendars |

### Event Format

Events created in Microsoft Calendar:

```
Subject: [Type Name] - [Customer Name]
Body:
  Customer: [Name]
  Email: [Email]
  Phone: [Phone]
  Reference: [Booking Ref]
  Notes: [Customer Notes]
Start: [Booking DateTime]
End: [Booking DateTime + Duration]
```

---

## REST API Reference

### Base URL

```
https://yourdomain.com/wp-json/book-now/v1/
```

### Public Endpoints

#### GET /consultation-types

List all active consultation types.

**Request:**
```bash
curl https://yourdomain.com/wp-json/book-now/v1/consultation-types
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Discovery Call",
      "slug": "discovery-call",
      "description": "30-minute introductory call",
      "duration": 30,
      "price": "0.00",
      "currency": "USD",
      "category": {
        "id": 1,
        "name": "Free Consultations"
      }
    }
  ]
}
```

#### GET /availability

Get available time slots.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type_id | int | Yes | Consultation type ID |
| date | string | Yes | Date (YYYY-MM-DD) |

**Request:**
```bash
curl "https://yourdomain.com/wp-json/book-now/v1/availability?type_id=1&date=2026-01-15"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "date": "2026-01-15",
    "slots": [
      {"time": "09:00", "available": true},
      {"time": "09:30", "available": true},
      {"time": "10:00", "available": false},
      {"time": "10:30", "available": true}
    ]
  }
}
```

#### POST /bookings

Create a new booking.

**Request Body:**
```json
{
  "consultation_type_id": 1,
  "date": "2026-01-15",
  "time": "10:00",
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+1234567890",
  "notes": "Looking forward to the call",
  "payment_intent_id": "pi_xxxxx"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "reference": "BN-ABC123",
    "status": "confirmed",
    "booking": {
      "id": 42,
      "date": "2026-01-15",
      "time": "10:00",
      "type": "Discovery Call"
    }
  }
}
```

#### POST /payment/create-intent

Create Stripe Payment Intent.

**Request Body:**
```json
{
  "consultation_type_id": 1,
  "customer_email": "john@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "client_secret": "pi_xxxxx_secret_xxxxx",
    "amount": 15000,
    "currency": "usd"
  }
}
```

### Admin Endpoints

Require authentication with `manage_options` capability.

#### POST /admin/test-stripe

Test Stripe connection.

**Request:**
```bash
curl -X POST \
  -H "X-WP-Nonce: your-nonce" \
  https://yourdomain.com/wp-json/book-now/v1/admin/test-stripe
```

**Response:**
```json
{
  "success": true,
  "data": {
    "account_name": "Your Business",
    "account_id": "acct_xxxxx",
    "mode": "test"
  }
}
```

#### POST /admin/test-google

Test Google Calendar connection.

**Response:**
```json
{
  "success": true,
  "data": {
    "account": "your.email@gmail.com",
    "calendar": "Your Calendar",
    "test_event": "created_and_deleted"
  }
}
```

#### POST /admin/test-microsoft

Test Microsoft Calendar connection.

**Response:**
```json
{
  "success": true,
  "data": {
    "account": "your.email@outlook.com",
    "calendar": "Calendar",
    "test_event": "created_and_deleted"
  }
}
```

---

## Webhooks

### Stripe Webhooks

**Endpoint:**
```
POST /wp-json/book-now/v1/payment/webhook
```

**Events Handled:**

| Event | Action |
|-------|--------|
| `payment_intent.succeeded` | Confirm booking, send emails |
| `payment_intent.payment_failed` | Mark booking failed, notify customer |
| `charge.refunded` | Update booking status |
| `charge.dispute.created` | Alert admin |

**Signature Verification:**
All webhooks are verified using the signing secret.

---

## Test Connection Features

Each integration has a Test Connection button in the admin settings.

### Stripe Test Connection

**Location:** Book Now > Settings > Payments

**What it does:**
1. Validates API keys
2. Retrieves account information
3. Verifies API access

**Implementation:**
```php
// Admin AJAX handler
function booknow_test_stripe_connection() {
    check_ajax_referer('booknow_admin', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $settings = get_option('booknow_payment_settings');
    $secret_key = booknow_decrypt($settings['secret_key']);

    try {
        \Stripe\Stripe::setApiKey($secret_key);
        $account = \Stripe\Account::retrieve();

        wp_send_json_success([
            'account_name' => $account->business_profile->name ?? 'N/A',
            'account_id' => $account->id,
            'mode' => strpos($secret_key, 'test') !== false ? 'test' : 'live'
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        wp_send_json_error($e->getMessage());
    }
}
```

### Google Calendar Test Connection

**Location:** Book Now > Settings > Integrations

**What it does:**
1. Validates OAuth tokens
2. Refreshes token if expired
3. Creates test event
4. Deletes test event
5. Verifies calendar access

**Implementation:**
```php
function booknow_test_google_connection() {
    check_ajax_referer('booknow_admin', 'nonce');

    $client = booknow_get_google_client();

    if (!$client->getAccessToken()) {
        wp_send_json_error('Not connected');
    }

    try {
        $service = new Google_Service_Calendar($client);
        $calendar_id = get_option('booknow_google_calendar_id');

        // Get user info
        $oauth2 = new Google_Service_Oauth2($client);
        $user_info = $oauth2->userinfo->get();

        // Create test event
        $event = new Google_Service_Calendar_Event([
            'summary' => 'Book Now Test Event',
            'start' => ['dateTime' => date('c')],
            'end' => ['dateTime' => date('c', strtotime('+30 minutes'))]
        ]);

        $created = $service->events->insert($calendar_id, $event);
        $service->events->delete($calendar_id, $created->id);

        wp_send_json_success([
            'account' => $user_info->email,
            'calendar' => $calendar_id,
            'test_event' => 'created_and_deleted'
        ]);
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
```

### Microsoft Calendar Test Connection

**Location:** Book Now > Settings > Integrations

**What it does:**
1. Validates OAuth tokens
2. Refreshes token if expired
3. Creates test event
4. Deletes test event
5. Verifies permissions

**Implementation:**
```php
function booknow_test_microsoft_connection() {
    check_ajax_referer('booknow_admin', 'nonce');

    $graph = booknow_get_graph_client();

    if (!$graph) {
        wp_send_json_error('Not connected');
    }

    try {
        // Get user info
        $user = $graph->createRequest('GET', '/me')
            ->setReturnType(Model\User::class)
            ->execute();

        $calendar_id = get_option('booknow_microsoft_calendar_id');

        // Create test event
        $event = [
            'subject' => 'Book Now Test Event',
            'start' => [
                'dateTime' => date('c'),
                'timeZone' => 'UTC'
            ],
            'end' => [
                'dateTime' => date('c', strtotime('+30 minutes')),
                'timeZone' => 'UTC'
            ]
        ];

        $created = $graph->createRequest('POST', "/me/calendars/{$calendar_id}/events")
            ->attachBody($event)
            ->execute();

        $graph->createRequest('DELETE', "/me/calendar/events/{$created['id']}")
            ->execute();

        wp_send_json_success([
            'account' => $user->getMail() ?: $user->getUserPrincipalName(),
            'calendar' => $calendar_id,
            'test_event' => 'created_and_deleted'
        ]);
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
```

### Test Connection UI

Each test button shows:
- Loading spinner during test
- Green checkmark on success
- Red X with error message on failure
- Detailed response information

**JavaScript Implementation:**
```javascript
jQuery('.booknow-test-connection').on('click', function() {
    const $btn = jQuery(this);
    const $result = $btn.siblings('.connection-result');
    const service = $btn.data('service');

    $btn.prop('disabled', true);
    $result.html('<span class="spinner is-active"></span>');

    wp.ajax.post('booknow_test_' + service, {
        nonce: booknowAdmin.nonce
    })
    .done(function(response) {
        $result.html(
            '<span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' +
            'Connection successful!<br>' +
            '<small>Account: ' + response.account + '</small>'
        );
    })
    .fail(function(error) {
        $result.html(
            '<span class="dashicons dashicons-dismiss" style="color: red;"></span> ' +
            'Connection failed: ' + error
        );
    })
    .always(function() {
        $btn.prop('disabled', false);
    });
});
```

---

## Troubleshooting

### Stripe Issues

| Error | Cause | Solution |
|-------|-------|----------|
| Invalid API key | Wrong key entered | Copy key again from Stripe |
| Account not verified | New Stripe account | Complete Stripe verification |
| Webhook signature invalid | Wrong secret | Copy signing secret again |
| Currency not supported | Account limitation | Check Stripe account settings |

### Google Calendar Issues

| Error | Cause | Solution |
|-------|-------|----------|
| Invalid credentials | Wrong Client ID/Secret | Regenerate credentials |
| Token expired | OAuth token needs refresh | Click Reconnect |
| Calendar API not enabled | API disabled | Enable in Google Cloud Console |
| Redirect URI mismatch | Wrong callback URL | Update OAuth settings |

### Microsoft Calendar Issues

| Error | Cause | Solution |
|-------|-------|----------|
| AADSTS50011 | Wrong redirect URI | Update in Azure Portal |
| Insufficient privileges | Missing permissions | Add required permissions |
| Token expired | OAuth token needs refresh | Click Reconnect |
| Admin consent required | Org policy | Contact Azure admin |

### General Debugging

**Enable debug logging:**
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('BOOKNOW_DEBUG', true);
```

**Check logs:**
```bash
tail -f /path/to/wordpress/wp-content/debug.log
```

---

## Security Notes

### API Key Storage

All API keys and secrets are:
- Encrypted using WordPress salts
- Stored in wp_options
- Never exposed in frontend
- Masked in admin display

### OAuth Token Storage

OAuth tokens are:
- Encrypted before storage
- Refreshed automatically
- Invalidated on disconnect

### Webhook Security

- Stripe: Signature verification with signing secret
- All endpoints: Nonce verification for admin actions
- Capability checks on all admin endpoints

---

**Document Version:** 1.0
**Last Updated:** 2026-01-08
