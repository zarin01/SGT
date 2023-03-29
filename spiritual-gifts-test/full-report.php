<?php
/*
 * Template Name: Full Report Page Template
 */


if ( !is_user_logged_in()) {
	wp_redirect( site_url('my-account') );
	exit;
}

get_header();
$GFQR = GravityFormsPersonalityQuizResults::get_instance();

if(get_user_meta(get_current_user_id(), 'church_admin_code', true)) {
	$gift_filters = explode( ',', get_user_meta(get_current_user_id(), 'gift_filter', true));
} elseif(get_user_meta(get_current_user_id(), 'church_code', true)) {
	$admin = get_users(array('meta_key' => 'church_admin_code', 'meta_value' => get_user_meta(get_current_user_id(), 'church_code', true)));
	$gift_filters = explode( ',', get_user_meta($admin[0]->ID, 'gift_filter', true));
}

// User Info
$user_id = $_GET["userid"];
$user_info = get_user_by('id', $user_id);
$usercountry = get_user_meta($user_info->ID, 'billing_country', true);
$usergender = get_user_meta($user_info->ID, 'gender', true);
$useragerange = get_user_meta($user_info->ID, 'age_range', true);

//Quiz Data
$quiz_data = array_change_key_case(get_user_meta($user_info->ID, 'quiz_results_sgt_a', true));

if(empty($quiz_data))
	$quiz_data = array_change_key_case(get_user_meta($user_info->ID, 'quiz_results_sgt_y', true));

foreach($gift_filters as $filter) {
	unset($quiz_data[trim($filter)]);
}

//$personality_quiz_data = array_change_key_case(get_user_meta($user_info->ID, 'quiz_results_personality', true));

$user_data_taken = array_pop($quiz_data);
$testTime = strtotime( $user_data_taken );
$testDate = date( 'F dS, Y', $testTime );

$quiz_data = array_map( function($val) { return round($val / 7, 3); }, $quiz_data);

$gift_parameters = $GFQR->get_gift_parameters();

$gift_averages = $GFQR->get_gift_averages();

$gift_ranking = array();
foreach ($quiz_data as $gift => $score) {

	$gift_ranking[$gift] = $score/$gift_averages[$gift];
}
arsort($gift_ranking);

//Personality Trait Sets
$personality_score = array_change_key_case(get_user_meta($user_info->ID, 'quiz_results_personality', true));

$personality_sets = $GFQR->get_personality_sets();

