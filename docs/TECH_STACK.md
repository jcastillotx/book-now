# Book Now - Technology Stack

Complete technical stack documentation for the Book Now WordPress plugin.

---

## Core Platform

### WordPress
| Component | Requirement | Notes |
|-----------|-------------|-------|
| WordPress Version | 6.0+ | Required for modern block editor and REST API features |
| PHP Version | 8.0+ | Required for modern PHP features and Composer dependencies |
| MySQL Version | 5.7+ | Or MariaDB 10.3+ |
| Memory Limit | 128MB+ | 256MB recommended |

### Server Requirements
| Requirement | Details |
|-------------|---------|
| SSL Certificate | **Required** for Stripe payment processing |
| cURL Extension | Required for API calls |
| JSON Extension | Required for API communication |
| OpenSSL Extension | Required for encryption |
| mbstring Extension | Required for string handling |

---

## Backend Technologies

### PHP Framework
- **WordPress Plugin API** - Core plugin architecture
- **WordPress Settings API** - Settings management
- **WordPress REST API** - Frontend/backend communication
- **WordPress AJAX API** - Form submissions and dynamic content
- **WordPress Cron API** - Scheduled tasks (reminders)

### Database
- **WordPress $wpdb** - Database abstraction layer
- **Custom Tables** - Plugin-specific data storage
- **WordPress Options API** - Settings storage

### PHP Libraries (Composer)

```json
{
  "require": {
    "php": ">=8.0",
    "stripe/stripe-php": "^10.0",
    "google/apiclient": "^2.15",
    "microsoft/microsoft-graph": "^1.100"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.0",
    "wp-coding-standards/wpcs": "^3.0",
    "dealerdirect/phpcodesniffer-composer-installer": "^1.0",
    "phpcompatibility/phpcompatibility-wp": "^2.1"
  }
}
```

#### Library Details

**stripe/stripe-php (v10.x)**
- Official Stripe PHP library
- Payment Intents API support
- Webhook signature verification
- PCI-compliant tokenization

**google/apiclient (v2.15.x)**
- Google Calendar API v3 access
- OAuth 2.0 authentication
- Token refresh handling
- Rate limit handling

**microsoft/microsoft-graph (v1.100.x)**
- Microsoft Graph API access
- Azure AD OAuth 2.0
- Calendar operations
- Free/busy queries

---

## Frontend Technologies

### Core Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| HTML5 | - | Semantic markup |
| CSS3 | - | Styling and layout |
| JavaScript | ES6+ | Interactive functionality |
| jQuery | WP bundled | DOM manipulation (WordPress standard) |

### CSS Architecture
- **CSS Custom Properties** - Theming and customization
- **BEM Methodology** - Class naming convention
- **Flexbox/Grid** - Layout systems
- **Mobile-First** - Responsive approach

### JavaScript Libraries

**Bundled with WordPress (no additional load):**
- jQuery (DOM manipulation)
- wp.ajax (AJAX handling)
- wp.api (REST API client)

**External Libraries:**
```json
{
  "dependencies": {
    "@stripe/stripe-js": "^2.0.0"
  }
}
```

**Stripe.js**
- PCI-compliant card collection
- Stripe Elements UI components
- Payment Intent confirmation
- 3D Secure handling

### Build Tools (Optional)

If using asset compilation:

```json
{
  "devDependencies": {
    "webpack": "^5.0.0",
    "webpack-cli": "^5.0.0",
    "css-loader": "^6.0.0",
    "mini-css-extract-plugin": "^2.0.0",
    "sass": "^1.60.0",
    "sass-loader": "^13.0.0",
    "terser-webpack-plugin": "^5.0.0"
  }
}
```

---

## API Integrations

### Stripe Payment API

| Aspect | Details |
|--------|---------|
| API Version | Latest (auto-managed by library) |
| Authentication | Secret Key (server-side) |
| Frontend | Publishable Key + Stripe.js |
| Payment Method | Payment Intents (SCA-compliant) |
| Card Collection | Stripe Elements |

