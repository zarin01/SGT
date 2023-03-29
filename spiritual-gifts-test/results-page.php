<?php
/*
 * Template Name: Results Page Template
 */

if ( !is_user_logged_in()) {
	wp_redirect( site_url('my-account') );
	exit;
}

get_header();
$cu = sgt_get_current_user();

if(isset($_GET['userid']) && $_GET['userid'] != '') {
  if($cu->admin_for($_GET['userid'])) {
    $member = new SGTChurchMember($_GET['userid']);
    }
}

if(!isset($member))
	$member = $cu;

if (!$member->has_quiz()) { ?>
	<div id='main-content'>
		<div id='results-page-wrapper'>
			<div class='no-result-yet'>
				You must take a test before you can view your results.
				<a href='<?php echo site_url('adult-spiritual-gifts-test'); ?>'>Spiritual Gifts Test</a> | <a href='<?php echo site_url('personality-test'); ?>'>Personality Test</a>
			</div>
		</div>
	</div>
<?php
get_footer();
exit;
} ?>
<div id="main-content">
	<div class="title">Spiritual Gifts and Personality Profile</div>
	<div id="results-page-wrapper">
		<div id="results-introduction" class="results-page-section">
			<div class="info">
				<?php echo sprintf("This report is based on answers given by %s %s on %s.",
									$member->user_firstname, $member->user_lastname, $member->quiz_date()); ?>
			</div>
			<div class="name"><?php echo $member->user_firstname; ?>,</div>
			<div class="body-text static">
				<?php 
					$post = get_post(16531);
					$content = apply_filters('the_content', $post->post_content); 
					echo $content;
				?>
				<div class="signature">
					Blessings to you in Christ,</br>
					The SpiritualGiftsTest.com Team
				</div>
			</div>
		</div>
		<div id="quick-summary" class="results-page-section">
			<div class="section-header header">Quick Summary:</div>

			<?php if ($member->has_quiz('sgt')) { ?>

				<div class="top-three-gifts quick-summary-table">
					<div class="sub-header">Your top three gifts:</div>
					<table>
						<tr class="tabel-header-row">
							<th>
								Rank
							</th>
							<th>
								Range
							</th>
							<th>
								Spiritual Gift
							</th>
						</tr>


						<?php
						$quiz = $member->the_quiz('sgt');
						$ranges = array("high", "average", "low");
						$i = 1;
						foreach ($ranges as $range) {
							while($quiz->has_gift()) : $quiz->the_gift();
								if ($range == $quiz->get_range()) {
									echo sprintf('<tr class="%s %s"><td>%d</td><td>%s</td><td>%s</td></tr>',
										$quiz->get_name(), $quiz->get_range(), $i, $quiz->get_range(), ucwords($quiz->get_name()));
									$i++;
									if ($i > 3) break;
								}
							endwhile;
							if ($i > 3) break;
						}
						$quiz->reset_gift();

						?>
					</table>
				</div>
			<?php }

			if ($member->has_quiz('personality')) { ?>

				<div class="personality-results quick-summary-table">
					<div class="sub-header">Your personality results:</div>
					<table>
						<tr class="tabel-header-row">
							<th>Range</th>
							<th>Personality Trait</th>
						</tr>
						<?php
						$quiz = $member->the_quiz('personality');
						$ranges = array("high", "average", "low");
						foreach ($ranges as $range) {
							while($quiz->has_domain()) {
								$quiz->the_domain();

								if ($range == $quiz->get_range()) echo sprintf('<tr><td>%s</td><td>%s</td></tr>', $quiz->get_range(), $quiz->get_name());
							}
						}
						?>
					</table>
				</div>
			<?php } ?>
			<div class="multicolor-divider" style="padding: 25px 0px;"><div></div><div></div><div></div><div></div></div>
		</div>
		<div id="icon-menu" class="results-page-section">
			<div class="sub-header">Click Icons to View Full Results</div>
				<?php
				if (!$member->has_quiz('sgt')) {
					echo sprintf('<div class="section-icon"><a href="%s"><img src="%s"><div class="icon-text">Take Spiritual Gifts Test</div></a></div>',
									site_url('adult-spiritual-gifts-test'), site_url('/wp-content/uploads/2018/02/sgt-icon-noboarder.png'));
				} else {
					echo sprintf('<div class="section-icon icon-sgt"><a href="#sgt-results"><img src="%s"><div class="icon-text">Spiritual Gifts Test Results</div></a></div>',
									site_url('wp-content/uploads/2018/02/sgt-icon-noboarder.png'));
				}

				if (!$member->has_quiz('personality')) {
					echo sprintf('<div class="section-icon"><a href="%s"><img src="%s"><div class="icon-text">Take Personality Test</div></a></div>',
								site_url('personality-test'), site_url('wp-content/uploads/2018/02/Personality-noboarder.png'));
				} else {
					echo sprintf('<div class="section-icon icon-personality"><a href="#personality-results"><img src="%s"><div class="icon-text">Personality Test Results</div></a></div>',
								site_url('wp-content/uploads/2018/02/Personality-noboarder.png'));
				}

				if ($member->has_quiz('sgt') && $member->has_quiz('personality')) { ?>

					<div class="section-icon icon-combined">
						<a href="#combined-results"><img src="<?php echo site_url('wp-content/uploads/2018/02/ResultsIcon-noborder.png'); ?>">
							<div class="icon-text">Combined Profile</div>
						</a>
					</div>
				<?php
				}
				?>

			<div class="multicolor-divider"><div></div><div></div><div></div><div></div></div>
		</div>

		<?php
		if($member->has_quiz('sgt')) :
			$quiz = $member->the_quiz('sgt');
		?>

		<div id="sgt-results" class="results-page-section">
			<div class="section-header header">Your Spiritual Gifts Test Results</div>
			<div class="body-text static">
				The spiritual gifts test results below rank each gift based on your highest to lowest score as compared to the rest of the population. Each gift is given a range of High, Average, or Low based on your score. A description of your top three gifts is given below your results. Further information is found in the Combined Profile section of this report.
			</div>
			<div class="sgt-results-graph dynamic">
				<table>
					<tr class="tabel-header-row">
						<th>
							Rank
						</th>
						<th>
							Range
						</th>
						<th>
							Spiritual Gift
						</th>
					</tr>
					<?php
					$ranges = array("high", "average", "low");
					$i = 1;
					foreach ($ranges as $range) {
						while($quiz->has_gift()) : $quiz->the_gift();
							if ($range == $quiz->get_range()) {
								echo sprintf('<tr class="%s %s"><td>%d</td><td>%s</td><td>%s</td></tr>',
									$quiz->get_range(), $quiz->get_name(),
									$i++, ucwords($quiz->get_range()), ucwords($quiz->get_name()));
							}
						endwhile;
					}
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
			<div class="multicolor-divider"><div></div><div></div><div></div><div></div></div>
		</div>
		<?php
			endif;

		if($member->has_quiz('personality')) : $quiz = $member->the_quiz('personality');
		?>

		<div id="personality-results" class="results-page-section">
			<div class="section-header header">Your Personality Test Results</div>
			<div class="body-text static">
				<?php echo sprintf("<p>This report compares %s %s from %s to other %ss between %s years of age. The personality test results estimate the individual's level on each of the five broad personality domains of the Five-Factor Model. The description of each one of the five broad domains is followed by a more detailed description of personality according to the six subdomains that comprise each domain. Further information is found in the Combined Profile section of this report.</p>",
									$member->user_firstname, $member->user_lastname, $member->get_country(), $member->get_gender(), $member->get_age_range() );
				?>

				<p>A note on terminology. Personality traits describe, relative to other people, the frequency or intensity of a person's feelings, thoughts, or behaviors. Possession of a trait is therefore a matter of degree. We might describe two individuals as extraverts, but still see one as more extraverted than the other. This report uses expressions such as "extravert" or "high in extraversion" to describe someone who is likely to be seen by others as relatively extraverted. The computer program that generates this report classifies you as low, average, or high in a trait according to whether your score is approximately in the lowest 30%, middle 40%, or highest 30% of scores obtained by people of your sex and roughly your age. Your numerical scores are reported and graphed as percentile estimates. For example, a score of "60" means that your level on that trait is estimated to be higher than 60% of persons of your sex and age.</p>

				<p>Please keep in mind that "low," "average," and "high" scores on a personality test are neither absolutely good nor bad. A particular level on any trait will probably be neutral or irrelevant for a great many activities, be helpful for accomplishing some things, and detrimental for accomplishing other things. As with any personality inventory, scores and descriptions can only approximate an individual's actual personality. High and low score descriptions are usually accurate, but average scores close to the low or high boundaries might misclassify you as only average. On each set of six subdomain scales it is somewhat uncommon but certainly possible to score high in some of the subdomains and low in the others. In such cases more attention should be paid to the subdomain scores than to the broad domain score. Questions about the accuracy of your results are best resolved by showing your report to people who know you well.</p>
			</div>
			<div id="personality-trait-results">
				<?php

				while($quiz->has_domain()) : $quiz->the_domain();

					?>
					<div class="sub-section" id="<?php $quiz->the_name('strtolower') ?>">
						<div class="subsection-header header"><?php $quiz->the_name(); ?></div>
						<div class="body-text static">
							<?php $quiz->the_description(); ?>
						</div>
						<div class="personality-results-graph dynamic">
							<div class="personality-graph-head">
								<div class="domain-facit"><strong>DOMAIN</strong>/Facet</div>
								<div class="score">Score</div>
								<div class="comparasin-chart">
									<div>0</div>
									<div>10</div>
									<div>20</div>
									<div>30</div>
									<div>40</div>
									<div>50</div>
									<div>60</div>
									<div>70</div>
									<div>80</div>
									<div>90</div>
									<div>99</div>
								</div>
							</div>
							<div class="trait-percentile">
								<div class="title"><?php $quiz->the_name(); ?></div>
								<div class="percentile"><?php $quiz->the_percentile(); ?></div>
								<div class="percentile-bar-wrapper">
									<div class="percentile-bar" style="width: <?php $quiz->the_percentile(); ?>%"></div>
								</div>
							</div>
							<?php
							while($quiz->has_facet()) : $quiz->the_facet();
								?>
								<div class="facit-percentile">
									<div class="title"><?php $quiz->the_name(); ?></div>
									<div class="percentile"><?php $quiz->the_percentile(); ?></div>
									<div class="percentile-bar-wrapper">
										<div class="percentile-bar" style="width: <?php $quiz->the_percentile() ?>%"></div>
									</div>
								</div>
							<?php endwhile; ?>
							<div class="dynamic-statement-trait">
								<?php $quiz->the_statement();?>
							</div>
						</div>
						<div class="sub-header header"><?php $quiz->the_name(); ?> Facets</div>
						<div class="facet-wrapper">
							<?php while($quiz->has_facet()) : $quiz->the_facet(); ?>

								<div class="facet" id="<?php $quiz->the_name('strtolower'); ?>">
									<div class="facet-header"><?php $quiz->the_name() ?></div>
									<div class="facet-body"><?php $quiz->the_description(); ?>
										<div class="dynamic-statement-facet">
											<?php echo sprintf('Your level of %s is %s', $quiz->get_name(), $quiz->get_range()); ?>
										</div>
									</div>
								</div>
							<?php endwhile; ?>
						</div>
					</div>
				<?php endwhile; ?>
			</div>
		</div>
		<?php

		endif;

		if($member->has_quiz('sgt') && $member->has_quiz('personality')) :
		$sgt = $member->the_quiz('sgt');
		$personality = $member->the_quiz('personality');
		?>

		<div id="combined-results" class="results-page-section">
			<div class="section-header header">Combined Profile – Your Top Three Gifts and Five Personality Traits</div>
			<div class="combined-results-wrapper">
				<?php
				for ($i = 1; $i <= 3; $i++) {
					while($personality->has_domain()) : $personality->the_domain();
						$combinedresults = new WP_Query(  array( 'post_type' => 'combinedresults', 'tag' => sprintf('%s %s-%s',$sgt->get_nth_name($i), $personality->get_range(), $personality->get_name()) ));
						while ( $combinedresults->have_posts() ) : $combinedresults->the_post();
							?> <div class="combinedresults <?php echo sprintf('%s %1$s-%s-%s', $sgt->get_nth_name($i), $personality->get_range(), $personality->get_name()); ?>">
								<div class="sub-header header dynamic"><div class="multicolor-divider combined-title"><div> <?php the_title(); ?> </div><div></div><div></div><div><span>Read More</span><span>Close</span></div></div></div>
								<div class="body-text dynamic">
									<div class="body-text-subsection strengths">
										<div class="body-text-subsection-header">Strengths</div> <?php echo get_post_meta($post->ID, 'strengths', true); ?>
									</div>
									<div class="body-text-subsection potential_weaknesses">
										<div class="body-text-subsection-header">Potential Weaknesses</div> <?php echo get_post_meta($post->ID, 'potential_weaknesses', true); ?>
									</div>
									<div class="body-text-subsection overcoming_weaknesses">
										<div class="body-text-subsection-header">Overcoming Weaknesses</div> <?php echo get_post_meta($post->ID, 'overcoming_weaknesses', true); ?>
									</div>
									<div class="body-text-subsection suggested_ministries">
										<div class="body-text-subsection-header">Suggested Ministries</div> <?php echo get_post_meta($post->ID, 'suggested_ministries', true); ?>
									</div>
								</div>
							</div> <?php
						endwhile;
					endwhile;
				}
				?>
			</div>
		</div>
		<?php
			endif;
		?>
		<div id="results-next-steps" class="results-page-section">
			<div class="section-header header">Next Steps</div>
			<div class="body-text static">
				Thank you for taking the time to understand your spiritual gifts and personality traits. You are now on a journey to begin serving The Lord in ways that better suit how He designed you. We hope you are excited to grow deeper in fellowship with Jesus, and to help the church build itself up in love (Ephesians 4:16). Now that you have your results, you are probably asking yourself, “What next?” Here are three steps you can take to begin walking with Christ and bearing much fruit for God’s glory.
			</div>
			<div class="sub-header header static">Pray</div>
			<div class="body-text static">
				The first step is to pray and seek confirmation of these results. The contents of this report are simply a tool for you to use so that you can better serve God in the work He has already prepared for you. As you pray, humbly ask God to give you guidance and peace about how, where, and whom you will serve in His name.
			</div>
			<div class="sub-header header static">Meet</div>
			<div class="body-text static">
				Take this opportunity to meet with other Christians who can help you on your journey. This will likely be someone in your local church body who can connect with you and discern both your gifts and your heart. They will also know the needs of the church and the community around them, so this will help you find a place to begin serving God. The suggested ministries in your combined profile are a helpful tool in this process.
			</div>
			<div class="sub-header header static">Serve</div>
			<div class="body-text static">
				Start. That is the best advice we can give you. Don’t wait for the perfect opportunity, or the perfect time. Just get going! It is impossible to steer a vehicle unless it is moving. Once you start serving Jesus He may steer you one way or the other, but the change won’t be difficult if you are already in motion. Again, the suggested ministries in your profile are helpful, but don’t limit yourself to them. If you feel led to start a new work for The Lord, go for it! Don’t try it alone though. It is best to have someone come alongside you as either a guide or a partner.
			</div>
			<div class="sub-header header static">Share</div>
			<div class="body-text static">
				There are others just like you who don’t know where to begin. Maybe you will be the inspiration for their next journey with The Lord. Share this resource with them and see what God will do!
			</div>
		</div>

		<div id="thankyou" class="results-page-section">
			<div class="section-header header">Thank you</div>
			<div class="body-text static">
				Thank you for using our resources! Our prayer is that you would go and fulfill the Great Commission in the spirit of the Greatest Commandment. That you would love God and love others, so that they too would love God and worship Him forever. Let us know how we can continue to be a blessing to you in that.
				<div class="signature">
					Many blessings in Christ,</br>
					The SpiritualGiftsTest.com Team
				</div>
			</div>
		</div>
		<div id="print-section" class="results-page-section">
            <?php // Create sudo loop
                    the_post(); ?>

			<?php the_content(); ?>
		</div>
		<div class="send-via-email">
				<?php echo gravity_form( 25, false, false, false, '', false ); ?>
				Simply enter an email address to forward your results. Note: The recipient will see the full report as displayed above.
				<div style="clear: both;"></div>
		</div>
		<div class="delete-results">
			<a href="#" id="delete-results" data-user="<?php echo $member->ID; ?>" data-verify="<?php echo base64_encode($member->ID); ?>">Delete my test results.</a>
		</div>
	</div>
</div> <!-- #main-content -->
<?php
get_footer();
