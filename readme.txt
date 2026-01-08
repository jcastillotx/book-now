=== Book Now ===
Contributors: kre8ivtech
Donate link: https://kre8ivtech.com/donate
Tags: booking, appointments, consultation, stripe, calendar, scheduling, payments
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress plugin for consultation booking with Stripe payments and calendar integration.

== Description ==

Book Now is a powerful WordPress plugin that enables businesses to provide a seamless consultation booking experience. Website visitors can browse available consultation types, view real-time availability, and complete bookings with integrated payment processing.

= Key Features =

* **Consultation Type Management** - Create and manage multiple consultation types with custom pricing and duration
* **Booking System** - Complete booking CRUD operations with status tracking
* **Admin Dashboard** - Comprehensive admin interface for managing bookings and settings
* **Stripe Payments** - Secure payment processing with PCI-compliant tokenization
* **Calendar Integration** - Sync with Google Calendar and Microsoft 365/Outlook
* **Email Notifications** - Automated confirmations, reminders, and notifications
* **Availability Management** - Set weekly schedules, specific dates, and time blocking
* **Flexible Shortcodes** - Display booking forms anywhere on your site
* **Responsive Design** - Mobile-friendly interface for all devices

= Perfect For =

* Consultants and coaches
* Healthcare professionals
* Legal professionals
* Financial advisors
* Freelancers and agencies
* Any service-based business

= Payment Processing =

Book Now uses Stripe for secure payment processing. Stripe is a PCI-compliant payment processor trusted by millions of businesses worldwide. All credit card information is handled securely by Stripe - your WordPress site never stores sensitive payment data.

= Calendar Synchronization =

Keep your schedule in sync with your favorite calendar app:

* **Google Calendar** - Two-way sync with your Google Calendar
* **Microsoft 365/Outlook** - Sync with Microsoft Calendar
* **Automatic Updates** - Bookings, cancellations, and reschedules sync automatically

= Shortcodes =

* `[book_now_form]` - Complete booking form wizard
* `[book_now_types]` - Display consultation type cards
* `[book_now_calendar]` - Interactive calendar view
* `[book_now_list]` - List view of available time slots

= Documentation =

Comprehensive documentation is included:

* Installation Guide
* User Guide
* API Integration Guide
* Technical Stack Documentation

= Support =

* GitHub: [Report issues](https://github.com/jcastillotx/book-now/issues)
* Documentation: Check the docs folder in the plugin directory

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Book Now by Kre8iv Tech"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"

= After Installation =

1. Navigate to Book Now > Settings
2. Configure your business information and timezone
3. Set up Stripe payment credentials (test mode for development)
4. Create your first consultation type
5. Set your availability schedule
6. Add the `[book_now_form]` shortcode to a page

For detailed setup instructions, see the INSTALL.md file in the plugin's docs folder.

== Frequently Asked Questions ==

= Do I need a Stripe account? =

Yes, if you want to accept payments. Stripe is required for processing credit card payments. You can create a free account at stripe.com. For free consultations, you can set the price to $0 and skip payment processing.

= Can I use both Google Calendar and Microsoft Calendar? =

Yes! You can connect both calendars simultaneously. The plugin will sync bookings to both calendars.

= Is this GDPR compliant? =

The plugin doesn't set tracking cookies. Customer data is stored in your WordPress database. You should add appropriate privacy policy information to your site explaining how you handle customer data.

= Can customers book multiple appointments at once? =

Currently, customers can book one appointment at a time. After completing a booking, they can return to book additional appointments.

= What payment methods are supported? =

All major credit and debit cards via Stripe (Visa, Mastercard, American Express, Discover, etc.). Stripe also supports 3D Secure authentication for enhanced security.

= Can I customize the styling? =

Yes! You can customize colors and styling through the Settings > Styling page, or add custom CSS for complete control.

= Does it work with page builders? =

Yes! The shortcodes work with all major page builders including Elementor, Divi, Beaver Builder, WPBakery, and others.

= What happens if there's a scheduling conflict? =

The system prevents double-booking automatically. Unavailable time slots are not shown to customers.

= Can I offer deposits instead of full payment? =

Yes! You can set a deposit amount on each consultation type. Customers will pay the deposit to book, with the remainder due later.

= How do refunds work? =

Refunds are processed through the booking details page in the admin area. The refund is processed automatically through Stripe and the customer is notified via email.

== Screenshots ==

1. Admin Dashboard - Overview of bookings and statistics
2. Consultation Types Management - Create and manage service offerings
3. Bookings List - View and manage all appointments
4. Settings Page - Configure general settings, payments, and integrations
5. Frontend Booking Form - Customer-facing booking wizard
6. Consultation Types Grid - Display services with shortcode

== Changelog ==

= 1.0.0 - 2026-01-08 =
* Initial release
* Consultation type management
* Booking system with CRUD operations
* Admin dashboard and menu structure
* Database schema implementation
* Settings management (general, payments, integrations, emails)
* Stripe payment integration
* Google Calendar integration
* Microsoft Calendar integration
* Email notification system
* Availability management (weekly schedules, date overrides)
* Shortcode system (form, types, calendar, list)
* Responsive frontend design
* Security implementation (nonces, capability checks, data sanitization)
* Comprehensive documentation

== Upgrade Notice ==

= 1.0.0 =
Initial release of Book Now. Install and configure to start accepting consultation bookings.

== Privacy Policy ==

Book Now stores the following customer information in your WordPress database:

* Name
* Email address
* Phone number (optional)
* Booking details (date, time, consultation type)
* Payment information (amount, Stripe payment ID - not credit card details)

Credit card information is never stored in your WordPress database. All payment processing is handled securely by Stripe.

The plugin does not set any tracking cookies or share data with third parties except:

* Stripe (for payment processing)
* Google (if Google Calendar integration is enabled)
* Microsoft (if Microsoft Calendar integration is enabled)

You are responsible for adding appropriate privacy policy information to your website explaining how you handle customer data.

== Third-Party Services ==

This plugin integrates with the following third-party services:

= Stripe =
* Purpose: Payment processing
* Privacy Policy: https://stripe.com/privacy
* Terms of Service: https://stripe.com/legal

= Google Calendar API =
* Purpose: Calendar synchronization (optional)
* Privacy Policy: https://policies.google.com/privacy
* Terms of Service: https://policies.google.com/terms

= Microsoft Graph API =
* Purpose: Calendar synchronization (optional)
* Privacy Policy: https://privacy.microsoft.com/
* Terms of Service: https://www.microsoft.com/servicesagreement

== Credits ==

Developed by Kre8iv Tech
Website: https://kre8ivtech.com
GitHub: https://github.com/jcastillotx/book-now
