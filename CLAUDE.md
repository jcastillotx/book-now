# CLAUDE.md - AI Assistant Guide

> **Last Updated**: 2026-01-10
> **Repository**: book-now
> **Version**: 1.1.0
> **Status**: Active Development

This document provides essential context for AI assistants working with this WordPress plugin codebase.

---

## Project Overview

**Book Now** is a WordPress plugin for consultation booking with Stripe payments and calendar integration. It enables businesses to offer online booking with real-time availability, payment processing, and calendar synchronization.

### Key Features
- Consultation type management with custom pricing and duration
- Booking system with status tracking
- Stripe payment integration (Payment Intents API)
- Google Calendar and Microsoft 365/Outlook sync
- Email notifications system
- Admin dashboard for managing bookings
- Shortcode system for frontend display

### Technology Stack
- **Platform**: WordPress 6.0+
- **PHP**: 8.0+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Payment**: Stripe PHP SDK v10.x
- **Calendar**: Google API Client v2.15.x, Microsoft Graph v1.100.x
- **Frontend**: jQuery (WP bundled), Stripe.js

---

## Codebase Structure

```
book-now/
├── book-now-kre8iv.php     # Main plugin file (entry point)
├── uninstall.php           # Cleanup on uninstall
├── composer.json           # PHP dependencies
├── package.json            # JS dependencies
│
├── includes/               # Core PHP classes
│   ├── class-book-now.php              # Main plugin class
│   ├── class-book-now-activator.php    # Activation logic
│   ├── class-book-now-deactivator.php  # Deactivation logic
│   ├── class-book-now-loader.php       # Hook loader
│   ├── class-book-now-booking.php      # Booking model
│   ├── class-book-now-consultation-type.php  # Consultation type model
│   ├── class-book-now-availability.php # Availability logic
│   ├── class-book-now-stripe.php       # Stripe integration
│   ├── class-book-now-google-calendar.php    # Google Calendar
│   ├── class-book-now-microsoft-calendar.php # Microsoft Calendar
│   ├── class-book-now-email.php        # Email handling
│   ├── class-book-now-smtp.php         # SMTP configuration
│   ├── class-book-now-rest-api.php     # REST API endpoints
│   ├── class-book-now-webhook.php      # Webhook handling
│   └── helpers.php                     # Utility functions
│
├── admin/                  # Admin functionality
│   ├── class-book-now-admin.php        # Admin controller
│   ├── class-book-now-setup-wizard.php # Setup wizard
│   ├── css/                # Admin styles
│   ├── js/                 # Admin scripts
│   └── partials/           # Admin view templates
│
├── public/                 # Frontend functionality
│   ├── class-book-now-public.php       # Public controller
│   ├── class-book-now-public-ajax.php  # AJAX handlers
│   ├── class-book-now-shortcodes.php   # Shortcode definitions
│   ├── css/                # Frontend styles
│   ├── js/                 # Frontend scripts
│   └── partials/           # Frontend templates
│
├── languages/              # Translation files
│   └── book-now-kre8iv.pot
│
└── docs/                   # Documentation
    ├── API_GUIDE.md
    ├── INSTALL.md
    ├── TECH_STACK.md
    └── PROJECT_SPEC.md
```

### Key Files
- `book-now-kre8iv.php:1` - Main plugin file with version and metadata
- `includes/class-book-now.php` - Core plugin class orchestrating all components
- `includes/helpers.php` - Utility functions (`booknow_get_setting()`, `booknow_format_price()`)
- `includes/class-book-now-rest-api.php` - REST API endpoint definitions

### Database Tables
- `{prefix}booknow_bookings` - Booking records
- `{prefix}booknow_consultation_types` - Service definitions
- `{prefix}booknow_availability` - Schedule rules
- `{prefix}booknow_categories` - Type categorization
- `{prefix}booknow_email_log` - Email tracking

---

## Development Workflow

### Branch Naming
- Feature branches: `claude/<description>-<session-id>`
- Bug fixes: `claude/fix-<issue>-<session-id>`

### Standard Workflow
1. Read and understand existing code before making changes
2. Use TodoWrite for complex tasks (3+ steps)
3. Follow WordPress coding standards
4. Test changes locally
5. Commit with clear messages
6. Push to feature branch: `git push -u origin <branch-name>`

