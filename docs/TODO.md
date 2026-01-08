# Book Now - Development TODO

A comprehensive task list for developing the Book Now WordPress plugin.

**Legend:**
- [ ] Not started
- [x] Completed
- [~] In progress

---

## Phase 1: Foundation

### 1.1 Plugin Scaffolding
- [ ] Create main plugin file `book-now-kre8iv.php` with proper headers
- [ ] Create `uninstall.php` for clean removal
- [ ] Set up directory structure (includes, admin, public, etc.)
- [ ] Create `class-book-now.php` main plugin class
- [ ] Create `class-book-now-loader.php` for hooks/filters
- [ ] Create `class-book-now-activator.php` for activation tasks
- [ ] Create `class-book-now-deactivator.php` for deactivation
- [ ] Create `class-book-now-i18n.php` for internationalization
- [ ] Create `helpers.php` utility functions file
- [ ] Set up autoloading or manual includes

### 1.2 Database Schema
- [ ] Design final database schema
- [ ] Create `booknow_consultation_types` table
- [ ] Create `booknow_categories` table
- [ ] Create `booknow_bookings` table
- [ ] Create `booknow_availability` table
- [ ] Create `booknow_email_log` table
- [ ] Add proper indexes for performance
- [ ] Create database version tracking
- [ ] Implement upgrade/migration system

### 1.3 Admin Menu Structure
- [ ] Register main admin menu "Book Now"
- [ ] Add Dashboard submenu
- [ ] Add Bookings submenu
- [ ] Add Consultation Types submenu
- [ ] Add Categories submenu
- [ ] Add Availability submenu
- [ ] Add Settings submenu with tabs
- [ ] Set proper capabilities for each menu

### 1.4 Settings Framework
- [ ] Create settings registration system
- [ ] Implement settings API wrapper
- [ ] Create settings page renderer
- [ ] Add settings sanitization
- [ ] Create default settings on activation
- [ ] Build settings tabs navigation

---

## Phase 2: Core Booking Engine

### 2.1 Consultation Types CRUD
- [ ] Create `Book_Now_Consultation_Type` model class
- [ ] Implement create consultation type
- [ ] Implement read/get consultation types
- [ ] Implement update consultation type
- [ ] Implement delete consultation type
- [ ] Add validation rules
- [ ] Create admin list table
- [ ] Create admin add/edit form
- [ ] Handle featured image upload
- [ ] Implement display order sorting

### 2.2 Categories CRUD
- [ ] Create `Book_Now_Category` model class
- [ ] Implement category CRUD operations
- [ ] Add hierarchical support (parent/child)
- [ ] Create admin list table
- [ ] Create admin add/edit form
- [ ] Implement category image upload

### 2.3 Availability System
- [ ] Create `Book_Now_Availability` class
- [ ] Implement weekly schedule rules
- [ ] Implement specific date rules
- [ ] Implement date blocking rules
- [ ] Create availability calculation algorithm
- [ ] Handle timezone conversions
- [ ] Create admin availability UI
- [ ] Add consultation type specific availability
- [ ] Implement buffer time logic

### 2.4 Booking CRUD
- [ ] Create `Book_Now_Booking` model class
- [ ] Implement create booking
- [ ] Implement read bookings with filtering
- [ ] Implement update booking
- [ ] Implement cancel booking
- [ ] Generate unique reference numbers
- [ ] Handle booking status transitions
- [ ] Create admin bookings list table
- [ ] Create admin booking detail view
- [ ] Implement booking search
- [ ] Add booking export to CSV

### 2.5 REST API Endpoints
- [ ] Create `Book_Now_REST_API` class
- [ ] Register REST namespace
- [ ] Implement GET `/consultation-types`
- [ ] Implement GET `/consultation-types/{slug}`
- [ ] Implement GET `/categories`
- [ ] Implement GET `/availability`
- [ ] Implement POST `/bookings`
- [ ] Implement GET `/bookings/{ref}`
- [ ] Implement POST `/bookings/{ref}/cancel`
- [ ] Add proper validation and sanitization
- [ ] Add rate limiting
- [ ] Document all endpoints

---

## Phase 3: Frontend Components

### 3.1 Shortcode System
- [ ] Create `Book_Now_Shortcodes` class
- [ ] Register all shortcodes
- [ ] Create shortcode attribute parser
- [ ] Set up conditional asset loading

### 3.2 Booking Form Wizard
- [ ] Create form wizard HTML structure
- [ ] Implement Step 1: Type selection
- [ ] Implement Step 2: Date/time selection
- [ ] Implement Step 3: Customer details
- [ ] Implement Step 4: Payment
- [ ] Implement Step 5: Confirmation
- [ ] Create step navigation logic
- [ ] Add form validation (client-side)
- [ ] Add form validation (server-side)
- [ ] Handle form submission via AJAX
- [ ] Create loading/progress indicators
- [ ] Add error handling and display

