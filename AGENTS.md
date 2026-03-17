# AGENTS.md

Agent guide for the `book-now` WordPress plugin.

## Project Snapshot

- Type: WordPress plugin
- Entry point: `book-now-kre8iv.php`
- Runtime targets: PHP 8.1+, WordPress 6.0+, Node 18+
- Main areas: `includes/`, `admin/`, `public/`, `tests/`
- Architecture: runtime code is loaded manually with `require_once`, while PHPUnit uses Composer dev dependencies

## Install Commands

Run from the plugin root:

```bash
composer install
npm install
```

## Build Commands

- Production bundle: `npm run build`
- Watch mode for JS/CSS: `npm run dev`
- Translation template: `npm run pot`
- Release zip: `npm run zip`

## Lint And Format Commands

- PHP syntax lint: `composer lint`
- PHP_CodeSniffer (WPCS): `composer phpcs`
- PHP auto-fix: `composer phpcbf`
- JS lint: `npm run lint:js`
- CSS lint: `npm run lint:css`
- JS/CSS format: `npm run format`

## Test Commands

- Full suite: `composer test`
- Coverage text summary: `composer test:coverage`
- Unit suite only: `vendor/bin/phpunit --testsuite unit`
- Integration suite only: `vendor/bin/phpunit --testsuite integration`

## Single-Test Commands

- Single test file: `vendor/bin/phpunit tests/unit/BookingTest.php`
- Single integration file: `vendor/bin/phpunit tests/integration/StripePaymentTest.php`
- Single test class: `vendor/bin/phpunit --filter BookingTest`
- Single test method: `vendor/bin/phpunit --filter test_create_inserts_booking`

## Single-File Lint Commands

- One PHP file with phpcs: `vendor/bin/phpcs --standard=WordPress path/to/file.php`
- Auto-fix one PHP file: `vendor/bin/phpcbf --standard=WordPress path/to/file.php`
- PHP syntax check: `php -l path/to/file.php`
- One JS file: `npx eslint path/to/file.js`
- One CSS file: `npx stylelint path/to/file.css`
- Format one asset file: `npx prettier --write path/to/file.js`

## Testing Notes

- PHPUnit config: `phpunit.xml.dist`
- Bootstrap file: `tests/bootstrap.php`
- Coverage targets `includes/` and excludes vendor, i18n, and loader-style bootstrap classes
- Tests depend on WordPress function mocks and a mocked `$wpdb`

## Architecture Notes

- Runtime classes use names like `Book_Now_Public`, `Book_Now_Booking`, and `Book_Now_REST_API`
- Helper functions live in `includes/helpers.php` and use the `booknow_` prefix
- Frontend and admin JS are plain JavaScript with jQuery, not TypeScript
- Frontend data is passed with `wp_localize_script()`; prefer that over hard-coded AJAX URLs or nonces

## General Editing Rules

- Read the surrounding file before editing and match local conventions
- Make minimal, targeted changes; avoid unrelated refactors
- Preserve WordPress compatibility, existing hook names, and the `book-now-kre8iv` text domain
- Follow WordPress Coding Standards first

## PHP Style Guidelines

- Start files with `<?php`; keep the existing file docblock style when present
- Use tabs for indentation and WordPress spacing: `if ( $value )`, `array( ... )`, `function foo( $bar )`
- Prefer long array syntax in legacy runtime code: `array(...)`
- Prefer single quotes unless interpolation or escaping makes double quotes clearer
- Keep one runtime class per file using `class-book-now-*.php`
- Use WordPress-style class names with underscores, not namespaced runtime classes
- Avoid `use` imports in plugin runtime code
- Load runtime dependencies with `require_once` from known plugin paths

## Data, Validation, And Types

- Sanitize all request data before use
- Unsplash then sanitize superglobals: `sanitize_text_field( wp_unslash( $_POST['field'] ) )`
- Use `absint()` for IDs and counts
- Prefer repository helpers when applicable: `booknow_sanitize_email()`, `booknow_sanitize_phone()`, `booknow_validate_booking_date()`, `booknow_validate_booking_time()`
- Prefer explicit defaults instead of relying on missing indexes
- Return `false`, arrays, `WP_Error`, or JSON responses in the surrounding style

## Database Rules

- Use `$wpdb->prepare()` for every query with dynamic input
- Use prefixed table names via `$wpdb`, not hard-coded `wp_` outside tests
- Keep writes narrow and explicit
- Validate sortable or filterable columns against an allowlist before injecting them into SQL

## Security Rules

- Verify nonces with `check_ajax_referer()` or `wp_verify_nonce()`
- Check capabilities for admin-only actions, typically `current_user_can( 'manage_options' )`
- Escape output with the right function: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Treat `$_POST`, `$_GET`, and webhook payloads as untrusted until validated
- Never commit secrets, live API keys, or OAuth credentials

## Error Handling

- Fail early on missing or invalid input
- Use `wp_send_json_error()` / `wp_send_json_success()` for AJAX handlers
- Keep user-facing errors clear, translatable, and non-sensitive
- Log internal details without exposing secrets or stack traces to end users
- Prefer graceful fallback behavior for Stripe and calendar integrations

## JavaScript Guidelines

- Follow `.eslintrc.json`, which extends `plugin:@wordpress/eslint-plugin/recommended`
- Existing globals include `wp`, `ajaxurl`, `booknow_admin`, `booknow_public`, and `Stripe`
- Use `const` by default and `let` only when reassignment is needed
- Keep browser compatibility aligned with the current tooling and avoid introducing unsupported syntax casually
- Avoid module import/export patterns unless that path already supports them

## CSS Guidelines

- Follow `.stylelintrc.json` and use tabs for indentation
- Reuse existing CSS custom properties such as `--booknow-primary`
- Keep selectors plugin-scoped with the `booknow-` prefix
- Avoid unnecessary specificity, especially in wp-admin overrides

## Naming Conventions

- PHP classes: `Book_Now_X`
- PHP functions/hooks/options/transients: `booknow_*`
- AJAX actions: `booknow_*`
- CSS classes: `booknow-*`
- Test classes: `{ClassName}Test`
- Test methods: `test_<method>_<scenario>`

## Testing Expectations For Changes

- For PHP logic changes, run at least the nearest PHPUnit file or filtered test
- For booking, availability, payment, or DB logic, prefer the relevant targeted test plus broader coverage when the change spans multiple paths
- For JS/CSS changes, run the relevant lint command and rebuild assets when needed
- Before finishing substantial work, report the smallest meaningful set of checks you ran

## Repository Rule Files

- No Cursor rules were found in `.cursor/rules/`
- No `.cursorrules` file was found
- No Copilot instructions were found in `.github/copilot-instructions.md`
