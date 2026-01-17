# Book Now by Kre8ivTech

A comprehensive, production-ready WordPress plugin for consultation booking with Stripe payments and calendar integration.

## Description

Book Now is a powerful WordPress plugin that enables businesses to provide a seamless consultation booking experience. Website visitors can browse available consultation types, view real-time availability, complete bookings with integrated Stripe payment processing, and automatically sync with Google Calendar and Microsoft 365/Outlook.

**Status: 100% Complete & Production Ready** ✅

## ✨ Features

### ✅ Complete Feature Set (All Phases Implemented)

#### Core Booking System
- ✅ **Consultation Type Management** - Create and manage multiple consultation types with custom pricing, duration, and descriptions
- ✅ **Category System** - Organize consultation types with hierarchical categories (parent/child support)
- ✅ **Booking Management** - Complete booking CRUD operations with status tracking and reference numbers
- ✅ **Availability Rules** - Weekly schedules, specific dates, time blocking, and buffer times
- ✅ **Smart Slot Calculation** - Real-time availability with conflict detection and booking window enforcement

#### Payment Processing
- ✅ **Stripe Integration** - Secure payment processing with Payment Intents API
- ✅ **Deposit Support** - Accept full payments or deposits (fixed amount or percentage)
- ✅ **Refund Processing** - Full and partial refunds through admin interface
- ✅ **Webhook Handling** - Automated payment status updates and dispute management
- ✅ **Test & Live Modes** - Separate keys for testing and production

#### Calendar Synchronization
- ✅ **Google Calendar Sync** - Bidirectional sync with OAuth 2.0 authentication
- ✅ **Microsoft Calendar Sync** - Bidirectional sync with Microsoft 365/Outlook
- ✅ **Automatic Event Creation** - Bookings automatically create calendar events
- ✅ **Busy Time Blocking** - Prevent double-booking by checking calendar availability
- ✅ **Event Updates** - Sync cancellations and modifications

#### Email Notifications
- ✅ **Automated Emails** - Confirmation, reminder, cancellation, and refund notifications
- ✅ **Customizable Templates** - HTML email templates with merge tags
- ✅ **Admin Notifications** - Alert administrators of new bookings
- ✅ **Scheduled Reminders** - Automated reminder emails via WP-Cron
- ✅ **Email Logging** - Track all sent emails for debugging
- ✅ **SMTP Support** - Custom SMTP configuration for reliable delivery

#### Admin Interface
- ✅ **Comprehensive Dashboard** - Overview of bookings, revenue, and statistics
- ✅ **Booking Management** - View, filter, and manage all bookings
- ✅ **Consultation Types** - Full CRUD interface with inline editing
- ✅ **Category Management** - Hierarchical category organization
- ✅ **Availability Settings** - Visual weekly schedule and date blocking
- ✅ **Settings Pages** - General, Payment, Email, and Integration settings
- ✅ **Test Connections** - Built-in tools to test Stripe, Google, and Microsoft integrations

#### Frontend Components
- ✅ **Booking Form Wizard** - Multi-step booking process with validation
- ✅ **Calendar View** - Interactive calendar showing available dates
- ✅ **List View** - Upcoming availability in list format
- ✅ **Consultation Types Grid** - Responsive grid display with filtering
- ✅ **Stripe Elements** - Secure, PCI-compliant payment forms
- ✅ **Mobile Responsive** - Optimized for all device sizes

#### REST API
- ✅ **20+ Endpoints** - Complete REST API for external integrations
- ✅ **Authentication** - Secure API key authentication
- ✅ **CRUD Operations** - Full access to bookings, types, and availability
- ✅ **Webhook Support** - Receive real-time updates

## Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 8.0 or higher
- **MySQL:** 5.7+ or MariaDB 10.3+
- **SSL Certificate:** Required for Stripe payments
- **Composer:** For dependency management

## Technology Stack

- **Backend:** PHP 8.0+, WordPress Plugin API, REST API
- **Frontend:** JavaScript (ES6+), jQuery, HTML5, CSS3
- **Payments:** Stripe PHP SDK, Stripe.js & Elements
- **Calendar APIs:** Google Calendar API, Microsoft Graph API
- **Dependencies:** Composer for PHP, npm for frontend assets

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/jcastillotx/book-now/tags). The latest stable version is **1.3.1**. For a detailed list of changes, please refer to the [CHANGELOG.md](CHANGELOG.md).

