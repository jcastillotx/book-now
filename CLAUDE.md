# CLAUDE.md - AI Assistant Guide for book-now

This document provides comprehensive guidance for AI assistants working on the book-now project, including codebase structure, development workflows, and key conventions.

## Project Overview

**Project Name:** Book Now by Kre8iv Tech
**Repository:** jcastillotx/book-now
**Plugin Slug:** `book-now-kre8i`
**Text Domain:** `book-now-kre8iv`
**Current Status:** Initial development phase
**Version:** 1.0.0

**Description:** A comprehensive WordPress plugin designed to provide businesses with a seamless consultation booking experience. Enables website visitors to browse available consultation types, view real-time availability, and complete bookings with integrated payment processing through Stripe.

### Key Features

- Multiple consultation types with customizable pricing and duration
- Real-time availability display via calendar, list, and form views
- Secure Stripe payment integration for upfront or deposit collection
- Bidirectional sync with Google Calendar and Microsoft 365/Outlook
- Flexible shortcode system for frontend display
- Comprehensive admin dashboard for booking management
- Email notifications for confirmations, reminders, and cancellations

### Target Users

- Consultants and coaches offering paid sessions
- Professional service providers (legal, financial, medical consultations)
- Agencies offering discovery calls or strategy sessions
- Any business requiring scheduled appointments with payment collection

## Repository Structure

### Current State
Fresh repository - plugin development starting from scratch.

### Target WordPress Plugin Structure

```
book-now-kre8iv/
├── book-now-kre8iv.php              # Main plugin file
├── uninstall.php                     # Cleanup on uninstall
├── readme.txt                        # WordPress.org readme
├── LICENSE                           # GPL v2+
├── CLAUDE.md                         # This file - AI assistant guidance
│
├── includes/
│   ├── class-book-now.php           # Main plugin class
│   ├── class-book-now-loader.php    # Hook/filter registration
│   ├── class-book-now-activator.php # Activation tasks
│   ├── class-book-now-deactivator.php
│   ├── class-book-now-i18n.php      # Internationalization
│   ├── class-book-now-booking.php   # Booking model & CRUD
│   ├── class-book-now-consultation-type.php
│   ├── class-book-now-availability.php
│   ├── class-book-now-customer.php
│   ├── class-book-now-notifications.php
│   └── helpers.php                   # Utility functions
│
├── admin/
│   ├── class-book-now-admin.php     # Admin controller
│   ├── css/
│   │   └── book-now-admin.css
│   ├── js/
│   │   └── book-now-admin.js
│   └── partials/
│       ├── dashboard.php
│       ├── bookings-list.php
│       ├── booking-edit.php
│       ├── consultation-types-list.php
│       ├── consultation-type-edit.php
│       ├── availability.php
│       ├── categories.php
│       ├── settings-general.php
│       ├── settings-payments.php
│       ├── settings-integrations.php
│       ├── settings-emails.php
│       └── settings-styling.php
│
├── public/
│   ├── class-book-now-public.php    # Frontend controller
│   ├── class-book-now-shortcodes.php
│   ├── css/
│   │   ├── book-now-public.css
│   │   └── book-now-calendar.css
│   ├── js/
│   │   ├── book-now-public.js
│   │   ├── book-now-form.js
│   │   ├── book-now-calendar.js
│   │   └── book-now-stripe.js
│   └── partials/
│       ├── form-wizard.php
│       ├── form-step-types.php
│       ├── form-step-datetime.php
│       ├── form-step-details.php
│       ├── form-step-payment.php
│       ├── form-step-confirmation.php
│       ├── calendar-view.php
│       ├── list-view.php
│       └── consultation-cards.php
│
├── integrations/
│   ├── class-book-now-stripe.php
│   ├── class-book-now-google-calendar.php
│   └── class-book-now-microsoft-calendar.php
│
├── api/
│   └── class-book-now-rest-api.php  # REST endpoints
│
├── templates/
│   └── emails/
│       ├── booking-confirmation.php
│       ├── booking-reminder.php
│       ├── booking-cancelled.php
│       ├── admin-new-booking.php
│       └── admin-cancellation.php
│
├── assets/
│   ├── images/
│   └── fonts/
│
└── languages/
    └── book-now-kre8iv.pot
```

