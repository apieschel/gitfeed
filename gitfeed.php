<?php 

/**
 * @package Gitfeed
 */
/*
Plugin Name: Gitfeed
Plugin URI: https://github.com/apieschel/gitfeed
Description: Plugin for displaying a feed for your latest Git commits on a portfolio site.
Version: 1.0
Author: Alex Pieschel
Author URI: https://gtrsoftware.com
License: GPLv2 or later
Text Domain: gitfeed
*/

// First, let's test out the Github API in a new shortcode
add_shortcode( 'gitfeed', 'git_feed' );

function git_feed() {
	$certificate = "C:\users\apieschel\Desktop\gtrsoftware\cacert.pem";
	$user = 'apieschel';

	$defaults = array( 
		CURLOPT_URL => 'https://api.github.com/users/' . $user . '/repos',
		CURLOPT_HEADER => 0, 
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_CAINFO => $certificate,
		CURLOPT_CAPATH => $certificate,
		CURLOPT_USERAGENT => 'apieschel'
	); 

	$ch = curl_init(); 
	curl_setopt_array($ch, $defaults); 
	$data = json_decode(curl_exec($ch));
	
	echo '<div style="margin-top: 30px;" class="container">';
	
	for($i = 0; $i < count($data); $i++) {
		echo '<p><strong>' . $data[$i]->name . '</strong>: ' . $data[$i]->description . '</p>';
	}
	
	echo '</div>';

	curl_close($ch); 
}