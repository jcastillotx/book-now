# Changelog

All notable changes to Book Now will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-01-08

### Added
- **Setup Wizard** - Complete 6-step onboarding wizard
  - Account type selection (Single Person vs Agency/Team)
  - Business information configuration
  - Payment setup (optional)
  - Availability configuration
  - First service creation
  - Professional UI with modern styling
  - Auto-redirect on first activation
  - Accessible from admin menu
- **Comprehensive Settings Page** - Complete tabbed interface
  - General tab (business info, timezone, currency, booking rules, account type)
  - Payment tab (Stripe test/live mode, API keys, deposit settings)
  - Email tab (sender info, notifications, reminders, admin alerts)
  - Integrations tab (Google Calendar, Microsoft Calendar)
- **Team Members Support** - Database schema for multi-user/agency mode
  - Team members table with calendar integration fields
  - Account type setting (single/agency)
  - Foundation for Phase 2 team features
- **Production Files**
  - composer.json with PHP dependencies
  - package.json with frontend build tools
  - .gitignore for version control
  - .distignore for WordPress.org deployment
  - LICENSE file (GPL v2)
  - Translation template (.pot file)
  - Comprehensive CHANGELOG.md
  - ACTIVATION_GUIDE.md with complete usage instructions

### Changed
- Plugin name simplified from "Book Now by Kre8iv Tech" to "Book Now"
- Settings page now uses tabbed interface instead of single page
- Admin menu now includes Setup Wizard link

### Technical
- All Phase 1 foundation items completed
- Complete admin interface with 7 menu pages
- Full CRUD operations for all entities
- Security hardening (nonces, sanitization, capability checks)
- Model classes with proper data access methods
- Helper functions for common operations
- AJAX handlers for all admin operations

## [1.0.0] - 2026-01-08

### Added
- Initial release of Book Now
- **Consultation Type Management**
  - Create, read, update, delete consultation types
  - Custom pricing and duration settings
  - Deposit amount configuration (fixed or percentage)
  - Buffer time settings (before/after appointments)
  - Category organization
  - Active/inactive status management
  
- **Booking System**
  - Complete booking CRUD operations
  - Reference number generation
  - Multiple booking statuses (pending, confirmed, completed, cancelled, no-show)
  - Customer information storage (name, email, phone, notes)
  - Admin notes functionality
  - Booking date and time management with timezone support
  
- **Admin Dashboard**
  - Today's bookings overview
  - Upcoming bookings display
  - Statistics panel (total, pending, confirmed, completed)
  - Quick access to booking details
  - Integration status indicators
  
- **Database Schema**
  - `booknow_consultation_types` table
  - `booknow_bookings` table
  - `booknow_availability` table
  - `booknow_categories` table
  - `booknow_email_log` table
  - Proper indexes for performance optimization
  
- **Settings Management**
  - General settings (business name, timezone, currency, date/time formats)
  - Payment settings (Stripe integration configuration)
  - Integration settings (Google Calendar, Microsoft Calendar)
  - Email settings (sender information, notification preferences)
  - Styling settings (colors, themes, custom CSS)
  
- **Stripe Payment Integration**
  - Test and live mode support
  - Payment Intent API implementation
  - Webhook handling for payment events
  - Refund processing
  - PCI-compliant tokenization with Stripe.js
  
- **Google Calendar Integration**
  - OAuth 2.0 authentication
  - Automatic event creation for bookings
  - Event updates on reschedule
  - Event deletion on cancellation
  - Free/busy time checking
  
- **Microsoft Calendar Integration**
  - Azure AD OAuth 2.0 authentication
  - Calendar event synchronization
  - Support for personal and work/school accounts
  - Bidirectional sync capabilities
  
- **Availability Management**
  - Weekly schedule configuration
  - Day-specific hours and breaks
  - Date overrides for holidays/vacations
  - Custom hours for specific dates
  - Consultation-specific availability rules
  - Time slot calculation algorithm
  
- **Email Notifications**
  - Booking confirmation emails
  - Reminder emails (configurable hours before)
  - Cancellation notifications
  - Admin notification system
  - Customizable email templates
  - Template variables support
  
- **Shortcode System**
  - `[book_now_form]` - Complete booking wizard
  - `[book_now_types]` - Consultation type cards display
  - `[book_now_calendar]` - Calendar view (placeholder)
  - `[book_now_list]` - List view (placeholder)
  - Shortcode attributes for customization
  
- **Frontend Features**
  - Responsive booking form wizard
  - Multi-step booking process
  - Real-time availability checking
  - Mobile-friendly design
  - Consultation type selection interface
  - Customer information collection
  
- **Security Features**
  - WordPress nonce verification on all forms
  - AJAX referer checking
  - Capability checks for admin functions
  - Data sanitization and validation
  - SQL injection prevention with prepared statements
  - XSS protection with output escaping
  - Encrypted storage for API credentials
  
- **Developer Features**
  - Action hooks for booking events
  - Filter hooks for customization
  - Helper functions for common tasks
  - Model classes for data access
  - REST API foundation
  - Comprehensive inline documentation
  
- **Documentation**
  - Complete user guide (HELP.md)
  - Installation guide (INSTALL.md)
  - API integration guide (API_GUIDE.md)
  - Technical stack documentation (TECH_STACK.md)
  - Project specifications (PROJECT_SPEC.md)
  - Development TODO list (TODO.md)
  
- **Internationalization**
  - Text domain: `book-now-kre8iv`
  - Translation-ready strings
  - POT file for translators
  - Support for RTL languages

### Technical Details
- **Minimum Requirements**
  - WordPress 6.0+
  - PHP 8.0+
  - MySQL 5.7+ or MariaDB 10.3+
  - SSL certificate (for Stripe)
  
- **Dependencies**
  - stripe/stripe-php ^10.0
  - google/apiclient ^2.15
  - microsoft/microsoft-graph ^1.100
  - @stripe/stripe-js ^2.4.0
  
- **Code Quality**
  - WordPress Coding Standards compliant
  - PHPUnit test framework setup
  - ESLint for JavaScript
  - Comprehensive error handling

### Security
- All AJAX endpoints protected with nonces
- Capability checks on all admin operations
- Prepared statements for all database queries
- Output escaping on all user-generated content
- Input sanitization on all form submissions

### Performance
- Conditional asset loading
- Database query optimization with indexes
- Transient caching for availability calculations
- Minified assets for production

---

## [Unreleased]

### Planned Features
- Enhanced calendar views with interactive UI
- Advanced reporting and analytics
- Bulk booking operations
- Customer portal for managing appointments
- SMS notifications via Twilio
- Zoom/Google Meet integration
- Recurring appointment support
- Team member management
- Custom fields for booking forms
- Advanced availability rules
- Payment plan support
- Gift certificates/vouchers
- Multi-language admin interface
- Mobile app API endpoints

---

## Version History

- **1.0.0** (2026-01-08) - Initial release

---

For detailed upgrade instructions and breaking changes, see [INSTALL.md](docs/INSTALL.md).

For support and bug reports, visit [GitHub Issues](https://github.com/jcastillotx/book-now/issues).