## Development Workflow

### Branch Strategy

1. **Feature Branches:** All development work should be done on feature branches
   - Branch naming convention: `claude/<feature-description>-<session-id>`
   - Example: `claude/add-claude-documentation-IV0bs`

2. **Branch Operations:**
   - Always create feature branches from the main branch
   - Keep branches focused on single features or fixes
   - Delete branches after merging

### Git Conventions

#### Commits
- **Message Format:** Use clear, descriptive commit messages
  - Start with a verb (Add, Fix, Update, Refactor, Remove)
  - Be specific about what changed and why
  - Examples:
    - `Add user authentication module`
    - `Fix booking validation logic`
    - `Update API endpoints for new booking flow`

#### Pushing Changes
- Always use: `git push -u origin <branch-name>`
- Branch names must start with `claude/` and end with the session ID
- If push fails due to network errors, retry up to 4 times with exponential backoff (2s, 4s, 8s, 16s)

#### Pull Requests
- Create PRs when feature work is complete
- Include:
  - Clear title describing the change
  - Summary of changes (1-3 bullet points)
  - Test plan with verification steps
- Use: `gh pr create --title "..." --body "..."`

### Code Quality Standards

#### General Principles
1. **Avoid Over-Engineering**
   - Only make changes that are directly requested or clearly necessary
   - Keep solutions simple and focused
   - Don't add features beyond what was asked
   - Don't add unnecessary abstractions or utilities

2. **Security First**
   - Watch for OWASP top 10 vulnerabilities:
     - Command injection
     - XSS (Cross-Site Scripting)
     - SQL injection
     - Insecure authentication
     - Security misconfigurations
   - Validate at system boundaries (user input, external APIs)
   - Trust internal code and framework guarantees

3. **Clean Code**
   - Only add comments where logic isn't self-evident
   - Don't add docstrings or comments to unchanged code
   - Remove unused code completely (no backwards-compatibility hacks)
   - No `_unused` variables or `// removed` comments

#### Testing
- Write tests for new features and bug fixes
- Run existing tests before committing
- Ensure all tests pass before pushing

## Technology Stack

### WordPress Requirements

| Requirement       | Specification                  |
|-------------------|--------------------------------|
| WordPress Version | 6.0 or higher                  |
| PHP Version       | 8.0 or higher                  |
| MySQL Version     | 5.7 or higher / MariaDB 10.3+  |
| SSL Certificate   | Required (for Stripe)          |
| cURL Extension    | Required                       |
| JSON Extension    | Required                       |

### Core Technologies

- **Backend Framework:** WordPress Plugin API
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Payment Processing:** Stripe API (Payment Intents)
- **Calendar Integration:**
  - Google Calendar API
  - Microsoft Graph API (Microsoft 365/Outlook)
- **Database:** WordPress Custom Tables
- **REST API:** WordPress REST API
- **Email:** WordPress wp_mail() with HTML templates

### JavaScript Libraries

- **Stripe.js:** For PCI-compliant payment collection
- **Calendar UI:** Custom implementation (or lightweight library TBD)
- **Date/Time:** Native JavaScript Date with timezone support

### PHP Dependencies

- **Stripe PHP Library:** `stripe/stripe-php`
- **Google API Client:** `google/apiclient`
- **Microsoft Graph SDK:** `microsoft/microsoft-graph`

### Development Tools

- **Testing:** PHPUnit for PHP, Jest for JavaScript
- **Code Standards:** WordPress Coding Standards (WPCS)
- **Build Tools:** npm/webpack for asset compilation (if needed)
- **Version Control:** Git

