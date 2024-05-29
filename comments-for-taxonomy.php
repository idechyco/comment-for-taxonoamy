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


function bbloomer_shop_product_short_description() {
	the_excerpt();
}

function my_excerpt_length($length){
	return 20;
}
add_filter('excerpt_length', 'my_excerpt_length');

function new_excerpt_more( $more ) {
	return ' ... ';
}
add_filter('excerpt_more', 'new_excerpt_more');


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