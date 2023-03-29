<?php
/*
 * Template Name: Download CSV
 */

if(class_exists('SGTQuizSettings')) {
	$class = SGTQuizSettings::get_instance();
	$class->get_csv();
}