<?php
/*
Plugin Name:  Comments For Taxonomy
Plugin URI:   https://idechy.ir/
Description:  افزودن قابلیت کامنت گذاری در صفحات دسته بندی 
Version:      1.0
Author:       idechy
Author URI:   https://idechy.ir/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wpb-tutorial
Domain Path:  /languages
*/


if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

function create_term_comments_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'term_comments';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        comment_id bigint(20) NOT NULL AUTO_INCREMENT,
        comment_term_id bigint(20) NOT NULL,
		comment_author tinytext NOT NULL,
		comment_author_email varchar(100) NOT NULL,
		comment_date datetime DEFAULT current_timestamp(),
		comment_content text NOT NULL,
		comment_approved varchar(20) DEFAULT 0,
		comment_parent bigint(20) DEFAULT 0,
		user_id bigint(20),
        PRIMARY KEY  (comment_id),
        KEY comment_term_id (comment_term_id),
        KEY comment_author_email (comment_author_email),
        KEY comment_date (comment_date),
        KEY comment_approved (comment_approved)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
add_action( 'after_setup_theme', 'create_term_comments_table' );


function display_custom_term_table(){
    $current_term_id = get_queried_object_id();
	$current_user = wp_get_current_user();
	$userEmail = $current_user->user_email;
	$userName = $current_user->display_name;
	$userId = $current_user->ID;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['term_comment_submit'])) {
        ob_start();
        handle_term_comment_submit();
        $data = ob_get_clean();
        echo $data;
    }
    ?>
    <div class="comments-area">
        <div class="termCommentsListParent">
            <?php
                global $wpdb;
                $results = $wpdb->get_results("SELECT * FROM wp_term_comments");
                echo "<ol class='comment-list'>";
                foreach ($results as $row) {
                    $comment_date = new DateTime($row->comment_date);
                    $formatted_date = $comment_date->format('Y-m-d');
                    echo "<li class='comment'>";
                    echo "<div class='comment-body'>";
                    echo "<div class='comment-author vcard'>";
                    echo "<b class='fn'>" . htmlspecialchars($row->comment_author) . "</b> ";
                    echo "<span class='says'>گفت : </span>";
                    echo "</div>";
                    echo "<div class='comment-meta commentmetadata'>";
                    echo "<a href='#'>";
                    echo "<time datetime='" . htmlspecialchars($row->comment_date) . "'>" . htmlspecialchars($formatted_date) . "</time>";
                    echo "</a>";
                    echo "</div>";
                    echo "<div class='comment-content'>";
                    echo "<p>" . nl2br(htmlspecialchars($row->comment_content)) . "</p>";
                    echo "</div>";
                    echo "<div class='reply'>";
                    echo "<a href='#' class='comment-reply-link'>پاسخ</a>";
                    echo "</div>";
                    echo "</div>";
                    echo "</li>";
                }
                echo "</ol>";
            ?>
        </div>
        <div class="termCommentsFormParent">
            <h3 class="termCommentsFormTitle">دیدگاهتان را بنویسید</h3>
            <form method="post">
                <?php echo wp_nonce_field('term_comment_form_submit', 'term_comment_form_submit_field',true,false); ?>
                <div class="userNameAuthorParent">
                    <?php
                        if ( is_user_logged_in() ){
                            echo '<input class="userNameLoggedin" name="comment_author" type="hidden" value="'.$userName.'">';
                        } else{
                            echo '<label>نام : </label>';
                            echo '<input class="userNameAuthor" name="comment_author" type="text">';
                        }
                    ?>
                </div>
                <div class="userEmailAuthorParent">
                    <?php
                        if ( is_user_logged_in() ){
                            echo '<input class="userNameLoggedin" name="comment_author_email" type="hidden" value="'.$userEmail.'">';
                        } else{
                            echo '<label>ایمیل : </label>';
                            echo '<input class="userNameAuthor" name="comment_author_email" type="email">';
                        }
                    ?>
                </div>
                <div class="userCommentContentParent">
                    <label>دیدگاه : </label>
                    <textarea class="userCommentContent" name="comment_content"></textarea>
                </div>
                <input name="comment_term_id" type="hidden" value="<?php echo $current_term_id ?>">
                <input name="user_id" type="hidden" value="<?php echo $userId ?>">
                <input name="comment_parent" type="hidden" value="0">
                <input name="comment_approved" type="hidden" value="0">
                <input type="submit" value="Send" name="term_comment_submit">
            </form> 
        </div>
    </div>
    <?php
}

