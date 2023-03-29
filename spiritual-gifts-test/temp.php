<?php
/*
 * Template Name: TESTPLATE
 */

if (!is_user_logged_in() && wp_get_current_user()->user_login == "jacobostrand") return;
echo " --- Hello Jacob, let's get cooking! --- ";

$args = array(
    'role'    => 'churchadmin',
    'orderby' => 'user_nicename',
    'order'   => 'ASC'
);
$users = get_users( $args );

$GFCoupons = GFCoupons::get_instance();
global $wpdb;

$coupons = $wpdb->get_results('SELECT meta FROM msgtp1_gf_addon_feed WHERE addon_slug LIKE "gravityformscoupons"');

foreach ($coupons as $raw_coupon) {
    $coupon = json_decode($raw_coupon->meta);

    foreach ($users as $user) {
        $coupon_code = get_user_meta($user->ID, 'church_discount_code', true);
        $posts = $wpdb->get_results('SELECT * FROM msgtp1_posts WHERE post_title LIKE "' . $coupon_code .'"');
        
        if ($coupon_code == $coupon->couponCode && count($posts) != 0) break;

        if ($coupon_code == $coupon->couponCode && count($posts) == 0) {
            
            $amount_left = $coupon->usageLimit - $coupon->usageCount;
            
            echo $coupon->couponCode . " : " . $coupon->usageLimit . " / " . $amount_left;

            /**
            * Create a coupon programatically
            */
            $percent = '100'; // Amount
            $discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product
        
            $new_coupon = array(
            'post_title' => $coupon_code,
            'post_content' => 'Church Admin coupon code for church ' . $coupon_code,
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon');
        
            $new_coupon_id = wp_insert_post( $new_coupon );
        
            // Add meta
            update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
            update_post_meta( $new_coupon_id, 'coupon_amount', $percent );
            update_post_meta( $new_coupon_id, 'individual_use', 'no' );
            update_post_meta( $new_coupon_id, 'product_ids', '15044,15077' );
            update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
            update_post_meta( $new_coupon_id, 'usage_limit', $amount_left );
            update_post_meta( $new_coupon_id, 'expiry_date', '' );
            update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
            update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

            break;
        }
    }
}