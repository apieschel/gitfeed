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
add_shortcode( 'repofeed', 'repo_feed' );

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
		array_push($array, $current->language);
		// subtract five hours to adjust to U.S. Central time
		$repos[strtotime($data[$i]->updated_at) - (60 * 60 * 5)] = $array;
	}
	
	// sort the array in reverse order according to the timestamps
	krsort($repos);
	
	curl_close($ch);
	
	// display the data
	echo '<div class="container-fluid">';
		echo '<h2 style="font-size:1.4rem; font-weight:normal; text-align:center; margin-bottom:20px;">This custom WordPress plugin displays a feed of ' . $user . '&apos;s Git repos, sorted from the most recently updated.</h2>';
		echo '<p style="text-align:center; margin-bottom:40px;"><a target="_blank" style="color:#0000EE;" href="https://github.com/apieschel">Link to apieschel&apos;s Github Page</a></p>';

		foreach($repos as $key=>$value) {	
			echo '<div style="background:#eee; border:1px solid lightgrey; margin:0 auto; margin-bottom:20px; padding:40px; width:50%;">';
				echo '<p><strong>' . $value[0] . '</strong>: ' . $value[1] . '</p>';
				echo '<p><span style="color:green;"><em>Last updated</em>: ' . date("F j, Y, g:i a", $key) . ' U.S. Central Time</span></p>';
				echo '<p><em>Language</em>: ' . $value[2] . '</p>';
			echo '</div>';
		}
	echo '</div>'; 
}

function repo_feed() {
	$defaults = array( 
		CURLOPT_URL => 'https://api.github.com/repos/apieschel/gitfeed/git/commits/535d9561a5fee12050476aa9e62392ab55451dc6',
		CURLOPT_HEADER => 0, 
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_CAINFO => 'C:\users\apieschel\Desktop\gtrsoftware\cacert.pem',
		CURLOPT_CAPATH => 'C:\users\apieschel\Desktop\gtrsoftware\cacert.pem',
		CURLOPT_USERAGENT => 'apieschel'
	); 

	$ch = curl_init(); 
	curl_setopt_array($ch, $defaults); 
	$data = json_decode(curl_exec($ch));
	var_dump($data);		
	curl_close($ch);
}