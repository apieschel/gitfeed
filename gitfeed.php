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
}

add_action('init', setUpEnv);

// First, let's test out the Github API in a new shortcode
add_shortcode( 'gitfeed', 'gf_git_feed' );
add_shortcode( 'repofeed', 'gf_repo_feed' );

function gf_git_feed() {
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
	var_dump($data);
	$repos = array();
	
	if(gettype($data) == 'object') {
		echo '<div class="container">Uh oh, it looks like you have exceeded the API call limit.</div>';
		print_r($_ENV);
		echo getenv('CLIENT_ID');
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
					echo '<p><span style="color:green;"><em>';
					esc_html_e('Last updated', 'gitfeed');
					echo '</em>: ' . date("F j, Y, g:i a", $key) . ' U.S. Central Time</span></p>';
					echo '<p><em>Language</em>: ' . $value[2] . '</p>';
				echo '</div>';
				
				// API call to each individual repo within the loop
				/*
				$defaults = array( 
					CURLOPT_URL => 'https://api.github.com/users/' . $value[0] . '/commits',
					CURLOPT_HEADER => 0, 
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_CAINFO => $certificate,
					CURLOPT_CAPATH => $certificate,
					CURLOPT_USERAGENT => 'apieschel'
				); 

				$ch = curl_init(); 
				curl_setopt_array($ch, $defaults); 
				$data = json_decode(curl_exec($ch));
				var_dump($data);
				curl_close($ch); */			
			}
		echo '</div>';
	}
}

function gf_repo_feed() {
	// https://stackoverflow.com/questions/9179828/github-api-retrieve-all-commits-for-all-branches-for-a-repo
	
	$defaults = array( 
		CURLOPT_URL => 'https://api.github.com/repos/apieschel/gitfeed/commits?per_page=100&sha=a6506ef9d22a2635ebfe55ed86c4b50c42d5ff93',
		CURLOPT_HEADER => 0, 
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_CAINFO => 'C:\users\apieschel\Desktop\gtrsoftware\cacert.pem',
		CURLOPT_CAPATH => 'C:\users\apieschel\Desktop\gtrsoftware\cacert.pem',
		CURLOPT_USERAGENT => 'apieschel'
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