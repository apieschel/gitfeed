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
	$defaults = array( 
		CURLOPT_URL => 'https://api.github.com/users/apieschel/repos',
		CURLOPT_HEADER => 0, 
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_SSL_VERIFYHOST => FALSE,
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_USERAGENT => 'apieschel'
	); 
    
	$ch = curl_init(); 
	curl_setopt_array($ch, $defaults); 
	
	$data = curl_exec($ch);
	$info = curl_getinfo($ch);
	$error = curl_error($ch);
	$code = curl_errno($ch);
	
	var_dump($error);
	var_dump($info);
	var_dump($data);
	curl_close($ch); 
}