<?php
/**
 * Setup Wizard functionality
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Setup_Wizard {

    /**
     * Current step in the wizard.
     *
     * @var string
     */
    private $step = '';

    /**
     * Steps for the setup wizard.
     *
     * @var array
     */
    private $steps = array();

    /**
     * Initialize the setup wizard.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menus'));
        add_action('admin_init', array($this, 'setup_wizard_redirect'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        $this->steps = array(
            'account_type' => array(
                'name'    => __('Account Type', 'book-now-kre8iv'),
                'view'    => array($this, 'setup_account_type'),
                'handler' => array($this, 'save_account_type'),
            ),
            'business_info' => array(
                'name'    => __('Business Info', 'book-now-kre8iv'),
                'view'    => array($this, 'setup_business_info'),
                'handler' => array($this, 'save_business_info'),
            ),
            'payment' => array(
                'name'    => __('Payment Setup', 'book-now-kre8iv'),
                'view'    => array($this, 'setup_payment'),
                'handler' => array($this, 'save_payment'),
            ),
            'availability' => array(
                'name'    => __('Availability', 'book-now-kre8iv'),
                'view'    => array($this, 'setup_availability'),
                'handler' => array($this, 'save_availability'),
            ),
            'consultation' => array(
                'name'    => __('First Service', 'book-now-kre8iv'),
                'view'    => array($this, 'setup_consultation'),
                'handler' => array($this, 'save_consultation'),
            ),
            'complete' => array(
                'name'    => __('Complete', 'book-now-kre8iv'),
                'view'    => array($this, 'setup_complete'),
                'handler' => '',
            ),
        );

        $this->step = isset($_GET['step']) ? sanitize_key($_GET['step']) : current(array_keys($this->steps));
    }

    /**
     * Add admin menus/screens.
     */
    public function admin_menus() {
        add_dashboard_page('', '', 'manage_options', 'booknow-setup', '');
    }

    /**
     * Redirect to setup wizard on activation.
     */
    public function setup_wizard_redirect() {
        if (!get_option('booknow_setup_wizard_redirect', false)) {
            return;
        }

        if (get_option('booknow_setup_wizard_completed', false)) {
            return;
        }

        update_option('booknow_setup_wizard_redirect', false);

        if ((!empty($_GET['page']) && in_array($_GET['page'], array('booknow-setup'))) || is_network_admin() || !current_user_can('manage_options')) {
            return;
        }

        wp_safe_redirect(admin_url('index.php?page=booknow-setup'));
        exit;
    }

    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts() {
        if (empty($_GET['page']) || 'booknow-setup' !== $_GET['page']) {
            return;
        }

        wp_enqueue_style('booknow-setup', BOOK_NOW_PLUGIN_URL . 'admin/css/setup-wizard.css', array(), BOOK_NOW_VERSION);
        wp_enqueue_script('booknow-setup', BOOK_NOW_PLUGIN_URL . 'admin/js/setup-wizard.js', array('jquery'), BOOK_NOW_VERSION, true);
        
        wp_localize_script('booknow-setup', 'bookNowSetup', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('booknow_setup_nonce'),
        ));
    }

    /**
     * Show the setup wizard.
     */
    public function setup_wizard() {
        if (empty($_GET['page']) || 'booknow-setup' !== $_GET['page']) {
            return;
        }

        $this->setup_wizard_header();
        $this->setup_wizard_steps();
        $this->setup_wizard_content();
        $this->setup_wizard_footer();
    }

    /**
     * Setup wizard header.
     */
    private function setup_wizard_header() {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width" />
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title><?php esc_html_e('Book Now Setup Wizard', 'book-now-kre8iv'); ?></title>
            <?php do_action('admin_print_styles'); ?>
            <?php do_action('admin_print_scripts'); ?>
            <?php do_action('admin_head'); ?>
        </head>
        <body class="booknow-setup wp-core-ui">
            <div class="booknow-setup-wrapper">
                <div class="booknow-setup-header">
                    <h1><?php esc_html_e('Book Now Setup', 'book-now-kre8iv'); ?></h1>
                    <p><?php esc_html_e('Let\'s get your booking system ready in just a few steps!', 'book-now-kre8iv'); ?></p>
                </div>
        <?php
    }

    /**
     * Setup wizard steps.
     */
    private function setup_wizard_steps() {
        $output_steps = $this->steps;
        ?>
        <ol class="booknow-setup-steps">
            <?php
            foreach ($output_steps as $step_key => $step) {
                $is_completed = array_search($this->step, array_keys($this->steps), true) > array_search($step_key, array_keys($this->steps), true);
                
                if ($step_key === $this->step) {
                    ?>
                    <li class="active"><?php echo esc_html($step['name']); ?></li>
                    <?php
                } elseif ($is_completed) {
                    ?>
                    <li class="done">
                        <a href="<?php echo esc_url(add_query_arg('step', $step_key)); ?>"><?php echo esc_html($step['name']); ?></a>
                    </li>
                    <?php
                } else {
                    ?>
                    <li><?php echo esc_html($step['name']); ?></li>
                    <?php
                }
            }
            ?>
        </ol>
        <?php
    }

    /**
     * Setup wizard content.
     */
    private function setup_wizard_content() {
        echo '<div class="booknow-setup-content">';
        if (!empty($this->steps[$this->step]['view'])) {
            call_user_func($this->steps[$this->step]['view'], $this);
        }
        echo '</div>';
    }

    /**
     * Setup wizard footer.
     */
    private function setup_wizard_footer() {
        ?>
                <div class="booknow-setup-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=book-now')); ?>" class="booknow-setup-footer-link">
                        <?php esc_html_e('Skip setup and go to dashboard', 'book-now-kre8iv'); ?>
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Step 1: Account Type.
     */
    public function setup_account_type() {
        $settings = get_option('booknow_general_settings', array());
        $account_type = isset($settings['account_type']) ? $settings['account_type'] : 'single';
        ?>
        <form method="post" class="booknow-setup-form">
            <?php wp_nonce_field('booknow_setup_account_type'); ?>
            
            <h2><?php esc_html_e('What type of booking system do you need?', 'book-now-kre8iv'); ?></h2>
            <p class="description"><?php esc_html_e('This helps us configure the right features for your business.', 'book-now-kre8iv'); ?></p>

            <div class="booknow-account-types">
                <label class="booknow-account-type-option <?php echo $account_type === 'single' ? 'selected' : ''; ?>">
                    <input type="radio" name="account_type" value="single" <?php checked($account_type, 'single'); ?> required>
                    <div class="account-type-card">
                        <span class="dashicons dashicons-admin-users"></span>
                        <h3><?php esc_html_e('Single Person', 'book-now-kre8iv'); ?></h3>
                        <p><?php esc_html_e('Perfect for solo consultants, coaches, or freelancers. All bookings come directly to you.', 'book-now-kre8iv'); ?></p>
                        <ul>
                            <li><?php esc_html_e('One calendar', 'book-now-kre8iv'); ?></li>
                            <li><?php esc_html_e('Simple availability management', 'book-now-kre8iv'); ?></li>
                            <li><?php esc_html_e('Direct booking flow', 'book-now-kre8iv'); ?></li>
                        </ul>
                    </div>
                </label>

                <label class="booknow-account-type-option <?php echo $account_type === 'agency' ? 'selected' : ''; ?>">
                    <input type="radio" name="account_type" value="agency" <?php checked($account_type, 'agency'); ?> required>
                    <div class="account-type-card">
                        <span class="dashicons dashicons-groups"></span>
                        <h3><?php esc_html_e('Agency / Team', 'book-now-kre8iv'); ?></h3>
                        <p><?php esc_html_e('For businesses with multiple team members. Customers can choose who they book with.', 'book-now-kre8iv'); ?></p>
                        <ul>
                            <li><?php esc_html_e('Multiple team members', 'book-now-kre8iv'); ?></li>
                            <li><?php esc_html_e('Individual calendars', 'book-now-kre8iv'); ?></li>
                            <li><?php esc_html_e('Team member selection', 'book-now-kre8iv'); ?></li>
                        </ul>
                    </div>
                </label>
            </div>

            <p class="booknow-setup-actions">
                <button type="submit" class="button button-primary button-large" name="save_step" value="<?php echo esc_attr($this->step); ?>">
                    <?php esc_html_e('Continue', 'book-now-kre8iv'); ?>
                </button>
            </p>
        </form>
        <?php
    }

    /**
     * Save account type step.
     */
    public function save_account_type() {
        check_admin_referer('booknow_setup_account_type');

        $account_type = isset($_POST['account_type']) ? sanitize_text_field($_POST['account_type']) : 'single';
        
        $settings = get_option('booknow_general_settings', array());
        $settings['account_type'] = $account_type;
        $settings['enable_team_members'] = ($account_type === 'agency');
        update_option('booknow_general_settings', $settings);

        wp_safe_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    /**
     * Step 2: Business Information.
     */
    public function setup_business_info() {
        $settings = get_option('booknow_general_settings', array());
        ?>
        <form method="post" class="booknow-setup-form">
            <?php wp_nonce_field('booknow_setup_business_info'); ?>
            
            <h2><?php esc_html_e('Tell us about your business', 'book-now-kre8iv'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="business_name"><?php esc_html_e('Business Name', 'book-now-kre8iv'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="business_name" name="business_name" class="regular-text" 
                               value="<?php echo esc_attr($settings['business_name'] ?? get_bloginfo('name')); ?>" required>
                        <p class="description"><?php esc_html_e('This will appear in emails and booking confirmations.', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="timezone"><?php esc_html_e('Timezone', 'book-now-kre8iv'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="timezone" name="timezone" class="regular-text" required>
                            <?php
                            $current_timezone = $settings['timezone'] ?? get_option('timezone_string', 'UTC');
                            $timezones = timezone_identifiers_list();
                            foreach ($timezones as $timezone) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($timezone),
                                    selected($current_timezone, $timezone, false),
                                    esc_html($timezone)
                                );
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('All booking times will be displayed in this timezone.', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="currency"><?php esc_html_e('Currency', 'book-now-kre8iv'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="currency" name="currency" class="regular-text" required>
                            <?php
                            $current_currency = $settings['currency'] ?? 'USD';
                            $currencies = array(
                                'USD' => 'US Dollar ($)',
                                'EUR' => 'Euro (€)',
                                'GBP' => 'British Pound (£)',
                                'CAD' => 'Canadian Dollar (C$)',
                                'AUD' => 'Australian Dollar (A$)',
                                'JPY' => 'Japanese Yen (¥)',
                                'INR' => 'Indian Rupee (₹)',
                                'MXN' => 'Mexican Peso ($)',
                            );
                            foreach ($currencies as $code => $name) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($code),
                                    selected($current_currency, $code, false),
                                    esc_html($name)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="booknow-setup-actions">
                <a href="<?php echo esc_url($this->get_prev_step_link()); ?>" class="button button-large">
                    <?php esc_html_e('Previous', 'book-now-kre8iv'); ?>
                </a>
                <button type="submit" class="button button-primary button-large" name="save_step" value="<?php echo esc_attr($this->step); ?>">
                    <?php esc_html_e('Continue', 'book-now-kre8iv'); ?>
                </button>
            </p>
        </form>
        <?php
    }

    /**
     * Save business info step.
     */
    public function save_business_info() {
        check_admin_referer('booknow_setup_business_info');

        $settings = get_option('booknow_general_settings', array());
        $settings['business_name'] = sanitize_text_field($_POST['business_name']);
        $settings['timezone'] = sanitize_text_field($_POST['timezone']);
        $settings['currency'] = sanitize_text_field($_POST['currency']);
        update_option('booknow_general_settings', $settings);

        wp_safe_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    /**
     * Step 3: Payment Setup.
     */
    public function setup_payment() {
        $settings = get_option('booknow_payment_settings', array());
        ?>
        <form method="post" class="booknow-setup-form">
            <?php wp_nonce_field('booknow_setup_payment'); ?>
            
            <h2><?php esc_html_e('Payment Setup (Optional)', 'book-now-kre8iv'); ?></h2>
            <p class="description"><?php esc_html_e('Connect Stripe to accept payments. You can skip this and set it up later.', 'book-now-kre8iv'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Enable Payments', 'book-now-kre8iv'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="payment_required" value="1" <?php checked(!empty($settings['payment_required'])); ?>>
                            <?php esc_html_e('Require payment for bookings', 'book-now-kre8iv'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Uncheck this if you offer free consultations or want to set up payments later.', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
            </table>

            <div id="stripe-settings" style="<?php echo empty($settings['payment_required']) ? 'display:none;' : ''; ?>">
                <h3><?php esc_html_e('Stripe API Keys', 'book-now-kre8iv'); ?></h3>
                <p class="description">
                    <?php
                    printf(
                        esc_html__('Get your API keys from your %s. Start with test mode keys.', 'book-now-kre8iv'),
                        '<a href="https://dashboard.stripe.com/apikeys" target="_blank">' . esc_html__('Stripe Dashboard', 'book-now-kre8iv') . '</a>'
                    );
                    ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="stripe_test_publishable_key"><?php esc_html_e('Test Publishable Key', 'book-now-kre8iv'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="stripe_test_publishable_key" name="stripe_test_publishable_key" 
                                   class="regular-text code" value="<?php echo esc_attr($settings['stripe_test_publishable_key'] ?? ''); ?>" 
                                   placeholder="pk_test_...">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="stripe_test_secret_key"><?php esc_html_e('Test Secret Key', 'book-now-kre8iv'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="stripe_test_secret_key" name="stripe_test_secret_key" 
                                   class="regular-text code" value="<?php echo esc_attr($settings['stripe_test_secret_key'] ?? ''); ?>" 
                                   placeholder="sk_test_...">
                        </td>
                    </tr>
                </table>
            </div>

            <p class="booknow-setup-actions">
                <a href="<?php echo esc_url($this->get_prev_step_link()); ?>" class="button button-large">
                    <?php esc_html_e('Previous', 'book-now-kre8iv'); ?>
                </a>
                <button type="submit" class="button button-large" name="save_step" value="skip">
                    <?php esc_html_e('Skip for Now', 'book-now-kre8iv'); ?>
                </button>
                <button type="submit" class="button button-primary button-large" name="save_step" value="<?php echo esc_attr($this->step); ?>">
                    <?php esc_html_e('Continue', 'book-now-kre8iv'); ?>
                </button>
            </p>
        </form>
        <?php
    }

    /**
     * Save payment step.
     */
    public function save_payment() {
        check_admin_referer('booknow_setup_payment');

        $settings = get_option('booknow_payment_settings', array());
        $settings['payment_required'] = isset($_POST['payment_required']);
        $settings['stripe_test_publishable_key'] = sanitize_text_field($_POST['stripe_test_publishable_key'] ?? '');
        $settings['stripe_test_secret_key'] = sanitize_text_field($_POST['stripe_test_secret_key'] ?? '');
        update_option('booknow_payment_settings', $settings);

        wp_safe_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    /**
     * Step 4: Availability.
     */
    public function setup_availability() {
        ?>
        <form method="post" class="booknow-setup-form">
            <?php wp_nonce_field('booknow_setup_availability'); ?>
            
            <h2><?php esc_html_e('Set Your Availability', 'book-now-kre8iv'); ?></h2>
            <p class="description"><?php esc_html_e('When are you available for bookings? You can adjust this later.', 'book-now-kre8iv'); ?></p>

            <table class="booknow-availability-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Day', 'book-now-kre8iv'); ?></th>
                        <th><?php esc_html_e('Available', 'book-now-kre8iv'); ?></th>
                        <th><?php esc_html_e('Start Time', 'book-now-kre8iv'); ?></th>
                        <th><?php esc_html_e('End Time', 'book-now-kre8iv'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $days = array(
                        1 => __('Monday', 'book-now-kre8iv'),
                        2 => __('Tuesday', 'book-now-kre8iv'),
                        3 => __('Wednesday', 'book-now-kre8iv'),
                        4 => __('Thursday', 'book-now-kre8iv'),
                        5 => __('Friday', 'book-now-kre8iv'),
                        6 => __('Saturday', 'book-now-kre8iv'),
                        0 => __('Sunday', 'book-now-kre8iv'),
                    );
                    
                    foreach ($days as $day_num => $day_name) {
                        $is_weekday = $day_num >= 1 && $day_num <= 5;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($day_name); ?></strong></td>
                            <td>
                                <input type="checkbox" name="availability[<?php echo esc_attr($day_num); ?>][enabled]" 
                                       value="1" <?php checked($is_weekday); ?> class="availability-toggle">
                            </td>
                            <td>
                                <input type="time" name="availability[<?php echo esc_attr($day_num); ?>][start]" 
                                       value="09:00" class="availability-time" <?php disabled(!$is_weekday); ?>>
                            </td>
                            <td>
                                <input type="time" name="availability[<?php echo esc_attr($day_num); ?>][end]" 
                                       value="17:00" class="availability-time" <?php disabled(!$is_weekday); ?>>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>

            <p class="booknow-setup-actions">
                <a href="<?php echo esc_url($this->get_prev_step_link()); ?>" class="button button-large">
                    <?php esc_html_e('Previous', 'book-now-kre8iv'); ?>
                </a>
                <button type="submit" class="button button-primary button-large" name="save_step" value="<?php echo esc_attr($this->step); ?>">
                    <?php esc_html_e('Continue', 'book-now-kre8iv'); ?>
                </button>
            </p>
        </form>
        <?php
    }

    /**
     * Save availability step.
     */
    public function save_availability() {
        check_admin_referer('booknow_setup_availability');

        global $wpdb;
        $table = $wpdb->prefix . 'booknow_availability';

        if (isset($_POST['availability']) && is_array($_POST['availability'])) {
            foreach ($_POST['availability'] as $day => $data) {
                if (!empty($data['enabled'])) {
                    $wpdb->insert(
                        $table,
                        array(
                            'rule_type'    => 'weekly',
                            'day_of_week'  => intval($day),
                            'start_time'   => sanitize_text_field($data['start']),
                            'end_time'     => sanitize_text_field($data['end']),
                            'is_available' => 1,
                        ),
                        array('%s', '%d', '%s', '%s', '%d')
                    );
                }
            }
        }

        wp_safe_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    /**
     * Step 5: First Consultation Type.
     */
    public function setup_consultation() {
        ?>
        <form method="post" class="booknow-setup-form">
            <?php wp_nonce_field('booknow_setup_consultation'); ?>
            
            <h2><?php esc_html_e('Create Your First Service', 'book-now-kre8iv'); ?></h2>
            <p class="description"><?php esc_html_e('What type of consultation or service do you offer? You can add more later.', 'book-now-kre8iv'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="consultation_name"><?php esc_html_e('Service Name', 'book-now-kre8iv'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="consultation_name" name="consultation_name" class="regular-text" 
                               placeholder="<?php esc_attr_e('e.g., Discovery Call, Strategy Session', 'book-now-kre8iv'); ?>" required>
                        <p class="description"><?php esc_html_e('What do you call this service?', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="consultation_duration"><?php esc_html_e('Duration (minutes)', 'book-now-kre8iv'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="consultation_duration" name="consultation_duration" 
                               class="small-text" value="30" min="15" step="15" required>
                        <p class="description"><?php esc_html_e('How long does this service take?', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="consultation_price"><?php esc_html_e('Price', 'book-now-kre8iv'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="consultation_price" name="consultation_price" 
                               class="small-text" value="0" min="0" step="0.01" required>
                        <p class="description"><?php esc_html_e('Set to 0 for free consultations.', 'book-now-kre8iv'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="consultation_description"><?php esc_html_e('Description', 'book-now-kre8iv'); ?></label>
                    </th>
                    <td>
                        <textarea id="consultation_description" name="consultation_description" 
                                  class="large-text" rows="4" 
                                  placeholder="<?php esc_attr_e('Brief description of what this service includes...', 'book-now-kre8iv'); ?>"></textarea>
                    </td>
                </tr>
            </table>

            <p class="booknow-setup-actions">
                <a href="<?php echo esc_url($this->get_prev_step_link()); ?>" class="button button-large">
                    <?php esc_html_e('Previous', 'book-now-kre8iv'); ?>
                </a>
                <button type="submit" class="button button-large" name="save_step" value="skip">
                    <?php esc_html_e('Skip for Now', 'book-now-kre8iv'); ?>
                </button>
                <button type="submit" class="button button-primary button-large" name="save_step" value="<?php echo esc_attr($this->step); ?>">
                    <?php esc_html_e('Continue', 'book-now-kre8iv'); ?>
                </button>
            </p>
        </form>
        <?php
    }

    /**
     * Save consultation step.
     */
    public function save_consultation() {
        check_admin_referer('booknow_setup_consultation');

        if (!empty($_POST['consultation_name'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'booknow_consultation_types';

            $name = sanitize_text_field($_POST['consultation_name']);
            $slug = sanitize_title($name);

            $wpdb->insert(
                $table,
                array(
                    'name'        => $name,
                    'slug'        => $slug,
                    'description' => sanitize_textarea_field($_POST['consultation_description'] ?? ''),
                    'duration'    => intval($_POST['consultation_duration']),
                    'price'       => floatval($_POST['consultation_price']),
                    'status'      => 'active',
                ),
                array('%s', '%s', '%s', '%d', '%f', '%s')
            );
        }

        wp_safe_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    /**
     * Final step: Complete.
     */
    public function setup_complete() {
        update_option('booknow_setup_wizard_completed', true);
        ?>
        <div class="booknow-setup-complete">
            <span class="dashicons dashicons-yes-alt"></span>
            <h2><?php esc_html_e('Setup Complete!', 'book-now-kre8iv'); ?></h2>
            <p><?php esc_html_e('Your booking system is ready to go. Here\'s what you can do next:', 'book-now-kre8iv'); ?></p>

            <div class="booknow-next-steps">
                <div class="next-step">
                    <h3><?php esc_html_e('Add Booking Form to a Page', 'book-now-kre8iv'); ?></h3>
                    <p><?php esc_html_e('Use the shortcode [book_now_form] on any page to display your booking form.', 'book-now-kre8iv'); ?></p>
                </div>

                <div class="next-step">
                    <h3><?php esc_html_e('Configure Calendar Integration', 'book-now-kre8iv'); ?></h3>
                    <p><?php esc_html_e('Connect Google Calendar or Microsoft Calendar to sync your bookings.', 'book-now-kre8iv'); ?></p>
                </div>

                <div class="next-step">
                    <h3><?php esc_html_e('Customize Email Templates', 'book-now-kre8iv'); ?></h3>
                    <p><?php esc_html_e('Personalize the emails sent to customers when they book.', 'book-now-kre8iv'); ?></p>
                </div>
            </div>

            <p class="booknow-setup-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=book-now')); ?>" class="button button-primary button-hero">
                    <?php esc_html_e('Go to Dashboard', 'book-now-kre8iv'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-settings')); ?>" class="button button-large">
                    <?php esc_html_e('View Settings', 'book-now-kre8iv'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Get next step link.
     */
    private function get_next_step_link() {
        $keys = array_keys($this->steps);
        $current_index = array_search($this->step, $keys, true);
        
        if ($current_index !== false && isset($keys[$current_index + 1])) {
            return add_query_arg('step', $keys[$current_index + 1], admin_url('index.php?page=booknow-setup'));
        }
        
        return admin_url('admin.php?page=book-now');
    }

    /**
     * Get previous step link.
     */
    private function get_prev_step_link() {
        $keys = array_keys($this->steps);
        $current_index = array_search($this->step, $keys, true);
        
        if ($current_index !== false && isset($keys[$current_index - 1])) {
            return add_query_arg('step', $keys[$current_index - 1], admin_url('index.php?page=booknow-setup'));
        }
        
        return admin_url('index.php?page=booknow-setup');
    }
}

// Initialize the setup wizard
if (isset($_GET['page']) && 'booknow-setup' === $_GET['page']) {
    new Book_Now_Setup_Wizard();
    add_action('admin_init', function() {
        $wizard = new Book_Now_Setup_Wizard();
        
        if (isset($_POST['save_step']) && $_POST['save_step'] !== 'skip') {
            $step = sanitize_key($_POST['save_step']);
            if (isset($wizard->steps[$step]['handler']) && !empty($wizard->steps[$step]['handler'])) {
                call_user_func($wizard->steps[$step]['handler']);
            }
        } elseif (isset($_POST['save_step']) && $_POST['save_step'] === 'skip') {
            wp_safe_redirect(esc_url_raw($wizard->get_next_step_link()));
            exit;
        }
    }, 1);
    
    add_action('admin_init', function() {
        $wizard = new Book_Now_Setup_Wizard();
        $wizard->setup_wizard();
        exit;
    }, 99);
}