### 3.3 Calendar View
- [ ] Create calendar HTML structure
- [ ] Implement month navigation
- [ ] Render availability on calendar
- [ ] Handle date selection
- [ ] Show slot details on click
- [ ] Make mobile responsive
- [ ] Add keyboard navigation

### 3.4 List View
- [ ] Create list view HTML structure
- [ ] Fetch and display available slots
- [ ] Group by date
- [ ] Add expand/collapse for times
- [ ] Implement booking links
- [ ] Add pagination/load more

### 3.5 Consultation Type Cards
- [ ] Create card component HTML
- [ ] Style grid layout
- [ ] Style list layout
- [ ] Add category filtering
- [ ] Implement responsive design
- [ ] Add "Book Now" button linking

### 3.6 Styling
- [ ] Create base CSS variables
- [ ] Style booking form wizard
- [ ] Style calendar component
- [ ] Style list component
- [ ] Style consultation cards
- [ ] Add responsive breakpoints
- [ ] Create admin-configurable colors
- [ ] Test across browsers

---

## Phase 4: Payment Integration

### 4.1 Stripe Setup
- [ ] Create `Book_Now_Stripe` class
- [ ] Add Stripe PHP library via Composer
- [ ] Create settings page for Stripe keys
- [ ] Implement test/live mode toggle
- [ ] Mask API keys in display
- [ ] **Implement Test Connection button**
- [ ] Display connection status indicator

### 4.2 Payment Flow
- [ ] Implement Payment Intent creation
- [ ] Add Stripe Elements to payment step
- [ ] Handle card validation
- [ ] Process payment confirmation
- [ ] Handle payment errors gracefully
- [ ] Create payment success flow
- [ ] Create payment failure flow

### 4.3 Stripe Webhooks
- [ ] Create webhook endpoint
- [ ] Implement signature verification
- [ ] Handle `payment_intent.succeeded`
- [ ] Handle `payment_intent.payment_failed`
- [ ] Handle `charge.refunded`
- [ ] Handle `charge.dispute.created`
- [ ] Add webhook logging

### 4.4 Refund Processing
- [ ] Create refund functionality
- [ ] Add refund button to admin
- [ ] Handle partial refunds
- [ ] Update booking status on refund
- [ ] Send refund notification email

---

## Phase 5: Calendar Sync

### 5.1 Google Calendar Integration
- [ ] Create `Book_Now_Google_Calendar` class
- [ ] Add Google API client library
- [ ] Create OAuth connection flow
- [ ] Store OAuth tokens securely
- [ ] Implement token refresh
- [ ] **Implement Test Connection button**
- [ ] Create calendar selection UI
- [ ] Implement event creation
- [ ] Implement event update
- [ ] Implement event deletion
- [ ] Read busy times for availability
- [ ] Add disconnect functionality
- [ ] Handle API errors gracefully

### 5.2 Microsoft Calendar Integration
- [ ] Create `Book_Now_Microsoft_Calendar` class
- [ ] Add Microsoft Graph SDK
- [ ] Create Azure AD OAuth flow
- [ ] Store OAuth tokens securely
- [ ] Implement token refresh
- [ ] **Implement Test Connection button**
- [ ] Create calendar selection UI
- [ ] Implement event CRUD operations
- [ ] Read free/busy information
- [ ] Add disconnect functionality
- [ ] Handle API errors gracefully

### 5.3 Sync Logic
- [ ] Create sync manager class
- [ ] Implement automatic event creation on booking
- [ ] Implement event update on booking change
- [ ] Implement event deletion on cancellation
- [ ] Add manual sync trigger
- [ ] Create sync status tracking
- [ ] Handle sync conflicts
- [ ] Add sync logging

---

## Phase 6: Notifications

### 6.1 Email Template System
- [ ] Create `Book_Now_Notifications` class
- [ ] Create email template loader
- [ ] Implement template variable replacement
- [ ] Create HTML email wrapper
- [ ] Add plain text fallback

### 6.2 Email Templates
- [ ] Create booking confirmation template
- [ ] Create booking reminder template
- [ ] Create cancellation notification template
- [ ] Create admin new booking alert template
- [ ] Create admin cancellation alert template
- [ ] Create refund notification template
- [ ] Make templates customizable via admin

### 6.3 Email Sending
- [ ] Implement email sending function
- [ ] Add email queue for bulk sending
- [ ] Log all sent emails
- [ ] Handle send failures
- [ ] Add test email functionality

