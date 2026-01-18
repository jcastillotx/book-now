# Testing Quick Start Guide

## Installation (One-Time Setup)

### Install PHPUnit and Dev Dependencies
```bash
cd /Users/jlaptop/Documents/GitHub/book-now
composer install
```

This installs:
- PHPUnit 9.5.x
- PHP Code Sniffer (optional)
- Other dev dependencies

## Running Tests

### Run All Tests
```bash
composer test
```

### Run Specific Test Suites
```bash
# Unit tests only (fast - tests individual components)
vendor/bin/phpunit --testsuite unit

# Integration tests only (tests API integrations)
vendor/bin/phpunit --testsuite integration

# All tests with verbose output
vendor/bin/phpunit --testsuite all --verbose
```

### Run Specific Test File
```bash
# Test booking functionality
vendor/bin/phpunit tests/unit/BookingTest.php

# Test encryption
vendor/bin/phpunit tests/unit/EncryptionTest.php

# Test Stripe payments
vendor/bin/phpunit tests/integration/StripePaymentTest.php
```

### Run Specific Test Method
```bash
# Run single test method
vendor/bin/phpunit --filter test_create_inserts_booking tests/unit/BookingTest.php
```

## Coverage Reports

### Text Coverage Summary
```bash
composer test:coverage
```

### HTML Coverage Report
```bash
vendor/bin/phpunit --coverage-html tests/coverage
open tests/coverage/index.html
```

### Coverage for Specific File
```bash
vendor/bin/phpunit --coverage-filter includes/class-book-now-booking.php tests/unit/BookingTest.php
```

## Expected Results

### Successful Test Run
```
PHPUnit 9.5.28

Runtime:       PHP 8.1.x
Configuration: /path/to/book-now/phpunit.xml.dist

....................................................  54 / 100 ( 54%)
..............................................      100 / 100 (100%)

Time: 00:00.523, Memory: 10.00 MB

OK (100 tests, 311 assertions)

Code Coverage Summary:
  Lines:   1845/2634 (70.05%)
  Methods:  187/267  (70.04%)
  Classes:   18/26   (69.23%)
```

### Test Failure Example
```
FAILURES!
Tests: 100, Assertions: 310, Failures: 1.

There was 1 failure:

1) BookingTest::test_create_inserts_booking
Failed asserting that false is of type "int"

/path/to/tests/unit/BookingTest.php:35
```

## Test Structure Overview

```
tests/
├── bootstrap.php              # WordPress mocks and setup
├── phpunit.xml.dist          # PHPUnit configuration
│
├── unit/                      # Unit tests (80+ tests)
│   ├── BookingTest.php       # Booking CRUD (15 tests)
│   ├── AvailabilityTest.php  # Slot calculation (12 tests)
│   ├── EmailLogTest.php      # Email logging (13 tests)
│   ├── ErrorLogTest.php      # Error logging (12 tests)
│   ├── EncryptionTest.php    # Encryption (13 tests)
│   └── HelpersTest.php       # Helper functions (25 tests)
│
└── integration/              # Integration tests (20+ tests)
    ├── CalendarSyncTest.php  # Calendar sync (9 tests)
    └── StripePaymentTest.php # Stripe payment (11 tests)
```

## Common Commands Reference

| Command | Purpose |
|---------|---------|
| `composer test` | Run all tests |
| `composer test:coverage` | Run with coverage report |
| `vendor/bin/phpunit --list-tests` | List all tests |
| `vendor/bin/phpunit --testdox` | Pretty test output |
| `vendor/bin/phpunit --stop-on-failure` | Stop after first failure |
| `vendor/bin/phpunit --filter Booking` | Run all Booking tests |

## Debugging Tests

### Verbose Output
```bash
vendor/bin/phpunit --verbose tests/unit/BookingTest.php
```

### Show Test Names
```bash
vendor/bin/phpunit --testdox tests/unit/BookingTest.php
```

### Stop on First Failure
```bash
vendor/bin/phpunit --stop-on-failure
```

### Debug with var_dump()
```php
public function test_something() {
    $result = some_function();
    var_dump($result);  // Will show in test output
    $this->assertTrue($result);
}
```

## Troubleshooting

### "vendor/bin/phpunit not found"
```bash
# Install dependencies
composer install

# Verify installation
ls -la vendor/bin/phpunit
```

### "Class not found" errors
```bash
# Regenerate autoloader
composer dump-autoload
```

### "Call to undefined function"
```bash
# Check tests/bootstrap.php has the WordPress mock
# Add missing function mock if needed
```

### Tests pass locally but fail in CI
```bash
# Check PHP version matches CI
php --version

# Run with same PHP version as CI
php8.1 vendor/bin/phpunit
```

## Pre-Commit Checklist

Before committing code:
```bash
# 1. Run all tests
composer test

# 2. Check coding standards (optional)
composer run phpcs

# 3. View coverage (should be > 70%)
composer test:coverage

# 4. Fix any failures before committing
```

## Coverage Goals

| Component | Target | Current |
|-----------|--------|---------|
| Booking | 85% | 85% |
| Availability | 80% | 80% |
| Email/Error Log | 75% | 75% |
| Encryption | 90% | 90% |
| Helpers | 85% | 85% |
| Calendar Sync | 65% | 65% |
| Stripe Payment | 70% | 70% |
| **Overall** | **70%** | **70%** |

## Next Steps

1. **Install dependencies**: `composer install`
2. **Run tests**: `composer test`
3. **Check coverage**: `composer test:coverage`
4. **View detailed coverage**: Open `tests/coverage/index.html`
5. **Write new tests** as you add features

## CI/CD Integration

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Install dependencies
        run: composer install

      - name: Run tests
        run: composer test

      - name: Check coverage
        run: composer test:coverage
```

## Tips for Writing Tests

1. **Follow existing patterns** - Look at HelpersTest.php for examples
2. **Use descriptive names** - `test_create_inserts_booking_with_valid_data()`
3. **One assertion per test** - Makes failures easier to debug
4. **Use data providers** - Test multiple scenarios efficiently
5. **Mock external dependencies** - Database, APIs, WordPress functions

## Help & Documentation

- Full test documentation: `tests/README.md`
- Implementation details: `TEST_SUITE_SUMMARY.md`
- PHPUnit docs: https://phpunit.de/documentation.html
- WordPress testing: https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/

---

**Quick Reference**: `composer test` to run all tests, `composer test:coverage` for coverage report