**Required Stripe Features:**
- Payment Intents API
- Webhook endpoints
- Refunds API
- Products/Prices API (optional)

**Webhook Events:**
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `charge.refunded`
- `charge.dispute.created`

### Google Calendar API

| Aspect | Details |
|--------|---------|
| API | Google Calendar API v3 |
| Authentication | OAuth 2.0 |
| Token Storage | WordPress options (encrypted) |
| Refresh | Automatic via library |

**Required Scopes:**
```
https://www.googleapis.com/auth/calendar
https://www.googleapis.com/auth/calendar.events
```

**Operations Used:**
- `events.insert` - Create booking events
- `events.update` - Update on reschedule
- `events.delete` - Remove on cancellation
- `freebusy.query` - Check availability

### Microsoft Graph API

| Aspect | Details |
|--------|---------|
| API | Microsoft Graph v1.0 |
| Authentication | OAuth 2.0 via Azure AD |
| Token Storage | WordPress options (encrypted) |
| Account Types | Personal & Work/School |

**Required Permissions:**
```
Calendars.ReadWrite
User.Read
```

**Endpoints Used:**
- `POST /me/calendar/events` - Create events
- `PATCH /me/calendar/events/{id}` - Update events
- `DELETE /me/calendar/events/{id}` - Delete events
- `POST /me/calendar/getSchedule` - Free/busy query

---

## Development Tools

### Code Quality

| Tool | Purpose |
|------|---------|
| PHP_CodeSniffer | PHP code standards |
| WordPress Coding Standards | WP-specific rules |
| PHPUnit | Unit testing |
| Jest | JavaScript testing |

### IDE/Editor Setup

**Recommended Extensions:**
- PHP Intelephense (PHP support)
- WordPress Snippets
- ESLint (JavaScript)
- Prettier (code formatting)
- GitLens (Git integration)

**VS Code Settings:**
```json
{
  "php.validate.executablePath": "/usr/bin/php",
  "phpcs.standard": "WordPress",
  "editor.formatOnSave": true
}
```

### Local Development

**Recommended Environments:**
1. **Local by Flywheel** - Easiest setup
2. **XAMPP/MAMP** - Traditional approach
3. **Docker** - Containerized environment
4. **Lando** - Docker-based WordPress

**wp-config.php Development Settings:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
define('SAVEQUERIES', true);
```

---

## Database Schema

### Custom Tables

| Table | Purpose |
|-------|---------|
| `{prefix}booknow_consultation_types` | Service definitions |
| `{prefix}booknow_categories` | Type categorization |
| `{prefix}booknow_bookings` | Appointment records |
| `{prefix}booknow_availability` | Schedule rules |
| `{prefix}booknow_email_log` | Email tracking |

### WordPress Tables Used

| Table | Usage |
|-------|-------|
| `wp_options` | Plugin settings storage |
| `wp_usermeta` | User-specific settings |

### Data Storage Strategy

| Data Type | Storage |
|-----------|---------|
| Plugin settings | `wp_options` (serialized arrays) |
| API credentials | `wp_options` (encrypted) |
| OAuth tokens | `wp_options` (encrypted) |
| Transient data | WordPress transients |
| Bookings | Custom table |
| Consultation types | Custom table |

---

## Security Architecture

### Authentication & Authorization

| Layer | Implementation |
|-------|----------------|
| Admin Access | WordPress capabilities (`manage_options`) |
| REST API (Public) | Nonce verification |
| REST API (Admin) | Capability + Nonce |
| Webhooks | Signature verification |
| OAuth | State parameter validation |

### Data Protection

| Concern | Solution |
|---------|----------|
| SQL Injection | `$wpdb->prepare()` |
| XSS | `esc_html()`, `esc_attr()`, `esc_url()` |
| CSRF | WordPress nonces |
| API Keys | Encrypted storage |
| PCI Compliance | Stripe.js tokenization |

### Encryption

```php
// API key encryption example
function booknow_encrypt($data) {
    $key = wp_salt('auth');
    return base64_encode(openssl_encrypt(
        $data,
        'AES-256-CBC',
        $key,
        0,
        substr(hash('sha256', $key), 0, 16)
    ));
}
```

---

## Performance Considerations

### Caching Strategy

| What | How |
|------|-----|
| Availability calculations | WordPress transients |
| API responses | Short-term transients |
| Static assets | Browser caching headers |
| Database queries | Query result caching |

### Asset Loading

- Conditional loading (only on pages with shortcodes)
- Minified CSS/JS in production
- Async/defer for non-critical scripts
- CDN for Stripe.js

### Database Optimization

- Proper indexes on frequently queried columns
- Efficient queries with specific column selection
- Pagination for large result sets
- Query caching where appropriate

---

## Deployment Architecture

### Directory Structure

```
book-now-kre8iv/
├── book-now-kre8iv.php      # Main plugin file
├── uninstall.php             # Cleanup script
├── composer.json             # PHP dependencies
├── package.json              # JS dependencies (optional)
│
├── vendor/                   # Composer autoload
│   └── autoload.php
│
├── includes/                 # Core PHP classes
├── admin/                    # Admin functionality
├── public/                   # Frontend functionality
├── integrations/             # Third-party integrations
├── api/                      # REST API classes
├── templates/                # Email templates
├── assets/                   # Static assets
└── languages/                # Translation files
```

### Build Process

```bash
# Development
composer install
npm install  # if using build tools

