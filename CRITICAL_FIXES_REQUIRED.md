# Critical Fixes Required for Production Release
## Book Now WordPress Plugin - Action Plan

**Priority:** URGENT  
**Estimated Time:** 4-8 hours  
**Must Complete Before:** Any production deployment

---

## Fix #1: Resolve Composer Dependencies Issue

### Problem
Plugin declares dependencies in `composer.json` but they're not included in the repository:
- `stripe/stripe-php: ^10.0`
- `google/apiclient: ^2.15`
- `microsoft/microsoft-graph: ^1.100`

### Impact
**CRITICAL:** Plugin will fail immediately on activation when trying to use these libraries.

### Solution Options

#### Option A: Remove Unused Dependencies (RECOMMENDED for v1.0.0)
Since Stripe and Calendar features aren't implemented yet (Phase 1 only), remove them temporarily.

**File:** `composer.json`

```json
{
    "require": {
        "php": ">=8.0"
    }
}
```

**Rationale:** Clean v1.0.0 release without unused dependencies. Add them back when features are implemented.

#### Option B: Include Vendor Directory
If you need the dependencies now:

1. Run: `composer install --no-dev --optimize-autoloader`
2. Remove `/vendor/` from `.distignore`
3. Add autoloader to main plugin file:

**File:** `book-now-kre8iv.php` (after line 35)

```php
// Load Composer autoloader
if (file_exists(BOOK_NOW_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once BOOK_NOW_PLUGIN_DIR . 'vendor/autoload.php';
}
```

**Action Required:** Choose Option A or B and implement.

---

## Fix #2: Complete Uninstall Script

### Problem
Missing table and options in cleanup.

### Solution

**File:** `uninstall.php`

Replace the entire file with:

```php
<?php
/**
 * Fired when the plugin is uninstalled
 *
 * @package BookNow
 * @since   1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to delete data on uninstall
$delete_data = get_option('booknow_delete_data_on_uninstall', false);

if ($delete_data) {
    global $wpdb;

    // Drop custom tables
    $tables = array(
        $wpdb->prefix . 'booknow_bookings',
        $wpdb->prefix . 'booknow_consultation_types',
        $wpdb->prefix . 'booknow_availability',
        $wpdb->prefix . 'booknow_categories',
        $wpdb->prefix . 'booknow_email_log',
        $wpdb->prefix . 'booknow_team_members', // ADDED
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // Delete plugin options
    $options = array(
        'booknow_version',
        'booknow_general_settings',
        'booknow_payment_settings',
        'booknow_email_settings',
        'booknow_integration_settings',
        'booknow_delete_data_on_uninstall',
        'booknow_setup_wizard_completed',    // ADDED
        'booknow_setup_wizard_redirect',     // ADDED
    );

    foreach ($options as $option) {
        delete_option($option);
    }

    // Clear scheduled events
    wp_clear_scheduled_hook('booknow_send_reminders');
    wp_clear_scheduled_hook('booknow_cleanup_pending_bookings');

    // Flush rewrite rules
    flush_rewrite_rules();
}
```

---

## Fix #3: Prevent SQL Injection in ORDER BY Clauses

### Problem
Dynamic ORDER BY construction without validation allows SQL injection.

### Solution

**File:** `includes/class-book-now-consultation-type.php`

Replace the `get_all()` method (lines 15-58):

```php
/**
 * Get all consultation types.
 *
 * @param array $args Query arguments.
 * @return array
 */
public static function get_all($args = array()) {
    global $wpdb;
    $table = $wpdb->prefix . 'booknow_consultation_types';

    $defaults = array(
        'status'   => 'active',
        'category' => null,
        'orderby'  => 'name',
        'order'    => 'ASC',
    );

    $args = wp_parse_args($args, $defaults);

    // Whitelist allowed orderby values to prevent SQL injection
    $allowed_orderby = array('name', 'price', 'duration', 'created_at', 'id');
    $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'name';

    // Whitelist allowed order values
    $allowed_order = array('ASC', 'DESC');
    $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'ASC';

    $where = array('1=1');
    $values = array();

    if ($args['status']) {
        $where[] = 'status = %s';
        $values[] = $args['status'];
    }

    if ($args['category']) {
        $where[] = 'category_id = %d';
        $values[] = $args['category'];
    }

    $where_clause = implode(' AND ', $where);
    $order_clause = sprintf('%s %s', $orderby, $order);

    if (!empty($values)) {
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_clause}",
            ...$values
        );
    } else {
        $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_clause}";
    }

    return $wpdb->get_results($sql);
}
```

**File:** `includes/class-book-now-booking.php`

Replace the `get_all()` method (lines 15-80):

