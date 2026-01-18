# Book Now Plugin - Test Suite

Comprehensive PHPUnit test suite for the Book Now WordPress plugin.

## Overview

This test suite provides coverage for critical components including booking management, availability calculation, email/error logging, encryption, Stripe payments, and calendar synchronization.

**Current Coverage Target**: 70%

## Test Structure

```
tests/
├── bootstrap.php           # Test environment setup and WordPress mocks
├── phpunit.xml.dist        # PHPUnit configuration
├── unit/                   # Unit tests for individual components
│   ├── HelpersTest.php     # Helper function tests
│   ├── BookingTest.php     # Booking CRUD operations
│   ├── AvailabilityTest.php # Availability slot calculation
│   ├── EmailLogTest.php    # Email logging functionality
│   ├── ErrorLogTest.php    # Error logging functionality
│   └── EncryptionTest.php  # Encryption/decryption tests
└── integration/            # Integration tests
    ├── CalendarSyncTest.php # Calendar synchronization
    └── StripePaymentTest.php # Stripe payment processing
```

## Running Tests

### All Tests
```bash
composer test
```

### Unit Tests Only
```bash
vendor/bin/phpunit --testsuite unit
```

### Integration Tests Only
```bash
vendor/bin/phpunit --testsuite integration
```

### Specific Test File
```bash
vendor/bin/phpunit tests/unit/BookingTest.php
```

### With Coverage Report
```bash
composer test:coverage
```

## Test Categories

### Unit Tests

**BookingTest.php** - Booking Model
- Create booking with validation
- Retrieve bookings by ID, date, reference
- Update booking status and payment
- Delete and cancel bookings
- Input sanitization (XSS prevention)
- Email and phone validation
- Statistics and race condition prevention

**AvailabilityTest.php** - Availability Rules
- Create/update/delete availability rules
- Calculate available time slots
- Detect booking conflicts
- Handle timezone conversions
- Block rule overrides
- SQL injection prevention

**EmailLogTest.php** - Email Logging
- Paginated log retrieval with filters
- Filter by email type, status, date range
- Search by recipient/subject
- Delete old logs (retention policy)
- Export to CSV
- Email statistics and success rate

**ErrorLogTest.php** - Error Logging
- Log errors with levels (DEBUG, INFO, WARNING, ERROR, CRITICAL)
- Filter by level, source, booking
- IP address capture
- User context tracking
- Statistics by level and source
- Cleanup old errors

**EncryptionTest.php** - Data Encryption
- Encrypt/decrypt sensitive data
- Prevent double encryption
- Handle empty values
- Mask sensitive values for display
- Automatic migration from plaintext
- Settings encryption (Stripe keys, OAuth secrets)

### Integration Tests

**CalendarSyncTest.php** - Calendar Integration
- Google Calendar and Microsoft Calendar sync
- Graceful handling of API failures
- Check availability across calendars
- Create/update/delete calendar events
- Respect enable/disable settings

**StripePaymentTest.php** - Payment Processing
- Stripe configuration validation
- Payment intent creation
- Webhook signature verification
- Handle payment success/failure
- Process refunds
- Test/live mode switching

## Coverage Goals

Target: 70% code coverage focusing on critical paths

### Priority Areas (90%+ coverage)
- Booking creation and validation
- Payment processing
- Data encryption
- Availability calculation

### Secondary Areas (60%+ coverage)
- Email/error logging
- Calendar synchronization
- Helper functions

### Excluded from Coverage
- Third-party integrations (mocked)
- i18n/l10n functions
- Loader classes

## Writing New Tests

### Test Naming Convention
- Test class: `{ClassName}Test.php`
- Test method: `test_{method}_{scenario}`
- Data provider: `{test_name}_provider`

### Example Test
```php
/**
 * Test create() inserts booking with required fields.
 */
public function test_create_inserts_booking() {
    $booking_data = array(
        'customer_name'  => 'John Doe',
        'customer_email' => 'john@example.com',
        'booking_date'   => '2026-02-15',
        'booking_time'   => '14:30:00',
        'duration'       => 60,
    );

    $booking_id = Book_Now_Booking::create( $booking_data );

    $this->assertIsInt( $booking_id );
    $this->assertGreaterThan( 0, $booking_id );
}
```

### Best Practices
1. **Arrange-Act-Assert pattern** - Clear test structure
2. **Mock external dependencies** - Database, APIs, WordPress functions
3. **Test one thing** - Single assertion per test method
4. **Use data providers** - Test multiple scenarios efficiently
5. **Descriptive names** - Test name explains what it tests
6. **Clean up** - Reset state in tearDown()

## Mocking WordPress

The test suite provides comprehensive WordPress function mocks in `bootstrap.php`:

- Database: `$wpdb` mock object
- Options: `get_option()`, `update_option()`
- Sanitization: `sanitize_text_field()`, `sanitize_email()`
- Escaping: `esc_html()`, `esc_url_raw()`
- Transients: `get_transient()`, `set_transient()`
- Errors: `WP_Error`, `is_wp_error()`
- Actions/Filters: `add_action()`, `do_action()`

### Example Mock Usage
```php
protected function setUp(): void {
    $this->wpdb = $this->createMock( stdClass::class );
    $this->wpdb->prefix = 'wp_';

    global $wpdb;
    $wpdb = $this->wpdb;

    global $mock_options;
    $mock_options['booknow_general_settings'] = array(
        'currency' => 'USD',
    );
}
```

## Continuous Integration

Tests run automatically on:
- Pull requests
- Commits to main branch
- Pre-deployment checks

### CI Commands
```bash
# Install dependencies
composer install --no-dev

# Run tests
composer test

# Generate coverage
composer test:coverage
```

## Troubleshooting

### Tests Fail with "Class not found"
Ensure Composer autoloader is up to date:
```bash
composer dump-autoload
```

### Database Errors
Check that `$wpdb` mock is properly configured in setUp():
```php
$this->wpdb = $this->createMock( stdClass::class );
$this->wpdb->prefix = 'wp_';
global $wpdb;
$wpdb = $this->wpdb;
```

### WordPress Function Undefined
Add mock function to `tests/bootstrap.php`:
```php
if ( ! function_exists( 'your_function' ) ) {
    function your_function( $param ) {
        return $param;
    }
}
```

## Performance

Test execution time targets:
- Unit tests: < 10 seconds
- Integration tests: < 30 seconds
- Full suite: < 1 minute

## Contributing

When adding new features:
1. Write tests first (TDD)
2. Ensure > 70% coverage for new code
3. Update this README if adding new test categories
4. Run full test suite before committing

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Plugin Unit Tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
- [Composer Scripts](https://getcomposer.org/doc/articles/scripts.md)
