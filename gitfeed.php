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
	$user = getenv('USER');
	$password = getenv('PASSWORD');	
	$url = 'https://api.github.com/users/' . $user . '/repos';
	$headers = array('Authorization' => 'Basic '.base64_encode("$user:$password"), 'User-Agent' => $user);
	$wpget = wp_remote_get( $url, array('headers' => $headers) );
	$data = json_decode($wpget["body"]);
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
		// maintain the existing keys for the timestamps
		if(count($repos) > 9) {
			$repos = array_slice($repos, 0, 10, TRUE);
		}
		
		$commits = array();
		$commit_stats = array();
		// Set up multi curl request
		foreach($repos as $key=>$value) {
			$url = 'https://api.github.com/repos/' . $user . '/' . $value[0] . '/commits';
			$headers = array('Authorization' => 'Basic '.base64_encode("$user:$password"), 'User-Agent' => $user);
			$wpget = wp_remote_get( $url, array('headers' => $headers) );
			$data = json_decode($wpget["body"]);
			array_push($commits, $data);
			/*
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
			*/
		}
		
		/*
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
		curl_multi_close($mh);*/
		

		// Set up multi curl request for commit stats
		foreach($commits as $key=>$value) {
			$sha = $value[0]->sha;
			$url = $value[0]->url;
			$headers = array('Authorization' => 'Basic '.base64_encode("$user:$password"), 'User-Agent' => $user);
			$wpget = wp_remote_get( $url, array('headers' => $headers) );
			$data = json_decode($wpget["body"]);
			array_push($commit_stats, $data);
			/*
			${'chc' . $key} = curl_init();
			$response = json_decode(curl_multi_getcontent(${'ch' . $key}));
			$sha = $response[0]->sha;
			$url = 'https://api.github.com/repos/' . $user . '/' . $value[0] . '/commits/' . $sha;
		
			curl_setopt(${'chc' . $key}, CURLOPT_URL, $url);
			curl_setopt(${'chc' . $key}, CURLOPT_HEADER, 0);
			curl_setopt(${'chc' . $key}, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt(${'chc' . $key}, CURLOPT_CAINFO, $certificate);
			curl_setopt(${'chc' . $key}, CURLOPT_CAPATH, $certificate);
			curl_setopt(${'chc' . $key}, CURLOPT_USERAGENT, $user);
			curl_setopt(${'chc' . $key}, CURLOPT_TIMEOUT, 30);
			curl_setopt(${'chc' . $key}, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt(${'chc' . $key}, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt(${'chc' . $key}, CURLOPT_USERPWD, $user . ':' . $password);
			*/
		}
		//var_dump($commit_stats);
		/*
		
		$mh = curl_multi_init();
		
		foreach($repos as $key=>$value) {
			curl_multi_add_handle($mh, ${'chc' . $key});
		}
		
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while ($running);
		
		foreach($repos as $key=>$value) {
			curl_multi_remove_handle($mh, ${'chc' . $key});
		}
		curl_multi_close($mh);
		*/
		
		// display the data
		echo '<div class="container-fluid">';
			echo '<h2 style="font-size:1.4rem; font-weight:normal; text-align:center; margin-bottom:20px;">This custom WordPress plugin displays a feed of ' . $user . '&apos;s Git repos, sorted from the most recently updated.</h2>';
			echo '<p style="text-align:center; margin-bottom:40px;"><a target="_blank" style="color:#0000EE;" href="https://github.com/apieschel">Link to apieschel&apos;s Github Page</a></p>';
			
			$count = 0;
			foreach($repos as $key=>$value) {	
				echo '<div style="background:#edffed; border:1px solid lightgrey; margin:0 auto; margin-bottom:20px; padding:40px; width:75%;">';
					echo '<h3 style="text-align:center; font-size:28px; margin-bottom:20px;"><strong>' . $value[0] . '</strong>: ' . $value[1] . '</h3>';
					echo '<p><span style="color:green;"><em>';
					esc_html_e('Last updated', 'gitfeed');
					echo '</em>: ' . date("F j, Y, g:i a", $key) . ' U.S. Central Time</span></p>';
				
					$response = $commits[$count];
					echo '<p><em>Latest Commit</em>: ' . $response[0]->commit->message . '</p>';
				
					$response2 = $commit_stats[$count];
					echo '<p><em>Total Code Changes</em>: ' . $response2->stats->total . '</p>';	
					echo '<p><em>Lines Added</em>: ' . $response2->stats->additions . '</p>';	
					echo '<p><em>Lines Deleted</em>: ' . $response2->stats->deletions . '</p>';	
				
					foreach($response2->files as $key=>$value) {
							echo '<p><em>File Changed</em>: ' . $response2->files[$key]->filename . '</p>';
							echo '<p><em>Patch</em>: ' . esc_html($response2->files[$key]->patch) . '</p>';
					}
				echo '</div>';
				$count++;
			}
		echo '</div>';
	}
}

function gf_repo_feed() {
	$certificate = "C:\users\apieschel\Desktop\gtrsoftware\cacert.pem";
	$user = 'apieschel';
	
	// https://stackoverflow.com/questions/9179828/github-api-retrieve-all-commits-for-all-branches-for-a-repo
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