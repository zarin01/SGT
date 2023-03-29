<?php
/*
 * Template Name: Spiritual Gifts Test Page
 */

if ( !is_user_logged_in()) {
	wp_redirect( site_url('my-account') );
	exit;
}

get_header();

$member = sgt_get_current_user();
	?>
<div id="main-content">
	<div class="container">
		<h1 class="entry-title main_title"><?php the_title(); ?></h1>
		<div class="the-content"><?php the_content(); ?></div>
<?php
if (!$member->has_quiz('sgt')) {
    if(!$member->youthtest_embed) {
        echo '<div class="test-wrap">';
        echo gravity_form( 7, false, false, false, '', false );
        echo '</div>';
    } else {
        echo '<div class="test-wrap">';
        echo gravity_form( 9, false, false, false, '', false );
        echo '</div>';
    }
} else {
    echo '<div class="redirect-message">You have already taken this test <a href="'. site_url('results-page') .'">click here</a> to view your results.</div>';
} ?>
	  </div>
	</div>
<?php get_footer();?>
