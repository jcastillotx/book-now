<?php
/**
 * Encryption utility for sensitive data like API keys.
 *
 * Uses AES-256-CBC encryption with WordPress AUTH_KEY as the key base.
 * Provides automatic migration from plaintext to encrypted values.
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 * @since      1.1.0
 */

class Book_Now_Encryption {

    /**
     * Encryption cipher method.
     *
     * @var string
     */
    const CIPHER = 'aes-256-cbc';

    /**
     * Prefix for encrypted values to identify them.
     *
     * @var string
     */
    const ENCRYPTED_PREFIX = '$BNENC$';

    /**
     * Transient name for tracking fallback key usage warning.
     *
     * @var string
     */
    const FALLBACK_KEY_TRANSIENT = 'booknow_fallback_key_warning';

    /**
     * Get the encryption key derived from WordPress AUTH_KEY.
     *
     * @return string 32-byte encryption key.
     */
    private static function get_key() {
        // Use WordPress AUTH_KEY as the base for our encryption key
        if ( defined( 'AUTH_KEY' ) && AUTH_KEY && AUTH_KEY !== 'put your unique phrase here' ) {
            $auth_key = AUTH_KEY;
        } else {
            $auth_key = 'booknow-default-key-change-me';
            // Set transient to trigger admin warning (expires in 1 hour / session-like behavior)
            if ( ! get_transient( self::FALLBACK_KEY_TRANSIENT ) ) {
                set_transient( self::FALLBACK_KEY_TRANSIENT, true, HOUR_IN_SECONDS );
            }
        }

        // Derive a 32-byte key using SHA-256
        return hash( 'sha256', $auth_key . 'booknow_encryption_salt', true );
    }

    /**
     * Check if encryption is using the fallback key instead of AUTH_KEY.
     *
     * @return bool True if using fallback key, false if AUTH_KEY is properly configured.
     */
    public static function is_using_fallback_key() {
        return ! defined( 'AUTH_KEY' ) || ! AUTH_KEY || AUTH_KEY === 'put your unique phrase here';
    }

    /**
     * Initialize admin notice hooks for fallback key warning.
     *
     * Should be called during plugin initialization.
     *
     * @return void
     */
    public static function init_admin_notices() {
        add_action( 'admin_notices', array( __CLASS__, 'display_fallback_key_warning' ) );
    }