foreach($personality_sets as $dkey => $domain) {
	foreach($domain['facets'] as $key => $facets_info) {
		$personality_sets[$dkey]['score'] += $personality_sets[$dkey]['facets'][$key]['score'] = $personality_score[$key];
	}
	//uasort($personality_sets[$dkey]['facets'], 'cmp');
	uasort($personality_sets[$dkey]['facets'], array('GravityFormsPersonalityQuizResults', 'personality_sorter'));
}
//uasort($personality_sets, 'cmp');
uasort($personality_sets, array('GravityFormsPersonalityQuizResults', 'personality_sorter'));
if (!empty($quiz_data) || !empty($personality_score)) {
	?>

	<div id="main-content">
		<div class="title">Spiritual Gifts and Personality Profile</div>
		<div id="results-page-wrapper">
			<div id="results-introduction" class="results-page-section">
				<div class="info">This report is based on answers given by <?php echo $user_info->user_firstname; echo " "; echo $user_info->user_lastname; ?> on <?php echo $testDate;?>.</div>
				<div class="name"><?php echo $user_info->user_firstname; ?>,</div>
				<div class="body-text static">
					<p>We are grateful that you have invested the time to discover your spiritual gifts and personality traits! This profile report should be treated as a useful tool to help you understand how you may be gifted to serve in the body of Christ, and how your personality type may affect how and where you serve. The accuracy of these results is based on your honest responses combined with current research in the areas of spiritual gifts and personality.</p>

					<p>It is strongly suggested that you share these results with someone who knows you well enough to further validate their accuracy. Church leaders can use this information to assist you in finding a ministry or service opportunity to fit your gifts and personality. Of course, serving God is not limited to the walls of the church, but we believe that is the best place to start. God has placed pastors and leaders in the church to equip the saints for the work of ministry. It is His design that those of us who serve Him should be mentored and built up in the church, as well as held to a high standard in our service to Him by our brothers and sisters in Christ. We pray that you would use this as an opportunity to grow and mature in your love and service to Jesus.</p>

					<p>Understanding who you are in Christ and how He has crafted you as a human being is an exciting thing! However, the goal of believers is not just to know ourselves, but to know and glorify God in all we do. Like any gift, a spiritual gift is meant to be opened, put to use, and enjoyed. This brings happiness both to the Giver and receiver of the gift. God is pleased when we put our spiritual gifts into action because using them is an expression of our faith in Him. Our obedience to His call and purpose for our lives demonstrates our love for both God and our neighbor, fulfilling the greatest commandment of Scripture.</p>

					<p>As you read through this profile, know that God loves you and has built you for a specific purpose in His kingdom. Ephesians 2:10 (ESV) says, “For we are his workmanship, created in Christ Jesus for good works, which God prepared beforehand, that we should walk in them.” May you grow in your trust and walk in the works prepared for you with all humility and love for the One who has called you to them.</p>

					<div class="signature">
						Blessings to you in Christ,</br>
						The SpiritualGiftsTest.com Team
					</div>
				</div>
			</div>
			<div id="quick-summary"class="results-page-section">
				<div class="section-header header">Quick Summary:</div>
				<?php if (!empty($quiz_data)) {?>
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
							$i = 1;
							foreach($gift_ranking as $gift => $rank) {
								switch(true) {
									case $quiz_data[$gift] >= $gift_parameters[$gift]['high']:
										$gift_range = "High";
										break;
									case $quiz_data[$gift] <= $gift_parameters[$gift]['low']:
										$gift_range = "Low";
										break;
									default:
										$gift_range = "Average";
								}

								echo "<tr class='$gift_range $gift'>";
								echo '<td>'.$i++.'</td>';
								echo "<td>$gift_range</td>";
								echo '<td>'.ucwords($gift).'</td>';
								echo "</tr>";
								if($i==4) break;
							}

							?>
						</table>
					</div>
				<?php } ?>
				<?php if (!empty($personality_score)) {?>
					<div class="personality-results quick-summary-table">
						<div class="sub-header">Your personality results:</div>
						<table>
							<tr class="tabel-header-row">
								<th>Range</th>
								<th>Personality Trait</th>
							</tr>
							<?php
							$i = 1;
							foreach ($personality_sets as $dkey => $domain ) {?>
								<tr>
									<td><?php switch ( true ) {
											case $domain['score'] / 20 >= $domain['range']['high']:
												echo 'High';
												break;
											case $domain['score'] / 20 <= $domain['range']['low'];
												echo 'Low';
												break;
											default:
												echo 'Average';
										} ?></td>
									<td><?php echo $domain['title'];?></td>
								</tr>
							<?php } ?>
						</table>
					</div>
				<?php } ?>
				<div class="multicolor-divider" style="padding: 25px 0px;"><div></div><div></div><div></div><div></div></div>
			</div>
			<div id="icon-menu" class="results-page-section">
				<div class="sub-header">Click Icons to View Full Results</div>
				<?php if (empty($quiz_data)) {
					echo '<div class="section-icon">
					<a href="'.site_url('adult-spiritual-gifts-test').'"><img src="'.site_url().'/wp-content/uploads/2018/02/sgt-icon-noboarder.png">
					<div class="icon-text">Take Spiritual Gifts Test</div></a>
				</div>';
				}else {
					echo'<div class="section-icon icon-sgt">
				<a href="#sgt-results"><img src="'.site_url().'/wp-content/uploads/2018/02/sgt-icon-noboarder.png">
				<div class="icon-text">Spiritual Gifts Test Results</div></a>
			</div>';
				}
				if (empty($personality_score)) {
					echo '<div class="section-icon">
					<a href="'.site_url('/personality-test/').'"><img src="'.site_url().'/wp-content/uploads/2018/02/Personality-noboarder.png">
					<div class="icon-text">Take Personality Test</div></a>
				</div>';
				}else {
					echo'<div class="section-icon icon-personality">
				<a href="#personality-results"><img src="'.site_url().'/wp-content/uploads/2018/02/Personality-noboarder.png">
				<div class="icon-text">Personality Test Results</div></a>
			</div>';
				}if (!empty($quiz_data) && !empty($personality_score)) {
					echo'<div class="section-icon icon-combined">
				<a href="#combined-results"><img src="'.site_url().'/wp-content/uploads/2018/02/ResultsIcon-noborder.png">
				<div class="icon-text">Combined Profile</div></a>
			</div>';
				}?>
				<div class="multicolor-divider"><div></div><div></div><div></div><div></div></div>
			</div>
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
						$i = 1;
						foreach($gift_ranking as $gift => $rank) {
							switch(true) {
								case $quiz_data[$gift] >= $gift_parameters[$gift]['high']:
									$gift_range = "High";
									break;
								case $quiz_data[$gift] <= $gift_parameters[$gift]['low']:
									$gift_range = "Low";
									break;
								default:
									$gift_range = "Average";
							}


							echo "<tr class='$gift_range $gift'>";
							echo '<td>'.$i++.'</td>';
							echo "<td>$gift_range</td>";
							echo '<td>'.ucwords($gift).'</td>';
							echo "</tr>";

						}

						?>
					</table>
				</div>
				<div class="subsection-header header">Your Top Three Spiritual Gifts Defined</div>
				<div class="sgt-results-top-three">
					<?php
					$gifts = array_keys($gift_ranking);
					?>
					<div class="one">
						<?php
						$getsgtresultone = new WP_Query(  array( 'post_type' => 'sgtresults', 'name' => ucwords($gifts[0]) ));
						while ( $getsgtresultone->have_posts() ) : $getsgtresultone->the_post();
							?> <div class="sub-header header dynamic <?php echo $gifts[0]; ?>"> <?php the_title(); ?> </div>
							<div class="body-text dynamic"> <?php the_content(); ?></div><div class="topthree-readmore">Read More</div><?php
						endwhile;
						wp_reset_postdata();
						?>
					</div>
					<div class="two">
						<?php
						$getsgtresulttwo = new WP_Query(  array( 'post_type' => 'sgtresults', 'name' => ucwords($gifts[1]) ));
						while ( $getsgtresulttwo->have_posts() ) : $getsgtresulttwo->the_post();
							?> <div class="sub-header header dynamic <?php echo $gifts[1]; ?>"> <?php the_title(); ?> </div>
							<div class="body-text dynamic"> <?php the_content(); ?></div><div class="topthree-readmore">Read More</div> <?php
						endwhile;
						wp_reset_postdata();
						?>
					</div>
					<div class="three">
						<?php
						$getsgtresultthree = new WP_Query(  array( 'post_type' => 'sgtresults', 'name' => ucwords($gifts[2]) ));
						while ( $getsgtresultthree->have_posts() ) : $getsgtresultthree->the_post();
							?> <div class="sub-header header dynamic <?php echo $gifts[2]; ?>"> <?php the_title(); ?> </div>
							<div class="body-text dynamic"> <?php the_content(); ?></div><div class="topthree-readmore">Read More</div> <?php
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</div>
				<div class="multicolor-divider"><div></div><div></div><div></div><div></div></div>
			</div>

			<div id="personality-results" class="results-page-section">
				<div class="section-header header">Your Personality Test Results</div>
				<div class="body-text static">
					<p>This report compares <?php echo $user_info->user_firstname; echo " "; echo $user_info->user_lastname ?> from <?php echo $usercountry?> to other <?php echo $usergender; echo "s"; ?> between <?php echo $useragerange;?> years of age. The personality test results estimate the individual's level on each of the five broad personality domains of the Five-Factor Model. The description of each one of the five broad domains is followed by a more detailed description of personality according to the six subdomains that comprise each domain. Further information is found in the Combined Profile section of this report.</p>

					<p>A note on terminology. Personality traits describe, relative to other people, the frequency or intensity of a person's feelings, thoughts, or behaviors. Possession of a trait is therefore a matter of degree. We might describe two individuals as extraverts, but still see one as more extraverted than the other. This report uses expressions such as "extravert" or "high in extraversion" to describe someone who is likely to be seen by others as relatively extraverted. The computer program that generates this report classifies you as low, average, or high in a trait according to whether your score is approximately in the lowest 30%, middle 40%, or highest 30% of scores obtained by people of your sex and roughly your age. Your numerical scores are reported and graphed as percentile estimates. For example, a score of "60" means that your level on that trait is estimated to be higher than 60% of persons of your sex and age.</p>

					<p>Please keep in mind that "low," "average," and "high" scores on a personality test are neither absolutely good nor bad. A particular level on any trait will probably be neutral or irrelevant for a great many activities, be helpful for accomplishing some things, and detrimental for accomplishing other things. As with any personality inventory, scores and descriptions can only approximate an individual's actual personality. High and low score descriptions are usually accurate, but average scores close to the low or high boundaries might misclassify you as only average. On each set of six subdomain scales it is somewhat uncommon but certainly possible to score high in some of the subdomains and low in the others. In such cases more attention should be paid to the subdomain scores than to the broad domain score. Questions about the accuracy of your results are best resolved by showing your report to people who know you well.</p>
				</div>
				<div id="personality-trait-results">
					<?
					foreach ($personality_sets as $dkey => $domain ) {
						?>
						<div class="sub-section" id="<?php echo $dkey; ?>">
							<div class="subsection-header header"><?php echo $domain['title']; ?></div>
							<div class="body-text static">
								<?php echo $domain['description']; ?>
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
									<div class="title"><?php echo ucwords( $domain['title'] ); ?></div>
									<div class="percentile"><?php echo round($domain['score'] / 120 * 100); ?></div>
									<div class="percentile-bar-wrapper">
										<div class="percentile-bar" style="width: <?php echo $domain['score'] / 120 * 100; ?>%"></div>
									</div>
								</div>
								<?php
								foreach ( $domain['facets'] as $fkey => $facet ) {
									?>
									<div class="facit-percentile">
										<div class="title"><?php echo $facet['title']; ?></div>
										<div class="percentile"><?php echo round($facet['score'] / 20 * 100); ?></div>
										<div class="percentile-bar-wrapper">
											<div class="percentile-bar" style="width: <?php echo $facet['score'] / 20 * 100; ?>%"></div>
										</div>
									</div>
								<?php } ?>
								<div class="dynamic-statement-trait">
									<?php
									switch ( true ) {
										case $domain['score'] / 20 >= $domain['range']['high']:
											echo $domain['high_statement'];
											break;
										case $domain['score'] / 20 <= $domain['range']['low'];
											echo $domain['low_statement'];
											break;
										default:
											echo $domain['average_statement'];
									}
									?>
								</div>
							</div>
							<div class="sub-header header"><?php $domain['title']; ?> Facets</div>
							<div class="facet-wrapper">
								<?php
								foreach ( $domain['facets'] as $fkey => $facet ) {
									//print_r($facet);
									?>
									<div class="facet" id="<?php echo $fkey; ?>">
										<div class="facet-header"><?php echo $facet['title']; ?></div>
										<div class="facet-body"><?php echo $facet['description']; echo $facet['statement'];?>
											<div class="dynamic-statement-facet">
												<?php
												switch ( true ) {
													case $facet['score'] / 4 >= $domain['range']['high']:
														echo 'Your level of '.$facet['title'].' is high.';
														break;
													case $facet['score'] / 4 <= $domain['range']['low'];
														echo "Your level of ".$facet['title']." is low.";
														break;
													default:
														echo "Your level of ".$facet['title']." is average.";
														break;
												}
												?>
											</div>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>


			<div id="combined-results" class="results-page-section">
				<div class="section-header header">Combined Profile – Your Top Three Gifts and Five Personality Traits</div>
				<div class="combined-results-wrapper">
					<?php
					$gifts = array_keys($gift_ranking);
					for ($sgttopthree = 0; $sgttopthree <= 2; $sgttopthree++) {
						$count=0;
						foreach ($personality_sets as $dkey => $domain ) {
							$trait_slug = 'trait_slug_'.$count;
							$$trait_slug = $domain['slug'];
							switch ( true ) {
								case $domain['score'] / 20 >= $domain['range']['high']:
									$traitRangePrefix = 'high-';
									break;
								case $domain['score'] / 20 <= $domain['range']['low'];
									$traitRangePrefix = 'low-';
									break;
								default:
									$traitRangePrefix = 'average-';
							}
							$count++;
							$getcombinedresult = new WP_Query(  array( 'post_type' => 'combinedresults', 'tag' => ucwords($gifts[$sgttopthree]) . " " . $traitRangePrefix . $dkey ));
							while ( $getcombinedresult->have_posts() ) : $getcombinedresult->the_post();
								?> <div class="combinedresults <?php echo $gifts[$sgttopthree]; echo " "; echo $gifts[$sgttopthree]; echo "-"; echo $traitRangePrefix; echo $dkey;?>">
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
							wp_reset_postdata();

						}
					}
					?>
				</div>
			</div>

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
				<?php the_content(); ?>
			</div>
		</div>
	</div> <!-- #main-content -->
<?php } else{
	echo "<div id='main-content'><div id='results-page-wrapper'><div class='no-result-yet'>You must take a test before you can view your results. <a href='".site_url('adult-spiritual-gifts-test').">Spiritual Gifts Test</a> | <a href='".site_url('personality-test')."'>Personality Test</a></div></div></div>";
}?>
<?php get_footer(); ?>
