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
	// set up local certificate to deal with https permissions
	$certificate = "C:\users\apieschel\Desktop\gtrsoftware\cacert.pem";
	$user = 'apieschel';
	
	// set up GET request to Github API
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
	$repos = array();
	
	// loop through the data, and create a new array with timestamps as keys
	for($i = 0; $i < count($data); $i++) {
		$array = array();
		$current = $data[$i];
		array_push($array, $current->name);
		array_push($array, $current->description);
		$repos[strtotime($data[$i]->updated_at)] = $array;
	}
	
	// sort the array in reverse order according to the timestamps
	krsort($repos);
	
	// display the data
	echo '<div class="container-fluid">';
	echo '<h2 style="font-size:1.4rem; font-weight:normal; text-align:center;">A feed of ' . $user . '&apos;s Git repos, sorted from the most recently updated.</h2>';
		foreach($repos as $key=>$value) {	
			echo '<p style="margin:20px;"><strong>' . $value[0] . '</strong>: ' . $value[1];
			echo ' <span style="margin-left:5px;"><em>Last updated</em>: ' . date("F j, Y, g:i a", $key) . '</span></p>';
		}
	echo '</div>';
	
	curl_close($ch); 
}