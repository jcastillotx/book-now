<?php
/**
 * Admin consultation types list page
 *
 * @package BookNow
 * @since   1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Security check - verify user has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'book-now-kre8iv'));
}

// Get all consultation types
$consultation_types = Book_Now_Consultation_Type::get_all(array('status' => ''));
?>

<div class="booknow-wrap">
    <div class="booknow-page-header">
        <h1>
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e('Consultation Types', 'book-now-kre8iv'); ?>
        </h1>
        <div class="booknow-page-actions">
            <a href="#" class="button button-primary" id="add-consultation-type">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Add New', 'book-now-kre8iv'); ?>
            </a>
        </div>
    </div>

    <div class="booknow-card">
        <div class="booknow-card-body">
            <?php if (!empty($consultation_types)) : ?>
                <table class="wp-list-table widefat fixed striped booknow-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Duration', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Price', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Status', 'book-now-kre8iv'); ?></th>
                            <th class="booknow-actions-column"><?php esc_html_e('Actions', 'book-now-kre8iv'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultation_types as $type) : ?>
                            <tr>
                                <td>
                                    <strong class="booknow-type-name"><?php echo esc_html($type->name); ?></strong>
                                    <span class="booknow-type-slug"><?php echo esc_html($type->slug); ?></span>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-clock" style="color: var(--booknow-gray-400); font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
                                    <?php echo esc_html($type->duration); ?> <?php esc_html_e('mins', 'book-now-kre8iv'); ?>
                                </td>
                                <td class="booknow-price"><?php echo esc_html(booknow_format_price($type->price)); ?></td>
                                <td>
                                    <span class="booknow-status-badge status-<?php echo esc_attr($type->status); ?>">
                                        <?php echo esc_html(ucfirst($type->status)); ?>
                                    </span>
                                </td>
                                <td class="booknow-actions">
                                    <a href="#" class="button button-small edit-consultation-type" data-id="<?php echo esc_attr($type->id); ?>" title="<?php esc_attr_e('Edit', 'book-now-kre8iv'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                        <span class="screen-reader-text"><?php esc_html_e('Edit', 'book-now-kre8iv'); ?></span>
                                    </a>
                                    <a href="#" class="button button-small button-link-delete delete-consultation-type" data-id="<?php echo esc_attr($type->id); ?>" title="<?php esc_attr_e('Delete', 'book-now-kre8iv'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <span class="screen-reader-text"><?php esc_html_e('Delete', 'book-now-kre8iv'); ?></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="booknow-empty-state">
                    <span class="dashicons dashicons-list-view"></span>
                    <p><?php esc_html_e('No consultation types found.', 'book-now-kre8iv'); ?></p>
                    <p class="description"><?php esc_html_e('Add your first consultation type to get started.', 'book-now-kre8iv'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="consultation-type-modal" class="booknow-modal" style="display:none;">
    <div class="booknow-modal-content">
        <div class="booknow-modal-header">
            <h2 id="modal-title">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Add Consultation Type', 'book-now-kre8iv'); ?>
            </h2>
            <button type="button" class="booknow-modal-close" aria-label="<?php esc_attr_e('Close', 'book-now-kre8iv'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="booknow-modal-body">
            <form id="consultation-type-form">
                <input type="hidden" name="id" id="consultation-type-id">

                <div class="booknow-form-row">
                    <label for="name"><?php esc_html_e('Name', 'book-now-kre8iv'); ?> <span class="required">*</span></label>
                    <input type="text" name="name" id="name" class="regular-text" required placeholder="<?php esc_attr_e('e.g., Initial Consultation', 'book-now-kre8iv'); ?>">
                </div>

                <div class="booknow-form-row booknow-form-row-inline">
                    <div class="booknow-form-field">
                        <label for="duration">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e('Duration', 'book-now-kre8iv'); ?> <span class="required">*</span>
                        </label>
                        <div class="booknow-input-group">
                            <input type="number" name="duration" id="duration" value="30" min="1" required>
                            <span class="booknow-input-suffix"><?php esc_html_e('minutes', 'book-now-kre8iv'); ?></span>
                        </div>
                    </div>
                    <div class="booknow-form-field">
                        <label for="price">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php esc_html_e('Price', 'book-now-kre8iv'); ?> <span class="required">*</span>
                        </label>
                        <div class="booknow-input-group">
                            <span class="booknow-input-prefix">$</span>
                            <input type="number" name="price" id="price" value="0" min="0" step="0.01" required>
                        </div>
                    </div>
                </div>

                <div class="booknow-form-row">
                    <label for="description"><?php esc_html_e('Description', 'book-now-kre8iv'); ?></label>
                    <textarea name="description" id="description" rows="4" class="large-text" placeholder="<?php esc_attr_e('Describe what this consultation type includes...', 'book-now-kre8iv'); ?>"></textarea>
                </div>

                <div class="booknow-form-row">
                    <label for="status"><?php esc_html_e('Status', 'book-now-kre8iv'); ?></label>
                    <select name="status" id="status">
                        <option value="active"><?php esc_html_e('Active', 'book-now-kre8iv'); ?></option>
                        <option value="inactive"><?php esc_html_e('Inactive', 'book-now-kre8iv'); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="booknow-modal-footer">
            <button type="button" class="button booknow-modal-close"><?php esc_html_e('Cancel', 'book-now-kre8iv'); ?></button>
            <button type="submit" form="consultation-type-form" class="button button-primary">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Save', 'book-now-kre8iv'); ?>
            </button>
        </div>
    </div>
</div>