function add_custom_table_to_term_archive($query) {
    if ($query->is_main_query() && (is_tax() || is_category() || is_tag())) {
        add_action('woocommerce_after_shop_loop', 'display_custom_term_table');
    }
}
add_action('pre_get_posts', 'add_custom_table_to_term_archive');

function handle_term_comment_submit() {
    if (!isset($_POST['term_comment_form_submit_field']) || !wp_verify_nonce($_POST['term_comment_form_submit_field'], 'term_comment_form_submit')) {
        echo '<p>درخواست معتبر نیست.</p>';
        return;
    }
    // Sanitize input
    $comment_term_id = sanitize_text_field($_POST['comment_term_id']);
    $comment_author = sanitize_text_field($_POST['comment_author']);
    $comment_author_email = sanitize_text_field($_POST['comment_author_email']);
    $comment_date = current_time('mysql');
    $comment_content = sanitize_text_field($_POST['comment_content']);
    $comment_approved = sanitize_text_field($_POST['comment_approved']);
    $comment_parent = sanitize_text_field($_POST['comment_parent']);
    $user_id = sanitize_text_field($_POST['user_id']);
    echo $comment_parent;
    // Validate input
    if (empty($comment_term_id) || empty($comment_author) || empty($comment_author_email) || empty($comment_content) || $comment_approved=="" || $comment_parent=="" || $user_id=="") {
        echo '<p>لطفا فیلدهای مورد نیاز را تکمیل نمایید.</p>';
        return;
    }

    // Insert data into the custom table
    global $wpdb;
    $table_name = $wpdb->prefix . 'term_comments';

    $inserted = $wpdb->insert(
        $table_name,
        [
            'comment_term_id' => $comment_term_id,
            'comment_author' => $comment_author,
            'comment_author_email' => $comment_author_email,
            'comment_date' => $comment_date,
            'comment_content' => $comment_content,
            'comment_approved' => $comment_approved,
            'comment_parent' => $comment_parent,
            'user_id' => $user_id
        ],
        [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%d'
        ]
    );

    if ($inserted) {
        echo '<p>سپاسگزاریم؛ دیدگاه شما پس از بررسی منتشر خواهد شد</p>';
    } else {
        echo '<p>دیدگاه ارسال نشد.</p>';
    }
}


add_action('admin_menu', 'custom_comments_menu');
function custom_comments_menu() {
    add_menu_page(
        'Custom Comments',
        'کامنت طبقه‌بندی',
        'manage_options',
        'custom-comments',
        'display_custom_comments',
        'data:image/svg+xml;base64,' . base64_encode(
            '<svg fill="#ffffff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 661.58 661.58"><path class="cls-1" d="m352.84 507.218 242.58-242.58 66.158 66.157-242.58 242.58z"/><path class="cls-1" d="m396.95 595.42-66.16 66.16L88.21 419l242.58-242.58 66.16 66.16L220.53 419z"/><path class="cls-1" d="M573.37 242.58 330.79 485.16 264.63 419l176.42-176.42L264.63 66.16 330.79 0z"/><path class="cls-1" d="m-.003 330.796 242.58-242.58 66.157 66.157-242.58 242.58z"/></svg>')
    );

    add_submenu_page(
        null, // This makes the submenu page hidden from the main menu
        'Edit Comment',
        'Edit Comment',
        'manage_options',
        'edit-comment',
        'edit_comment_page'
    );
}

