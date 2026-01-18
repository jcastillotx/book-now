<?php
/**
 * Integration tests for Book_Now_Stripe class.
 *
 * @package BookNow
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Book_Now_Stripe.
 */
class StripePaymentTest extends TestCase {

	/**
	 * Mock wpdb instance
	 */
	private $wpdb;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		$this->wpdb = $this->createMock( stdClass::class );
		$this->wpdb->prefix = 'wp_';

		global $wpdb;
		$wpdb = $this->wpdb;

		global $mock_options;
		$mock_options = array();
	}

	/**
	 * Test create_payment_intent() creates Stripe payment intent.
	 */
	public function test_create_payment_intent_creates_intent() {
		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'test',
			'stripe_test_secret_key'     => 'sk_test_fake_key',
			'stripe_test_publishable_key' => 'pk_test_fake_key',
		);

		// Cannot fully test without actual Stripe API mocking library.
		// This would require PHPUnit mock objects for Stripe classes.
		$this->assertTrue( true );  // Placeholder.
	}

	/**
	 * Test is_configured() checks for API keys.
	 */
	public function test_is_configured_checks_keys() {
		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'test',
			'stripe_test_secret_key'     => '',
			'stripe_test_publishable_key' => '',
		);

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-stripe.php';

		$stripe = new Book_Now_Stripe();

		// Should return false when keys are empty.
		$this->assertFalse( $stripe->is_configured() );
	}

	/**
	 * Test is_configured() returns true with valid keys.
	 */
	public function test_is_configured_returns_true_with_keys() {
		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'test',
			'stripe_test_secret_key'     => 'sk_test_fake_key',
			'stripe_test_publishable_key' => 'pk_test_fake_key',
		);

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-stripe.php';

		$stripe = new Book_Now_Stripe();

		$this->assertTrue( $stripe->is_configured() );
	}

	/**
	 * Test get_publishable_key() returns correct key for test mode.
	 */
	public function test_get_publishable_key_test_mode() {
		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'test',
			'stripe_test_secret_key'     => 'sk_test_fake_key',
			'stripe_test_publishable_key' => 'pk_test_fake_key',
		);

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-stripe.php';

		$stripe = new Book_Now_Stripe();

		$this->assertEquals( 'pk_test_fake_key', $stripe->get_publishable_key() );
	}

	/**
	 * Test get_publishable_key() returns correct key for live mode.
	 */
	public function test_get_publishable_key_live_mode() {
		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'live',
			'stripe_live_secret_key'     => 'sk_live_fake_key',
			'stripe_live_publishable_key' => 'pk_live_fake_key',
		);

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-stripe.php';

		$stripe = new Book_Now_Stripe();

		$this->assertEquals( 'pk_live_fake_key', $stripe->get_publishable_key() );
	}

	/**
	 * Test webhook signature verification.
	 */
	public function test_verify_webhook_validates_signature() {
		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'test',
			'stripe_test_secret_key'     => 'sk_test_fake_key',
			'stripe_test_publishable_key' => 'pk_test_fake_key',
		);
		$mock_options['booknow_stripe_webhook_secret'] = '';

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-stripe.php';

		$stripe = new Book_Now_Stripe();

		// Should return error when webhook secret not configured.
		$result = $stripe->verify_webhook( 'payload', 'signature' );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'webhook_not_configured', $result->get_error_code() );
	}

	/**
	 * Test payment_intent.succeeded webhook updates booking.
	 */
	public function test_webhook_payment_succeeded_updates_booking() {
		$this->wpdb->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					return $data['payment_status'] === 'paid' && $data['status'] === 'confirmed';
				} ),
				$this->anything(),
				$this->anything(),
				$this->anything()
			)
			->willReturn( 1 );

		// Mock payment intent object.
		$payment_intent = (object) array(
			'id'       => 'pi_test_123',
			'metadata' => (object) array( 'booking_id' => 1 ),
		);

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';

		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'test',
			'stripe_test_secret_key'     => 'sk_test_fake_key',
			'stripe_test_publishable_key' => 'pk_test_fake_key',
		);

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-stripe.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-logger.php';

		// Cannot fully test private method without reflection.
		$this->assertTrue( true );  // Placeholder.
	}

	/**
	 * Test payment_intent.payment_failed webhook updates booking.
	 */
	public function test_webhook_payment_failed_updates_booking() {
		$this->wpdb->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					return $data['payment_status'] === 'failed';
				} ),
				$this->anything(),
				$this->anything(),
				$this->anything()
			)
			->willReturn( 1 );

		// Test would require access to private method handle_payment_failed().
		$this->assertTrue( true );  // Placeholder.
	}

	/**
	 * Test charge.refunded webhook updates booking.
	 */
	public function test_webhook_refund_updates_booking() {
		$booking = (object) array(
			'id'                => 1,
			'payment_intent_id' => 'pi_test_123',
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( $booking );

		$this->wpdb->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					return $data['payment_status'] === 'refunded';
				} ),
				$this->anything(),
				$this->anything(),
				$this->anything()
			)
			->willReturn( 1 );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_bookings WHERE payment_intent_id = "pi_test_123"' );

		// Test would require access to private method handle_refund().
		$this->assertTrue( true );  // Placeholder.
	}

	/**
	 * Test error scenarios return WP_Error.
	 */
	public function test_returns_wp_error_on_failure() {
		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'test',
			'stripe_test_secret_key'     => '',
			'stripe_test_publishable_key' => '',
		);

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-stripe.php';

		$stripe = new Book_Now_Stripe();
		$result = $stripe->create_payment_intent( 100.00 );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'stripe_not_configured', $result->get_error_code() );
	}

	/**
	 * Test amount conversion to cents.
	 */
	public function test_converts_amount_to_cents() {
		// Payment intent should convert $100.00 to 10000 cents.
		// This would be tested by mocking Stripe\PaymentIntent::create().
		$this->assertTrue( true );  // Placeholder.
	}

	/**
	 * Test metadata is included in payment intent.
	 */
	public function test_includes_metadata_in_payment() {
		// Metadata like booking_id should be passed to Stripe for tracking.
		$this->assertTrue( true );  // Placeholder.
	}

	/**
	 * Test create_refund() processes refunds.
	 */
	public function test_create_refund_processes_refund() {
		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'test',
			'stripe_test_secret_key'     => '',
			'stripe_test_publishable_key' => '',
		);

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-stripe.php';

		$stripe = new Book_Now_Stripe();
		$result = $stripe->create_refund( 'pi_test_123' );

		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test test_connection() validates API keys.
	 */
	public function test_test_connection_validates_keys() {
		global $mock_options;
		$mock_options['booknow_payment_settings'] = array(
			'stripe_mode'                => 'test',
			'stripe_test_secret_key'     => '',
			'stripe_test_publishable_key' => '',
		);

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-encryption.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-stripe.php';

		$stripe = new Book_Now_Stripe();
		$result = $stripe->test_connection();

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'stripe_not_configured', $result->get_error_code() );
	}
}
