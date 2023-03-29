<?php
/*
 * Template Name: Church Admin Results Page Template OLD
 */

get_header();

$GFQR = GravityFormsPersonalityQuizResults::get_instance();
//Gift Parameters
//Move to WP admin in future for cleaner code and accessibility across site
$gift_parameters = $GFQR->get_gift_parameters();

//Gift Averages
//Move to WP admin in future for cleaner code and accessibility across site
$gift_averages = $GFQR->get_gift_averages();

//Personality Sets
//Move to WP admin in future for cleaner code and accessibility across site
$personality_sets = $GFQR->get_personality_sets();

// Church admin info

$admin_info = wp_get_current_user();
$church_code = get_user_meta($admin_info->ID, 'church_admin_code', true);
if(!$church_code)
    $church_code = 'UNKNOWN';
$church_discount_code = get_user_meta($admin_info->ID, 'church_discount_code', true);
if(!$church_discount_code)
    $church_discount_code = 'UNKNOWN';



//Need this override code for test. Leave here until live.
//$church_code = 'LEADERSHIP123';

$church_name = get_user_meta($admin_info->ID, 'church_name', true);
//print_r(get_user_meta($admin_info->ID, 'gift_filter', true));
//update_user_meta($admin_info->ID, 'church_admin_code', $church_code);

//Grabbing Coupon instance
$GFCoupons = GFCoupons::get_instance();
//Grabbing coupon information based on this admins coupon code.
$church_coupon = $GFCoupons->get_config(0, $church_discount_code);
$tests_remaining = $church_coupon['meta']['usageLimit'] - $church_coupon['meta']['usageCount'];

//Users with same church code that currecnt user bought
$users = get_users(array('meta_key' => 'church_code', 'meta_value' => $church_code));

$manual = get_user_meta($admin_info->ID, 'church_manual_entries', true);

$gift_filters = explode( ',', get_user_meta($admin_info->ID, 'gift_filter', true));


?>

