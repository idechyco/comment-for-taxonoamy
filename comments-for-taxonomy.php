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
        KEY comment_approved (comment_approved),
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
add_action( 'after_setup_theme', 'create_term_comments_table' );


function display_custom_term_table(){
	echo 'موفق باشی پسر !';
	echo '<div class="">';
}

function add_custom_table_to_term_archive($query) {
    if ($query->is_main_query() && (is_tax() || is_category() || is_tag())) {
        add_action('woocommerce_after_shop_loop', 'display_custom_term_table');
    }
}
add_action('pre_get_posts', 'add_custom_table_to_term_archive');


?>