### 6.4 Reminder System
- [ ] Set up WordPress cron for reminders
- [ ] Implement reminder sending logic
- [ ] Add configurable reminder timing
- [ ] Prevent duplicate reminders
- [ ] Track reminder status

---

## Phase 7: Admin Interface Polish

### 7.1 Dashboard
- [ ] Create dashboard widget layout
- [ ] Show today's bookings
- [ ] Show upcoming bookings
- [ ] Display quick statistics
- [ ] Show integration status cards
- [ ] Add quick action buttons

### 7.2 Settings Pages
- [ ] Complete General settings page
- [ ] Complete Payment settings page
- [ ] Complete Integrations settings page
- [ ] Complete Email settings page
- [ ] Complete Styling settings page
- [ ] Add settings import/export

### 7.3 Admin Assets
- [ ] Create admin CSS file
- [ ] Create admin JavaScript file
- [ ] Add date picker libraries
- [ ] Add color picker for styling
- [ ] Create admin UI components

---

## Phase 8: Testing & Quality

### 8.1 Unit Tests
- [ ] Set up PHPUnit
- [ ] Test consultation type model
- [ ] Test booking model
- [ ] Test availability calculation
- [ ] Test reference number generation
- [ ] Test API endpoints
- [ ] Test payment functions

### 8.2 Integration Tests
- [ ] Test complete booking flow
- [ ] Test payment processing
- [ ] Test calendar sync
- [ ] Test email sending
- [ ] Test shortcode rendering

### 8.3 Security Review
- [ ] Audit all database queries
- [ ] Review all user input handling
- [ ] Check nonce verification
- [ ] Review capability checks
- [ ] Test for XSS vulnerabilities
- [ ] Test for CSRF vulnerabilities
- [ ] Review API key storage

### 8.4 Code Quality
- [ ] Run PHPCS with WordPress standards
- [ ] Fix all coding standard issues
- [ ] Add inline documentation
- [ ] Generate PHPDoc documentation
- [ ] Review error handling

---

## Phase 9: Documentation

### 9.1 Developer Documentation
- [ ] Document all hooks and filters
- [ ] Document REST API endpoints
- [ ] Document database schema
- [ ] Create code examples
- [ ] Document extension points

### 9.2 User Documentation
- [ ] Create getting started guide
- [ ] Document consultation type setup
- [ ] Document availability configuration
- [ ] Create payment setup guide
- [ ] Create calendar integration guides
- [ ] Document shortcode usage
- [ ] Create FAQ section
- [ ] Add troubleshooting guide

### 9.3 WordPress.org Assets
- [ ] Write readme.txt
- [ ] Create plugin banner
- [ ] Create plugin icon
- [ ] Prepare screenshots
- [ ] Write changelog

---

## Phase 10: Launch Preparation

### 10.1 Final Testing
- [ ] Full end-to-end testing
- [ ] Test on fresh WordPress install
- [ ] Test plugin activation/deactivation
- [ ] Test plugin uninstall
- [ ] Test with popular themes
- [ ] Test with common plugins
- [ ] Performance testing

### 10.2 Release
- [ ] Update version numbers
- [ ] Generate final POT file
- [ ] Create release build script
- [ ] Create distributable ZIP
- [ ] Tag release in Git
- [ ] Submit to WordPress.org (if applicable)

---

## Backlog / Future Features

### Nice to Have (Post-MVP)
- [ ] Recurring appointments
- [ ] Group bookings
- [ ] Multiple staff/providers
- [ ] Waiting list
- [ ] Promo codes/discounts
- [ ] Additional payment gateways (PayPal, Square)
- [ ] SMS notifications
- [ ] Zoom/Meet integration
- [ ] Customer accounts/portal
- [ ] Advanced reporting
- [ ] Gutenberg blocks
- [ ] Elementor widgets
- [ ] iCal feed export
- [ ] Booking widget for other pages
- [ ] Multi-language support

---

## Notes

### Dependencies to Install
```bash
# PHP (Composer)
composer require stripe/stripe-php
composer require google/apiclient
composer require microsoft/microsoft-graph

# Development
composer require --dev phpunit/phpunit
composer require --dev wp-coding-standards/wpcs
```

### Key Files to Create First
1. `book-now-kre8iv.php` - Main plugin file
2. `includes/class-book-now.php` - Core class
3. `includes/class-book-now-activator.php` - Activation
4. Database table creation functions
5. Admin menu registration

### Development Environment
- Local by Flywheel or similar
- WordPress 6.0+ with PHP 8.0+
- Enable WP_DEBUG during development
- Use Stripe test mode

---

**Last Updated:** 2026-01-08
