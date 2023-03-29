<?php
/*
 * Template Name: Church Admin Results Page Template
 */

$admin = sgt_get_current_user();
if(!$admin->is_admin())
    wp_die('Only church admins may view this page.');

$church = $admin->get_church();

get_header();

?>

<div id="main-content" class="church-results-page">
    <div class="title"><?php echo $church->get_church_name(); ?> Dashboard</div>
    <div id="church-results-page-wrapper">
        <div id="church-results-body">
            <div class="name"><?php echo $admin->user_firstname; ?>,</div>
            <?php the_content(); ?>
            <div id="church-access-codes">
                <h3 class="access-codes-header">Access Codes</h3>
                <div id="general-access-code" class="access-code">
                    <h4 class="access-code-title">General Access Code</h4>
                    <span class="access-code"><?php echo $church->get_church_code(); ?></span>
                </div>
                <div id="personality-test-access-code" class="access-code">
                    <h4 class="access-code-title">Personality Test Access Code</h4>
                    <span class="access-code"><?php echo $church->get_church_discount(); ?></span>
                </div>
                <h3 class="access-codes-header" style="margin: 30px 0px 0px; padding-bottom: 0px;">Embed Code</h3>
                <div id="embed-access-code" class="access-code">
                    <?php
                    echo sprintf('<span class="access-code">&lt;script src="%s%3$s" &gt;&lt;/script&gt; &lt;iframe width="100%%" height="500px" src="%s%3$s" &gt;&lt;/iframe&gt;</span>',
                            site_url('/embed-script/church-id/'), site_url('embed-2/church-id/'), $church->get_church_code());

                    ?>
                </div>
                <h3 class="api-secret">API Secret</h3>
                <div id="api-secret" class="access-code">
                    <?php echo sprintf('<span class="access-code">%s</span>', $church->get_secret()); ?>
                </div>
            </div>
            <?php
            echo sprintf('<a href="%s"><div class="purchase-more button">Purchase More Personality Tests</div></a>', site_url('church-admin-add'));
            echo sprintf('<a href="%s"><div class="manual-entry button">Manual Entry</div></a>', site_url('manual-entry-form'));
            echo sprintf('<a href="%s" id="download-csv"><div class="download-csv button">Download CSV</div></a>', site_url('?p=3228'));
            echo sprintf('<div class="tests-remaining">Personality Tests Remaining - %s</div>', $church->get_tests_remaining());
            ?>
        </div>

        <div id="filter"></div>
        <table id="church-results">
            <tr class="tabel-header-row">
                <th>Name</th>
                <th>Date Taken</th>
                <th>Top Spiritual Gift/Personality Trait</th>
                <th>Details</th>
            </tr>
            <?php
            while($church->has_members()) :
                $member = $church->the_member();
                $data = sprintf('data-name="%s %s"', $member->user_firstname, $member->user_lastname);
                $data .= sprintf(' data-date="%s"', $member->quiz_date());
                $data .= sprintf(' data-age-range="%s"', $member->get_age_range());
                $data .= sprintf(' data-gender="%s"', $member->get_gender());
                $data .= sprintf(' data-top-gift="%s"', $member->get_top_gift());
                $data .= sprintf(' data-top-three="%s"', $member->get_top_three());
                $data .= sprintf(' data-top-personality="%s"', $member->get_top_personality());

                echo sprintf('<tr class="church-results-row %s" %s>', $member->get_top_gift(), $data);
                echo sprintf('<td>%s  %s</td>', $member->user_firstname, $member->user_lastname);
                echo sprintf('<td>%s</td>', $member->quiz_date());

                if(!$member->has_quiz('sgt'))
                    echo '<td>Tests Incomplete</td>';

                elseif($member->has_quiz('sgt') && $member->has_quiz('personality'))
                    echo sprintf('<td>%s %s</td>', $member->identity_set(), $member->is_youth() ? '- Youth' : '');

                elseif($member->has_quiz('sgt') && !$member->has_quiz('personality'))
                    echo sprintf('<td>%s %s</td>', $member->the_quiz('sgt')->get_nth_name(1), $member->is_youth() ? '- Youth' : '');

                ?>
                <td>

                    <?php if($member->has_quiz()) : ?>

                    <div class="button expand-results">
                        <span class="open">Summary</span><span class="close">Close</span>
                    </div>

                    <div class="subsection">
                        <table>
                            <tr class="table-header-row">
                                <th>Gift/Trait</th>
                                <th>Score</th>
                            </tr>
                            <?php
                            $i = 1;
                            if ($member->has_quiz('sgt')) {
                                $quiz = $member->the_quiz('sgt');
                                for($i = 1; $i <=3; $i++) {
                                    echo sprintf('<tr><td>%s</td><td>%s</td>', $quiz->get_nth_name($i), $quiz->get_nth_range($i));
                                }
                            }

                            if ($member->has_quiz('personality')) {
                                $quiz = $member->the_quiz('personality');
                                while($quiz->has_domain()) {
                                    $quiz->the_domain();
                                    echo sprintf('<tr><td>%s</td><td>%s</td></tr>', $quiz->get_name(), $quiz->get_range());
                                }
                            } ?>

                        </table>
                    </div>
                    <?php
                        echo sprintf('<a href="/full-report/?userid=%s" class="full-report-link">Full Report</a> | ', $member->ID);

                    endif;
                        echo sprintf('<a href="#" class="disconnect-user" data-user="%s" data-verify="%s" data-name="%s">Disconnect User</a>', $member->ID, base64_encode($member->ID), $member->user_firstname . " ". $member->user_lastname);
                    ?>
                </td>
                </tr>
                <?php
            endwhile;

            if($church->has_manual_entry()) {
            echo sprintf('<tr><td colspan="6">Manual Entries</td></tr>');
                while($church->has_manual_entry()) : $church->the_manual_entry();
                    echo $church->generate_entry_row();
                endwhile;
            }

            if($church->has_legacy_entry()) {
            echo sprintf('<tr><td colspan="6">Legacy Entries</td></tr>');
                while($church->has_legacy_entry()) : $church->the_legacy_entry();
                    echo $church->generate_entry_row();
                endwhile;
            }

            $count = SGTLegacy::get_legacy_tests_count_by_customer_id($church->get_church_code());

            if($count > LEGACYLIMIT) {
                ?>
                <tr class="load-more-row">
                    <td><a href="#" class="load-more" data-offset="<?php echo LEGACYLIMIT; ?>">Load More </a></td>
                </tr>
                <?php
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
<?php get_footer();