## Installation

### Quick Installation

1. **Download the plugin**
   ```bash
   git clone https://github.com/jcastillotx/book-now.git
   cd book-now
   ```

2. **Install dependencies**
   ```bash
   composer install --no-dev
   ```

3. **Upload to WordPress**
   - Upload the `book-now` folder to `/wp-content/plugins/`
   - Or zip the folder and upload via WordPress admin

4. **Activate the plugin**
   - Go to **Plugins** in WordPress admin
   - Find "Book Now" and click **Activate**

5. **Run Setup Wizard**
   - You'll be redirected to the setup wizard
   - Follow the steps to configure your plugin

### Detailed Installation Guide

See [INSTALLATION.md](INSTALLATION.md) for comprehensive setup instructions including:
- Server requirements
- Stripe configuration
- Google Calendar setup
- Microsoft Calendar setup
- Email configuration
- Troubleshooting

## Quick Start Guide

### 1. Configure General Settings

Navigate to **Book Now** > **Settings** > **General**

- **Business Name:** Your company name
- **Timezone:** Your business timezone
- **Currency:** USD, EUR, GBP, etc.
- **Slot Interval:** 15, 30, or 60 minutes
- **Min Booking Notice:** Minimum hours before booking (e.g., 24)
- **Max Booking Advance:** Maximum days in advance (e.g., 90)

### 2. Set Up Stripe Payments

Navigate to **Book Now** > **Settings** > **Payment**

