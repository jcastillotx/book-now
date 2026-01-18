<?php
/**
 * Unit tests for Book_Now_Encryption class.
 *
 * @package BookNow
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Book_Now_Encryption.
 */
class EncryptionTest extends TestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		// Define AUTH_KEY constant for testing.
		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key-for-encryption-testing' );
		}

		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}

		global $mock_options;
		$mock_options = array();
	}

	/**
	 * Test encrypt() encrypts plaintext value.
	 */
	public function test_encrypt_encrypts_value() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$plaintext = 'secret-api-key';
		$encrypted = Book_Now_Encryption::encrypt( $plaintext );

		$this->assertNotEquals( $plaintext, $encrypted );
		$this->assertStringStartsWith( '$BNENC$', $encrypted );
	}

	/**
	 * Test decrypt() decrypts encrypted value.
	 */
	public function test_decrypt_decrypts_value() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$plaintext = 'secret-api-key';
		$encrypted = Book_Now_Encryption::encrypt( $plaintext );
		$decrypted = Book_Now_Encryption::decrypt( $encrypted );

		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test encrypt() doesn't double-encrypt.
	 */
	public function test_encrypt_prevents_double_encryption() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$plaintext = 'secret-api-key';
		$encrypted = Book_Now_Encryption::encrypt( $plaintext );
		$double_encrypted = Book_Now_Encryption::encrypt( $encrypted );

		// Should return same value, not double-encrypt.
		$this->assertEquals( $encrypted, $double_encrypted );
	}

	/**
	 * Test is_encrypted() detects encrypted values.
	 */
	public function test_is_encrypted_detects_encrypted_values() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$plaintext = 'secret-api-key';
		$encrypted = Book_Now_Encryption::encrypt( $plaintext );

		$this->assertFalse( Book_Now_Encryption::is_encrypted( $plaintext ) );
		$this->assertTrue( Book_Now_Encryption::is_encrypted( $encrypted ) );
	}

	/**
	 * Test encrypt() handles empty values.
	 */
	public function test_encrypt_handles_empty_values() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$empty = '';
		$encrypted = Book_Now_Encryption::encrypt( $empty );

		$this->assertEquals( '', $encrypted );
	}

	/**
	 * Test decrypt() handles empty values.
	 */
	public function test_decrypt_handles_empty_values() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$empty = '';
		$decrypted = Book_Now_Encryption::decrypt( $empty );

		$this->assertEquals( '', $decrypted );
	}

	/**
	 * Test decrypt() returns plaintext for non-encrypted values.
	 */
	public function test_decrypt_returns_plaintext_for_non_encrypted() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$plaintext = 'not-encrypted';
		$decrypted = Book_Now_Encryption::decrypt( $plaintext );

		// Should return as-is for backwards compatibility.
		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test encrypt_settings() encrypts multiple fields.
	 */
	public function test_encrypt_settings_encrypts_fields() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$settings = array(
			'stripe_test_secret_key' => 'sk_test_123',
			'stripe_live_secret_key' => 'sk_live_456',
			'other_field'            => 'not-secret',
		);

		$secret_fields = array( 'stripe_test_secret_key', 'stripe_live_secret_key' );
		$encrypted_settings = Book_Now_Encryption::encrypt_settings( $settings, $secret_fields );

		$this->assertStringStartsWith( '$BNENC$', $encrypted_settings['stripe_test_secret_key'] );
		$this->assertStringStartsWith( '$BNENC$', $encrypted_settings['stripe_live_secret_key'] );
		$this->assertEquals( 'not-secret', $encrypted_settings['other_field'] );
	}

	/**
	 * Test decrypt_settings() decrypts multiple fields.
	 */
	public function test_decrypt_settings_decrypts_fields() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$settings = array(
			'stripe_test_secret_key' => 'sk_test_123',
			'stripe_live_secret_key' => 'sk_live_456',
		);

		$secret_fields = array( 'stripe_test_secret_key', 'stripe_live_secret_key' );
		$encrypted_settings = Book_Now_Encryption::encrypt_settings( $settings, $secret_fields );
		$decrypted_settings = Book_Now_Encryption::decrypt_settings( $encrypted_settings, $secret_fields );

		$this->assertEquals( 'sk_test_123', $decrypted_settings['stripe_test_secret_key'] );
		$this->assertEquals( 'sk_live_456', $decrypted_settings['stripe_live_secret_key'] );
	}

	/**
	 * Test mask() masks sensitive values.
	 */
	public function test_mask_masks_sensitive_values() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$secret = 'sk_test_1234567890';
		$masked = Book_Now_Encryption::mask( $secret, 4 );

		$this->assertStringEndsWith( '7890', $masked );
		$this->assertStringContainsString( '****', $masked );
	}

	/**
	 * Test mask() handles encrypted values.
	 */
	public function test_mask_handles_encrypted_values() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$secret = 'sk_test_1234567890';
		$encrypted = Book_Now_Encryption::encrypt( $secret );
		$masked = Book_Now_Encryption::mask( $encrypted, 4 );

		$this->assertStringEndsWith( '7890', $masked );
	}

	/**
	 * Test is_available() checks for OpenSSL.
	 */
	public function test_is_available_checks_openssl() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$available = Book_Now_Encryption::is_available();

		// Should be true if OpenSSL is available (which it is in most PHP installs).
		$this->assertTrue( $available );
	}

	/**
	 * Test different encryption values for same input.
	 */
	public function test_encryption_uses_random_iv() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		$plaintext = 'secret-api-key';
		$encrypted1 = Book_Now_Encryption::encrypt( $plaintext );
		$encrypted2 = Book_Now_Encryption::encrypt( $plaintext );

		// Due to random IV, encrypted values should be different.
		$this->assertNotEquals( $encrypted1, $encrypted2 );

		// But both should decrypt to same value.
		$this->assertEquals( $plaintext, Book_Now_Encryption::decrypt( $encrypted1 ) );
		$this->assertEquals( $plaintext, Book_Now_Encryption::decrypt( $encrypted2 ) );
	}

	/**
	 * Test save_payment_settings() encrypts before saving.
	 */
	public function test_save_payment_settings_encrypts() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		global $mock_options;

		$settings = array(
			'stripe_test_secret_key' => 'sk_test_123',
			'stripe_live_secret_key' => 'sk_live_456',
			'stripe_mode'            => 'test',
		);

		Book_Now_Encryption::save_payment_settings( $settings );

		// Check that options were saved encrypted.
		$saved = $mock_options['booknow_payment_settings'];
		$this->assertStringStartsWith( '$BNENC$', $saved['stripe_test_secret_key'] );
		$this->assertStringStartsWith( '$BNENC$', $saved['stripe_live_secret_key'] );
		$this->assertEquals( 'test', $saved['stripe_mode'] );
	}

	/**
	 * Test get_payment_settings() decrypts on retrieval.
	 */
	public function test_get_payment_settings_decrypts() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		global $mock_options;

		$settings = array(
			'stripe_test_secret_key' => Book_Now_Encryption::encrypt( 'sk_test_123' ),
			'stripe_live_secret_key' => Book_Now_Encryption::encrypt( 'sk_live_456' ),
		);

		$mock_options['booknow_payment_settings'] = $settings;

		$retrieved = Book_Now_Encryption::get_payment_settings();

		$this->assertEquals( 'sk_test_123', $retrieved['stripe_test_secret_key'] );
		$this->assertEquals( 'sk_live_456', $retrieved['stripe_live_secret_key'] );
	}

	/**
	 * Test encryption migration from unencrypted to encrypted.
	 */
	public function test_encryption_migration() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		global $mock_options;

		// Simulate legacy unencrypted settings.
		$unencrypted_settings = array(
			'stripe_test_secret_key' => 'sk_test_123',
			'stripe_live_secret_key' => 'sk_live_456',
		);

		$mock_options['booknow_payment_settings'] = $unencrypted_settings;

		$retrieved = Book_Now_Encryption::get_payment_settings();

		// Should automatically migrate and save encrypted version.
		$this->assertEquals( 'sk_test_123', $retrieved['stripe_test_secret_key'] );

		// Check that saved version is now encrypted.
		$saved = $mock_options['booknow_payment_settings'];
		$this->assertStringStartsWith( '$BNENC$', $saved['stripe_test_secret_key'] );
	}

	/**
	 * Test integration settings encryption.
	 */
	public function test_integration_settings_encryption() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		global $mock_options;

		$settings = array(
			'google_client_secret'    => 'google-secret-123',
			'microsoft_client_secret' => 'ms-secret-456',
		);

		Book_Now_Encryption::save_integration_settings( $settings );

		// Check that secrets are encrypted.
		$saved = $mock_options['booknow_integration_settings'];
		$this->assertStringStartsWith( '$BNENC$', $saved['google_client_secret'] );
		$this->assertStringStartsWith( '$BNENC$', $saved['microsoft_client_secret'] );

		// Check decryption.
		$retrieved = Book_Now_Encryption::get_integration_settings();
		$this->assertEquals( 'google-secret-123', $retrieved['google_client_secret'] );
		$this->assertEquals( 'ms-secret-456', $retrieved['microsoft_client_secret'] );
	}
}
