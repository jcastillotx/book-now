# Book Now by Kre8iv Tech

A comprehensive WordPress plugin for consultation booking with integrated payment processing and calendar synchronization.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](LICENSE)

---

## Overview

Book Now enables businesses to offer seamless online consultation booking. Visitors can browse available services, view real-time availability, and complete bookings with automatic payment processing and calendar synchronization.

### Key Features

- **Multiple Consultation Types** - Create various services with custom pricing and durations
- **Real-Time Availability** - Calendar, list, and form views showing available slots
- **Stripe Payment Integration** - Secure payment collection (full or deposit)
- **Calendar Sync** - Bidirectional sync with Google Calendar and Microsoft 365/Outlook
- **Flexible Shortcodes** - Embed booking forms anywhere on your site
- **Email Notifications** - Automatic confirmations, reminders, and alerts
- **Test Connection Buttons** - Verify all API integrations before going live

---

## Quick Start

### Installation

1. Download the plugin
2. Upload to `/wp-content/plugins/`
3. Activate via WordPress admin
4. Go to **Book Now > Settings** to configure

### Basic Setup

```
1. Create consultation types (Book Now > Consultation Types)
2. Set your availability (Book Now > Availability)
3. Configure Stripe payments (Book Now > Settings > Payments)
4. Add [book_now_form] shortcode to any page
```

---

## Documentation

| Document | Description |
|----------|-------------|
| [PROJECT_SPEC.md](docs/PROJECT_SPEC.md) | Detailed requirements and specifications |
| [TODO.md](docs/TODO.md) | Development task list and roadmap |
| [TECH_STACK.md](docs/TECH_STACK.md) | Technology stack documentation |
| [INSTALL.md](docs/INSTALL.md) | Installation and setup guide |
| [HELP.md](docs/HELP.md) | User guide and FAQ |
| [API_GUIDE.md](docs/API_GUIDE.md) | API integration guide |
| [CLAUDE.md](CLAUDE.md) | AI assistant development guide |

---

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[book_now_form]` | Complete booking wizard |
| `[book_now_calendar]` | Calendar availability view |
| `[book_now_list]` | List view of available slots |
| `[book_now_types]` | Consultation type cards |
| `[book_now_single type="slug"]` | Single type booking |

**Examples:**
```
[book_now_form category="coaching"]
[book_now_calendar months="2"]
[book_now_types columns="3" layout="grid"]
```

---

## Integrations

### Stripe Payments
- PCI-compliant card collection via Stripe Elements
- Payment Intents API (SCA-compliant)
- Webhook handling for payment events
- Refund processing

### Google Calendar
- OAuth 2.0 authentication
- Automatic event creation
- Busy time blocking
- Bidirectional sync

### Microsoft 365/Outlook
- Azure AD OAuth integration
- Calendar event management
- Free/busy queries
- Personal and work accounts

**All integrations include Test Connection buttons to verify setup.**

---

## Requirements

| Requirement | Minimum |
|-------------|---------|
| WordPress | 6.0+ |
| PHP | 8.0+ |
| MySQL | 5.7+ |
| SSL | Required |

---

## Directory Structure

```
book-now-kre8iv/
├── book-now-kre8iv.php     # Main plugin file
├── includes/               # Core classes
├── admin/                  # Admin functionality
├── public/                 # Frontend functionality
├── integrations/           # Third-party APIs
├── api/                    # REST API endpoints
├── templates/              # Email templates
├── assets/                 # Static assets
├── languages/              # Translations
└── docs/                   # Documentation
```

---

## Development

### Getting Started

```bash
# Clone repository
git clone https://github.com/jcastillotx/book-now.git

# Install dependencies
composer install

# For development
composer install --dev
```

### Code Standards

This project follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).

```bash
# Check code standards
vendor/bin/phpcs --standard=WordPress includes/

# Auto-fix issues
vendor/bin/phpcbf --standard=WordPress includes/
```

### Testing

```bash
# Run PHPUnit tests
vendor/bin/phpunit
```

---

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and code standards checks
5. Submit a pull request

See [CLAUDE.md](CLAUDE.md) for development conventions and guidelines.

---

## Support

- **Documentation:** Check the [docs](docs/) folder
- **Issues:** [GitHub Issues](https://github.com/jcastillotx/book-now/issues)

---

## License

This plugin is licensed under the GPL v2 or later.

```
Book Now by Kre8iv Tech is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
```

---

## Credits

**Developed by:** Kre8iv Tech

**Built with:**
- [Stripe PHP](https://github.com/stripe/stripe-php)
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)
- [Microsoft Graph SDK](https://github.com/microsoftgraph/msgraph-sdk-php)

---

**Version:** 1.0.0
**Status:** In Development
