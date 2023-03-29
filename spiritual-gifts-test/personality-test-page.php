<?php
/*
 * Template Name: Personality Test Page
 */

if ( !is_user_logged_in()) {
	wp_redirect( site_url('my-account') );
	exit;
}

get_header();

?>
<div id="main-content">
	<div class="container">
		<h1 class="entry-title main_title"><?php the_title(); ?></h1>
		<div class="the-content"><?php the_content(); ?></div>
		<?php if( current_user_can('churchadmin') || current_user_can('personalitytest') || current_user_can('administrator') ) {
			if (get_user_meta(get_current_user_id(), 'quiz_results_personality', true)) {
				echo sprintf('<div class="redirect-message">You have already taken this test <a href="%s">click here</a> to view your results.</div>', site_url('results'));
			} else {
				echo '<div class="test-wrap">';
				echo gravity_form( 6, false, false, false, '', false );
				echo '</div>';
			}
		}else {
			echo gravity_form( 18, false, false, false, '', false );
		} 
		?>
	</div>
</div>

<?php 
get_footer(); 
?>