function display_custom_comments() {
    global $wpdb;

    // Handle actions
    if (isset($_GET['action'])) {
        $action = sanitize_text_field($_GET['action']);
        $comment_id = isset($_GET['comment_id']) ? intval($_GET['comment_id']) : 0;

        if ($action === 'delete' && $comment_id) {
            $wpdb->delete("{$wpdb->prefix}term_comments", ['comment_id' => $comment_id]);
        } elseif ($action === 'approve' && $comment_id) {
            $wpdb->update("{$wpdb->prefix}term_comments", ['comment_approved' => '1'], ['comment_id' => $comment_id]);
        } elseif ($action === 'disapprove' && $comment_id) {
            $wpdb->update("{$wpdb->prefix}term_comments", ['comment_approved' => '0'], ['comment_id' => $comment_id]);
        }
    }

    // Handle pagination
    $limit = 10;
    $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $offset = ($page - 1) * $limit;

    // Handle filtering
    $filter = '';
    if (!empty($_GET['filter_author'])) {
        $filter_author = sanitize_text_field($_GET['filter_author']);
        $filter .= $wpdb->prepare(" AND comment_author LIKE %s", '%' . $wpdb->esc_like($filter_author) . '%');
    }
    if (!empty($_GET['filter_date'])) {
        $filter_date = sanitize_text_field($_GET['filter_date']);
        $filter .= $wpdb->prepare(" AND DATE(comment_date) = %s", $filter_date);
    }
    if (!empty($_GET['filter_approved'])) {
        $filter_approved = sanitize_text_field($_GET['filter_approved']);
        $filter .= $wpdb->prepare(" AND comment_approved = %s", $filter_approved);
    }

    // Handle sorting
    $sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'comment_date';
    $order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
    $allowed_sort_columns = ['comment_term_id', 'comment_author', 'comment_author_email', 'comment_date', 'comment_approved', 'comment_parent', 'user_id'];
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 'comment_date';
    }

    // Query the custom table
    $query = "SELECT * FROM {$wpdb->prefix}term_comments WHERE 1=1 {$filter} ORDER BY {$sort_by} {$order} LIMIT %d OFFSET %d";
    $prepared_query = $wpdb->prepare($query, $limit, $offset);
    $results = $wpdb->get_results($prepared_query);

    // Get the total number of records for pagination
    $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}term_comments WHERE 1=1 {$filter}";
    $total = $wpdb->get_var($total_query);
    $total_pages = ceil($total / $limit);

    // Fetch term names
    $term_ids = wp_list_pluck($results, 'comment_term_id');
    $term_names = [];
    if (!empty($term_ids)) {
        $terms = get_terms(['include' => $term_ids]);
        foreach ($terms as $term) {
            $term_names[$term->term_id] = $term->name;
        }
    }

    // Display the data
    ?>
    <div class="wrap">
        <h1>Custom Comments</h1>
        
        <!-- Filter Form -->
        <form method="get">
            <input type="hidden" name="page" value="custom-comments">
            <input type="text" name="filter_author" value="<?php echo isset($filter_author) ? esc_attr($filter_author) : ''; ?>" placeholder="Filter by author">
            <input type="date" name="filter_date" value="<?php echo isset($filter_date) ? esc_attr($filter_date) : ''; ?>" placeholder="Filter by date">
            <select name="filter_approved">
                <option value="">Filter by approval</option>
                <option value="1" <?php selected($filter_approved, '1'); ?>>Approved</option>
                <option value="0" <?php selected($filter_approved, '0'); ?>>Pending</option>
            </select>
            <button type="submit">Filter</button>
        </form>

        <!-- Data Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><a href="<?php echo esc_url(add_query_arg(['sort_by' => 'comment_term_id', 'order' => $order === 'asc' ? 'desc' : 'asc'])); ?>">عنوان</a></th>
                    <th><a href="<?php echo esc_url(add_query_arg(['sort_by' => 'comment_author', 'order' => $order === 'asc' ? 'desc' : 'asc'])); ?>">نویسنده</a></th>
                    <th><a href="<?php echo esc_url(add_query_arg(['sort_by' => 'comment_author_email', 'order' => $order === 'asc' ? 'desc' : 'asc'])); ?>">ایمیل</a></th>
                    <th><a href="<?php echo esc_url(add_query_arg(['sort_by' => 'comment_date', 'order' => $order === 'asc' ? 'desc' : 'asc'])); ?>">تاریخ</a></th>
                    <th>دیدگاه</th>
                    <th><a href="<?php echo esc_url(add_query_arg(['sort_by' => 'comment_approved', 'order' => $order === 'asc' ? 'desc' : 'asc'])); ?>">وضعیت</a></th>
                    <th><a href="<?php echo esc_url(add_query_arg(['sort_by' => 'comment_parent', 'order' => $order === 'asc' ? 'desc' : 'asc'])); ?>">والد</a></th>
                    <th><a href="<?php echo esc_url(add_query_arg(['sort_by' => 'user_id', 'order' => $order === 'asc' ? 'desc' : 'asc'])); ?>">کاربر</a></th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)) : ?>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html(isset($term_names[$row->comment_term_id]) ? $term_names[$row->comment_term_id] : ''); ?></td>
                            <td><?php echo esc_html($row->comment_author); ?></td>
                            <td><?php echo esc_html($row->comment_author_email); ?></td>
                            <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($row->comment_date . ' +3 hours 30 minutes'))); ?></td>
                            <td><?php echo esc_html($row->comment_content); ?></td>
                            <td>
                                <?php if ($row->comment_approved == '1') : ?>
                                    <span><svg width="24" height="24" viewBox="0 0 24 24" fill="#2fbd47" xmlns="http://www.w3.org/2000/svg"><path d="M12 1.75A10.25 10.25 0 1 0 22.25 12 10.26 10.26 0 0 0 12 1.75m5.07 8.34-5.37 5.37a1.8 1.8 0 0 1-.65.44c-.497.2-1.053.2-1.55 0a2 2 0 0 1-.65-.44L6.19 12.8a1.001 1.001 0 1 1 1.41-1.42l2.67 2.67 5.38-5.37a1 1 0 0 1 1.42 0 1 1 0 0 1 0 1.38z" fill="#2fbd47"/></svg></span>
                                <?php else : ?>
                                    <span><svg width="24" height="24" viewBox="0 0 24 24" fill="#c2c2c2" xmlns="http://www.w3.org/2000/svg"><path d="M12 1.75A10.25 10.25 0 1 0 22.25 12 10.26 10.26 0 0 0 12 1.75m3.88 14.13a1 1 0 0 1-.71.3 1 1 0 0 1-.7-.3l-3.46-3.46V5.68a1 1 0 1 1 2 0v5.92l2.87 2.87a1 1 0 0 1 0 1.41" fill="#c2c2c2"/></svg></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($row->comment_parent); ?></td>
                            <td><?php echo esc_html($row->user_id == 0 ? 'مهمان' : 'کاربر سایت'); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['page' => 'edit-comment', 'comment_id' => $row->comment_id])); ?>">ویرایش</a> |
                                <a style="color:red;" href="<?php echo esc_url(add_query_arg(['action' => 'delete', 'comment_id' => $row->comment_id])); ?>" onclick="return confirm('Are you sure you want to delete this comment?');">حذف</a> |
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'approve', 'comment_id' => $row->comment_id])); ?>">پذیرفتن</a> |
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'disapprove', 'comment_id' => $row->comment_id])); ?>">رد کردن</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9">No comments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php
        if ($total_pages > 1) {
            $current_url = admin_url('admin.php?page=custom-comments');
            $base_url = remove_query_arg('paged', $current_url);
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%', $base_url),
                'format' => '',
                'current' => max(1, $page),
                'total' => $total_pages,
            ));
        }
        ?>
    </div>
    <?php
}

