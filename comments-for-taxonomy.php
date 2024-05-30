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



?>