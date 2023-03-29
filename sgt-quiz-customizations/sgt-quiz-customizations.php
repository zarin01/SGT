<?php
/*
Plugin Name: SGT Quiz Customizations
Version: 1.0.0
Author: Robert Iseley
Author URI: http://www.robertiseley.com
*/

include_once('sgt-legacy-compatibility.php');
include_once('class-sgt-church.php');
include_once('class-sgt-church-member.php');
include_once('class-quiz-results.php');
include_once('class-quiz-results-sgt-base.php');
include_once('class-quiz-results-sgt.php');
include_once('class-quiz-results-sgt-entry.php');
include_once('class-quiz-results-personality.php');

add_action( 'gform_loaded', 'sgt_register_addon', 6, 4 );
function sgt_register_addon() {
	if ( ! class_exists('GravityFormsPersonalityQuizAddon') ) {
		return;
	}
	GFForms::include_addon_framework();
	require 'class-sgt-quiz-settings.php';
	GFAddOn::register( 'SGTQuizSettings' );
}

add_filter( 'init', array( 'SGTQuizSettings', 'sgt_rewrite_endpoint' ) );
add_filter( 'query_vars', array( 'SGTQuizSettings', 'add_query_vars' ) );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
register_activation_hook( __FILE__, array( 'SGTQuizSettings', 'activation') );