```php
/**
 * Get all bookings.
 *
 * @param array $args Query arguments.
 * @return array
 */
public static function get_all($args = array()) {
    global $wpdb;
    $table = $wpdb->prefix . 'booknow_bookings';

    $defaults = array(
        'status'    => '',
        'payment_status' => '',
        'date_from' => '',
        'date_to'   => '',
        'customer_email' => '',
        'consultation_type_id' => null,
        'orderby'   => 'booking_date',
        'order'     => 'DESC',
        'limit'     => 50,
        'offset'    => 0,
    );

    $args = wp_parse_args($args, $defaults);

    // Whitelist allowed orderby values to prevent SQL injection
    $allowed_orderby = array('booking_date', 'booking_time', 'created_at', 'customer_name', 'status', 'payment_status', 'id');
    $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'booking_date';

    // Whitelist allowed order values
    $allowed_order = array('ASC', 'DESC');
    $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'DESC';

    $where = array('1=1');
    $values = array();

    if ($args['status']) {
        $where[] = 'status = %s';
        $values[] = $args['status'];
    }

    if ($args['payment_status']) {
        $where[] = 'payment_status = %s';
        $values[] = $args['payment_status'];
    }

    if ($args['date_from']) {
        $where[] = 'booking_date >= %s';
        $values[] = $args['date_from'];
    }

    if ($args['date_to']) {
        $where[] = 'booking_date <= %s';
        $values[] = $args['date_to'];
    }

    if ($args['customer_email']) {
        $where[] = 'customer_email = %s';
        $values[] = $args['customer_email'];
    }

    if ($args['consultation_type_id']) {
        $where[] = 'consultation_type_id = %d';
        $values[] = $args['consultation_type_id'];
    }

    $where_clause = implode(' AND ', $where);
    $order_clause = sprintf('%s %s', $orderby, $order);

    // Add limit and offset to values array
    $values[] = absint($args['limit']);
    $values[] = absint($args['offset']);

    $sql = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_clause} LIMIT %d OFFSET %d",
        ...$values
    );

    return $wpdb->get_results($sql);
}
```

**File:** `includes/class-book-now-availability.php`

Replace the `get_all()` method (lines 15-60):

```php
/**
 * Get all availability rules.
 *
 * @param array $args Query arguments.
 * @return array
 */
public static function get_all($args = array()) {
    global $wpdb;
    $table = $wpdb->prefix . 'booknow_availability';

    $defaults = array(
        'rule_type'            => '',
        'consultation_type_id' => null,
        'day_of_week'          => null,
        'specific_date'        => null,
        'orderby'              => 'priority',
        'order'                => 'DESC',
    );

    $args = wp_parse_args($args, $defaults);

    // Whitelist allowed orderby values to prevent SQL injection
    $allowed_orderby = array('priority', 'day_of_week', 'specific_date', 'start_time', 'created_at', 'id');
    $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'priority';

    // Whitelist allowed order values
    $allowed_order = array('ASC', 'DESC');
    $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'DESC';

    $where = array('1=1');
    $values = array();

    if ($args['rule_type']) {
        $where[] = 'rule_type = %s';
        $values[] = $args['rule_type'];
    }

    if ($args['consultation_type_id']) {
        $where[] = '(consultation_type_id = %d OR consultation_type_id IS NULL)';
        $values[] = $args['consultation_type_id'];
    }

    if ($args['day_of_week'] !== null) {
        $where[] = 'day_of_week = %d';
        $values[] = $args['day_of_week'];
    }

    if ($args['specific_date']) {
        $where[] = 'specific_date = %s';
        $values[] = $args['specific_date'];
    }

    $where_clause = implode(' AND ', $where);
    $order_clause = sprintf('%s %s', $orderby, $order);

    if (!empty($values)) {
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_clause}",
            ...$values
        );
    } else {
        $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_clause}";
    }

    return $wpdb->get_results($sql);
}
```

---

## Fix #4: Add Activation Requirement Checks

### Problem
No verification of system requirements during activation.

### Solution

**File:** `includes/class-book-now-activator.php`

Replace the `activate()` method (lines 15-25):

