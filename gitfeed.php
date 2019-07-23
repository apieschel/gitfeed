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

// First, let's test out the Github API in the WP Admin screen
add_action( 'admin_notices', 'git_feed' );

function git_feed() {
	
	$certificate = "C:\Users\apieschel\Desktop\gtrsoftware\cacert.pem";
	
	$defaults = array( 
		CURLOPT_URL => 'https://api.github.com/users/apieschel/repos',
		CURLOPT_HEADER => 0, 
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_CAINFO => $certificate,
		CURLOPT_CAPATH => $certificate,
		CURLOPT_USERAGENT => 'apieschel'
	); 
    
	$ch = curl_init(); 
	curl_setopt_array($ch, $defaults); 
	$data = json_decode(curl_exec($ch));
	
	for($i = 0; $i < count($data); $i++) {
		echo $data[$i]->name . "<br>";
	}
	
	//var_dump($data);
	curl_close($ch); 
}