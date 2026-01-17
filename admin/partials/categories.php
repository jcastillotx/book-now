<?php
/**
 * Admin categories page
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

// Handle form submission
if (isset($_POST['save_category']) && check_admin_referer('booknow_save_category')) {
    $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
    
    $data = array(
        'name'          => sanitize_text_field($_POST['name']),
        'slug'          => sanitize_title($_POST['slug']),
        'description'   => wp_kses_post($_POST['description']),
        'parent_id'     => !empty($_POST['parent_id']) ? absint($_POST['parent_id']) : null,
        'display_order' => absint($_POST['display_order']),
    );
    
    if ($category_id) {
        $result = Book_Now_Category::update($category_id, $data);
        $message = __('Category updated successfully.', 'book-now-kre8iv');
    } else {
        $result = Book_Now_Category::create($data);
        $message = __('Category created successfully.', 'book-now-kre8iv');
    }
    
    if ($result) {
        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__('Failed to save category.', 'book-now-kre8iv') . '</p></div>';
    }
}

// Handle delete
if (isset($_GET['delete']) && check_admin_referer('booknow_delete_category_' . $_GET['delete'])) {
    $result = Book_Now_Category::delete(absint($_GET['delete']));
    
    if ($result) {
        echo '<div class="notice notice-success"><p>' . esc_html__('Category deleted successfully.', 'book-now-kre8iv') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__('Cannot delete category. It may have subcategories or be in use by consultation types.', 'book-now-kre8iv') . '</p></div>';
    }
}

// Get categories
$categories = Book_Now_Category::get_with_counts();

// Handle edit
$editing_category = null;
if (isset($_GET['edit'])) {
    $editing_category = Book_Now_Category::get_by_id(absint($_GET['edit']));
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Categories', 'book-now-kre8iv'); ?></h1>
    
    <div class="booknow-categories-page">
        <div class="booknow-categories-form">
            <h2><?php echo $editing_category ? esc_html__('Edit Category', 'book-now-kre8iv') : esc_html__('Add New Category', 'book-now-kre8iv'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('booknow_save_category'); ?>
                
                <?php if ($editing_category) : ?>
                    <input type="hidden" name="category_id" value="<?php echo esc_attr($editing_category->id); ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="name"><?php esc_html_e('Name', 'book-now-kre8iv'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="regular-text" 
                                   value="<?php echo $editing_category ? esc_attr($editing_category->name) : ''; ?>" 
                                   required>
                            <p class="description"><?php esc_html_e('The name of the category.', 'book-now-kre8iv'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="slug"><?php esc_html_e('Slug', 'book-now-kre8iv'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="slug" 
                                   id="slug" 
                                   class="regular-text" 
                                   value="<?php echo $editing_category ? esc_attr($editing_category->slug) : ''; ?>">
                            <p class="description"><?php esc_html_e('URL-friendly version of the name. Leave blank to auto-generate.', 'book-now-kre8iv'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description"><?php esc_html_e('Description', 'book-now-kre8iv'); ?></label>
                        </th>
                        <td>
                            <textarea name="description" 
                                      id="description" 
                                      rows="4" 
                                      class="large-text"><?php echo $editing_category ? esc_textarea($editing_category->description) : ''; ?></textarea>
                            <p class="description"><?php esc_html_e('Optional description for the category.', 'book-now-kre8iv'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="parent_id"><?php esc_html_e('Parent Category', 'book-now-kre8iv'); ?></label>
                        </th>
                        <td>
                            <select name="parent_id" id="parent_id">
                                <option value=""><?php esc_html_e('None (Top Level)', 'book-now-kre8iv'); ?></option>
                                <?php
                                $all_categories = Book_Now_Category::get_all(array('parent_id' => 0));
                                foreach ($all_categories as $cat) {
                                    if ($editing_category && $cat->id == $editing_category->id) {
                                        continue; // Skip self
                                    }
                                    $selected = $editing_category && $editing_category->parent_id == $cat->id ? 'selected' : '';
                                    echo '<option value="' . esc_attr($cat->id) . '" ' . $selected . '>' . esc_html($cat->name) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php esc_html_e('Create a hierarchy by selecting a parent category.', 'book-now-kre8iv'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="display_order"><?php esc_html_e('Display Order', 'book-now-kre8iv'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="display_order" 
                                   id="display_order" 
                                   value="<?php echo $editing_category ? esc_attr($editing_category->display_order) : '0'; ?>" 
                                   min="0">
                            <p class="description"><?php esc_html_e('Lower numbers appear first.', 'book-now-kre8iv'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="save_category" class="button button-primary">
                        <?php echo $editing_category ? esc_html__('Update Category', 'book-now-kre8iv') : esc_html__('Add Category', 'book-now-kre8iv'); ?>
                    </button>
                    <?php if ($editing_category) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-categories')); ?>" class="button">
                            <?php esc_html_e('Cancel', 'book-now-kre8iv'); ?>
                        </a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <div class="booknow-categories-list">
            <h2><?php esc_html_e('Existing Categories', 'book-now-kre8iv'); ?></h2>
            
            <?php if (!empty($categories)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Slug', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Parent', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Types', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Order', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Actions', 'book-now-kre8iv'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category) :
                            $parent = $category->parent_id ? Book_Now_Category::get_by_id($category->parent_id) : null;
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($category->name); ?></strong>
                                    <?php if ($category->description) : ?>
                                        <br><small><?php echo esc_html(wp_trim_words($category->description, 10)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo esc_html($category->slug); ?></code></td>
                                <td><?php echo $parent ? esc_html($parent->name) : '—'; ?></td>
                                <td><?php echo esc_html($category->type_count); ?></td>
                                <td><?php echo esc_html($category->display_order); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg('edit', $category->id)); ?>" class="button button-small">
                                        <?php esc_html_e('Edit', 'book-now-kre8iv'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(wp_nonce_url(
                                        add_query_arg('delete', $category->id),
                                        'booknow_delete_category_' . $category->id
                                    )); ?>" 
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this category?', 'book-now-kre8iv'); ?>');">
                                        <?php esc_html_e('Delete', 'book-now-kre8iv'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No categories found. Add your first category above.', 'book-now-kre8iv'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.booknow-categories-page {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
    margin-top: 20px;
}

.booknow-categories-form,
.booknow-categories-list {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.booknow-categories-form h2,
.booknow-categories-list h2 {
    margin-top: 0;
}

@media (max-width: 782px) {
    .booknow-categories-page {
        grid-template-columns: 1fr;
    }
}
</style>