```php
/**
 * Activate the plugin.
 *
 * Creates database tables, sets default options, and flushes rewrite rules.
 *
 * @since 1.0.0
 */
public static function activate() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(BOOK_NOW_BASENAME);
        wp_die(
            '<h1>' . __('Plugin Activation Failed', 'book-now-kre8iv') . '</h1>' .
            '<p>' . sprintf(
                __('Book Now requires PHP version 8.0 or higher. You are running PHP %s.', 'book-now-kre8iv'),
                PHP_VERSION
            ) . '</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">' . __('Return to Plugins', 'book-now-kre8iv') . '</a></p>',
            __('Plugin Activation Failed', 'book-now-kre8iv'),
            array('back_link' => true)
        );
    }

    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, '6.0', '<')) {
        deactivate_plugins(BOOK_NOW_BASENAME);
        wp_die(
            '<h1>' . __('Plugin Activation Failed', 'book-now-kre8iv') . '</h1>' .
            '<p>' . sprintf(
                __('Book Now requires WordPress version 6.0 or higher. You are running WordPress %s.', 'book-now-kre8iv'),
                $wp_version
            ) . '</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">' . __('Return to Plugins', 'book-now-kre8iv') . '</a></p>',
            __('Plugin Activation Failed', 'book-now-kre8iv'),
            array('back_link' => true)
        );
    }

    // Check database permissions
    global $wpdb;
    $test_table = $wpdb->prefix . 'booknow_activation_test';
    
    // Suppress errors temporarily
    $wpdb->suppress_errors(true);
    
    $result = $wpdb->query("CREATE TABLE IF NOT EXISTS {$test_table} (id INT)");
    
    if ($result === false) {
        deactivate_plugins(BOOK_NOW_BASENAME);
        wp_die(
            '<h1>' . __('Plugin Activation Failed', 'book-now-kre8iv') . '</h1>' .
            '<p>' . __('Book Now requires database CREATE TABLE permissions. Please contact your hosting provider.', 'book-now-kre8iv') . '</p>' .
            '<p><strong>' . __('Database Error:', 'book-now-kre8iv') . '</strong> ' . $wpdb->last_error . '</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">' . __('Return to Plugins', 'book-now-kre8iv') . '</a></p>',
            __('Plugin Activation Failed', 'book-now-kre8iv'),
            array('back_link' => true)
        );
    }
    
    // Clean up test table
    $wpdb->query("DROP TABLE IF EXISTS {$test_table}");
    $wpdb->suppress_errors(false);

    // Proceed with activation
    self::create_tables();
    self::set_default_options();

    // Set plugin version
    update_option('booknow_version', BOOK_NOW_VERSION);

    // Flush rewrite rules
    flush_rewrite_rules();
}
```

---

## Fix #5: Add Input Validation Functions

### Problem
Missing validation for dates and times.

### Solution

**File:** `includes/helpers.php`

Add these functions at the end of the file (before the closing `?>`):

```php
/**
 * Validate date format (Y-m-d).
 *
 * @param string $date Date string to validate.
 * @return bool
 */
function booknow_validate_date($date) {
    if (empty($date)) {
        return false;
    }
    
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate time format (H:i:s or H:i).
 *
 * @param string $time Time string to validate.
 * @return bool
 */
function booknow_validate_time($time) {
    if (empty($time)) {
        return false;
    }
    
    // Try H:i:s format first
    $t = DateTime::createFromFormat('H:i:s', $time);
    if ($t && $t->format('H:i:s') === $time) {
        return true;
    }
    
    // Try H:i format
    $t = DateTime::createFromFormat('H:i', $time);
    return $t && $t->format('H:i') === $time;
}

/**
 * Validate and sanitize booking date.
 *
 * @param string $date Date to validate.
 * @return string|false Sanitized date or false on failure.
 */
function booknow_validate_booking_date($date) {
    $date = sanitize_text_field($date);
    
    if (!booknow_validate_date($date)) {
        return false;
    }
    
    // Check if date is in the past
    if (strtotime($date) < strtotime('today')) {
        return false;
    }
    
    // Check if date is within booking window
    if (!booknow_is_date_bookable($date)) {
        return false;
    }
    
    return $date;
}

/**
 * Validate and sanitize booking time.
 *
 * @param string $time Time to validate.
 * @return string|false Sanitized time or false on failure.
 */
function booknow_validate_booking_time($time) {
    $time = sanitize_text_field($time);
    
    if (!booknow_validate_time($time)) {
        return false;
    }
    
    // Normalize to H:i:s format
    if (strlen($time) === 5) {
        $time .= ':00';
    }
    
    return $time;
}
```

**File:** `public/class-book-now-public.php`

Update the `ajax_create_booking()` method to use validation (around line 90):

```php
// Validate and sanitize date
$booking_date = booknow_validate_booking_date($_POST['booking_date']);
if (!$booking_date) {
    wp_send_json_error(array('message' => __('Invalid booking date. Please select a valid date.', 'book-now-kre8iv')));
}

// Validate and sanitize time
$booking_time = booknow_validate_booking_time($_POST['booking_time']);
if (!$booking_time) {
    wp_send_json_error(array('message' => __('Invalid booking time. Please select a valid time.', 'book-now-kre8iv')));
}

// Create booking data
$booking_data = array(
    'consultation_type_id' => $consultation_type_id,
    'customer_name'        => sanitize_text_field($_POST['customer_name']),
    'customer_email'       => $email,
    'customer_phone'       => booknow_sanitize_phone($_POST['customer_phone'] ?? ''),
    'customer_notes'       => wp_kses_post($_POST['customer_notes'] ?? ''),
    'booking_date'         => $booking_date,  // Use validated date
    'booking_time'         => $booking_time,  // Use validated time
    'duration'             => $consultation_type->duration,
    'timezone'             => sanitize_text_field($_POST['timezone'] ?? booknow_get_setting('general', 'timezone')),
    'status'               => 'pending',
    'payment_status'       => 'pending',
    'payment_amount'       => $consultation_type->price,
);
```

