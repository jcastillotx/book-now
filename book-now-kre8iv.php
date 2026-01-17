<?php
/**
 * Plugin Name:       Book Now
 * Plugin URI:        https://github.com/jcastillotx/book-now
 * Description:       A comprehensive WordPress plugin for consultation booking with Stripe payments and calendar integration.
 * Version:           1.3.2
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Kre8iv Tech
 * Author URI:        https://kre8ivtech.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       book-now-kre8iv
 * Domain Path:       /languages
 *
 * @package BookNow
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('BOOK_NOW_VERSION', '1.3.2');

/**
 * Plugin directory path.
 */
define('BOOK_NOW_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('BOOK_NOW_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 */
define('BOOK_NOW_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function booknow_activate() {
    require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-activator.php';
    Book_Now_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function booknow_deactivate() {
    require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-deactivator.php';
    Book_Now_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'booknow_activate');
register_deactivation_hook(__FILE__, 'booknow_deactivate');

/**
 * The core plugin class.
 */
require BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now.php';

/**
 * Begins execution of the plugin.
 */
function booknow_run() {
    $plugin = new Book_Now();
    $plugin->run();
}
booknow_run();