## File Organization Conventions

### WordPress Plugin Standards

#### Class Naming
- Prefix all classes with `Book_Now_` (e.g., `Book_Now_Booking`, `Book_Now_Admin`)
- Use underscores to separate words (WordPress convention)
- One class per file

#### File Naming
- Class files: `class-book-now-{name}.php` (lowercase with hyphens)
- Template files: `{name}.php` (lowercase with hyphens)
- JavaScript: `book-now-{name}.js`
- CSS: `book-now-{name}.css`

#### Function Naming
- Prefix functions with `booknow_` (e.g., `booknow_get_booking()`)
- Use underscores to separate words
- Be descriptive and specific

#### Database Tables
- Prefix: `{wp_prefix}booknow_` (e.g., `wp_booknow_bookings`)
- Use underscores to separate words
- Plural names for tables (e.g., `bookings`, `consultation_types`)

#### WordPress Options
- Prefix: `booknow_` (e.g., `booknow_general_settings`)
- Group related options in arrays

#### Hooks (Actions & Filters)
- Prefix: `booknow_` (e.g., `booknow_before_booking_created`)
- Be specific about timing (before/after)
- Document all custom hooks

#### REST API Endpoints
- Namespace: `/book-now/v1/`
- Use lowercase with hyphens
- Use plural nouns for resources (e.g., `/bookings`, `/consultation-types`)

#### Text Domain
- Use `book-now-kre8iv` consistently for all translatable strings
- Example: `__('Booking confirmed', 'book-now-kre8iv')`

### Code Organization Principles