function edit_comment_page() {
    global $wpdb;

    $comment_id = isset($_GET['comment_id']) ? intval($_GET['comment_id']) : 0;

    if ($comment_id) {
        $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}term_comments WHERE comment_id = %d", $comment_id));

        if (!$comment) {
            wp_die('دیدگاه یافت نشد');
        }

        if (isset($_POST['update_comment'])) {
            $comment_author = sanitize_text_field($_POST['comment_author']);
            $comment_author_email = sanitize_email($_POST['comment_author_email']);
            $comment_content = sanitize_textarea_field($_POST['comment_content']);
            $comment_approved = sanitize_text_field($_POST['comment_approved']);

            $wpdb->update(
                "{$wpdb->prefix}term_comments",
                [
                    'comment_author' => $comment_author,
                    'comment_author_email' => $comment_author_email,
                    'comment_content' => $comment_content,
                    'comment_approved' => $comment_approved,
                ],
                ['comment_id' => $comment_id]
            );

            echo '<div class="updated"><p>کامنت با موفقیت به روز شد.</p></div>';
        }
    } else {
        wp_die('Invalid comment ID.');
    }

    ?>
    <div class="wrap">
        <h1>ویرایش کامنت</h1>

        <form method="post">
            <table class="form-table">
                <tr>
                    <th>نویسنده</th>
                    <td><input type="text" name="comment_author" value="<?php echo esc_attr($comment->comment_author); ?>" required></td>
                </tr>
                <tr>
                    <th>ایمیل</th>
                    <td><input type="email" name="comment_author_email" value="<?php echo esc_attr($comment->comment_author_email); ?>" required></td>
                </tr>
                <tr>
                    <th>محتوا</th>
                    <td><textarea name="comment_content" required><?php echo esc_textarea($comment->comment_content); ?></textarea></td>
                </tr>
                <tr>
                    <th>وضعیت</th>
                    <td>
                        <select name="comment_approved">
                            <option value="1" <?php selected($comment->comment_approved, '1'); ?>>Approved</option>
                            <option value="0" <?php selected($comment->comment_approved, '0'); ?>>Pending</option>
                        </select>
                    </td>
                </tr>
            </table>
            <input type="submit" name="update_comment" class="button button-primary" value="به‌روزرسانی">
        </form>
    </div>
    <?php
}



?>