# Production build
composer install --no-dev --optimize-autoloader
npm run build  # if using build tools
```

### Release Checklist

1. Update version in main plugin file
2. Update version in readme.txt
3. Run `composer install --no-dev`
4. Generate POT file for translations
5. Create distributable ZIP (excluding dev files)

---

## Browser Support

### Supported Browsers

| Browser | Version |
|---------|---------|
| Chrome | Last 2 versions |
| Firefox | Last 2 versions |
| Safari | Last 2 versions |
| Edge | Last 2 versions |
| Mobile Safari | iOS 14+ |
| Chrome Mobile | Android 8+ |

### Not Supported

- Internet Explorer (all versions)
- Opera Mini
- Browsers without JavaScript

### Progressive Enhancement

- Core booking works without JavaScript (form fallback)
- Enhanced UX with JavaScript enabled
- Graceful degradation for older browsers

---

## Monitoring & Logging

### Error Logging

```php
// Plugin error logging
if (WP_DEBUG_LOG) {
    error_log('[Book Now] Error message here');
}
```

### Debug Mode

```php
// Enable plugin debug mode
define('BOOKNOW_DEBUG', true);
```

### Recommended Monitoring Tools

- Query Monitor (WordPress plugin)
- Debug Bar (WordPress plugin)
- Stripe Dashboard (payment monitoring)
- Google API Console (Calendar API usage)

---

## Version Control

### Git Strategy

- Feature branches: `claude/<feature>-<session-id>`
- Meaningful commit messages
- No compiled assets in repository
- `.gitignore` for vendor/node_modules

### .gitignore

```
/vendor/
/node_modules/
.env
*.log
.DS_Store
Thumbs.db
```

---

## External Resources

### Official Documentation

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [Stripe API Documentation](https://stripe.com/docs/api)
- [Google Calendar API](https://developers.google.com/calendar/api)
- [Microsoft Graph API](https://docs.microsoft.com/en-us/graph/)

### Useful WordPress Functions

```php
// Database
$wpdb->prepare()
$wpdb->insert()
$wpdb->update()
$wpdb->get_row()
$wpdb->get_results()

// Security
wp_nonce_field()
wp_verify_nonce()
check_ajax_referer()
current_user_can()
sanitize_text_field()
esc_html()
esc_attr()

// Options
get_option()
update_option()
delete_option()

// Transients
get_transient()
set_transient()
delete_transient()

// REST API
register_rest_route()
rest_ensure_response()
WP_REST_Response
WP_Error
```

---

**Last Updated:** 2026-01-08