---

## Fix #6: Improve Error Handling

### Problem
Database errors not logged or handled properly.

### Solution

**File:** `includes/class-book-now-consultation-type.php`

Update the `create()` method (around line 95):

```php
public static function create($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'booknow_consultation_types';

    // Generate slug if not provided
    if (empty($data['slug'])) {
        $data['slug'] = sanitize_title($data['name']);
    }

    // Ensure unique slug
    $data['slug'] = self::unique_slug($data['slug']);

    $result = $wpdb->insert($table, array(
        'name'             => sanitize_text_field($data['name']),
        'slug'             => $data['slug'],
        'description'      => wp_kses_post($data['description'] ?? ''),
        'duration'         => absint($data['duration'] ?? 30),
        'price'            => floatval($data['price'] ?? 0),
        'deposit_amount'   => isset($data['deposit_amount']) ? floatval($data['deposit_amount']) : null,
        'deposit_type'     => sanitize_text_field($data['deposit_type'] ?? 'fixed'),
        'category_id'      => isset($data['category_id']) ? absint($data['category_id']) : null,
        'buffer_before'    => absint($data['buffer_before'] ?? 0),
        'buffer_after'     => absint($data['buffer_after'] ?? 0),
        'status'           => sanitize_text_field($data['status'] ?? 'active'),
    ), array(
        '%s', '%s', '%s', '%d', '%f', '%f', '%s', '%d', '%d', '%d', '%s'
    ));

    if ($result === false) {
        error_log('BookNow DB Error in create_consultation_type: ' . $wpdb->last_error);
        return false;
    }

    return $wpdb->insert_id;
}
```

Apply similar error logging to:
- `Book_Now_Consultation_Type::update()`
- `Book_Now_Consultation_Type::delete()`
- `Book_Now_Booking::create()`
- `Book_Now_Booking::update()`
- `Book_Now_Booking::delete()`
- `Book_Now_Availability::create()`
- `Book_Now_Availability::update()`
- `Book_Now_Availability::delete()`

---

## Testing Checklist After Fixes

### 1. Fresh Installation Test
```bash
# On a clean WordPress installation:
1. Upload plugin
2. Activate plugin
3. Check for errors in debug.log
4. Verify all database tables created
5. Verify default options set
```

### 2. Functionality Test
```bash
1. Create a consultation type
2. Try to create booking (should work without Stripe)
3. View bookings list
4. Update booking status
5. Delete consultation type
```

### 3. Security Test
```bash
1. Try SQL injection in orderby parameter
2. Try XSS in form fields
3. Try CSRF without nonce
4. Verify all inputs sanitized
```

### 4. Uninstall Test
```bash
1. Enable "Delete data on uninstall" in settings
2. Deactivate plugin
3. Delete plugin
4. Check database for orphaned tables
5. Check wp_options for orphaned options
```

---

## Deployment Checklist

- [ ] All 6 critical fixes implemented
- [ ] Code tested on local environment
- [ ] No PHP errors in debug.log
- [ ] No JavaScript console errors
- [ ] Database tables created successfully
- [ ] Uninstall works completely
- [ ] POT file generated: `npm run pot`
- [ ] Version number updated if needed
- [ ] CHANGELOG.md updated
- [ ] Git commit with clear message
- [ ] Create release tag
- [ ] Build distribution package: `npm run zip`

---

## Build Commands

```bash
# Install dependencies (if using Option B for composer)
composer install --no-dev --optimize-autoloader

# Generate translation file
npm run pot

# Create distribution package
npm run zip
```

---

## Estimated Time Breakdown

| Fix | Time | Priority |
|-----|------|----------|
| #1: Composer Dependencies | 30 min | CRITICAL |
| #2: Uninstall Script | 15 min | CRITICAL |
| #3: SQL Injection Fix | 45 min | CRITICAL |
| #4: Activation Checks | 30 min | CRITICAL |
| #5: Input Validation | 60 min | HIGH |
| #6: Error Handling | 45 min | HIGH |
| **Testing** | 120 min | CRITICAL |
| **Total** | **5.5 hours** | |

---

## Support After Fixes

If you encounter any issues after implementing these fixes:

1. Check WordPress debug.log
2. Check browser console for JS errors
3. Verify database tables exist
4. Verify file permissions
5. Test on fresh WordPress installation

---

**Document Version:** 1.0  
**Last Updated:** January 2024  
**Status:** Ready for Implementation
