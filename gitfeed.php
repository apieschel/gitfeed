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

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

load_plugin_textdomain('gitfeed', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( file_exists( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'env.php' ) ) {
	include_once 'env.php';
	add_action('init', gf_set_up_env);
}

// First, let's test out the Github API in a new shortcode
add_shortcode( 'gitfeed', 'gf_git_feed' );
add_shortcode( 'repofeed', 'gf_repo_feed' );

function gf_git_feed() {
	// set up local certificate to deal with https permissions
	$certificate = "C:\users\apieschel\Desktop\gtrsoftware\cacert.pem";
	$user = 'apieschel';
	$password = getenv('PASSWORD');	
	
	// set up GET request to Github API
	$defaults = array( 
		CURLOPT_URL => 'https://api.github.com/users/' . $user . '/repos',
		CURLOPT_HEADER => 0, 
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_CAINFO => $certificate,
		CURLOPT_CAPATH => $certificate,
		CURLOPT_USERAGENT => $user,
		CURLOPT_TIMEOUT => 30,
    CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
		CURLOPT_USERPWD => $user . ':' . $password
	); 

	$ch = curl_init(); 
	curl_setopt_array($ch, $defaults); 
	$data = curl_exec($ch);
	//var_dump($data);
	$data = json_decode($data);
	$info = curl_getinfo ($ch);
	
	$repos = array();
	
	if(gettype($data) == 'object') {
		echo '<div class="container">Uh oh, it looks like you have exceeded the API call limit.</div>';
	} else {
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
		curl_close($ch);
		
		
		// sort the array in reverse order according to the timestamps
		krsort($repos);
		
		// if there are more than 10 repos, then only keep the 10 most recently updated
		if(count($repos) > 9) {
			$repos = array_slice($repos, 0, 10);
		}
		
		// Set up multi curl request
		foreach($repos as $key=>$value) {
			// https://stackoverflow.com/questions/9257505/using-braces-with-dynamic-variable-names-in-php
			${'ch' . $key} = curl_init();
		
			curl_setopt(${'ch' . $key}, CURLOPT_URL, 'https://api.github.com/repos/' . $user . '/' . $value[0] . '/commits');
			curl_setopt(${'ch' . $key}, CURLOPT_HEADER, 0);
			curl_setopt(${'ch' . $key}, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt(${'ch' . $key}, CURLOPT_CAINFO, $certificate);
			curl_setopt(${'ch' . $key}, CURLOPT_CAPATH, $certificate);
			curl_setopt(${'ch' . $key}, CURLOPT_USERAGENT, $user);
			curl_setopt(${'ch' . $key}, CURLOPT_TIMEOUT, 30);
			curl_setopt(${'ch' . $key}, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt(${'ch' . $key}, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt(${'ch' . $key}, CURLOPT_USERPWD, $user . ':' . $password);
		}
		
		$mh = curl_multi_init();
		
		foreach($repos as $key=>$value) {
			curl_multi_add_handle($mh, ${'ch' . $key});
		}
		
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while ($running);
		
		foreach($repos as $key=>$value) {
			curl_multi_remove_handle($mh, ${'ch' . $key});
		}
		curl_multi_close($mh);

		// display the data
		echo '<div class="container-fluid">';
			echo '<h2 style="font-size:1.4rem; font-weight:normal; text-align:center; margin-bottom:20px;">This custom WordPress plugin displays a feed of ' . $user . '&apos;s Git repos, sorted from the most recently updated.</h2>';
			echo '<p style="text-align:center; margin-bottom:40px;"><a target="_blank" style="color:#0000EE;" href="https://github.com/apieschel">Link to apieschel&apos;s Github Page</a></p>';
				
			foreach($repos as $key=>$value) {	
				echo '<div style="background:#eee; border:1px solid lightgrey; margin:0 auto; margin-bottom:20px; padding:40px; width:50%;">';
					echo '<p>' . ($key + 1) . '. <strong>' . $value[0] . '</strong>: ' . $value[1] . '</p>';
					echo '<p><span style="color:green;"><em>';
					esc_html_e('Last updated', 'gitfeed');
					echo '</em>: ' . date("F j, Y, g:i a", $key) . ' U.S. Central Time</span></p>';
					echo '<p><em>Language</em>: ' . $value[2] . '</p>';
				
					$response = json_decode(curl_multi_getcontent(${'ch' . $key}));
					echo '<p><em>Latest Commit</em>: ' . $response[0]->commit->message . '</p>';
				echo '</div>';
			}
		echo '</div>';
	}
}

function gf_repo_feed() {
	// https://stackoverflow.com/questions/9179828/github-api-retrieve-all-commits-for-all-branches-for-a-repo
	
	$certificate = "C:\users\apieschel\Desktop\gtrsoftware\cacert.pem";
	$user = 'apieschel';
	
	$defaults = array( 
		CURLOPT_URL => 'https://api.github.com/repos/' . $user . '/gitfeed/commits?per_page=100&sha=a6506ef9d22a2635ebfe55ed86c4b50c42d5ff93',
		CURLOPT_HEADER => 0, 
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_CAINFO => $certificate,
		CURLOPT_CAPATH => $certificate,
		CURLOPT_USERAGENT => $user
	); 

	$ch = curl_init(); 
	curl_setopt_array($ch, $defaults); 
	$data = json_decode(curl_exec($ch));
	foreach($data as $value) {		
		echo '<div style="background:#eee; border:1px solid lightgrey; margin:0 auto; margin-bottom:20px; padding:40px; width:50%;">';
			echo '<p><strong>';
			esc_html_e('Commit Message', 'gitfeed');
			echo ': </strong>' . $value->commit->message . '</p>';
			echo '<p><strong>Commit Date: </strong>' . date("F j, Y, g:i a", (strtotime($value->commit->committer->date) - (60 * 60 * 5))) . '</p>';
			echo '<br><br>';
		echo '</div>';
	}			
}