### Push/Pull Practices
- Branch must start with `claude/` and match session ID
- Retry network errors up to 4 times with exponential backoff (2s, 4s, 8s, 16s)
- Fetch specific branches: `git fetch origin <branch-name>`

---

## Coding Conventions

### WordPress PHP Standards
- Use `$wpdb->prepare()` for all SQL queries
- Escape output: `esc_html()`, `esc_attr()`, `esc_url()`
- Sanitize input: `sanitize_text_field()`, `absint()`
- Use WordPress nonces for form security
- Follow WordPress naming: `function_name()`, `$variable_name`, `Class_Name`

### Security Checklist
- Never trust user input - always sanitize
- Use `wp_verify_nonce()` for form submissions
- Check capabilities: `current_user_can('manage_options')`
- Encrypt sensitive data (API keys, tokens)
- Never commit credentials or `.env` files

### File Patterns
- Class files: `class-{plugin}-{name}.php`
- Admin templates: `admin/partials/{feature}.php`
- Public templates: `public/partials/{feature}.php`
- Each class in its own file

### Helper Functions
```php
booknow_get_setting('general', 'currency');  // Get settings
booknow_format_price(100.00);                // Format: $100.00
booknow_format_date('2026-01-08');           // Format date
booknow_format_time('14:30:00');             // Format: 2:30 pm
booknow_generate_reference_number();         // Generate: BN123ABC45
```

---

## Git Practices

### Commit Message Format
```
<type>: <short summary>

<optional detailed description>
```

**Types:** `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `style`

**Examples:**
```bash
feat: add Google Calendar OAuth flow
fix: resolve timezone offset in availability calculation
docs: update API documentation for booking endpoints
```

### Commit Best Practices
- Atomic commits - one logical change per commit
- Use HEREDOC for multi-line commit messages
- Never commit secrets or API keys
- Review changes before committing

---

## Common Commands

### Local Development
```bash
# Install PHP dependencies
composer install

# Install JS dependencies (if using build tools)
npm install

# Check coding standards
composer run phpcs

# Run tests
composer run test
```

### WordPress Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

### Plugin Debug Mode
```php
define('BOOKNOW_DEBUG', true);
```

---

## Shortcodes Reference

| Shortcode | Purpose |
|-----------|---------|
| `[book_now_form]` | Complete booking form wizard |
| `[book_now_types]` | Consultation types grid |
| `[book_now_calendar type="5"]` | Calendar view for type |
| `[book_now_list days="7"]` | List view of available slots |

---

## API Integrations

### Stripe
- Uses Payment Intents API (SCA-compliant)
- Webhook events: `payment_intent.succeeded`, `payment_intent.payment_failed`
- Frontend uses Stripe.js for PCI compliance

### Google Calendar
- OAuth 2.0 authentication
- Scopes: `calendar`, `calendar.events`
- Operations: insert, update, delete events, freebusy queries

### Microsoft Calendar
- OAuth 2.0 via Azure AD
- Permissions: `Calendars.ReadWrite`, `User.Read`
- Uses Microsoft Graph API v1.0

---

## AI Assistant Guidelines

### Before Making Changes
1. Read files first - never propose changes to unread code
2. Explore related files to understand patterns
3. Check existing conventions before adding new code

### During Implementation
1. Follow WordPress coding standards
2. Use proper escaping and sanitization
3. Avoid over-engineering - make minimal necessary changes
4. Watch for OWASP Top 10 vulnerabilities

### What NOT to Do
- Don't add unnecessary features beyond the task
- Don't refactor unrelated code
- Don't add comments where logic is self-evident
- Don't create premature abstractions
- Don't commit secrets or credentials

### Code References
Use `file_path:line_number` format when referencing code:
- "The booking model is in `includes/class-book-now-booking.php:45`"

---

## Troubleshooting

### Git Push Fails with 403
- Ensure branch starts with `claude/` and includes session ID
- Example: `claude/add-feature-s0a9J`

### Network Errors
- Retry with exponential backoff (2s, 4s, 8s, 16s)

### Pre-commit Hook Failures
- Review hook feedback and adjust changes

---

## Additional Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Stripe API Documentation](https://stripe.com/docs/api)
- [Google Calendar API](https://developers.google.com/calendar/api)
- [Microsoft Graph API](https://docs.microsoft.com/en-us/graph/)

---

**Remember**: This is a WordPress plugin. Follow WordPress conventions, use proper escaping/sanitization, and maintain compatibility with WordPress 6.0+.
