<?php
/**
 * Define the internationalization functionality
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'book-now-kre8iv',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
