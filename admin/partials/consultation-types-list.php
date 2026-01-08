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

// Get all consultation types
$consultation_types = Book_Now_Consultation_Type::get_all(array('status' => ''));
?>

<div class="wrap">
    <h1>
        <?php esc_html_e('Consultation Types', 'book-now-kre8iv'); ?>
        <a href="#" class="page-title-action" id="add-consultation-type">
            <?php esc_html_e('Add New', 'book-now-kre8iv'); ?>
        </a>
    </h1>

    <?php if (!empty($consultation_types)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'book-now-kre8iv'); ?></th>
                    <th><?php esc_html_e('Duration', 'book-now-kre8iv'); ?></th>
                    <th><?php esc_html_e('Price', 'book-now-kre8iv'); ?></th>
                    <th><?php esc_html_e('Status', 'book-now-kre8iv'); ?></th>
                    <th><?php esc_html_e('Actions', 'book-now-kre8iv'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consultation_types as $type) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($type->name); ?></strong><br>
                            <small><?php echo esc_html($type->slug); ?></small>
                        </td>
                        <td><?php echo esc_html($type->duration); ?> <?php esc_html_e('mins', 'book-now-kre8iv'); ?></td>
                        <td><?php echo esc_html(booknow_format_price($type->price)); ?></td>
                        <td>
                            <span class="booknow-status-badge status-<?php echo esc_attr($type->status); ?>">
                                <?php echo esc_html(ucfirst($type->status)); ?>
                            </span>
                        </td>
                        <td>
                            <a href="#" class="button button-small edit-consultation-type" data-id="<?php echo esc_attr($type->id); ?>">
                                <?php esc_html_e('Edit', 'book-now-kre8iv'); ?>
                            </a>
                            <a href="#" class="button button-small button-link-delete delete-consultation-type" data-id="<?php echo esc_attr($type->id); ?>">
                                <?php esc_html_e('Delete', 'book-now-kre8iv'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php esc_html_e('No consultation types found. Add your first consultation type to get started.', 'book-now-kre8iv'); ?></p>
    <?php endif; ?>
</div>

<!-- Add/Edit Modal (simplified - would need more complete form) -->
<div id="consultation-type-modal" class="booknow-modal" style="display:none;">
    <div class="booknow-modal-content">
        <span class="booknow-modal-close">&times;</span>
        <h2 id="modal-title"><?php esc_html_e('Add Consultation Type', 'book-now-kre8iv'); ?></h2>
        <form id="consultation-type-form">
            <input type="hidden" name="id" id="consultation-type-id">
            <table class="form-table">
                <tr>
                    <th><label for="name"><?php esc_html_e('Name', 'book-now-kre8iv'); ?> *</label></th>
                    <td><input type="text" name="name" id="name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="duration"><?php esc_html_e('Duration (minutes)', 'book-now-kre8iv'); ?> *</label></th>
                    <td><input type="number" name="duration" id="duration" value="30" min="1" required></td>
                </tr>
                <tr>
                    <th><label for="price"><?php esc_html_e('Price', 'book-now-kre8iv'); ?> *</label></th>
                    <td><input type="number" name="price" id="price" value="0" min="0" step="0.01" required></td>
                </tr>
                <tr>
                    <th><label for="description"><?php esc_html_e('Description', 'book-now-kre8iv'); ?></label></th>
                    <td><textarea name="description" id="description" rows="4" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th><label for="status"><?php esc_html_e('Status', 'book-now-kre8iv'); ?></label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="active"><?php esc_html_e('Active', 'book-now-kre8iv'); ?></option>
                            <option value="inactive"><?php esc_html_e('Inactive', 'book-now-kre8iv'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save', 'book-now-kre8iv'); ?></button>
                <button type="button" class="button booknow-modal-close"><?php esc_html_e('Cancel', 'book-now-kre8iv'); ?></button>
            </p>
        </form>
    </div>
</div>
