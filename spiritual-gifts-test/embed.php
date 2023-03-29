<?php
/*
 * Template Name: Embed
 */

$church_id = get_query_var('church-id');
if(!$church_id)
	$church_id = $_GET['uid'];

if(!$church_id) {
	echo 'Church ID not found';
	exit;
}

if(!$church_id) {
	echo 'Church ID not found';
	exit;
}

// $ip = $_SERVER['HTTP_CLIENT_IP'] ? $_SERVER['HTTP_CLIENT_IP'] : ($_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
if( ! isset($_GET['launch']) ) {
	$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$conains_params = count(explode("?", $current_url)) > 1;
	echo '<head></head>';
	echo '<body id="spiritual-gift-test-launch" style="text-align: center;">';
		printf(
			'<a class="launch-spiritual-gifts-test" href="javascript:void(0)" onclick="return handleLaunch()">Launch Spiritual Gifts Test</a>
			<style>
				.launch-spiritual-gifts-test {
					background-color: #DA6229;
					color: #FFF;
					display: inline-block;
					font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
					font-size: 24px;
					font-wieght: bold;
					margin: 0 auto;
					padding: 15px 25px;
					text-decoration: none;
				}
				.launch-spiritual-gifts-test:hover {
					background-color: #A72002;
					text-decoration: none;
				}
			</style>
			<script type="text/javascript">
				function handleLaunch() {
				    window.open("%s' . ($conains_params ? '&launch=true' : '?launch=true') . '", "", "width=800,height=600");
				}
			</script>',
			$current_url
		);
	echo '</body>';
	exit;
}

if(!SGTQuizSettings::verify_subscription($church_id)) {
	echo '<head>';
		gravity_form_enqueue_scripts( 22, true );
		wp_head();
	echo '</head>';
	echo '<body id="embeded-code">';
		echo "Hmmm, something went wrong. Try refreshing your browser. If that does not work <a href='https://spiritualgiftstest.com/contact-us/'>contact us here</a> and give us a detailed account of what happened (Device, Browser). Thanks! sorry for the inconvenience ";
		echo gravity_form(22, false, false, false, '', false );
		wp_footer();
	echo '</body>';
	exit;
}

if(!is_user_logged_in()) {
	echo '<head>';
	gravity_form_enqueue_scripts( 21, true );
	wp_head();

	echo '</head>';
	echo '<body id="embeded-code">';
	//do_shortcode('[gravityforms id="21"]');
	echo '<div id="embed-login-option"><span class="has-account">Already</span><span class="no-account">Don\'t</span> have an account? <div id="login-opener">Click Here</div>';
	wp_login_form();
	echo '<a href="' . wp_lostpassword_url() . '" class="lost-password">Lost Password?</a>';

	echo '</div>';
	echo gravity_form(21, false, false, false, '', false );
	wp_footer();

	echo '</body>';
	exit;

}
$member = sgt_get_current_user();
if (!$member->has_quiz('sgt')) {
	if(!$member->youthtest_embed) {
		echo '<head>';
		gravity_form_enqueue_scripts( 7, true );
		wp_head();
		echo '</head>';
		echo '<body id="embeded-code">';
		//do_shortcode('[gravityforms id="7"]');
		echo '<div class="test-wrap">';
		echo gravity_form( 7, false, false, false, '', false );
		echo '</div>';
		wp_footer();
		echo '</body>';
	} else {
		echo '<head>';
		gravity_form_enqueue_scripts( 9, true );
		wp_head();
		echo '</head>';
		echo '<body id="embeded-code">';
		//do_shortcode('[gravityforms id="7"]');
		echo '<div class="test-wrap">';
		echo gravity_form( 9, false, false, false, '', false );
		echo '</div>';
		wp_footer();
		echo '</body>';
	}
	exit;
}

echo '<head>';
wp_head();
echo '<script type="text/javascript" src="https://www.spiritualgiftstest.com/wp-content/themes/spiritual-gifts-test/ds-script.js?ver=1.1"></script></head>';
?><body><div id="main-content" style="padding: 60px;"><div id="results-page-wrapper"><div id="sgt-results" class="results-page-section active" style="margin-top: 0;">
		<div class="section-header header" style="margin-top: 0;">Your Spiritual Gifts Test Results</div>
		<div class="body-text static">
			The spiritual gifts test results below rank each gift based on your highest to lowest score as compared to the rest of the population.
			Each gift is given a range of High, Average, or Low based on your score. A description of your top three gifts is given below your results.
			Further information is found in the Combined Profile section of this report.
		</div>
		<div class="sgt-results-graph dynamic">
			<table>
				<tr class="tabel-header-row">
					<th>Rank</th>
					<th>Range</th>
					<th>Spiritual Gift</th>
				</tr>
				<?php
				$i = 1;
				$quiz = $member->the_quiz('sgt');
				while($quiz->has_gift()) : $quiz->the_gift();
					echo sprintf('<tr class="%s %s">', $quiz->get_range(), $quiz->get_name());
					echo sprintf('<td>%s</td>', $i++);
					echo sprintf('<td>%s</td>', $quiz->get_range());
					echo sprintf('<td>%s</td>', ucwords($quiz->get_name()));
					echo '</tr>';
				endwhile;

				?>
			</table>
		</div>
		<div class="subsection-header header">Your Top Three Spiritual Gifts Defined</div>
		<div class="sgt-results-top-three">
			<?php
			$i = 1;
			while($quiz->has_gift() && $i<=3) : $quiz->the_gift(); ?>

				<div class="column">
					<div class="sub-header header dynamic <?php echo $quiz->the_name('strtolower'); ?>"> <?php $quiz->the_description_title(); ?> </div>
					<div class="body-text dynamic"> <?php $quiz->the_description(); ?></div><div class="topthree-readmore">Read More</div>
				</div>
				<?php
				$i++;
			endwhile;
			$quiz->reset_gift();

			?>
			</div>
		</div>
		<div class="send-via-email embedded-test">
			<?php echo gravity_form( 25, false, false, false, '', true ); ?>
			Simply enter an email address to forward your results.
			<div style="clear: both;"></div>
		</div>
		<div class="multicolor-divider"><div></div><div></div><div></div><div></div></div>
	</div></div></div>
</body>
