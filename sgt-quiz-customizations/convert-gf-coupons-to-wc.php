<?php

$args = array(
    'role'    => 'churchadmin',
    'orderby' => 'user_nicename',
    'order'   => 'ASC'
);
$users = get_users( $args );

$GFCoupons = GFCoupons::get_instance();

foreach ($users as $user) {
    $coupon = get_user_meta($user->ID, 'church_discount_code', true);

    global $wbdb;
    $results = $wbdb->get_results('SELECT meta FROM msgtp1_gf_addon_feed WHERE meta LIKE "%' . $coupon . '%" AND addon_slug LIKE "gravityformscoupons"');

    if (count($results) <= 0) continue;

    $amount = json_decode($results[0])->usageLimit;


    /**
    * Create a coupon programatically
    */
    $percent = '100'; // Amount
    $discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product

    $coupon = array(
    'post_title' => $coupon,
    'post_content' => 'Church Admin coupon code for church ' . $coupon,
    'post_status' => 'publish',
    'post_author' => 1,
    'post_type' => 'shop_coupon');

    $new_coupon_id = wp_insert_post( $coupon );

    // Add meta
    update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
    update_post_meta( $new_coupon_id, 'coupon_amount', $percent );
    update_post_meta( $new_coupon_id, 'individual_use', 'no' );
    update_post_meta( $new_coupon_id, 'product_ids', '15044,15077' );
    update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
    update_post_meta( $new_coupon_id, 'usage_limit', $amount );
    update_post_meta( $new_coupon_id, 'expiry_date', '' );
    update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
    update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
}