1. Create a Stripe account at [stripe.com](https://stripe.com)
2. Get your API keys from Stripe Dashboard
3. Enter **Test** keys for testing
4. Enter **Live** keys when ready for production
5. Click **Test Connection** to verify

### 3. Configure Calendar Sync (Optional)

**Google Calendar:**
1. Go to **Settings** > **Integrations**
2. Follow the Google Calendar setup guide
3. Authorize your Google account
4. Select the calendar to sync with

**Microsoft Calendar:**
1. Go to **Settings** > **Integrations**
2. Follow the Microsoft Calendar setup guide
3. Authorize your Microsoft account
4. Select the calendar to sync with

### 4. Set Up Email Notifications

Navigate to **Book Now** > **Settings** > **Email**

- **From Name:** Your business name
- **From Email:** Your business email
- **Admin Email:** Where to receive booking notifications
- **Reminder Hours:** Hours before appointment to send reminder (e.g., 24)

### 5. Create Consultation Types

1. Go to **Book Now** > **Consultation Types**
2. Click **Add New**
3. Fill in the details:
   - **Name:** e.g., "30-Minute Strategy Call"
   - **Duration:** 30 minutes
   - **Price:** $99.00
   - **Description:** What's included
   - **Category:** Optional categorization
4. Click **Save**

### 6. Set Your Availability

1. Go to **Book Now** > **Availability**
2. Set your weekly schedule:
   - Check days you're available
   - Set start and end times
   - Add breaks if needed
3. Block specific dates for holidays/vacation
4. Save your availability

### 7. Add Booking Form to Your Site

Add this shortcode to any page or post:

```
[book_now_form]
```

That's it! Your booking system is ready to accept appointments.

## Shortcodes

### Main Booking Form

```
[book_now_form]
```

Complete multi-step booking wizard with payment processing.

**Attributes:**
- `type` - Pre-select consultation type ID
- `category` - Filter by category ID
- `show_types` - Show/hide type selection (default: true)

**Examples:**

```
[book_now_form type="5"]
```

```
[book_now_form category="2"]
```

### Consultation Types Grid

```
[book_now_types]
```

Display available consultation types in a responsive grid.

**Attributes:**
- `category` - Filter by category ID
- `columns` - Number of columns (2, 3, or 4, default: 3)

**Examples:**

```
[book_now_types columns="4"]
```

```
[book_now_types category="1" columns="2"]
```

### Calendar View

```
[book_now_calendar]
```

Interactive calendar showing available dates.

**Attributes:**
- `type` - Show availability for specific consultation type

**Example:**

```
[book_now_calendar type="5"]
```

### List View

```
[book_now_list]
```

List of upcoming available time slots.

**Attributes:**
- `type` - Filter by consultation type
- `days` - Number of days to show (default: 7)
- `limit` - Maximum slots to display (default: 10)

**Example:**

```
[book_now_list type="5" days="14" limit="20"]
```

## REST API

The plugin provides a comprehensive REST API for external integrations.

### Authentication

```
Authorization: Bearer YOUR_API_KEY
```

Generate API keys in **Book Now** > **Settings** > **API**

### Key Endpoints

**Bookings:**
- `GET /wp-json/booknow/v1/bookings` - List bookings
- `POST /wp-json/booknow/v1/bookings` - Create booking
- `GET /wp-json/booknow/v1/bookings/{id}` - Get booking
- `PUT /wp-json/booknow/v1/bookings/{id}` - Update booking
- `DELETE /wp-json/booknow/v1/bookings/{id}` - Delete booking

**Consultation Types:**
- `GET /wp-json/booknow/v1/types` - List types
- `POST /wp-json/booknow/v1/types` - Create type
- `GET /wp-json/booknow/v1/types/{id}` - Get type

**Availability:**
- `GET /wp-json/booknow/v1/availability` - Get available slots
- `POST /wp-json/booknow/v1/availability/rules` - Create rule

See [docs/API_GUIDE.md](docs/API_GUIDE.md) for complete API documentation.

## Database Structure

The plugin creates the following custom tables:

- `wp_booknow_bookings` - Booking records with customer and payment information
- `wp_booknow_consultation_types` - Consultation type definitions
- `wp_booknow_categories` - Hierarchical categories
- `wp_booknow_availability` - Availability rules and schedules
- `wp_booknow_email_log` - Email tracking (optional)

## Developer Documentation

### Helper Functions

```php
// Get settings
$currency = booknow_get_setting('general', 'currency');

// Format price
echo booknow_format_price(100.00); // $100.00

// Format date/time
echo booknow_format_date('2026-01-08'); // January 8, 2026
echo booknow_format_time('14:30:00'); // 2:30 pm

// Generate reference number
$ref = booknow_generate_reference_number(); // BN123ABC45
```

### Model Classes

```php
// Consultation Types
$types = Book_Now_Consultation_Type::get_all(['status' => 'active']);
$type = Book_Now_Consultation_Type::get_by_id(5);

// Bookings
$bookings = Book_Now_Booking::get_all(['status' => 'confirmed']);
$booking = Book_Now_Booking::get_by_reference('BN123ABC45');

// Availability
$slots = Book_Now_Availability::calculate_slots('2026-01-15', 5);

// Notifications
Book_Now_Notifications::send_confirmation($booking_id);
Book_Now_Notifications::send_reminder($booking_id);
```

## Security

The plugin implements multiple security layers:

- ✅ **Nonce Verification** - All forms and AJAX requests
- ✅ **Capability Checks** - User permission verification
- ✅ **SQL Injection Prevention** - Prepared statements
- ✅ **XSS Protection** - Output escaping
- ✅ **Input Sanitization** - All user input cleaned
- ✅ **Webhook Signature Verification** - Stripe webhook validation

## Support & Documentation

### Documentation
- [Installation Guide](INSTALLATION.md) - Complete setup instructions
- [API Guide](docs/API_GUIDE.md) - REST API documentation
- [Help Guide](docs/HELP.md) - Common questions and troubleshooting
- [Technical Stack](docs/TECH_STACK.md) - Architecture overview

### Support Channels
- **GitHub Issues:** [Report bugs or request features](https://github.com/jcastillotx/book-now/issues)
- **Email:** info@kre8ivtech.com
- **Website:** [https://kre8ivtech.com](https://kre8ivtech.com)

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### [1.3.1] - 2026-01-17

#### Fixed
- **Critical Bug:** Corrected a fatal PHP error in the admin bookings list caused by duplicated code.
- **Calendar Timezones:** Added explicit timezones when creating `DateTime` objects for Google and Microsoft calendar integrations to prevent crashes and incorrect time calculations.
- **Calendar Sync Feedback:** Improved user feedback for the manual "Sync Calendar" action to accurately report success or failure for each provider.

#### Changed
- **Error Handling:** Enhanced the booking creation process to show detailed database errors on the frontend for faster debugging.

For a detailed history, see the full [CHANGELOG.md](CHANGELOG.md).

## Credits

Developed by **Kre8iv Tech**

---

**Version:** 1.3.1  
**Requires WordPress:** 6.0+  
**Tested up to:** 6.4  
**PHP:** 8.0+  
**License:** GPLv2 or later  
**Status:** Production Ready ✅