    /**
     * Display admin notice warning about fallback encryption key usage.
     *
     * Only displays if:
     * - User can manage options
     * - Fallback key transient is set (meaning fallback was used)
     * - Currently using fallback key
     *
     * @return void
     */
    public static function display_fallback_key_warning() {
        // Only show to administrators
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Only show if transient is set (fallback key was used) and still using fallback
        if ( ! get_transient( self::FALLBACK_KEY_TRANSIENT ) || ! self::is_using_fallback_key() ) {
            return;
        }

        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e( 'Book Now Warning:', 'book-now-kre8iv' ); ?></strong>
                <?php esc_html_e( "Your site's AUTH_KEY is not properly configured. API keys are being encrypted with a fallback key which is less secure. Please ensure AUTH_KEY is defined in your wp-config.php file.", 'book-now-kre8iv' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Encrypt a string value.
     *
     * @param string $value The plaintext value to encrypt.
     * @return string The encrypted value with prefix and IV, base64 encoded.
     */
    public static function encrypt( $value ) {
        if ( empty( $value ) ) {
            return $value;
        }

        // Don't double-encrypt
        if ( self::is_encrypted( $value ) ) {
            return $value;
        }

        // Check if OpenSSL is available
        if ( ! function_exists( 'openssl_encrypt' ) ) {
            // Fallback: return value as-is if OpenSSL not available
            // This is less secure but prevents breakage
            if ( class_exists( 'Book_Now_Logger' ) ) {
                Book_Now_Logger::warning( 'OpenSSL not available for encryption' );
            }
            return $value;
        }

        $key = self::get_key();
        $iv_length = openssl_cipher_iv_length( self::CIPHER );
        $iv = openssl_random_pseudo_bytes( $iv_length );

        $encrypted = openssl_encrypt( $value, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

        if ( $encrypted === false ) {
            // Encryption failed, return original value
            if ( class_exists( 'Book_Now_Logger' ) ) {
                Book_Now_Logger::error( 'Encryption failed' );
            }
            return $value;
        }

        // Combine IV and encrypted data, then base64 encode
        $combined = $iv . $encrypted;

        return self::ENCRYPTED_PREFIX . base64_encode( $combined );
    }

    /**
     * Decrypt an encrypted string value.
     *
     * @param string $value The encrypted value to decrypt.
     * @return string The decrypted plaintext value.
     */
    public static function decrypt( $value ) {
        if ( empty( $value ) ) {
            return $value;
        }

        // If not encrypted, return as-is (backwards compatibility)
        if ( ! self::is_encrypted( $value ) ) {
            return $value;
        }

        // Check if OpenSSL is available
        if ( ! function_exists( 'openssl_decrypt' ) ) {
            if ( class_exists( 'Book_Now_Logger' ) ) {
                Book_Now_Logger::warning( 'OpenSSL not available for decryption' );
            }
            return '';
        }

        // Remove prefix and base64 decode
        $encoded = substr( $value, strlen( self::ENCRYPTED_PREFIX ) );
        $combined = base64_decode( $encoded );

        if ( $combined === false ) {
            return '';
        }

        $key = self::get_key();
        $iv_length = openssl_cipher_iv_length( self::CIPHER );

        // Split IV and encrypted data
        $iv = substr( $combined, 0, $iv_length );
        $encrypted = substr( $combined, $iv_length );

        $decrypted = openssl_decrypt( $encrypted, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

        if ( $decrypted === false ) {
            if ( class_exists( 'Book_Now_Logger' ) ) {
                Book_Now_Logger::error( 'Decryption failed' );
            }
            return '';
        }

        return $decrypted;
    }

    /**
     * Check if a value is already encrypted.
     *
     * @param string $value The value to check.
     * @return bool True if encrypted, false otherwise.
     */
    public static function is_encrypted( $value ) {
        return is_string( $value ) && strpos( $value, self::ENCRYPTED_PREFIX ) === 0;
    }

    /**
     * Mask a sensitive value for display (show only last 4 characters).
     *
     * @param string $value The value to mask.
     * @param int    $visible_chars Number of characters to show at the end.
     * @return string The masked value.
     */
    public static function mask( $value, $visible_chars = 4 ) {
        if ( empty( $value ) ) {
            return '';
        }

        // If encrypted, decrypt first for masking
        if ( self::is_encrypted( $value ) ) {
            $value = self::decrypt( $value );
        }

        $length = strlen( $value );

        if ( $length <= $visible_chars ) {
            return str_repeat( '*', $length );
        }

        $masked_length = $length - $visible_chars;
        return str_repeat( '*', $masked_length ) . substr( $value, -$visible_chars );
    }

    /**
     * Encrypt sensitive fields in a settings array.
     *
     * @param array $settings     The settings array.
     * @param array $secret_fields Array of field names to encrypt.
     * @return array The settings array with encrypted fields.
     */
    public static function encrypt_settings( $settings, $secret_fields ) {
        foreach ( $secret_fields as $field ) {
            if ( isset( $settings[ $field ] ) && ! empty( $settings[ $field ] ) ) {
                $settings[ $field ] = self::encrypt( $settings[ $field ] );
            }
        }
        return $settings;
    }

    /**
     * Decrypt sensitive fields in a settings array.
     *
     * @param array $settings     The settings array.
     * @param array $secret_fields Array of field names to decrypt.
     * @return array The settings array with decrypted fields.
     */
    public static function decrypt_settings( $settings, $secret_fields ) {
        foreach ( $secret_fields as $field ) {
            if ( isset( $settings[ $field ] ) && ! empty( $settings[ $field ] ) ) {
                $settings[ $field ] = self::decrypt( $settings[ $field ] );
            }
        }
        return $settings;
    }

    /**
     * Get payment settings with automatic decryption.
     *
     * Also handles migration of unencrypted legacy settings.
     *
     * @return array Decrypted payment settings.
     */
    public static function get_payment_settings() {
        $settings = get_option( 'booknow_payment_settings', array() );

        $secret_fields = array(
            'stripe_test_secret_key',
            'stripe_live_secret_key',
        );

        // Check if migration is needed (any secret field is not encrypted)
        $needs_migration = false;
        foreach ( $secret_fields as $field ) {
            if ( isset( $settings[ $field ] ) && ! empty( $settings[ $field ] ) && ! self::is_encrypted( $settings[ $field ] ) ) {
                $needs_migration = true;
                break;
            }
        }

        // Migrate unencrypted keys
        if ( $needs_migration ) {
            $encrypted_settings = self::encrypt_settings( $settings, $secret_fields );
            update_option( 'booknow_payment_settings', $encrypted_settings );
            // Return original unencrypted values for this request
            return $settings;
        }

        // Decrypt and return
        return self::decrypt_settings( $settings, $secret_fields );
    }

    /**
     * Save payment settings with automatic encryption.
     *
     * @param array $settings The settings to save.
     * @return bool True on success, false on failure.
     */
    public static function save_payment_settings( $settings ) {
        $secret_fields = array(
            'stripe_test_secret_key',
            'stripe_live_secret_key',
        );

        // Encrypt secret fields
        $encrypted_settings = self::encrypt_settings( $settings, $secret_fields );

        return update_option( 'booknow_payment_settings', $encrypted_settings );
    }

    /**
     * Get integration settings with automatic decryption.
     *
     * @return array Decrypted integration settings.
     */
    public static function get_integration_settings() {
        $settings = get_option( 'booknow_integration_settings', array() );

        $secret_fields = array(
            'google_client_secret',
            'microsoft_client_secret',
        );

        // Check if migration is needed
        $needs_migration = false;
        foreach ( $secret_fields as $field ) {
            if ( isset( $settings[ $field ] ) && ! empty( $settings[ $field ] ) && ! self::is_encrypted( $settings[ $field ] ) ) {
                $needs_migration = true;
                break;
            }
        }

        // Migrate unencrypted secrets
        if ( $needs_migration ) {
            $encrypted_settings = self::encrypt_settings( $settings, $secret_fields );
            update_option( 'booknow_integration_settings', $encrypted_settings );
            return $settings;
        }

        return self::decrypt_settings( $settings, $secret_fields );
    }

    /**
     * Save integration settings with automatic encryption.
     *
     * @param array $settings The settings to save.
     * @return bool True on success, false on failure.
     */
    public static function save_integration_settings( $settings ) {
        $secret_fields = array(
            'google_client_secret',
            'microsoft_client_secret',
        );

        $encrypted_settings = self::encrypt_settings( $settings, $secret_fields );

        return update_option( 'booknow_integration_settings', $encrypted_settings );
    }

    /**
     * Check if OpenSSL encryption is available.
     *
     * @return bool True if encryption is available.
     */
    public static function is_available() {
        return function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' );
    }
}