#### Separation of Concerns
- **includes/**: Core business logic, models, utilities
- **admin/**: Admin-only functionality, pages, assets
- **public/**: Frontend-only functionality, shortcodes, assets
- **integrations/**: Third-party API integrations
- **api/**: REST API endpoints
- **templates/**: Reusable templates (emails, etc.)

#### Asset Loading
- Enqueue scripts/styles only where needed
- Use dependency management (wp_enqueue_script dependencies parameter)
- Minify for production

#### Security
- Sanitize all input: `sanitize_text_field()`, `sanitize_email()`, etc.
- Escape all output: `esc_html()`, `esc_attr()`, `esc_url()`, etc.
- Validate and verify nonces for all forms
- Check user capabilities: `current_user_can('manage_options')`
- Use prepared statements for all database queries

## Key Patterns and Practices

### Code Modifications
1. **Always read before editing**
   - Never propose changes to code you haven't read
   - Understand existing patterns before making changes

2. **Maintain consistency**
   - Follow existing code style and patterns
   - Match the project's naming conventions
   - Use the same libraries/frameworks as existing code

3. **Minimal changes**
   - Change only what needs to be changed
   - Don't refactor surrounding code unless requested
   - Don't add error handling for impossible scenarios

### Documentation
- Update documentation when adding features
- Keep README.md current with setup instructions
- Document breaking changes clearly
- Update this CLAUDE.md file as patterns emerge

## WordPress Plugin Development Patterns

### Plugin Activation & Deactivation

```php
// Activation - Create tables, set default options
register_activation_hook(__FILE__, 'booknow_activate');

function booknow_activate() {
    // Create database tables
    booknow_create_tables();

    // Set default options
    add_option('booknow_version', BOOK_NOW_VERSION);
    add_option('booknow_general_settings', booknow_default_settings());

    // Flush rewrite rules if adding custom post types
    flush_rewrite_rules();
}

// Deactivation - Cleanup temporary data
register_deactivation_hook(__FILE__, 'booknow_deactivate');

function booknow_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('booknow_send_reminders');

    // Flush rewrite rules
    flush_rewrite_rules();
}
```

### Database Table Creation

```php
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();
$table_name = $wpdb->prefix . 'booknow_bookings';

$sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_number VARCHAR(20) NOT NULL,
    -- more columns...
    PRIMARY KEY (id),
    UNIQUE KEY reference_number (reference_number)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
```

### Custom Database Queries (Secure)

```php
global $wpdb;

// SELECT with prepared statement
$booking_id = 123;
$booking = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}booknow_bookings WHERE id = %d",
        $booking_id
    )
);

// INSERT with wpdb
$wpdb->insert(
    $wpdb->prefix . 'booknow_bookings',
    array(
        'reference_number' => $reference,
        'customer_email' => $email,
        'booking_date' => $date,
    ),
    array('%s', '%s', '%s')
);

$booking_id = $wpdb->insert_id;
```

### Options and Settings API

```php
// Save settings
$settings = array(
    'business_name' => 'Acme Consulting',
    'timezone' => 'America/New_York',
    'currency' => 'USD',
);
update_option('booknow_general_settings', $settings);

// Retrieve settings
$settings = get_option('booknow_general_settings', array());
$timezone = isset($settings['timezone']) ? $settings['timezone'] : 'UTC';
```

### REST API Registration

```php
add_action('rest_api_init', 'booknow_register_rest_routes');

function booknow_register_rest_routes() {
    register_rest_route('book-now/v1', '/bookings', array(
        'methods' => 'POST',
        'callback' => 'booknow_create_booking',
        'permission_callback' => '__return_true', // Public endpoint
        'args' => array(
            'consultation_type_id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}
```

### Shortcode Registration

```php
add_shortcode('book_now_form', 'booknow_form_shortcode');

function booknow_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => '',
        'category' => '',
        'show_types' => 'true',
    ), $atts, 'book_now_form');

    // Security: Nonce for AJAX
    ob_start();
    ?>
    <div class="booknow-form-wrapper">
        <?php wp_nonce_field('booknow_booking', 'booknow_nonce'); ?>
        <!-- Form HTML -->
    </div>
    <?php
    return ob_get_clean();
}
```

### AJAX Handlers

```php
// For logged-in users
add_action('wp_ajax_booknow_get_availability', 'booknow_ajax_get_availability');

// For non-logged-in users (public)
add_action('wp_ajax_nopriv_booknow_get_availability', 'booknow_ajax_get_availability');

function booknow_ajax_get_availability() {
    check_ajax_referer('booknow_booking', 'nonce');

    $type_id = intval($_POST['type_id']);
    $date = sanitize_text_field($_POST['date']);

    $availability = booknow_calculate_availability($type_id, $date);

    wp_send_json_success($availability);
}
```

### Enqueuing Assets

```php
add_action('wp_enqueue_scripts', 'booknow_enqueue_public_assets');

function booknow_enqueue_public_assets() {
    // Only enqueue on pages with shortcode
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'book_now_form')) {
        return;
    }

    wp_enqueue_style(
        'booknow-public',
        plugin_dir_url(__FILE__) . 'public/css/book-now-public.css',
        array(),
        BOOK_NOW_VERSION
    );

    wp_enqueue_script(
        'booknow-form',
        plugin_dir_url(__FILE__) . 'public/js/book-now-form.js',
        array('jquery'),
        BOOK_NOW_VERSION,
        true
    );

    // Localize script with PHP data
    wp_localize_script('booknow-form', 'bookNowData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('booknow_booking'),
        'restUrl' => rest_url('book-now/v1/'),
        'restNonce' => wp_create_nonce('wp_rest'),
    ));
}
```

### Email Notifications

```php
function booknow_send_confirmation_email($booking_id) {
    $booking = booknow_get_booking($booking_id);

    $to = $booking->customer_email;
    $subject = __('Booking Confirmation', 'book-now-kre8iv');

    // Load email template
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/emails/booking-confirmation.php';
    $message = ob_get_clean();

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_option('booknow_from_name') . ' <' . get_option('booknow_from_email') . '>',
    );

    wp_mail($to, $subject, $message, $headers);
}
```

### Scheduled Events (Cron)

```php
// Schedule on activation
if (!wp_next_scheduled('booknow_send_reminders')) {
    wp_schedule_event(time(), 'hourly', 'booknow_send_reminders');
}

// Hook handler
add_action('booknow_send_reminders', 'booknow_process_reminders');

function booknow_process_reminders() {
    $upcoming_bookings = booknow_get_upcoming_bookings();

    foreach ($upcoming_bookings as $booking) {
        if (booknow_should_send_reminder($booking)) {
            booknow_send_reminder_email($booking->id);
        }
    }
}
```

## AI Assistant Best Practices

### Task Management
1. **Use TodoWrite for complex tasks**
   - Plan tasks with 3+ steps
   - Track progress on non-trivial work
   - Mark todos as completed immediately after finishing

2. **Never skip todos**
   - Don't batch completions
   - Keep exactly ONE task in_progress at a time
   - Only mark completed when fully done (no errors, all tests passing)

### Communication
- Be concise and clear
- Don't use emojis unless requested
- Output text directly (don't use bash echo)
- Use code references with `file_path:line_number` format

### Tool Usage
- Use specialized tools (Read, Edit, Write) instead of bash for file operations
- Use Task tool with Explore agent for codebase exploration
- Run independent operations in parallel when possible
- Use exact values provided by users (don't make up values)

## Common Commands

### WordPress Development
```bash
# Install Composer dependencies
composer install

# Install npm dependencies (if using build tools)
npm install

# Run PHP tests
vendor/bin/phpunit

# Run JavaScript tests
npm test

# Build assets (if using build process)
npm run build

# Watch for changes during development
npm run watch

# Check PHP code standards
vendor/bin/phpcs --standard=WordPress path/to/file.php

# Fix PHP code standards automatically
vendor/bin/phpcbf --standard=WordPress path/to/file.php

# Generate translation POT file
wp i18n make-pot . languages/book-now-kre8iv.pot --domain=book-now-kre8iv

# Database management (using WP-CLI if available)
wp db export backup.sql
wp db import backup.sql
```

### Git Operations
```bash
# Create and switch to feature branch
git checkout -b claude/feature-name-sessionid

# Check status
git status

# Stage and commit changes
git add .
git commit -m "Descriptive commit message"

# Push to remote
git push -u origin claude/feature-name-sessionid

# Create pull request
gh pr create --title "Feature: Description" --body "Summary of changes"
```

## Troubleshooting

### Common Issues
*To be documented as issues arise*

### Network Issues
- Git push/fetch failures: Retry up to 4 times with exponential backoff
- Use specific branch fetching: `git fetch origin <branch-name>`

## Project-Specific Conventions

### Core Architecture Components

#### 1. Booking Engine
- **Purpose:** Core scheduling logic and availability calculation
- **Key Classes:** `Book_Now_Booking`, `Book_Now_Availability`
- **Responsibilities:**
  - Calculate available time slots based on rules
  - Handle booking creation, updates, and cancellations
  - Manage booking status transitions
  - Conflict detection and prevention

#### 2. Payment Processing Module
- **Purpose:** Stripe integration for payment collection
- **Key Class:** `Book_Now_Stripe`
- **Responsibilities:**
  - Create Payment Intents
  - Handle Stripe webhooks
  - Process refunds
  - Link bookings with Stripe transactions

#### 3. Calendar Integration Module
- **Purpose:** Sync with external calendars
- **Key Classes:** `Book_Now_Google_Calendar`, `Book_Now_Microsoft_Calendar`
- **Responsibilities:**
  - OAuth authentication flow
  - Create/update/delete calendar events
  - Read busy times for availability
  - Bidirectional synchronization

#### 4. Admin Dashboard
- **Purpose:** Backend management interface
- **Key Class:** `Book_Now_Admin`
- **Responsibilities:**
  - Render admin pages and menus
  - Handle admin AJAX requests
  - Provide booking management interface
  - Settings configuration

#### 5. Frontend Display Components
- **Purpose:** Public-facing booking interface
- **Key Classes:** `Book_Now_Public`, `Book_Now_Shortcodes`
- **Responsibilities:**
  - Render shortcode outputs
  - Handle public AJAX requests
  - Display availability calendars
  - Process booking form submissions

#### 6. Notification System
- **Purpose:** Email communications
- **Key Class:** `Book_Now_Notifications`
- **Responsibilities:**
  - Render email templates
  - Send transactional emails
  - Schedule reminder emails
  - Track email delivery

### Database Schema Overview

#### Custom Tables

1. **booknow_consultation_types** - Service offerings
   - Stores: name, duration, price, settings
   - Key fields: id, slug, status, stripe_product_id

2. **booknow_bookings** - Appointment records
   - Stores: customer info, datetime, status, payment info
   - Key fields: id, reference_number, status, payment_status

3. **booknow_availability** - Scheduling rules
   - Stores: weekly schedules, specific dates, blocks
   - Key fields: id, rule_type, day_of_week, is_available

4. **booknow_categories** - Grouping for consultation types
   - Stores: name, description, hierarchy
   - Key fields: id, slug, parent_id

5. **booknow_email_log** - Email tracking (optional)
   - Stores: email history, delivery status
   - Key fields: id, booking_id, email_type, status

### API Integrations

#### Stripe Payment Integration
- **API Version:** Latest Stripe API
- **Payment Method:** Payment Intents (SCA-compliant)
- **Frontend:** Stripe Elements for card collection
- **Webhooks:** Payment confirmation events
- **Required Events:**
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
  - `charge.refunded`
  - `charge.dispute.created`

#### Google Calendar Integration
- **API:** Google Calendar API v3
- **Authentication:** OAuth 2.0
- **Required Scopes:**
  - `https://www.googleapis.com/auth/calendar`
  - `https://www.googleapis.com/auth/calendar.events`
- **Operations:** Create, update, delete events, read freebusy

#### Microsoft Calendar Integration
- **API:** Microsoft Graph API
- **Authentication:** OAuth 2.0 via Azure AD
- **Required Permissions:** `Calendars.ReadWrite`
- **Endpoints:**
  - `/me/calendar/events` (create, update, delete)
  - `/me/calendar/getSchedule` (free/busy)

### REST API Endpoints

All endpoints under namespace: `/book-now/v1/`

#### Public Endpoints
- `GET /consultation-types` - List active types
- `GET /availability` - Get available slots
- `POST /bookings` - Create booking
- `POST /payment/create-intent` - Create Stripe Payment Intent
- `POST /payment/webhook` - Stripe webhook handler

#### Authenticated Endpoints
- `GET /bookings/{id}` - Get booking details
- `POST /bookings/{id}/cancel` - Cancel booking

#### Admin Endpoints
- `GET /admin/bookings` - List all bookings
- `PUT /admin/bookings/{id}` - Update booking
- `GET /admin/stats` - Dashboard statistics

### Shortcodes Reference

| Shortcode              | Purpose                          |
|------------------------|----------------------------------|
| `[book_now_form]`      | Complete booking wizard          |
| `[book_now_calendar]`  | Calendar availability view       |
| `[book_now_list]`      | List view of available slots     |
| `[book_now_types]`     | Display consultation type cards  |
| `[book_now_single]`    | Single consultation type booking |

### Key Dependencies

#### PHP Composer Dependencies
```json
{
  "require": {
    "stripe/stripe-php": "^10.0",
    "google/apiclient": "^2.15",
    "microsoft/microsoft-graph": "^1.100"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.0",
    "wp-coding-standards/wpcs": "^3.0"
  }
}
```

#### JavaScript npm Dependencies (if using build process)
```json
{
  "dependencies": {
    "@stripe/stripe-js": "^2.0.0"
  },
  "devDependencies": {
    "webpack": "^5.0.0",
    "jest": "^29.0.0"
  }
}
```

## Security Considerations

### Secrets Management
- Never commit sensitive data (.env files, credentials, API keys)
- Use environment variables for configuration
- Add sensitive file patterns to .gitignore

### Code Review Checklist
- [ ] No hardcoded secrets or credentials
- [ ] Input validation at boundaries
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] Proper error handling without information leakage
- [ ] Authentication and authorization properly implemented

## Testing Strategy

### Test Types
*To be defined as testing is implemented*
- Unit tests
- Integration tests
- End-to-end tests

### Coverage Goals
*To be established*

## Deployment

### WordPress Plugin Installation

#### Manual Installation
1. Upload plugin folder to `/wp-content/plugins/`
2. Activate via WordPress admin
3. Database tables created automatically
4. Configure settings via admin panel

#### WordPress.org (Future)
1. Install from WordPress plugin directory
2. Standard WordPress plugin installation
3. Activation and setup wizard

### Development Environments

#### Local Development
- Use Local by Flywheel, XAMPP, or Docker
- WordPress 6.0+ with PHP 8.0+
- Enable WP_DEBUG and WP_DEBUG_LOG
- Use test mode for Stripe

#### Staging
- Mirror production environment
- Test Stripe keys
- Test calendar integrations
- Full QA testing

#### Production
- Live WordPress site with SSL
- Live Stripe keys
- Production calendar credentials
- Monitoring and error logging

### Update Process

#### Database Migrations
```php
function booknow_check_version() {
    $current_version = get_option('booknow_version');

    if (version_compare($current_version, '1.1.0', '<')) {
        booknow_update_110();
    }

    update_option('booknow_version', BOOK_NOW_VERSION);
}

function booknow_update_110() {
    global $wpdb;
    // Add new column
    $wpdb->query("ALTER TABLE {$wpdb->prefix}booknow_bookings
                  ADD COLUMN new_field VARCHAR(255)");
}
```

### Plugin Uninstall

#### Controlled Uninstall (uninstall.php)
```php
// Only if setting allows data deletion
if (get_option('booknow_delete_data_on_uninstall')) {
    // Drop tables
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}booknow_bookings");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}booknow_consultation_types");
    // ...

    // Delete options
    delete_option('booknow_version');
    delete_option('booknow_general_settings');
    // ...

    // Clear scheduled events
    wp_clear_scheduled_hook('booknow_send_reminders');
}
```

## Maintenance and Updates

### Regular Tasks
*Document recurring maintenance tasks*

### Update Procedures
*Document how to update dependencies, migrate databases, etc.*

## Development Roadmap

### Phase 1: Foundation (Weeks 1-2)
- [ ] Plugin scaffolding and file structure
- [ ] Database schema implementation
- [ ] Basic admin menu and settings pages
- [ ] Consultation type CRUD operations
- [ ] Category management

**Key Deliverables:**
- Main plugin file with proper headers
- Database tables created on activation
- Admin menu structure
- Basic consultation type management

### Phase 2: Core Booking Engine (Weeks 3-4)
- [ ] Availability rules system
- [ ] Slot calculation algorithm
- [ ] Booking CRUD operations
- [ ] Conflict detection
- [ ] REST API endpoints

**Key Deliverables:**
- Working availability calculation
- Booking creation and management
- REST API for AJAX operations

### Phase 3: Frontend Components (Weeks 5-6)
- [ ] Shortcode system architecture
- [ ] Form wizard component
- [ ] Calendar view component
- [ ] List view component
- [ ] Consultation type cards display
- [ ] Responsive styling
- [ ] AJAX interactions

**Key Deliverables:**
- All shortcodes functional
- Mobile-responsive frontend
- Smooth user experience

### Phase 4: Payment Integration (Week 7)
- [ ] Stripe PHP library integration
- [ ] Payment Intent creation flow
- [ ] Stripe Elements frontend
- [ ] Webhook handler
- [ ] Refund processing
- [ ] Payment status management

**Key Deliverables:**
- Full payment processing
- Webhook event handling
- Secure card collection

### Phase 5: Calendar Sync (Week 8)
- [ ] Google Calendar OAuth setup
- [ ] Google event CRUD operations
- [ ] Microsoft Calendar OAuth setup
- [ ] Microsoft event CRUD operations
- [ ] Bidirectional sync logic
- [ ] Busy time blocking

**Key Deliverables:**
- Google Calendar integration
- Microsoft 365/Outlook integration
- Automatic event creation

### Phase 6: Notifications (Week 9)
- [ ] Email template system
- [ ] Booking confirmation emails
- [ ] Reminder scheduling (cron)
- [ ] Cancellation notifications
- [ ] Admin notifications
- [ ] Email logging (optional)

**Key Deliverables:**
- HTML email templates
- Automated reminders
- Complete notification flow

### Phase 7: Polish & Testing (Weeks 10-11)
- [ ] PHPUnit test suite
- [ ] JavaScript tests
- [ ] Security audit
- [ ] Performance optimization
- [ ] Code standards compliance
- [ ] Documentation
- [ ] User guide

**Key Deliverables:**
- 70%+ test coverage
- Security hardened
- Optimized performance
- Complete documentation

### Phase 8: Launch Preparation (Week 12)
- [ ] Beta testing
- [ ] Bug fixes from testing
- [ ] Final code review
- [ ] WordPress.org submission prep
- [ ] Marketing materials
- [ ] Support documentation

**Key Deliverables:**
- Production-ready plugin
- WordPress.org listing
- User documentation

### Future Enhancements (Post-Launch)
- [ ] Additional payment gateways (PayPal, Square)
- [ ] Recurring bookings
- [ ] Group bookings/webinars
- [ ] Multi-staff support
- [ ] Waiting list functionality
- [ ] Advanced reporting
- [ ] Customer portal
- [ ] Mobile app integration
- [ ] Zoom/Google Meet integration
- [ ] SMS notifications

## Resources

### Documentation
- [Project README](README.md)
- [GitHub Repository](https://github.com/jcastillotx/book-now)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)

### External API Documentation
- [Stripe API Documentation](https://stripe.com/docs/api)
- [Google Calendar API](https://developers.google.com/calendar/api)
- [Microsoft Graph API](https://docs.microsoft.com/en-us/graph/api/resources/calendar)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)

### Development Tools
- [WordPress Plugin Boilerplate](https://github.com/DevinVinson/WordPress-Plugin-Boilerplate)
- [WP-CLI](https://wp-cli.org/)
- [Local by Flywheel](https://localwp.com/)
- [Query Monitor Plugin](https://wordpress.org/plugins/query-monitor/) (debugging)

---

## Document Maintenance

**Last Updated:** 2026-01-08
**Updated By:** Claude (AI Assistant)
**Change Summary:** Comprehensive CLAUDE.md created for WordPress consultation booking plugin project

### Revision History

| Date       | Version | Changes                                                    |
|------------|---------|-----------------------------------------------------------|
| 2026-01-08 | 1.0.0   | Initial comprehensive documentation with full project specs|

### Update Guidelines
- Keep this document current as the project evolves
- Update sections when new patterns or conventions are established
- Document architectural decisions as they are made
- Add examples from the actual codebase as they become available
- Update the development roadmap checklist as phases complete
- Add new sections for emerging patterns or requirements
- Document all custom hooks and filters as they are created
- Keep API endpoint documentation synchronized with implementation

### Maintenance Schedule
- **Weekly:** Review and update roadmap progress
- **Per Phase:** Document lessons learned and architectural decisions
- **Per Release:** Update version history and changelog
- **As Needed:** Add troubleshooting notes, FAQ items, and best practices

---

**Note:** This document serves as the primary reference for AI assistants and developers working on the Book Now plugin. It should be treated as a living document that grows with the project.
