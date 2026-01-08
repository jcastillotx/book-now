# Book Now by Kre8iv Tech

A comprehensive WordPress plugin for consultation booking with Stripe payments and calendar integration.

## Description

Book Now is a powerful WordPress plugin that enables businesses to provide a seamless consultation booking experience. Website visitors can browse available consultation types, view real-time availability, and complete bookings with integrated payment processing.

## Features

### Current Features (Phase 1 - Foundation)

- ✅ **Consultation Type Management** - Create and manage multiple consultation types with custom pricing and duration
- ✅ **Booking System** - Complete booking CRUD operations with status tracking
- ✅ **Admin Dashboard** - Comprehensive admin interface for managing bookings and settings
- ✅ **Database Schema** - Robust database structure for bookings, consultation types, and availability
- ✅ **Shortcodes** - Flexible shortcode system for displaying booking forms
- ✅ **Settings Management** - Configure general settings, timezone, currency, and booking rules

### Coming Soon

- 🔄 **Availability Rules** (Phase 2) - Weekly schedules, specific dates, and time blocking
- 🔄 **Calendar Views** (Phase 3) - Interactive calendar and list views
- 🔄 **Stripe Payments** (Phase 4) - Secure payment processing with Stripe integration
- 🔄 **Calendar Sync** (Phase 5) - Bidirectional sync with Google Calendar and Microsoft 365/Outlook
- 🔄 **Email Notifications** (Phase 6) - Automated confirmations, reminders, and notifications

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- SSL Certificate (required for Stripe payments)

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `book-now-kre8iv` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Book Now** > **Settings** to configure the plugin

### WordPress.org Installation (Coming Soon)

1. Navigate to **Plugins** > **Add New** in your WordPress admin
2. Search for "Book Now by Kre8iv Tech"
3. Click **Install Now** and then **Activate**

## Quick Start

### 1. Configure General Settings

Navigate to **Book Now** > **Settings** and configure:

- Business Name
- Timezone
- Currency
- Slot Interval
- Booking Notice Periods

### 2. Create Consultation Types

1. Go to **Book Now** > **Consultation Types**
2. Click **Add New**
3. Enter:
   - Name (e.g., "30-Minute Strategy Call")
   - Duration (in minutes)
   - Price
   - Description
4. Click **Save**

### 3. Add Booking Form to a Page

Use any of these shortcodes in your pages or posts:

```
[book_now_form]
```

Displays the complete booking form wizard.

```
[book_now_types]
```

Displays a grid of available consultation types.

```
[book_now_types category="1" columns="3"]
```

Displays consultation types for a specific category in a 3-column grid.

## Shortcodes

### Main Booking Form

```
[book_now_form]
```

**Attributes:**
- `type` - Pre-select a specific consultation type ID
- `category` - Filter by category ID
- `show_types` - Show/hide type selection (default: true)

**Examples:**

```
[book_now_form type="5"]
```
Direct booking for consultation type ID 5

```
[book_now_form category="2" show_types="true"]
```
Show only types from category 2

### Consultation Types Grid

```
[book_now_types]
```

**Attributes:**
- `category` - Filter by category ID
- `columns` - Number of columns (2, 3, or 4)

**Example:**

```
[book_now_types category="1" columns="3"]
```

### Calendar View (Coming in Phase 3)

```
[book_now_calendar type="5"]
```

### List View (Coming in Phase 3)

```
[book_now_list days="7" limit="10"]
```

## Development Roadmap

### ✅ Phase 1: Foundation (Complete)
- Plugin scaffolding and file structure
- Database schema implementation
- Basic admin menu and settings pages
- Consultation type CRUD operations
- Category management

### 🔄 Phase 2: Core Booking Engine (In Progress)
- Availability rules system
- Slot calculation algorithm
- Conflict detection
- REST API endpoints

### 📋 Phase 3: Frontend Components
- Form wizard component
- Calendar view component
- List view component
- Responsive styling

### 📋 Phase 4: Payment Integration
- Stripe integration
- Payment Intent flow
- Webhook handling
- Refund processing

### 📋 Phase 5: Calendar Sync
- Google Calendar OAuth
- Microsoft Calendar OAuth
- Bidirectional synchronization
- Busy time blocking

### 📋 Phase 6: Notifications
- Email template system
- Confirmation emails
- Reminder scheduling
- Admin notifications

### 📋 Phase 7: Polish & Testing
- Comprehensive testing
- Security audit
- Performance optimization
- Documentation

### 📋 Phase 8: Launch
- Beta testing
- WordPress.org submission
- Marketing materials

## Database Structure

The plugin creates the following custom tables:

- `wp_booknow_bookings` - Stores booking records
- `wp_booknow_consultation_types` - Consultation type definitions
- `wp_booknow_availability` - Availability rules and schedules
- `wp_booknow_categories` - Categories for organizing consultation types
- `wp_booknow_email_log` - Email tracking (optional)

## Developer Documentation

### Hooks

**Actions:**

- `booknow_booking_created` - Fired after a booking is created
- `booknow_booking_updated` - Fired after a booking is updated
- `booknow_before_booking_deleted` - Fired before a booking is deleted

### Helper Functions

```php
// Get settings
booknow_get_setting('general', 'currency');

// Format price
booknow_format_price(100.00); // Returns $100.00

// Format date/time
booknow_format_date('2026-01-08'); // Returns January 8, 2026
booknow_format_time('14:30:00'); // Returns 2:30 pm

// Generate reference number
booknow_generate_reference_number(); // Returns BN123ABC45
```

### Model Classes

```php
// Consultation Types
Book_Now_Consultation_Type::get_all($args);
Book_Now_Consultation_Type::get_by_id($id);
Book_Now_Consultation_Type::create($data);
Book_Now_Consultation_Type::update($id, $data);

// Bookings
Book_Now_Booking::get_all($args);
Book_Now_Booking::get_by_id($id);
Book_Now_Booking::create($data);
Book_Now_Booking::update($id, $data);

// Availability
Book_Now_Availability::calculate_slots($date, $type_id);
```

## Support

For support, feature requests, or bug reports:

- GitHub: [https://github.com/jcastillotx/book-now](https://github.com/jcastillotx/book-now)
- Website: [https://kre8ivtech.com](https://kre8ivtech.com)

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`claude/feature-name-sessionid`)
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Changelog

### 1.0.0 - 2026-01-08

**Phase 1: Foundation Complete**

- Initial plugin scaffolding
- Database schema implementation
- Admin dashboard and menu structure
- Consultation type management (CRUD)
- Basic booking system
- Settings management
- Shortcode system foundation
- Helper functions and utilities

## Credits

Developed by Kre8iv Tech

## Screenshots

*(Screenshots will be added as the plugin develops)*

1. Admin Dashboard
2. Consultation Types Management
3. Bookings List
4. Settings Page
5. Frontend Booking Form
6. Consultation Types Grid

---

**Version:** 1.0.0
**Requires WordPress:** 6.0+
**Tested up to:** 6.4
**PHP:** 8.0+
**License:** GPLv2 or later