<div id="main-content" class="church-results-page">
    <div class="title"><?php echo $church_name; ?> Dashboard</div>
    <div id="church-results-page-wrapper">
        <div id="church-results-body">
            <div class="name"><?php echo $admin_info->user_firstname; ?>,</div>
            <?php the_content(); ?>
            <div id="church-access-codes">
                <h3 class="access-codes-header">Access Codes</h3>
                <div id="general-access-code" class="access-code">
                    <h4 class="access-code-title">General Access Code</h4>
                    <span class="access-code"><?php echo $church_code;?></span>
                </div>
                <div id="personality-test-access-code" class="access-code">
                    <h4 class="access-code-title">Personality Test Access Code</h4>
                    <span class="access-code"><?php echo $church_discount_code;?></span>
                </div>
                <h3 class="access-codes-header" style="margin: 30px 0px 0px; padding-bottom: 0px;">Embed Code</h3>
                <div id="embed-access-code" class="access-code">
                    <span class="access-code">&lt;script src="https://spiritualgiftstest.com/embed-script/church-id/<?php echo $church_code;?>/"&gt;&lt;/script&gt;&lt;iframe width="100%" height="500px" src="http://spiritualgiftstest.com/embed-2/church-id/<?php echo $church_code;?>/"&gt;&lt;/iframe&gt;</span>
                </div>
            </div>
            <a href="https://spiritualgiftstest.com/church-admin-add/"><div class="purchase-more button">Purchase More Personality Tests</div></a>
            <a href="https://spiritualgiftstest.com/manual-entry-form/"><div class="manual-entry button">Manual Entry</div></a>
            <a href="https://spiritualgiftstest.com/?p=3228" id="download-csv"><div class="download-csv button">Download CSV</div></a>
            <div class="tests-remaining">Tests Remaining - <?php echo $tests_remaining;?></div>
        </div>

        <div id="filter"></div>
        <?php //print_r( LegacySGT::get_legacy_tests_count_by_customer_id($church_code) ); ?>
        <table id="church-results">
            <tr class="tabel-header-row">
                <th>
                    Name
                </th>
                <th>
                    Date Taken
                </th>
                <th>
                    Top Spiritual Gift/Personality Trait
                </th>
                <th>
                    Details
                </th>
            </tr>
            <?php
            foreach($users as $user_info) {

            //Quiz Data
            $quiz_data_youthonly = array_change_key_case(get_user_meta($user_info->ID, 'quiz_results_sgt_y', true));
            $quiz_data = array_change_key_case(get_user_meta($user_info->ID, 'quiz_results_sgt_a', true));
            if(empty($quiz_data))
                $quiz_data = array_change_key_case(get_user_meta($user_info->ID, 'quiz_results_sgt_y', true));

            $personality_quiz_data = array_change_key_case(get_user_meta($user_info->ID, 'quiz_results_personality', true));
            $useridforurlveriable = $user_info->ID;
            //if(empty($quiz_data) || empty($personality_quiz_data))
            //continue;

            $user_date_taken = array_pop($quiz_data);
            $testTime = strtotime( $user_date_taken );
            $testDate = date( 'm/d/Y', $testTime );
            $quiz_data = array_map( function($val) { return round($val / 7, 3); }, $quiz_data);

            foreach($gift_filters as $filter) {
                unset($quiz_data[$filter]);
            }

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

            reset($personality_sets);
            $first_domain = key($personality_sets);
            switch ( true ) {
                case $personality_sets[$first_domain]['score'] / 20 >= $personality_sets[$first_domain]['range']['high']:
                    $traitRangePrefix = 'high-';
                    break;
                case $personality_sets[$first_domain]['score'] / 20 <= $personality_sets[$first_domain]['range']['low']:
                    $traitRangePrefix = 'low-';
                    break;
                default:
                    $traitRangePrefix = 'average-';
            }

            //$combotitle = "ApAA – Apostleship and Average Agreeableness";
            $getcombinedresult = new WP_Query(  array( 'post_type' => 'combinedresults', 'tag' => ucwords(key($gift_ranking)) . " " . $traitRangePrefix . $first_domain ));
            $combotitle = $getcombinedresult->posts[0]->post_title;

            $data = "data-name='$user_info->user_firstname $user_info->user_lastname'";
            $data .= " data-date='$testTime'";
            $data .= " data-age-range='".get_user_meta($user_info->ID,'age_range',true)."'";
            $data .= " data-gender='".get_user_meta($user_info->ID,'gender',true)."'";
            $data .= " data-top-gift='".key($gift_ranking)."'";
            $top_three = '';
            for($i=0; $i<3; $i++) {
                if(key($gift_ranking))
                    $top_three .= key($gift_ranking)." ";
                next($gift_ranking);
            }
            reset($gift_ranking);
            $data .= " data-top-three='$top_three'";
            $data .= " data-top-personality='".key($personality_sets)."'";

            echo "<tr class='church-results-row ".key($gift_ranking)."' $data>";
            echo "<td>$user_info->user_firstname $user_info->user_lastname</td>";
            echo "<td>$testDate</td>";

            if (!empty($quiz_data) && !empty($personality_score)) {
                if(empty($quiz_data_youthonly)) {?>
                    <td><?php echo $combotitle;?></td>
                <?php } else {?>
                    <td><?php echo $combotitle;?> - <em>Youth</em></td>
                <?php } ?>
            <?php } elseif (!empty($quiz_data) && empty($personality_score)) {
                if(empty($quiz_data_youthonly)) {
                    echo "<td>".key($gift_ranking)."</td>";
                } else {
                    echo "<td>".key($gift_ranking)." - <em>Youth</em></td>";
                }
            } else {
                echo"<td>Tests Incomplete</td>";
            } ?>
            <td><div class="button expand-results"><span class="open">Summary</span><span class="close">Close</span></div>

                <div class="subsection">
                    <table>
                        <tr class="table-header-row">
                            <th>Gift/Trait</th>
                            <th>Score</th>
                        </tr>
                        <?php
                        $i = 1;
                        if (!empty($quiz_data)) {
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
                                echo "<tr>";
                                echo '<td>'.ucwords($gift).'</td>';
                                echo "<td>$gift_range</td>";
                                echo "</tr>";
                                if ($i++ == 3) break;
                            }
                        }

                        if (!empty($personality_sets[$dkey]['score'])) {
                            foreach ($personality_sets as $dkey => $domain ) { ?>
                                <tr>
                                    <td><?php echo $domain['title'];?></td>
                                    <td><?php echo round($domain['score'] / 120 * 100); ?>%</td>
                                </tr>
                            <?php }
                        } ?>
                    </table>
                </div>
                <?php echo "<a href='/full-report/?userid=".$useridforurlveriable."' class='full-report-link'>Full Report</a>"; ?>
                <?php } ?>
            </td>
            </tr>
            <?php

            if(!empty($manual)) {
            echo sprintf('<tr><td colspan="6"> Manual Entries</td></tr>');
                foreach($manual as $entry) {

                $user_date_taken = $entry['date_created'];
                $testTime = strtotime( $user_date_taken );
                $testDate = date( 'm/d/Y', $testTime );

                $quiz_data = array_map( function($val) { return round($val / 7, 3); }, $entry['sgt']);

                foreach($gift_filters as $filter) {
                  unset($quiz_data[$filter]);
                }

                $gift_ranking = array();
                foreach ($quiz_data as $gift => $score) {
                    $gift_ranking[$gift] = $score/$gift_averages[$gift];
                }
                arsort($gift_ranking);

                $data = sprintf('data-name="%s %s"', $entry['first_name'], $entry['last_name']);
                $data .= sprintf(' data-date="%s"',$testTime);
                $data .= sprintf(' data-age-range="%s"', $entry['age_range']);

                $data .= sprintf(' data-gender="%s"', $entry['gender']);
                $data .= sprintf(' data-top-gift="%s"', key($gift_ranking));
                $top_three = '';
                for($i=0; $i<3; $i++) {
                    if(key($gift_ranking))
                        $top_three .= key($gift_ranking)." ";
                    next($gift_ranking);
                }
                reset($gift_ranking);

                $data .= sprintf(' data-top-three="%s"', $top_three);

                echo "<tr class='church-results-row manual ".key($gift_ranking)."' $data>";
                    echo sprintf('<td>%s %s</td>', $entry['first_name'], $entry['last_name']);
                    echo sprintf('<td>%s</td>', $testDate);

                    echo"<td></td>";
                    ?>
                    <td><div class="button expand-results"><span class="open">Summary</span><span class="close">Close</span></div>

                        <div class="subsection">
                            <table>
                                <tr class="table-header-row">
                                    <th>Gift/Trait</th>
                                    <th>Score</th>
                                </tr>
                                <?php
                                $i = 1;
                                if (!empty($quiz_data)) {
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
                                        echo "<tr>";
                                        echo '<td>'.ucwords($gift).'</td>';
                                        echo "<td>$gift_range</td>";
                                        echo "</tr>";
                                        if ($i++ == 3) {break;}
                                    }
                                } ?>
                            </table>
                        </div>

                    </td>
                </tr>
            <?php
                }
            }

            $legacy = LegacySGT::get_legacy_scores_by_customer_id($church_code);
            if(!empty($legacy)) {
            echo sprintf('<tr><td colspan="6">Legacy Entries</td></tr>');
                foreach($legacy as $entry) {
                $user_date_taken = $entry['date_created'];
                $testTime = strtotime( $user_date_taken );
                $testDate = date( 'm/d/Y', $testTime );

                $quiz_data = array_map( function($val) { return round($val / 7, 3); }, $entry['sgt']);

                foreach($gift_filters as $filter) {
                    unset($quiz_data[$filter]);
                }

                $gift_ranking = array();
                foreach ($quiz_data as $gift => $score) {
                    $gift_ranking[$gift] = $score/$gift_averages[$gift];
                }
                arsort($gift_ranking);

                $data = sprintf('data-name="%s %s"', $entry['first_name'], $entry['last_name']);
                $data .= sprintf(' data-date="%s"',$testTime);
                //$data .= sprintf(' data-age-range="%s"', $entry['age_range']);

                //$data .= sprintf(' data-gender="%s"', $entry['gender']);
                $data .= sprintf(' data-top-gift="%s"', key($gift_ranking));
                $top_three = '';
                for($i=0; $i<3; $i++) {
                    if(key($gift_ranking))
                        $top_three .= key($gift_ranking)." ";
                    next($gift_ranking);
                }
                reset($gift_ranking);

                $data .= sprintf(' data-top-three="%s"', $top_three);

                echo "<tr class='church-results-row legacy ".key($gift_ranking)."' $data>";
                    echo sprintf('<td>%s %s</td>', $entry['first_name'], $entry['last_name']);
                    echo sprintf('<td>%s</td>', $testDate);

                    echo"<td></td>";
                    ?>
                    <td><div class="button expand-results"><span class="open">Summary</span><span class="close">Close</span></div>


                        <div class="subsection">
                            <table>
                                <tr class="table-header-row">
                                    <th>Gift/Trait</th>
                                    <th>Score</th>
                                </tr>
                                <?php
                                $i = 1;
                                if (!empty($quiz_data)) {
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
                                        echo "<tr>";
                                        echo '<td>'.ucwords($gift).'</td>';
                                        echo "<td>$gift_range</td>";
                                        echo "</tr>";
                                        if ($i++ == 3) {break;}
                                    }
                                } ?>
                            </table>
                        </div>
                    </td>
                </tr>
            <?php
                }
            }
            $count = LegacySGT::get_legacy_tests_count_by_customer_id($church_code);
            if($count > LEGACYLIMIT) {
                ?>
                <tr class="load-more-row">
                    <td><a href="#" class="load-more" data-offset="<?php echo LEGACYLIMIT; ?>">Load More</a></td>
                </tr>
                <?
            }
            ?>
        </table>
    </div>
</div> <!-- #main-content -->
<script type="text/javascript" >
    jQuery('.load-more').click(function($) {
        jQuery('.load-more').addClass('loading').html('LOADING...');
        var offset = jQuery('.load-more').data('offset');
        var total_count = <?php echo $count; ?>;
        var data = {
            'action': 'sgt_legacy_load_more',
            'offset': offset
        };

        jQuery.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {
            jQuery('tr.load-more-row').before(response);
            jQuery('.load-more').data('offset', offset+<?php echo LEGACYLIMIT; ?>);
            jQuery('#filter-input select').trigger('change');
            jQuery('#filter-input input').trigger('keyup');
            jQuery('#filter-input input').trigger('change');
            if(jQuery('.load-more').data('offset')>total_count)
                jQuery('.load-more').remove();
            else
                jQuery('.load-more').removeClass('loading').html('Load More');
        });
        return false;
    });
</script>
<?php get_footer(); ?>
